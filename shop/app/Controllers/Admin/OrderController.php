<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\OrderModel;

class OrderController extends BaseController
{
    private OrderModel $orderModel;

    private const STATUS_LABELS = [
        'pending'           => '결제 대기',
        'awaiting_payment'  => '입금 대기',
        'paid'              => '결제 완료',
        'preparing'         => '배송 준비',
        'shipped'           => '배송 중',
        'delivered'         => '배송 완료',
        'cancelled'         => '취소',
        'expired'           => '만료',
        'refund_requested'  => '환불 요청',
        'refunded'          => '환불 완료',
    ];

    private const NEXT_STATUS = [
        'paid'             => 'preparing',
        'preparing'        => 'shipped',
        'shipped'          => 'delivered',
        'refund_requested' => 'refunded',
    ];

    public function __construct()
    {
        $this->orderModel = new OrderModel();
    }

    /** GET /admin/orders */
    public function index()
    {
        $keyword = trim($this->request->getGet('q') ?? '');
        $status  = $this->request->getGet('status') ?? '';
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));

        if (! array_key_exists($status, self::STATUS_LABELS) && $status !== '') {
            $status = '';
        }

        $result = $this->orderModel->adminGetAll(compact('keyword', 'status', 'page'));

        return $this->render('admin/orders/list', array_merge($result, [
            'keyword'      => $keyword,
            'status'       => $status,
            'statusLabels' => self::STATUS_LABELS,
        ]));
    }

    /** GET /admin/orders/:id */
    public function detail(int $id)
    {
        $order = $this->orderModel->adminGetWithItems($id);
        if (! $order) {
            return redirect()->to('/admin/orders')->with('error', '주문을 찾을 수 없습니다.');
        }

        return $this->render('admin/orders/detail', [
            'order'        => $order,
            'statusLabels' => self::STATUS_LABELS,
            'nextStatus'   => self::NEXT_STATUS,
        ]);
    }

    /** POST /admin/orders/:id/status */
    public function updateStatus(int $id)
    {
        $newStatus = $this->request->getPost('status');

        if (! $newStatus || ! array_key_exists($newStatus, self::STATUS_LABELS)) {
            return redirect()->back()->with('error', '잘못된 상태값입니다.');
        }

        if ($newStatus === 'shipped') {
            $order = $this->orderModel->find($id);
            if (! $order || trim($order['tracking_number'] ?? '') === '') {
                return redirect()->back()->with('error', '송장번호를 먼저 입력해주세요.');
            }
        }

        $ok = $this->orderModel->updateStatus($id, $newStatus);

        return redirect()->to("/admin/orders/{$id}")
            ->with($ok ? 'success' : 'error', $ok ? '주문 상태가 변경되었습니다.' : '상태 변경에 실패했습니다.');
    }

    /** POST /admin/orders/:id/tracking */
    public function updateTracking(int $id)
    {
        $company = trim($this->request->getPost('tracking_company') ?? '');
        $number  = trim($this->request->getPost('tracking_number')  ?? '');

        if ($number === '') {
            return redirect()->back()->with('error', '운송장 번호를 입력해주세요.');
        }

        $ok = $this->orderModel->updateTracking($id, $company, $number);

        return redirect()->to("/admin/orders/{$id}")
            ->with($ok ? 'success' : 'error', $ok ? '송장번호가 저장되었습니다.' : '저장에 실패했습니다.');
    }

    /** POST /admin/orders/:id/cancel */
    public function cancel(int $id)
    {
        $ok = $this->orderModel->adminCancel($id);

        return redirect()->to("/admin/orders/{$id}")
            ->with($ok ? 'success' : 'error', $ok ? '주문이 취소되었습니다.' : '취소할 수 없는 주문입니다.');
    }

    /** POST /admin/orders/:id/bank_confirm — 무통장 입금 확인 */
    public function confirmBankTransfer(int $id)
    {
        $ok = $this->orderModel->confirmBankTransfer($id);

        return redirect()->to("/admin/orders/{$id}")
            ->with($ok ? 'success' : 'error', $ok ? '입금 확인 처리가 완료되었습니다.' : '입금 확인에 실패했습니다. (재고 부족 또는 이미 처리된 주문)');
    }

    /** POST /admin/orders/:id/refund */
    public function refund(int $id)
    {
        $ok = $this->orderModel->markRefunded($id);

        return redirect()->to("/admin/orders/{$id}")
            ->with($ok ? 'success' : 'error', $ok ? '환불 완료 처리되었습니다.' : '환불 처리에 실패했습니다. (PG 콘솔에서 취소 후 다시 시도하세요)');
    }
}
