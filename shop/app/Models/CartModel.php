<?php

namespace App\Models;

use CodeIgniter\Model;

class CartModel extends Model
{
    protected $table         = 'cart_items';
    protected $primaryKey    = 'id';
    protected $useTimestamps = false;
    protected $allowedFields = ['user_id', 'product_id', 'qty', 'created_at'];

    /**
     * 사용자의 장바구니 목록 (상품 정보 + 대표 이미지 JOIN)
     */
    public function getByUser(int $userId): array
    {
        $rows = $this->select(
                'cart_items.id, cart_items.product_id, cart_items.qty,
                 products.name, products.slug, products.price, products.discount_price,
                 products.stock, products.status,
                 products.shipping_type, products.shipping_fee, products.free_threshold,
                 media.file_path'
            )
            ->join('products', 'products.id = cart_items.product_id')
            ->join('product_images pi', 'pi.product_id = cart_items.product_id AND pi.is_primary = 1', 'left')
            ->join('media', 'media.id = pi.media_id', 'left')
            ->where('cart_items.user_id', $userId)
            ->where('products.deleted_at IS NULL', null, false)
            ->orderBy('cart_items.id', 'DESC')
            ->findAll();

        foreach ($rows as &$row) {
            $row['primary_image'] = $row['file_path'] ? base_url($row['file_path']) : null;
            $row['display_price'] = $row['discount_price'] ?? $row['price'];
            $row['is_available']  = $row['status'] !== 'hidden' && (int) $row['stock'] > 0;
        }
        return $rows;
    }

    /**
     * 담긴 상품 종류 수 (뱃지 표시용)
     */
    public function getCount(int $userId): int
    {
        return (int) $this->where('user_id', $userId)->countAllResults();
    }

    /**
     * 담기 — 이미 있으면 qty 합산, 없으면 신규 삽입 (원자적 처리)
     */
    public function upsert(int $userId, int $productId, int $qty): void
    {
        $this->db->query(
            'INSERT INTO cart_items (user_id, product_id, qty, created_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty)',
            [$userId, $productId, $qty]
        );
    }

    /**
     * 수량 직접 지정 (수정 버튼)
     */
    public function updateQty(int $userId, int $productId, int $qty): void
    {
        $this->where('user_id', $userId)->where('product_id', $productId)->set('qty', $qty)->update();
    }

    /**
     * 개별 삭제
     */
    public function removeItem(int $userId, int $productId): void
    {
        $this->where('user_id', $userId)->where('product_id', $productId)->delete();
    }

    /**
     * 전체 비우기
     */
    public function clear(int $userId): void
    {
        $this->where('user_id', $userId)->delete();
    }

    /**
     * 세션 장바구니 → DB 병합 (로그인 후 호출)
     * 합산 결과가 재고를 초과하면 재고 수량으로 클리핑
     * $stockMap: [product_id => stock] — 호출자가 미리 조회해서 전달
     */
    public function mergeSession(int $userId, array $sessionCart, array $stockMap): void
    {
        if (empty($sessionCart)) return;

        // DB에 이미 있는 항목 조회 (클리핑 계산용)
        $existing = $this->where('user_id', $userId)
            ->whereIn('product_id', array_map('intval', array_keys($sessionCart)))
            ->findAll();
        $dbQtyMap = array_column($existing, 'qty', 'product_id');

        foreach ($sessionCart as $productId => $sessionQty) {
            $productId   = (int) $productId;
            $stock       = (int) ($stockMap[$productId] ?? 0);
            if ($stock < 1) continue;

            $currentQty = (int) ($dbQtyMap[$productId] ?? 0);
            $addQty     = min((int) $sessionQty, $stock - $currentQty);
            if ($addQty < 1) continue;

            $this->upsert($userId, $productId, $addQty);
        }
    }
}
