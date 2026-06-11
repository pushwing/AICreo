<?php

namespace App\Models;

use App\Traits\HasSlug;
use CodeIgniter\Model;

class ProductModel extends Model
{
    use HasSlug;
    protected $table          = 'products';
    protected $primaryKey     = 'id';
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    protected $allowedFields  = [
        'category_id', 'name', 'slug', 'price', 'discount_price', 'stock',
        'status', 'description', 'shipping_type', 'shipping_fee', 'free_threshold',
    ];

    public const STATUSES = [
        'on_sale'  => '판매중',
        'sold_out' => '품절',
        'hidden'   => '숨김',
    ];

    public const SHIPPING_TYPES = [
        'free'        => '무료배송',
        'fixed'       => '고정 배송비',
        'conditional' => '조건부 무료',
    ];

    /**
     * 프론트 상품 목록: 숨김 제외, 검색·카테고리·정렬 지원
     */
    public function getList(array $params = []): array
    {
        $keyword    = $params['keyword']    ?? '';
        $categoryId = $params['category_id'] ?? null;
        $sort       = $params['sort']        ?? 'latest';
        $perPage    = $params['per_page']    ?? 12;
        $page       = max(1, (int) ($params['page'] ?? 1));

        $builder = $this->db->table('products')
            ->select('products.*, categories.name as category_name')
            ->join('categories', 'categories.id = products.category_id', 'left')
            ->where('products.deleted_at IS NULL')
            ->whereIn('products.status', ['on_sale', 'sold_out']);

        if ($keyword) {
            $builder->like('products.name', $keyword);
        }

        if ($categoryId) {
            // 대분류 선택 시 소분류 포함
            $subIds = $this->db->table('categories')
                ->select('id')
                ->where('parent_id', $categoryId)
                ->get()->getResultArray();

            if ($subIds) {
                $ids = array_column($subIds, 'id');
                $ids[] = $categoryId;
                $builder->whereIn('products.category_id', $ids);
            } else {
                $builder->where('products.category_id', $categoryId);
            }
        }

        match ($sort) {
            'price_asc'  => $builder->orderBy('COALESCE(products.discount_price, products.price)', 'ASC', false),
            'price_desc' => $builder->orderBy('COALESCE(products.discount_price, products.price)', 'DESC', false),
            default      => $builder->orderBy('products.id', 'DESC'),
        };

        return $this->buildPage($builder, $page, $perPage);
    }

    /**
     * 관리자 상품 목록
     */
    public function getAdminList(array $params = []): array
    {
        $keyword  = $params['keyword'] ?? '';
        $status   = $params['status']  ?? '';
        $perPage  = 20;
        $page     = max(1, (int) ($params['page'] ?? 1));

        $builder = $this->db->table('products')
            ->select('products.*, categories.name as category_name')
            ->join('categories', 'categories.id = products.category_id', 'left')
            ->where('products.deleted_at IS NULL');

        if ($keyword) {
            $builder->like('products.name', $keyword);
        }
        if ($status) {
            $builder->where('products.status', $status);
        }

        $builder->orderBy('products.id', 'DESC');

        return $this->buildPage($builder, $page, $perPage);
    }

    public function getLatest(int $limit = 8): array
    {
        return $this->db->table('products')
            ->select('products.*, categories.name as category_name')
            ->join('categories', 'categories.id = products.category_id', 'left')
            ->where('products.deleted_at IS NULL')
            ->whereIn('products.status', ['on_sale', 'sold_out'])
            ->orderBy('products.id', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();
    }

    public function getDiscounted(int $limit = 8): array
    {
        return $this->db->table('products')
            ->select('products.*, categories.name as category_name')
            ->join('categories', 'categories.id = products.category_id', 'left')
            ->where('products.deleted_at IS NULL')
            ->where('products.status', 'on_sale')
            ->where('products.discount_price IS NOT NULL')
            ->orderBy('products.id', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();
    }

    private function buildPage($builder, int $page, int $perPage): array
    {
        // DB Builder는 배열 기반 상태라 clone이 안전함 (Model clone과 달리 DB 커넥션을 통한 상태 공유 없음)
        $total  = (clone $builder)->countAllResults();
        $offset = ($page - 1) * $perPage;

        return [
            'items'       => $builder->limit($perPage, $offset)->get()->getResultArray(),
            'total'       => $total,
            'totalPages'  => (int) ceil($total / $perPage),
            'currentPage' => $page,
            'perPage'     => $perPage,
        ];
    }

}
