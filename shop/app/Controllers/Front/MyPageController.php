<?php

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Models\OrderModel;

class MyPageController extends BaseController
{
    private OrderModel $orderModel;

    // 상태 탭 정의 — key: 쿼리 파라미터 값, label: 표시명
    private const STATUS_TABS = [
        ''                  => '전체',
        'awaiting_payment'  => '입금대기',
        'paid'              => '결제완료',
        'preparing'         => '배송준비',
        'shipped'           => '배송중',
        'delivered'         => '배송완료',
        'cancel'            => '취소/환불',
    ];

    public function __construct()
    {
        $this->orderModel = new OrderModel();
    }

    /** GET /mypage/orders */
    public function orders()
    {
        $userId  = (int) session()->get('user_id');
        $period  = $this->request->getGet('period') ?? 'all';
        $status  = $this->request->getGet('status') ?? '';
        $keyword = trim($this->request->getGet('keyword') ?? '');
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));

        // 유효하지 않은 period 차단
        if (! in_array($period, ['1m', '3m', 'all'], true)) {
            $period = 'all';
        }

        // 유효하지 않은 status 차단
        if (! array_key_exists($status, self::STATUS_TABS)) {
            $status = '';
        }

        $result     = $this->orderModel->getByUser($userId, compact('period', 'status', 'keyword', 'page'));
        $statusTabs = self::STATUS_TABS;

        // 목록 카드용 상품명 요약 — 뷰에서 DB 접근 금지
        $db = \Config\Database::connect();
        foreach ($result['items'] as &$order) {
            $orderItems   = $db->table('order_items')
                ->select('product_name')
                ->where('order_id', $order['id'])
                ->orderBy('id', 'ASC')
                ->get()->getResultArray();
            $firstName    = $orderItems[0]['product_name'] ?? '';
            $extra        = count($orderItems) - 1;
            $order['_name_summary'] = $firstName . ($extra > 0 ? ' 외 ' . $extra . '건' : '');
        }
        unset($order);

        return $this->render('shop/orders/list', array_merge($result, compact('period', 'status', 'keyword', 'statusTabs')));
    }

    /** GET /mypage/orders/:orderNumber */
    public function orderDetail(string $orderNumber)
    {
        $userId = (int) session()->get('user_id');

        $row = $this->orderModel
            ->where('order_number', $orderNumber)
            ->where('user_id', $userId)
            ->first();

        if (! $row) {
            return redirect()->to('/mypage/orders')->with('error', '주문을 찾을 수 없습니다.');
        }

        $order = $this->orderModel->getWithItems($row['id'], $userId);

        return $this->render('shop/orders/detail', compact('order'));
    }

    /** POST /mypage/orders/confirm-delivery — 배송 완료 확인 */
    public function confirmDelivery()
    {
        $userId  = (int) session()->get('user_id');
        $orderId = (int) $this->request->getPost('order_id');

        if (! $orderId) {
            return $this->response->setJSON(['success' => false, 'message' => '잘못된 요청입니다.']);
        }

        $order = $this->orderModel->where('id', $orderId)->where('user_id', $userId)->first();

        if (! $order || $order['status'] !== 'shipped') {
            return $this->response->setJSON(['success' => false, 'message' => '배송 완료 처리할 수 없는 주문입니다.']);
        }

        $this->orderModel->update($orderId, ['status' => 'delivered']);

        return $this->response->setJSON(['success' => true]);
    }

    /** POST /mypage/orders/cancel — 즉시 취소 */
    public function cancel()
    {
        $userId  = (int) session()->get('user_id');
        $orderId = (int) $this->request->getPost('order_id');

        if (! $orderId) {
            return $this->response->setJSON(['success' => false, 'message' => '잘못된 요청입니다.']);
        }

        $success = $this->orderModel->cancelOrder($orderId, $userId);

        return $this->response->setJSON([
            'success' => $success,
            'message' => $success ? '주문이 취소되었습니다.' : '취소할 수 없는 주문입니다.',
        ]);
    }
}
