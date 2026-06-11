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
        'coupon_id', 'coupon_discount_amount', 'point_used_amount', 'point_earned_amount', 'payable_amount',
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

    /**
     * 결제 대기 주문 생성 — 쿠폰 확정 + 포인트 차감까지 트랜잭션 내 처리
     */
    public function createPending(
        int $userId,
        array $shippingData,
        array $cartItems,
        ?int $couponId = null,
        ?int $userCouponId = null,
        int $couponDiscountAmount = 0,
        int $pointUsed = 0,
        int $pointEarned = 0
    ): int {
        $totalProduct = 0;
        foreach ($cartItems as $item) {
            $price        = (int) ($item['discount_price'] ?? $item['price']);
            $totalProduct += $price * (int) $item['qty'];
        }

        $shippingFee   = $this->calculateShippingFee($cartItems, $totalProduct);
        $totalAmount   = $totalProduct + $shippingFee;
        $payableAmount = max(0, $totalAmount - $couponDiscountAmount - $pointUsed);
        $orderNumber   = $this->generateOrderNumber();
        $now           = date('Y-m-d H:i:s');

        $this->db->transStart();

        $orderId = (int) $this->insert([
            'user_id'                => $userId,
            'order_number'           => $orderNumber,
            'status'                 => 'pending',
            'total_product_price'    => $totalProduct,
            'shipping_fee'           => $shippingFee,
            'total_amount'           => $totalAmount,
            'coupon_id'              => $couponId,
            'coupon_discount_amount' => $couponDiscountAmount,
            'point_used_amount'      => $pointUsed,
            'point_earned_amount'    => $pointEarned,
            'payable_amount'         => $payableAmount,
            'receiver_name'          => $shippingData['receiver_name'],
            'receiver_phone'         => $shippingData['receiver_phone'],
            'zipcode'                => $shippingData['zipcode'],
            'address1'               => $shippingData['address1'],
            'address2'               => $shippingData['address2'] ?? null,
            'delivery_memo'          => $shippingData['delivery_memo'] ?? null,
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
                'created_at'    => $now,
            ];
        }
        $this->db->table('order_items')->insertBatch($items);

        // 쿠폰 확정
        if ($couponId && $couponDiscountAmount > 0) {
            $this->db->query('UPDATE coupons SET used_count = used_count + 1 WHERE id = ?', [$couponId]);

            if ($userCouponId) {
                $this->db->table('user_coupons')
                    ->where('id', $userCouponId)
                    ->where('user_id', $userId)
                    ->where('status', 'issued')
                    ->update(['status' => 'used', 'order_id' => $orderId, 'used_at' => $now, 'updated_at' => $now]);
            } else {
                // 코드 입력 — issued 상태 쿠폰이 있으면 사용 처리, 없으면 신규 INSERT
                $existingUC = $this->db->table('user_coupons')
                    ->where('user_id', $userId)
                    ->where('coupon_id', $couponId)
                    ->where('status', 'issued')
                    ->get()->getRowArray();

                if ($existingUC) {
                    $this->db->table('user_coupons')
                        ->where('id', $existingUC['id'])
                        ->update(['status' => 'used', 'order_id' => $orderId, 'used_at' => $now, 'updated_at' => $now]);
                } else {
                    $this->db->table('user_coupons')->insert([
                        'user_id'    => $userId,
                        'coupon_id'  => $couponId,
                        'order_id'   => $orderId,
                        'source'     => 'code',
                        'status'     => 'used',
                        'issued_at'  => $now,
                        'used_at'    => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        // 포인트 차감 (FOR UPDATE + 조건부 UPDATE)
        if ($pointUsed > 0) {
            $this->db->query('SELECT point_balance FROM users WHERE id = ? FOR UPDATE', [$userId]);
            $affected = $this->db->query(
                'UPDATE users SET point_balance = point_balance - ? WHERE id = ? AND point_balance >= ?',
                [$pointUsed, $userId, $pointUsed]
            );
            if ($this->db->affectedRows() === 0) {
                $this->db->transRollback();
                return 0;
            }
            $this->db->table('point_logs')->insert([
                'user_id'    => $userId,
                'type'       => 'use',
                'amount'     => -$pointUsed,
                'order_id'   => $orderId,
                'note'       => '주문 포인트 사용',
                'created_at' => $now,
            ]);
        }

        $this->db->transComplete();

        return $this->db->transStatus() ? $orderId : 0;
    }

    /**
     * 무통장입금 확정 — 재고 차감 + 주문·결제 상태 paid 전환
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
            'status'     => 'paid',
            'paid_at'    => $now,
            'updated_at' => $now,
        ]);

        $this->writeStatusLog($orderId, 'awaiting_payment', 'paid', '무통장 입금 확인');

        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * 결제 확정 — PG 콜백 수신 후 호출
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

        $this->db->table('payments')->insert([
            'order_id'     => $orderId,
            'pg_provider'  => $pgProvider,
            'pg_tid'       => $pgTid,
            'method'       => $method,
            'amount'       => (int) $order['payable_amount'],
            'status'       => 'paid',
            'raw_response' => json_encode($rawResponse, JSON_UNESCAPED_UNICODE),
            'paid_at'      => $now,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        // 장바구니 비우기
        $productIds = array_column($items, 'product_id');
        $this->db->table('cart_items')
            ->where('user_id', (int) $order['user_id'])
            ->whereIn('product_id', $productIds)
            ->delete();

        $this->writeStatusLog($orderId, 'pending', 'paid', 'PG 결제 확인 (' . $pgProvider . ')');

        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * 주문 취소 + 재고/쿠폰/포인트 복구
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
            $this->db->table('payments')
                ->where('order_id', $orderId)
                ->where('status', 'paid')
                ->update(['status' => 'cancelled', 'cancelled_at' => date('Y-m-d H:i:s')]);
        }

        $this->restoreCoupon($order);
        $this->restorePoints($order, 'cancel');

        $this->db->table('orders')->where('id', $orderId)->update([
            'status'       => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s'),
        ]);

        $this->writeStatusLog($orderId, $order['status'], 'cancelled', '회원 취소 요청');

        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * N분 이상 지난 pending 주문 만료 처리 — 쿠폰·포인트 복구 포함
     */
    public function expirePending(int $minutesOld = 30): int
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$minutesOld} minutes"));
        $now    = date('Y-m-d H:i:s');

        $orders = $this->db->table('orders')
            ->where('status', 'pending')
            ->where('created_at <', $cutoff)
            ->get()->getResultArray();

        $count = 0;
        foreach ($orders as $order) {
            $this->db->transStart();

            $this->restoreCoupon($order);
            $this->restorePoints($order, 'expire');

            $this->db->table('orders')->where('id', $order['id'])->update([
                'status'     => 'expired',
                'expired_at' => $now,
            ]);

            $this->writeStatusLog($order['id'], 'pending', 'expired', '미결제 자동 만료');

            $this->db->transComplete();
            if ($this->db->transStatus()) {
                $count++;
            }
        }

        return $count;
    }

    /** 주문 상태 변경 (단방향 흐름 + delivered 시 포인트 적립 확정) */
    public function updateStatus(int $orderId, string $newStatus): bool
    {
        $allowed = [
            'paid'             => 'preparing',
            'preparing'        => 'shipped',
            'shipped'          => 'delivered',
            'refund_requested' => 'refunded',
        ];

        $order = $this->find($orderId);
        if (! $order) return false;

        if (($allowed[$order['status']] ?? null) !== $newStatus) {
            return false;
        }

        if ($newStatus !== 'delivered' || (int) $order['point_earned_amount'] === 0) {
            $ok = (bool) $this->update($orderId, ['status' => $newStatus]);
            if ($ok) $this->writeStatusLog($orderId, $order['status'], $newStatus);
            return $ok;
        }

        // delivered 전환 + 포인트 적립 확정
        $this->db->transStart();

        $this->update($orderId, ['status' => 'delivered']);

        $this->db->query(
            'UPDATE users SET point_balance = point_balance + ? WHERE id = ?',
            [$order['point_earned_amount'], $order['user_id']]
        );
        $this->db->table('point_logs')->insert([
            'user_id'    => $order['user_id'],
            'type'       => 'earn',
            'amount'     => $order['point_earned_amount'],
            'order_id'   => $orderId,
            'note'       => '배송 완료 포인트 적립',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->writeStatusLog($orderId, $order['status'], 'delivered',
            '배송 완료 확인 · 포인트 ' . number_format((int) $order['point_earned_amount']) . '원 적립');

        $this->db->transComplete();
        return $this->db->transStatus();
    }

    /** 관리자 강제 취소 */
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

        $this->restoreCoupon($order);
        $this->restorePoints($order, 'cancel');

        $this->db->table('orders')->where('id', $orderId)->update([
            'status'       => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s'),
        ]);

        $this->writeStatusLog($orderId, $order['status'], 'cancelled', '관리자 강제 취소');

        $this->db->transComplete();
        return $this->db->transStatus();
    }

    /** 환불 완료 처리 — 쿠폰 복구 + 포인트 환급 + 적립 취소 */
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

        $this->restoreCoupon($order);

        // 포인트 사용분 환급
        if ((int) $order['point_used_amount'] > 0) {
            $this->db->query(
                'UPDATE users SET point_balance = point_balance + ? WHERE id = ?',
                [$order['point_used_amount'], $order['user_id']]
            );
            $this->db->table('point_logs')->insert([
                'user_id'    => $order['user_id'],
                'type'       => 'refund',
                'amount'     => $order['point_used_amount'],
                'order_id'   => $orderId,
                'note'       => '주문 환불 포인트 환급',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // 이미 적립된 포인트 회수
        if ((int) $order['point_earned_amount'] > 0) {
            $earnLog = $this->db->table('point_logs')
                ->where('order_id', $orderId)
                ->where('type', 'earn')
                ->get()->getRowArray();

            if ($earnLog) {
                $this->db->query(
                    'UPDATE users SET point_balance = GREATEST(0, point_balance - ?) WHERE id = ?',
                    [$order['point_earned_amount'], $order['user_id']]
                );
                $this->db->table('point_logs')->insert([
                    'user_id'    => $order['user_id'],
                    'type'       => 'cancel',
                    'amount'     => -(int)$order['point_earned_amount'],
                    'order_id'   => $orderId,
                    'note'       => '환불로 인한 포인트 회수',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->writeStatusLog($orderId, 'refund_requested', 'refunded', '환불 처리 완료');

        $this->db->transComplete();
        return $this->db->transStatus();
    }

    /** 주문 상세 */
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
            ->select('oi.*, p.slug AS product_slug, p.shipping_type, p.free_threshold, m.file_path AS thumbnail')
            ->join('products p', 'p.id = oi.product_id', 'left')
            ->join('product_images pi', 'pi.product_id = oi.product_id AND pi.is_primary = 1', 'left')
            ->join('media m', 'm.id = pi.media_id', 'left')
            ->where('oi.order_id', $orderId)
            ->get()->getResultArray();
    }

    public function getByUser(int $userId, array $params = []): array
    {
        $period  = $params['period']  ?? 'all';
        $status  = $params['status']  ?? '';
        $keyword = trim($params['keyword'] ?? '');
        $page    = max(1, (int) ($params['page']    ?? 1));
        $perPage = max(1, (int) ($params['perPage'] ?? 10));

        $builder = $this->db->table('orders')->where('user_id', $userId);

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

    public function adminGetWithItems(int $orderId): ?array
    {
        $order = $this->db->table('orders o')
            ->select('o.*, u.email AS user_email, u.nickname AS user_nickname')
            ->join('users u', 'u.id = o.user_id', 'left')
            ->where('o.id', $orderId)
            ->get()->getRowArray();

        if (! $order) return null;

        $order['items']      = $this->fetchOrderItems($orderId);
        $order['payment']    = $this->db->table('payments')->where('order_id', $orderId)->orderBy('id', 'DESC')->get()->getRowArray();
        $order['statusLogs'] = $this->db->table('order_status_logs')
            ->where('order_id', $orderId)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        return $order;
    }

    public function updateTracking(int $orderId, string $company, string $number): bool
    {
        $order = $this->find($orderId);
        if (! $order) return false;

        $ok = (bool) $this->update($orderId, [
            'tracking_company' => $company,
            'tracking_number'  => $number,
        ]);

        if ($ok) {
            $this->writeStatusLog($orderId, $order['status'], $order['status'],
                '운송장 등록: ' . $company . ' ' . $number);
        }

        return $ok;
    }

    /** 세션에서 현재 작업자 정보 추출 */
    private function resolveActor(): array
    {
        $session = session();
        $userId  = (int) ($session->get('user_id') ?? 0);
        $role    = $session->get('user_role') ?? '';
        $name    = $session->get('user_nickname') ?? '';

        if ($userId > 0 && $role === 'admin') {
            return ['admin', $userId, $name ?: 'admin'];
        }
        if ($userId > 0) {
            return ['member', $userId, $name ?: '회원'];
        }
        return ['system', null, 'system'];
    }

    /** 주문 상태 변경 로그 기록 */
    private function writeStatusLog(int $orderId, string $from, string $to, ?string $note = null): void
    {
        [$actorType, $actorId, $actorName] = $this->resolveActor();

        $this->db->table('order_status_logs')->insert([
            'order_id'   => $orderId,
            'from_status' => $from,
            'to_status'  => $to,
            'actor_type' => $actorType,
            'actor_id'   => $actorId,
            'actor_name' => $actorName,
            'note'       => $note,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function generateOrderNumber(): string
    {
        $prefix = 'ORD-' . date('Ymd') . '-';
        $seq    = str_pad((string) random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
        return $prefix . $seq;
    }

    public function calculateShippingFee(array $items, int $totalProduct): int
    {
        // 조건부 무료 기준 충족 시 전체 주문 무료배송
        foreach ($items as $item) {
            if ($item['shipping_type'] === 'conditional'
                && (int) $item['free_threshold'] > 0
                && $totalProduct >= (int) $item['free_threshold']) {
                return 0;
            }
        }

        // 충족되지 않은 경우 개별 배송비 중 최댓값
        $fee = 0;
        foreach ($items as $item) {
            $itemFee = match ($item['shipping_type']) {
                'free'    => 0,
                'fixed'   => (int) $item['shipping_fee'],
                default   => (int) $item['shipping_fee'],
            };
            $fee = max($fee, $itemFee);
        }

        return $fee;
    }

    /** 쿠폰 복구 헬퍼 */
    private function restoreCoupon(array $order): void
    {
        if (! $order['coupon_id'] || (int) $order['coupon_discount_amount'] === 0) return;

        $this->db->query(
            'UPDATE coupons SET used_count = GREATEST(0, used_count - 1) WHERE id = ?',
            [$order['coupon_id']]
        );

        $uc = $this->db->table('user_coupons')
            ->where('coupon_id', $order['coupon_id'])
            ->where('order_id', $order['id'])
            ->where('status', 'used')
            ->get()->getRowArray();

        if (! $uc) return;

        if ($uc['source'] === 'code') {
            $this->db->table('user_coupons')->where('id', $uc['id'])->delete();
        } else {
            $this->db->table('user_coupons')->where('id', $uc['id'])->update([
                'status'     => 'issued',
                'order_id'   => null,
                'used_at'    => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /** 포인트 환급 헬퍼 */
    private function restorePoints(array $order, string $reason = 'cancel'): void
    {
        if ((int) $order['point_used_amount'] <= 0) return;

        $this->db->query(
            'UPDATE users SET point_balance = point_balance + ? WHERE id = ?',
            [$order['point_used_amount'], $order['user_id']]
        );

        $note = match ($reason) {
            'expire' => '주문 만료 포인트 환급',
            default  => '주문 취소 포인트 환급',
        };

        $this->db->table('point_logs')->insert([
            'user_id'    => $order['user_id'],
            'type'       => 'refund',
            'amount'     => $order['point_used_amount'],
            'order_id'   => $order['id'],
            'note'       => $note,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
