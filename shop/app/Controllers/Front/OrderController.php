<?php

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Libraries\PG\PGFactory;
use App\Models\CartModel;
use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\ShippingAddressModel;

class OrderController extends BaseController
{
    private OrderModel          $orderModel;
    private CartModel           $cartModel;
    private ProductModel        $productModel;
    private ShippingAddressModel $addressModel;

    public function __construct()
    {
        $this->orderModel   = new OrderModel();
        $this->cartModel    = new CartModel();
        $this->productModel = new ProductModel();
        $this->addressModel = new ShippingAddressModel();
    }

    /** GET /order — 주문서 (장바구니 상품 기반) */
    public function index()
    {
        $userId = (int) session()->get('user_id');
        $items  = $this->cartModel->getByUser($userId);

        if (empty($items)) {
            return redirect()->to('/cart')->with('error', '장바구니가 비어 있습니다.');
        }

        // 구매 불가 상품 걸러내기
        $available = array_filter($items, fn($i) => $i['is_available']);
        if (empty($available)) {
            return redirect()->to('/cart')->with('error', '구매 가능한 상품이 없습니다.');
        }

        $totalProduct = array_sum(array_map(
            fn($i) => ($i['discount_price'] ?? $i['price']) * $i['qty'],
            $available
        ));

        $shippingFee  = $this->orderModel->calculateShippingFee($available, $totalProduct);
        $totalAmount  = $totalProduct + $shippingFee;
        $savedAddresses = $this->addressModel->getByUser($userId);
        $savedAddress   = $this->addressModel->getDefault($userId);
        $pgProviders    = PGFactory::labels();

        return $this->render('shop/checkout', compact(
            'available', 'totalProduct', 'shippingFee', 'totalAmount',
            'savedAddresses', 'savedAddress', 'pgProviders'
        ));
    }

    /**
     * POST /order/create — 주문 생성 (pending)
     * 재고는 아직 차감하지 않음 — 결제 확정(PaymentController::callback) 시 차감
     */
    public function create()
    {
        $userId = (int) session()->get('user_id');

        $rules = [
            'receiver_name'  => 'required|max_length[100]',
            'receiver_phone' => 'required|max_length[20]',
            'zipcode'        => 'required|max_length[10]',
            'address1'       => 'required|max_length[200]',
            'pg_provider'    => 'required|in_list[' . implode(',', PGFactory::providers()) . ']',
        ];

        if (! $this->validate($rules)) {
            return $this->response->setJSON(['success' => false, 'errors' => $this->validator->getErrors()]);
        }

        $shippingData = $this->request->getPost(['receiver_name', 'receiver_phone', 'zipcode', 'address1', 'address2', 'delivery_memo']);
        $pgProvider   = $this->request->getPost('pg_provider');
        $saveAddress  = (bool) $this->request->getPost('save_address');

        $items = $this->cartModel->getByUser($userId);
        $items = array_values(array_filter($items, fn($i) => $i['is_available']));

        if (empty($items)) {
            return $this->response->setJSON(['success' => false, 'message' => '구매 가능한 상품이 없습니다.']);
        }

        // 최종 재고 검증 (주문 생성 전 빠른 사전 확인 — 실제 차감은 결제 확정 시)
        foreach ($items as $item) {
            $stock = (int) $this->productModel->db
                ->table('products')->select('stock')->where('id', $item['product_id'])->get()->getRow()->stock;
            if ($stock < (int) $item['qty']) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => "[{$item['name']}] 재고가 부족합니다. (현재 {$stock}개)",
                ]);
            }
        }

        $orderId = $this->orderModel->createPending($userId, $shippingData, $items);
        if (! $orderId) {
            return $this->response->setJSON(['success' => false, 'message' => '주문 생성에 실패했습니다.']);
        }

        if ($saveAddress) {
            $this->addressModel->saveAddress($userId, $shippingData);
        }

        // 무통장입금: PG 콜백 없으므로 여기서 바로 처리
        if ($pgProvider === 'bank_transfer') {
            $db  = \Config\Database::connect();
            $now = date('Y-m-d H:i:s');

            // 주문 상태를 입금 대기로 전환
            $this->orderModel->update($orderId, ['status' => 'awaiting_payment']);

            // 결제 레코드 생성 (입금 전 pending 상태)
            $order = $this->orderModel->getWithItems($orderId, $userId);
            $db->table('payments')->insert([
                'order_id'     => $orderId,
                'pg_provider'  => 'bank_transfer',
                'pg_tid'       => null,
                'method'       => '무통장입금',
                'amount'       => (int) $order['total_amount'],
                'status'       => 'pending',
                'raw_response' => '{}',
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);

            // 장바구니 비우기 (PG 콜백이 없으므로 여기서 처리)
            $productIds = array_column($items, 'product_id');
            $db->table('cart_items')
                ->where('user_id', $userId)
                ->whereIn('product_id', $productIds)
                ->delete();

            return $this->response->setJSON([
                'success'  => true,
                'orderId'  => $orderId,
                'pgParams' => [
                    'pg'          => 'bank_transfer',
                    'redirectUrl' => '/order/bank_transfer/' . $order['order_number'],
                ],
            ]);
        }

        $order        = $this->orderModel->getWithItems($orderId, $userId);
        $pg           = PGFactory::make($pgProvider);
        $pgParams     = $pg->buildPaymentParams($order);

        // PG별 세션 저장 (승인 시 사용)
        if ($pgProvider === 'kakaopay' && isset($pgParams['tid'])) {
            session()->set('kakaopay_tid', $pgParams['tid']);
            session()->set('kakaopay_order_number', $order['order_number']);
        }
        if ($pgProvider === 'toss') {
            session()->set('toss_order_id', (string) $orderId);
        }

        return $this->response->setJSON([
            'success'  => true,
            'orderId'  => $orderId,
            'pgParams' => $pgParams,
        ]);
    }

    /** GET /order/bank_transfer/:orderNumber — 입금 대기 안내 페이지 */
    public function bankTransfer(string $orderNumber)
    {
        $userId = (int) session()->get('user_id');
        $order  = $this->orderModel->where('order_number', $orderNumber)->where('user_id', $userId)->first();

        if (! $order || ! in_array($order['status'], ['awaiting_payment', 'paid'], true)) {
            return redirect()->to('/')->with('error', '유효하지 않은 주문입니다.');
        }

        $order = $this->orderModel->getWithItems($order['id'], $userId);

        return $this->render('shop/bank_transfer', compact('order'));
    }

    /** GET /order/complete/:orderNumber */
    public function complete(string $orderNumber)
    {
        $userId = (int) session()->get('user_id');
        $order  = $this->orderModel->where('order_number', $orderNumber)->where('user_id', $userId)->first();

        if (! $order || $order['status'] !== 'paid') {
            return redirect()->to('/')->with('error', '유효하지 않은 주문입니다.');
        }

        $order = $this->orderModel->getWithItems($order['id'], $userId);

        return $this->render('shop/order_complete', compact('order'));
    }

    /** GET /order/fail/:orderNumber */
    public function fail(string $orderNumber)
    {
        $userId  = (int) session()->get('user_id');
        $order   = $this->orderModel->where('order_number', $orderNumber)->where('user_id', $userId)->first();
        $message = session()->getFlashdata('pg_error') ?? '결제에 실패했습니다.';

        return $this->render('shop/order_fail', compact('order', 'message'));
    }

    /** POST /order/cancel — 주문 취소 */
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
