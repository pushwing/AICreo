<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table         = 'orders';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'user_id', 'order_number', 'status',
        'total_product_price', 'shipping_fee', 'total_amount',
        'receiver_name', 'receiver_phone', 'zipcode', 'address1', 'address2', 'delivery_memo',
        'tracking_company', 'tracking_number',
        'paid_at', 'cancelled_at', 'expired_at',
    ];

    public const STATUS_LABELS = [
        'pending'           => '결제 대기',
        'awaiting_payment'  => '입금 대기',
        'paid'              => '결제 완료',
        'preparing'         => '상품 준비 중',
        'shipped'           => '배송 중',
        'delivered'         => '배송 완료',
        'cancelled'         => '취소',
        'expired'           => '만료',
        'refund_requested'  => '환불 요청',
        'refunded'          => '환불 완료',
    ];

    /** 결제 대기 주문 생성 — 재고는 아직 차감하지 않음 */
    public function createPending(int $userId, array $shippingData, array $cartItems): int
    {
        $totalProduct = 0;

        foreach ($cartItems as $item) {
            $price        = (int) ($item['discount_price'] ?? $item['price']);
            $totalProduct += $price * (int) $item['qty'];
        }

        $shippingFee = $this->calculateShippingFee($cartItems, $totalProduct);
        $totalAmount = $totalProduct + $shippingFee;
        $orderNumber = $this->generateOrderNumber();

        $this->db->transStart();

        $orderId = (int) $this->insert([
            'user_id'             => $userId,
            'order_number'        => $orderNumber,
            'status'              => 'pending',
            'total_product_price' => $totalProduct,
            'shipping_fee'        => $shippingFee,
            'total_amount'        => $totalAmount,
            'receiver_name'       => $shippingData['receiver_name'],
            'receiver_phone'      => $shippingData['receiver_phone'],
            'zipcode'             => $shippingData['zipcode'],
            'address1'            => $shippingData['address1'],
            'address2'            => $shippingData['address2'] ?? null,
            'delivery_memo'       => $shippingData['delivery_memo'] ?? null,
        ], true);

        $items = [];
        foreach ($cartItems as $item) {
            $price   = (int) ($item['discount_price'] ?? $item['price']);
            $qty     = (int) $item['qty'];
            $items[] = [
                'order_id'      => $orderId,
                'product_id'    => (int) $item['product_id'],
                'product_name'  => $item['name'],
                'product_price' => $price,
                'qty'           => $qty,
                'subtotal'      => $price * $qty,
                'created_at'    => date('Y-m-d H:i:s'),
            ];
        }
        $this->db->table('order_items')->insertBatch($items);

        $this->db->transComplete();

        return $orderId;
    }

    /**
     * 무통장입금 확정 — 관리자가 입금 확인 후 호출
     * 재고 차감 + 주문·결제 상태 paid 전환
     */
    public function confirmBankTransfer(int $orderId): bool
    {
        $this->db->transStart();

        $order = $this->db->table('orders')
            ->where('id', $orderId)
            ->where('status', 'awaiting_payment')
            ->get()->getRowArray();

        if (! $order) {
            $this->db->transRollback();
            return false;
        }

        $payment = $this->db->table('payments')
            ->where('order_id', $orderId)
            ->where('pg_provider', 'bank_transfer')
            ->where('status', 'pending')
            ->get()->getRowArray();

        if (! $payment) {
            $this->db->transRollback();
            return false;
        }

        $items = $this->db->table('order_items')->where('order_id', $orderId)->get()->getResultArray();

        foreach ($items as $item) {
            $stock = $this->db->query(
                'SELECT stock FROM products WHERE id = ? FOR UPDATE',
                [$item['product_id']]
            )->getRow();

            if (! $stock || (int) $stock->stock < (int) $item['qty']) {
                $this->db->transRollback();
                return false;
            }

            $this->db->query(
                'UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?',
                [$item['qty'], $item['product_id'], $item['qty']]
            );

            if ($this->db->affectedRows() === 0) {
                $this->db->transRollback();
                return false;
            }

            $this->db->query(
                'UPDATE products SET status = "sold_out" WHERE id = ? AND stock = 0 AND status = "on_sale"',
                [$item['product_id']]
            );
        }

        $now = date('Y-m-d H:i:s');

        $this->db->table('orders')->where('id', $orderId)->update([
            'status'  => 'paid',
            'paid_at' => $now,
        ]);

        $this->db->table('payments')->where('id', $payment['id'])->update([
            'status'  => 'paid',
            'paid_at' => $now,
            'updated_at' => $now,
        ]);

        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * 결제 확정 — PG 콜백 수신 후 호출
     * 트랜잭션 + FOR UPDATE + 조건부 UPDATE로 재고 차감
     */
    public function confirmPaid(int $orderId, string $pgProvider, string $pgTid, string $method, array $rawResponse): bool
    {
        $this->db->transStart();

        $order = $this->db->table('orders')
            ->where('id', $orderId)
            ->where('status', 'pending')
            ->get()->getRowArray();

        if (! $order) {
            $this->db->transRollback();
            return false;
        }

        // 주문 상품 목록
        $items = $this->db->table('order_items')->where('order_id', $orderId)->get()->getResultArray();

        foreach ($items as $item) {
            // FOR UPDATE 행 잠금
            $stock = $this->db->query(
                'SELECT stock FROM products WHERE id = ? FOR UPDATE',
                [$item['product_id']]
            )->getRow();

            if (! $stock || (int) $stock->stock < (int) $item['qty']) {
                $this->db->transRollback();
                return false;
            }

            // 조건부 UPDATE — stock >= qty 만족 시만 차감
            $affected = $this->db->query(
                'UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?',
                [$item['qty'], $item['product_id'], $item['qty']]
            );

            if ($this->db->affectedRows() === 0) {
                $this->db->transRollback();
                return false;
            }

            // 재고 0이면 sold_out 상태 자동 전환
            $this->db->query(
                'UPDATE products SET status = "sold_out" WHERE id = ? AND stock = 0 AND status = "on_sale"',
                [$item['product_id']]
            );
        }

        $now = date('Y-m-d H:i:s');

        $this->db->table('orders')->where('id', $orderId)->update([
            'status'  => 'paid',
            'paid_at' => $now,
        ]);

        $this->db->table('payments')->insert([
            'order_id'     => $orderId,
            'pg_provider'  => $pgProvider,
            'pg_tid'       => $pgTid,
            'method'       => $method,
            'amount'       => (int) $order['total_amount'],
            'status'       => 'paid',
            'raw_response' => json_encode($rawResponse, JSON_UNESCAPED_UNICODE),
            'paid_at'      => $now,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        // 결제 완료 후 장바구니 비우기
        $productIds = array_column($items, 'product_id');
        $this->db->table('cart_items')
            ->where('user_id', (int) $order['user_id'])
            ->whereIn('product_id', $productIds)
            ->delete();

        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * 주문 취소 + 재고 복구
     * paid 상태만 취소 가능 (pending은 재고를 차감하지 않았으므로 그냥 cancelled 처리)
     */
    public function cancelOrder(int $orderId, int $userId): bool
    {
        $this->db->transStart();

        $order = $this->db->table('orders')
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'awaiting_payment', 'paid'])
            ->get()->getRowArray();

        if (! $order) {
            $this->db->transRollback();
            return false;
        }

        if ($order['status'] === 'paid') {
            $items = $this->db->table('order_items')->where('order_id', $orderId)->get()->getResultArray();
            foreach ($items as $item) {
                $this->db->query(
                    'UPDATE products SET stock = stock + ?, status = IF(status = "sold_out" AND stock + ? > 0, "on_sale", status) WHERE id = ?',
                    [$item['qty'], $item['qty'], $item['product_id']]
                );
            }
            // 결제 취소 기록
            $this->db->table('payments')
                ->where('order_id', $orderId)
                ->where('status', 'paid')
                ->update(['status' => 'cancelled', 'cancelled_at' => date('Y-m-d H:i:s')]);
        }

        $this->db->table('orders')->where('id', $orderId)->update([
            'status'       => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * N분 이상 지난 pending 주문 만료 처리 (스케줄러에서 호출)
     */
    public function expirePending(int $minutesOld = 30): int
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$minutesOld} minutes"));

        $this->db->table('orders')
            ->where('status', 'pending')        // awaiting_payment은 만료 제외
            ->where('created_at <', $cutoff)
            ->update([
                'status'     => 'expired',
                'expired_at' => date('Y-m-d H:i:s'),
            ]);

        return $this->db->affectedRows();
    }

    /** 주문 상세 (order_items + payment 포함) */
    public function getWithItems(int $orderId, int $userId): ?array
    {
        $order = $this->where('id', $orderId)->where('user_id', $userId)->first();
        if (! $order) return null;

        $order['items']   = $this->fetchOrderItems($orderId);
        $order['payment'] = $this->db->table('payments')->where('order_id', $orderId)->orderBy('id', 'DESC')->get()->getRowArray();

        return $order;
    }

    private function fetchOrderItems(int $orderId): array
    {
        return $this->db->table('order_items oi')
            ->select('oi.*, p.slug AS product_slug, m.file_path AS thumbnail')
            ->join('products p', 'p.id = oi.product_id', 'left')
            ->join('product_images pi', 'pi.product_id = oi.product_id AND pi.is_primary = 1', 'left')
            ->join('media m', 'm.id = pi.media_id', 'left')
            ->where('oi.order_id', $orderId)
            ->get()->getResultArray();
    }

    /** 회원 주문 목록 */
    /**
     * 회원 주문 목록 — 기간·상태 필터 + 페이징
     *
     * $params:
     *   period  — '1m' | '3m' | 'all' (기본 all)
     *   status  — orders.status 값, 빈 문자열이면 전체
     *   page    — 페이지 번호 (기본 1)
     *   perPage — 페이지당 항목 수 (기본 10)
     */
    public function getByUser(int $userId, array $params = []): array
    {
        $period  = $params['period']  ?? 'all';
        $status  = $params['status']  ?? '';
        $keyword = trim($params['keyword'] ?? '');
        $page    = max(1, (int) ($params['page']    ?? 1));
        $perPage = max(1, (int) ($params['perPage'] ?? 10));

        $builder = $this->db->table('orders')
            ->where('user_id', $userId);

        if ($period === '1m') {
            $builder->where('created_at >=', date('Y-m-d H:i:s', strtotime('-1 month')));
        } elseif ($period === '3m') {
            $builder->where('created_at >=', date('Y-m-d H:i:s', strtotime('-3 months')));
        }

        if ($keyword !== '') {
            $k = $this->db->escapeLikeString($keyword);
            $builder->where("EXISTS (
                SELECT 1 FROM order_items oi
                LEFT JOIN products p ON p.id = oi.product_id
                WHERE oi.order_id = orders.id
                AND (oi.product_name LIKE '%{$k}%' ESCAPE '!'
                     OR p.description LIKE '%{$k}%' ESCAPE '!'
                     OR oi.product_price LIKE '%{$k}%' ESCAPE '!')
            )", null, false);
        }

        if ($status !== '') {
            // 탭 그룹 매핑: 'cancel' 탭은 cancelled + expired + refunded 묶음
            if ($status === 'cancel') {
                $builder->whereIn('status', ['cancelled', 'expired', 'refunded', 'refund_requested']);
            } else {
                $builder->where('status', $status);
            }
        }

        $total  = (clone $builder)->countAllResults();
        $orders = $builder->orderBy('id', 'DESC')
                          ->limit($perPage, ($page - 1) * $perPage)
                          ->get()->getResultArray();

        return [
            'items'       => $orders,
            'total'       => $total,
            'totalPages'  => (int) ceil($total / $perPage),
            'currentPage' => $page,
            'perPage'     => $perPage,
        ];
    }

    /** 관리자용 주문 목록 — user_id 제약 없음, 다중 필터 */
    public function adminGetAll(array $params = []): array
    {
        $keyword = trim($params['keyword'] ?? '');
        $status  = $params['status']  ?? '';
        $page    = max(1, (int) ($params['page']    ?? 1));
        $perPage = max(1, (int) ($params['perPage'] ?? 20));

        $builder = $this->db->table('orders o')
            ->select('o.*, u.email AS user_email, u.nickname AS user_nickname,
                (SELECT pg_provider FROM payments WHERE order_id = o.id ORDER BY id DESC LIMIT 1) AS pg_provider,
                (SELECT method FROM payments WHERE order_id = o.id ORDER BY id DESC LIMIT 1) AS payment_method')
            ->join('users u', 'u.id = o.user_id', 'left');

        if ($keyword !== '') {
            $builder->groupStart()
                ->like('o.order_number', $keyword)
                ->orLike('o.receiver_name', $keyword)
                ->orLike('u.email', $keyword)
                ->groupEnd();
        }

        if ($status !== '') {
            $builder->where('o.status', $status);
        }

        $total  = (clone $builder)->countAllResults(false);
        $orders = $builder->orderBy('o.id', 'DESC')
                          ->limit($perPage, ($page - 1) * $perPage)
                          ->get()->getResultArray();

        return [
            'items'       => $orders,
            'total'       => $total,
            'totalPages'  => (int) ceil($total / $perPage),
            'currentPage' => $page,
            'perPage'     => $perPage,
        ];
    }

    /** 관리자용 주문 상세 — user_id 제약 없음 */
    public function adminGetWithItems(int $orderId): ?array
    {
        $order = $this->db->table('orders o')
            ->select('o.*, u.email AS user_email, u.nickname AS user_nickname')
            ->join('users u', 'u.id = o.user_id', 'left')
            ->where('o.id', $orderId)
            ->get()->getRowArray();

        if (! $order) return null;

        $order['items']   = $this->fetchOrderItems($orderId);
        $order['payment'] = $this->db->table('payments')->where('order_id', $orderId)->orderBy('id', 'DESC')->get()->getRowArray();

        return $order;
    }

    /** 관리자 강제 취소 — user_id 체크 없음, paid 상태면 재고 복구 */
    public function adminCancel(int $orderId): bool
    {
        $this->db->transStart();

        $order = $this->db->table('orders')
            ->whereIn('status', ['pending', 'awaiting_payment', 'paid', 'preparing'])
            ->where('id', $orderId)
            ->get()->getRowArray();

        if (! $order) {
            $this->db->transRollback();
            return false;
        }

        if (in_array($order['status'], ['paid', 'preparing'], true)) {
            $items = $this->db->table('order_items')->where('order_id', $orderId)->get()->getResultArray();
            foreach ($items as $item) {
                $this->db->query(
                    'UPDATE products SET stock = stock + ?, status = IF(status = "sold_out" AND stock + ? > 0, "on_sale", status) WHERE id = ?',
                    [$item['qty'], $item['qty'], $item['product_id']]
                );
            }
            $this->db->table('payments')
                ->where('order_id', $orderId)
                ->where('status', 'paid')
                ->update(['status' => 'cancelled', 'cancelled_at' => date('Y-m-d H:i:s')]);
        }

        $this->db->table('orders')->where('id', $orderId)->update([
            'status'       => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db->transComplete();
        return $this->db->transStatus();
    }

    /** 주문 상태 변경 (단방향 흐름만 허용) */
    public function updateStatus(int $orderId, string $newStatus): bool
    {
        $allowed = [
            'paid'      => 'preparing',
            'preparing' => 'shipped',
            'shipped'   => 'delivered',
            'refund_requested' => 'refunded',
        ];

        $order = $this->find($orderId);
        if (! $order) return false;

        if (($allowed[$order['status']] ?? null) !== $newStatus) {
            return false;
        }

        return $this->update($orderId, ['status' => $newStatus]);
    }

    /** 송장번호 업데이트 */
    public function updateTracking(int $orderId, string $company, string $number): bool
    {
        $order = $this->find($orderId);
        if (! $order) return false;

        return $this->update($orderId, [
            'tracking_company' => $company,
            'tracking_number'  => $number,
        ]);
    }

    /** 환불 완료 처리 (PG 취소는 관리자가 직접 — B안) */
    public function markRefunded(int $orderId): bool
    {
        $order = $this->where('id', $orderId)->where('status', 'refund_requested')->first();
        if (! $order) return false;

        $this->db->transStart();

        $this->update($orderId, ['status' => 'refunded']);

        $this->db->table('payments')
            ->where('order_id', $orderId)
            ->where('status', 'paid')
            ->update(['status' => 'refunded', 'cancelled_at' => date('Y-m-d H:i:s')]);

        $this->db->transComplete();
        return $this->db->transStatus();
    }

    private function generateOrderNumber(): string
    {
        $prefix = 'ORD-' . date('Ymd') . '-';
        $seq    = str_pad((string) random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
        return $prefix . $seq;
    }

    public function calculateShippingFee(array $items, int $totalProduct): int
    {
        $fee = 0;
        foreach ($items as $item) {
            $itemFee = match ($item['shipping_type']) {
                'free'        => 0,
                'fixed'       => (int) $item['shipping_fee'],
                'conditional' => $totalProduct >= (int) $item['free_threshold']
                                    ? 0
                                    : (int) $item['shipping_fee'],
                default       => 0,
            };
            $fee = max($fee, $itemFee);
        }

        return $fee;
    }
}
