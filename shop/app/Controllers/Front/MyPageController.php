<?php

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Models\OrderModel;
use App\Models\PointLogModel;
use App\Models\ShippingAddressModel;
use App\Models\UserCouponModel;

class MyPageController extends BaseController
{
    private OrderModel           $orderModel;
    private ShippingAddressModel $addressModel;
    private UserCouponModel      $userCouponModel;
    private PointLogModel        $pointLogModel;

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
        $this->orderModel      = new OrderModel();
        $this->addressModel    = new ShippingAddressModel();
        $this->userCouponModel = new UserCouponModel();
        $this->pointLogModel   = new PointLogModel();
    }

    /** GET /mypage/orders */
    public function orders(): string
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
    public function orderDetail(string $orderNumber): \CodeIgniter\HTTP\RedirectResponse|string
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
    public function confirmDelivery(): \CodeIgniter\HTTP\ResponseInterface
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

        $ok = $this->orderModel->updateStatus($orderId, 'delivered');

        return $this->response->setJSON(['success' => $ok, 'message' => $ok ? '' : '처리에 실패했습니다.']);
    }

    /** GET /mypage/addresses */
    public function addresses(): string
    {
        $userId    = (int) session()->get('user_id');
        $addresses = $this->addressModel->getByUser($userId);
        return $this->render('shop/addresses/index', compact('addresses'));
    }

    /** POST /mypage/addresses */
    public function addressStore(): \CodeIgniter\HTTP\RedirectResponse
    {
        $userId = (int) session()->get('user_id');

        $rules = [
            'receiver_name'  => 'required|max_length[100]',
            'receiver_phone' => 'required|max_length[20]',
            'zipcode'        => 'required|max_length[10]',
            'address1'       => 'required|max_length[200]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = $this->request->getPost(['receiver_name', 'receiver_phone', 'zipcode', 'address1', 'address2']);

        $count = $this->addressModel->where('user_id', $userId)->countAllResults();
        $id    = $this->addressModel->saveAddress($userId, $data);

        if (! $id) {
            return redirect()->back()->withInput()->with('error', '배송지 저장에 실패했습니다. 다시 시도해주세요.');
        }

        // 첫 번째 주소는 자동으로 기본 배송지로 설정
        if ($count === 0) {
            $this->addressModel->setDefault($id, $userId);
        }

        return redirect()->to('/mypage/addresses')->with('success', '배송지가 저장되었습니다.');
    }

    /** POST /mypage/addresses/:id/default */
    public function addressSetDefault(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $userId = (int) session()->get('user_id');

        if (! $this->addressModel->setDefault($id, $userId)) {
            return redirect()->to('/mypage/addresses')->with('error', '배송지를 찾을 수 없습니다.');
        }

        return redirect()->to('/mypage/addresses')->with('success', '기본 배송지가 변경되었습니다.');
    }

    /** POST /mypage/addresses/:id/delete */
    public function addressDelete(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $userId  = (int) session()->get('user_id');
        $address = $this->addressModel->where('id', $id)->where('user_id', $userId)->first();

        if (! $address) {
            return redirect()->to('/mypage/addresses')->with('error', '배송지를 찾을 수 없습니다.');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $deleted = $this->addressModel->deleteByUser($id, $userId);

        if ($deleted && $address['is_default']) {
            $next = $this->addressModel->where('user_id', $userId)->orderBy('id', 'DESC')->first();
            if ($next) {
                $this->addressModel->setDefault($next['id'], $userId);
            }
        }

        $db->transComplete();

        if (! $db->transStatus() || ! $deleted) {
            return redirect()->to('/mypage/addresses')->with('error', '배송지 삭제에 실패했습니다.');
        }

        return redirect()->to('/mypage/addresses')->with('success', '배송지가 삭제되었습니다.');
    }

    /** GET /mypage/coupons */
    public function coupons(): string
    {
        $userId  = (int) session()->get('user_id');
        $tab         = $this->request->getGet('tab') ?? 'available';
        $currentPage = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage     = 10;

        if (! in_array($tab, ['available', 'used'], true)) $tab = 'available';

        $now     = date('Y-m-d H:i:s');
        $builder = \Config\Database::connect()->table('user_coupons uc')
            ->select('uc.*, c.code, c.name, c.type, c.discount_value,
                      c.min_order_amount, c.max_discount_amount, c.expires_at')
            ->join('coupons c', 'c.id = uc.coupon_id')
            ->where('uc.user_id', $userId);

        if ($tab === 'available') {
            $builder->where('uc.status', 'issued')
                ->groupStart()
                    ->where('c.expires_at IS NULL', null, false)
                    ->orWhere('c.expires_at >=', $now)
                ->groupEnd();
        } else {
            $builder->where('uc.status', 'used');
        }

        $total   = (clone $builder)->countAllResults();
        $coupons = $builder->orderBy('uc.id', 'DESC')
            ->limit($perPage, ($currentPage - 1) * $perPage)
            ->get()->getResultArray();

        return $this->render('shop/mypage/coupons', compact('coupons', 'tab', 'total', 'currentPage', 'perPage'));
    }

    /** GET /mypage/points */
    public function points(): string
    {
        $userId       = (int) session()->get('user_id');
        $currentPage  = max(1, (int) ($this->request->getGet('page') ?? 1));
        $result       = $this->pointLogModel->getByUser($userId, $currentPage);
        $user         = \Config\Database::connect()->table('users')->select('point_balance')->where('id', $userId)->get()->getRow();
        $pointBalance = (int) ($user->point_balance ?? 0);

        return $this->render('shop/mypage/points', array_merge($result, compact('pointBalance')));
    }

    /** POST /mypage/orders/return-request — 반품 신청 (배송 완료 후) */
    public function requestReturn(): \CodeIgniter\HTTP\ResponseInterface
    {
        $userId  = (int) session()->get('user_id');
        $orderId = (int) $this->request->getPost('order_id');
        $reason  = trim($this->request->getPost('reason') ?? '');

        if (! $orderId || $reason === '') {
            return $this->response->setJSON(['success' => false, 'message' => '반품 사유를 입력해주세요.']);
        }

        $success = $this->orderModel->requestReturn($orderId, $userId, $reason);

        return $this->response->setJSON([
            'success' => $success,
            'message' => $success ? '반품 신청이 완료되었습니다.' : '반품 신청할 수 없는 주문입니다.',
        ]);
    }

    /** POST /mypage/orders/cancel — 즉시 취소 */
    public function cancel(): \CodeIgniter\HTTP\ResponseInterface
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
