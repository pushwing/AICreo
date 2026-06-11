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
        'return_requested'   => '반품 요청',
        'return_approved'    => '반품 승인',
        'exchange_requested' => '교환 요청',
        'exchange_approved'  => '교환 승인',
        'exchange_completed' => '교환 완료',
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
    public function index(): string
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

    /** GET /admin/orders/export — 엑셀 다운로드 */
    public function exportExcel(): \CodeIgniter\HTTP\ResponseInterface
    {
        $keyword = trim($this->request->getGet('q') ?? '');
        $status  = $this->request->getGet('status') ?? '';

        if (! array_key_exists($status, self::STATUS_LABELS) && $status !== '') {
            $status = '';
        }

        $orders   = $this->orderModel->adminGetAll([
            'keyword' => $keyword,
            'status'  => $status,
            'page'    => 1,
            'perPage' => 5000,
        ])['items'];

        $orderIds = array_column($orders, 'id');
        $nameMap  = [];
        if ($orderIds) {
            $rows = \Config\Database::connect()->table('order_items')
                ->select('order_id, product_name, quantity')
                ->whereIn('order_id', $orderIds)
                ->orderBy('order_id')->orderBy('id')
                ->get()->getResultArray();
            foreach ($rows as $row) {
                $nameMap[(int) $row['order_id']][] = $row['product_name'] . ' x' . $row['quantity'];
            }
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        $headers = ['주문번호', '주문일시', '수취인', '연락처', '우편번호', '주소', '상세주소', '상품명', '결제금액', '상태'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }

        // 헤더 스타일
        $headerStyle = [
            'font'      => ['bold' => true],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFE9ECEF']],
            'borders'   => ['bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        ];
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

        foreach ($orders as $i => $order) {
            $names          = $nameMap[$order['id']] ?? [];
            $extra          = count($names) - 1;
            $productSummary = ($names[0] ?? '') . ($extra > 0 ? ' 외 ' . $extra . '건' : '');
            $rowNum         = $i + 2;

            $sheet->setCellValueByColumnAndRow(1,  $rowNum, $order['order_number']);
            $sheet->setCellValueByColumnAndRow(2,  $rowNum, $order['created_at']);
            $sheet->setCellValueByColumnAndRow(3,  $rowNum, $order['receiver_name']);
            $sheet->setCellValueByColumnAndRow(4,  $rowNum, $order['receiver_phone']);
            $sheet->setCellValueByColumnAndRow(5,  $rowNum, $order['zipcode'] ?? '');
            $sheet->setCellValueByColumnAndRow(6,  $rowNum, $order['address1'] ?? '');
            $sheet->setCellValueByColumnAndRow(7,  $rowNum, $order['address2'] ?? '');
            $sheet->setCellValueByColumnAndRow(8,  $rowNum, $productSummary);
            $sheet->setCellValueByColumnAndRow(9,  $rowNum, (int) $order['total_amount']);
            $sheet->setCellValueByColumnAndRow(10, $rowNum, self::STATUS_LABELS[$order['status']] ?? $order['status']);
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = '주문목록_' . date('Ymd_His') . '.xlsx';

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return $this->response
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . rawurlencode($filename) . '"')
            ->setHeader('Cache-Control', 'max-age=0')
            ->setBody($content);
    }

    /** POST /admin/orders/bulk-status — 주문 상태 일괄 변경 */
    public function bulkUpdateStatus(): \CodeIgniter\HTTP\ResponseInterface
    {
        $status   = $this->request->getPost('status');
        $orderIds = $this->request->getPost('order_ids');

        if (! $status || ! array_key_exists($status, self::STATUS_LABELS)) {
            return $this->response->setJSON(['success' => false, 'message' => '잘못된 상태값입니다.']);
        }

        if (! is_array($orderIds) || empty($orderIds)) {
            return $this->response->setJSON(['success' => false, 'message' => '주문을 선택해주세요.']);
        }

        $orderIds = array_slice(array_map('intval', $orderIds), 0, 100);

        $updated = 0;
        $failed  = 0;
        foreach ($orderIds as $id) {
            if ($id <= 0) { $failed++; continue; }
            $this->orderModel->updateStatus($id, $status) ? $updated++ : $failed++;
        }

        return $this->response->setJSON([
            'success' => true,
            'updated' => $updated,
            'failed'  => $failed,
            'message' => "{$updated}건 변경 완료" . ($failed > 0 ? ", {$failed}건 실패 (허용되지 않는 전환)" : ''),
        ]);
    }

    /** GET /admin/orders/:id */
    public function detail(int $id): \CodeIgniter\HTTP\RedirectResponse|string
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
    public function updateStatus(int $id): \CodeIgniter\HTTP\RedirectResponse
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
    public function updateTracking(int $id): \CodeIgniter\HTTP\RedirectResponse
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
    public function cancel(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $ok = $this->orderModel->adminCancel($id);

        return redirect()->to("/admin/orders/{$id}")
            ->with($ok ? 'success' : 'error', $ok ? '주문이 취소되었습니다.' : '취소할 수 없는 주문입니다.');
    }

    /** POST /admin/orders/:id/bank_confirm — 무통장 입금 확인 */
    public function confirmBankTransfer(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $ok = $this->orderModel->confirmBankTransfer($id);

        return redirect()->to("/admin/orders/{$id}")
            ->with($ok ? 'success' : 'error', $ok ? '입금 확인 처리가 완료되었습니다.' : '입금 확인에 실패했습니다. (재고 부족 또는 이미 처리된 주문)');
    }

    /** POST /admin/orders/:id/refund */
    public function refund(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $ok = $this->orderModel->markRefunded($id);

        return redirect()->to("/admin/orders/{$id}")
            ->with($ok ? 'success' : 'error', $ok ? '환불 완료 처리되었습니다.' : '환불 처리에 실패했습니다. (PG 콘솔에서 취소 후 다시 시도하세요)');
    }

    /** POST /admin/orders/:id/return-approve — 반품 승인 (재고·쿠폰·포인트 복구) */
    public function approveReturn(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $ok = $this->orderModel->approveReturn($id);

        return redirect()->to("/admin/orders/{$id}")
            ->with($ok ? 'success' : 'error', $ok ? '반품이 승인되었습니다. PG 콘솔에서 환불 후 환불 완료 버튼을 눌러주세요.' : '반품 승인에 실패했습니다.');
    }

    /** POST /admin/orders/:id/return-reject — 반품 거부 */
    public function rejectReturn(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $ok = $this->orderModel->rejectReturn($id);

        return redirect()->to("/admin/orders/{$id}")
            ->with($ok ? 'success' : 'error', $ok ? '반품이 거부되었습니다.' : '반품 거부에 실패했습니다.');
    }

    /** POST /admin/orders/:id/return-refund — 반품 환불 완료 확인 */
    public function confirmReturnRefund(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $ok = $this->orderModel->confirmReturnRefund($id);

        return redirect()->to("/admin/orders/{$id}")
            ->with($ok ? 'success' : 'error', $ok ? '환불 완료 처리되었습니다.' : '환불 완료 처리에 실패했습니다.');
    }

    /** POST /admin/orders/:id/exchange-approve — 교환 승인 (재고 복구) */
    public function approveExchange(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $ok = $this->orderModel->approveExchange($id);

        return redirect()->to("/admin/orders/{$id}")
            ->with($ok ? 'success' : 'error', $ok ? '교환이 승인되었습니다. 대체품 발송 후 완료 처리하세요.' : '교환 승인에 실패했습니다.');
    }

    /** POST /admin/orders/:id/exchange-reject — 교환 거부 */
    public function rejectExchange(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $ok = $this->orderModel->rejectExchange($id);

        return redirect()->to("/admin/orders/{$id}")
            ->with($ok ? 'success' : 'error', $ok ? '교환이 거부되었습니다.' : '교환 거부에 실패했습니다.');
    }

    /** POST /admin/orders/:id/exchange-complete — 교환 완료 (대체품 발송 확인) */
    public function completeExchange(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $ok = $this->orderModel->completeExchange($id);

        return redirect()->to("/admin/orders/{$id}")
            ->with($ok ? 'success' : 'error', $ok ? '교환 완료 처리되었습니다.' : '교환 완료 처리에 실패했습니다.');
    }
}
