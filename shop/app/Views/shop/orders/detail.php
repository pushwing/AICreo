<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$payment  = $order['payment'] ?? null;
$items    = $order['items']   ?? [];

$statusBadge = [
    'pending'           => ['secondary', '결제 대기'],
    'awaiting_payment'  => ['primary',   '입금 대기'],
    'paid'              => ['success',   '결제 완료'],
    'preparing'         => ['info',      '배송 준비'],
    'shipped'           => ['warning',   '배송 중'],
    'delivered'         => ['success',   '배송 완료'],
    'cancelled'         => ['danger',    '취소'],
    'expired'           => ['secondary', '만료'],
    'refund_requested'  => ['warning',   '환불 요청'],
    'refunded'          => ['dark',      '환불 완료'],
];
$pgLabels = [
    'bank_transfer' => '무통장입금',
    'toss'          => '토스페이먼츠',
    'inicis'        => 'KG이니시스',
    'nicepay'       => '나이스페이',
    'kakaopay'      => '카카오페이',
    'naverpay'      => '네이버페이',
    'payco'         => 'PAYCO',
];
$methodLabels = [
    'card'            => '신용/체크카드',
    'virtual_account' => '가상계좌',
    'transfer'        => '계좌이체',
    'phone'           => '휴대폰',
    'kakaopay'        => '카카오페이',
    'naverpay'        => '네이버페이',
    'payco'           => 'PAYCO',
    '무통장입금'      => '무통장입금',
];

[$badgeColor, $badgeLabel] = $statusBadge[$order['status']] ?? ['secondary', $order['status']];
$canCancel       = in_array($order['status'], ['pending', 'awaiting_payment', 'paid'], true);
$canConfirmDelivery = $order['status'] === 'shipped';
$isBankTransfer  = ($payment['pg_provider'] ?? '') === 'bank_transfer';
?>

<div class="container py-4" style="max-width:680px">

    <!-- 헤더 -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="/mypage/orders" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-chevron-left"></i>
        </a>
        <div>
            <h5 class="fw-bold mb-0">주문 상세</h5>
            <div class="text-muted small"><?= esc($order['order_number']) ?></div>
        </div>
        <span class="badge bg-<?= $badgeColor ?> ms-auto"><?= $badgeLabel ?></span>
    </div>

    <!-- 주문 상품 -->
    <div class="card mb-3">
        <div class="card-header fw-semibold bg-white">
            <i class="bi bi-bag me-2 text-primary"></i>주문 상품
        </div>
        <div class="card-body p-0">
            <?php foreach ($items as $item): ?>
            <div class="d-flex align-items-center gap-3 p-3 border-bottom">
                <a href="/shop/<?= esc($item['product_slug'] ?? $item['product_id']) ?>" class="flex-shrink-0">
                    <?php if (! empty($item['thumbnail'])): ?>
                    <img src="/<?= esc($item['thumbnail']) ?>" alt="<?= esc($item['product_name']) ?>"
                         style="width:64px;height:64px;object-fit:cover;border-radius:6px">
                    <?php else: ?>
                    <div class="bg-light d-flex align-items-center justify-content-center text-muted"
                         style="width:64px;height:64px;border-radius:6px;font-size:1.4rem">
                        <i class="bi bi-image"></i>
                    </div>
                    <?php endif; ?>
                </a>
                <div class="flex-grow-1 small">
                    <a href="/shop/<?= esc($item['product_slug'] ?? $item['product_id']) ?>" class="fw-semibold mb-1 text-dark text-decoration-none">
                        <?= esc($item['product_name']) ?>
                    </a>
                    <div class="text-muted">
                        <?= number_format($item['product_price']) ?>원 × <?= (int) $item['qty'] ?>개
                    </div>
                </div>
                <div class="fw-bold"><?= number_format($item['subtotal']) ?>원</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 배송지 -->
    <div class="card mb-3">
        <div class="card-header fw-semibold bg-white">
            <i class="bi bi-geo-alt me-2 text-primary"></i>배송지
        </div>
        <div class="card-body small">
            <div class="fw-semibold mb-1">
                <?= esc($order['receiver_name']) ?>
                <span class="text-muted fw-normal ms-2"><?= esc($order['receiver_phone']) ?></span>
            </div>
            <div class="text-muted">
                (<?= esc($order['zipcode']) ?>)
                <?= esc($order['address1']) ?>
                <?= ! empty($order['address2']) ? ' ' . esc($order['address2']) : '' ?>
            </div>
            <?php if (! empty($order['delivery_memo'])): ?>
            <div class="text-muted mt-1">
                <i class="bi bi-chat-left-text me-1"></i><?= esc($order['delivery_memo']) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 배송 현황 -->
    <?php if (! empty($order['tracking_number'])): ?>
    <div class="card mb-3">
        <div class="card-header fw-semibold bg-white">
            <i class="bi bi-truck me-2 text-primary"></i>배송 현황
        </div>
        <div class="card-body small">
            <dl class="row mb-0">
                <dt class="col-4 fw-normal text-muted">택배사</dt>
                <dd class="col-8"><?= esc($order['tracking_company'] ?? '-') ?></dd>
                <dt class="col-4 fw-normal text-muted">운송장 번호</dt>
                <dd class="col-8">
                    <span class="me-2"><?= esc($order['tracking_number']) ?></span>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0" id="btnCopyTracking"
                            data-tracking="<?= esc($order['tracking_number']) ?>">
                        <i class="bi bi-clipboard"></i> 복사
                    </button>
                </dd>
            </dl>
        </div>
    </div>
    <?php endif; ?>

    <!-- 무통장 입금 안내 -->
    <?php if ($isBankTransfer && $order['status'] === 'awaiting_payment'): ?>
    <div class="card mb-3 border-primary">
        <div class="card-header fw-semibold bg-primary text-white">
            <i class="bi bi-bank me-2"></i>무통장 입금 안내
        </div>
        <div class="card-body">
            <dl class="row mb-3 small">
                <dt class="col-5 fw-normal text-muted">입금 은행</dt>
                <dd class="col-7 fw-semibold"><?= esc($settings['bank_name'] ?? '—') ?></dd>

                <dt class="col-5 fw-normal text-muted">계좌번호</dt>
                <dd class="col-7">
                    <span id="bankAccount" class="fw-semibold me-2"><?= esc($settings['bank_account'] ?? '—') ?></span>
                    <?php if (! empty($settings['bank_account'])): ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-0" id="btnCopyAccount"
                            data-account="<?= esc($settings['bank_account']) ?>">
                        <i class="bi bi-clipboard"></i> 복사
                    </button>
                    <?php endif; ?>
                </dd>

                <dt class="col-5 fw-normal text-muted">예금주</dt>
                <dd class="col-7 fw-semibold"><?= esc($settings['bank_holder'] ?? '—') ?></dd>
            </dl>
            <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded">
                <span class="fw-semibold text-muted small">입금하실 금액</span>
                <span class="fs-5 fw-bold text-primary"><?= number_format($order['total_amount']) ?>원</span>
            </div>
            <div class="text-muted small mt-2">
                <i class="bi bi-info-circle me-1"></i>
                입금자명은 주문자 성함과 동일하게 입금해 주세요. 입금 확인 후 주문이 처리됩니다.
            </div>
        </div>
    </div>
    <?php elseif ($isBankTransfer && $order['status'] === 'paid'): ?>
    <div class="alert alert-success d-flex align-items-center mb-3" role="alert">
        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
        <span>입금이 확인되었습니다. 주문이 정상적으로 처리되었습니다.</span>
    </div>
    <?php endif; ?>

    <!-- 결제 정보 -->
    <div class="card mb-4">
        <div class="card-header fw-semibold bg-white">
            <i class="bi bi-credit-card me-2 text-primary"></i>결제 정보
        </div>
        <div class="card-body">
            <dl class="row mb-0 small">
                <dt class="col-5 fw-normal text-muted">상품 합계</dt>
                <dd class="col-7"><?= number_format($order['total_product_price']) ?>원</dd>

                <dt class="col-5 fw-normal text-muted">배송비</dt>
                <dd class="col-7"><?= (int) $order['shipping_fee'] > 0 ? number_format($order['shipping_fee']) . '원' : '무료' ?></dd>

                <dt class="col-5 fw-bold text-dark border-top pt-2 mt-1">총 결제 금액</dt>
                <dd class="col-7 fw-bold text-primary border-top pt-2 mt-1"><?= number_format($order['total_amount']) ?>원</dd>

                <?php if ($payment): ?>
                <dt class="col-5 fw-normal text-muted mt-2">결제 수단</dt>
                <dd class="col-7 mt-2">
                    <?= esc($pgLabels[$payment['pg_provider']] ?? $payment['pg_provider']) ?>
                    <?php if ($payment['method']): ?>
                    · <?= esc($methodLabels[$payment['method']] ?? $payment['method']) ?>
                    <?php endif; ?>
                </dd>

                <dt class="col-5 fw-normal text-muted">결제 일시</dt>
                <dd class="col-7">
                    <?= $payment['paid_at'] ? date('Y.m.d H:i', strtotime($payment['paid_at'])) : '-' ?>
                </dd>
                <?php endif; ?>

                <dt class="col-5 fw-normal text-muted">주문 일시</dt>
                <dd class="col-7"><?= date('Y.m.d H:i', strtotime($order['created_at'])) ?></dd>
            </dl>
        </div>
    </div>

    <!-- 버튼 -->
    <div class="d-flex gap-2">
        <a href="/mypage/orders" class="btn btn-outline-secondary flex-fill">목록으로</a>
        <?php if ($canConfirmDelivery): ?>
        <button type="button" id="btnConfirmDelivery" class="btn btn-success flex-fill"
                data-order-id="<?= (int) $order['id'] ?>"
                data-csrf="<?= csrf_token() ?>"
                data-csrf-val="<?= csrf_hash() ?>">
            <i class="bi bi-check2-circle me-1"></i>배송 완료 확인
        </button>
        <?php elseif ($canCancel): ?>
        <button type="button" id="btnCancel" class="btn btn-danger flex-fill"
                data-order-id="<?= (int) $order['id'] ?>"
                data-csrf="<?= csrf_token() ?>"
                data-csrf-val="<?= csrf_hash() ?>">
            주문 취소
        </button>
        <?php else: ?>
        <a href="/shop" class="btn btn-primary flex-fill">쇼핑 계속하기</a>
        <?php endif; ?>
    </div>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    function makeCopyHandler(btnId, dataKey) {
        document.getElementById(btnId)?.addEventListener('click', function () {
            navigator.clipboard.writeText(this.dataset[dataKey]).then(() => {
                const btn = this;
                btn.innerHTML = '<i class="bi bi-check2"></i> 복사됨';
                btn.classList.replace('btn-outline-secondary', 'btn-success');
                setTimeout(() => {
                    btn.innerHTML = '<i class="bi bi-clipboard"></i> 복사';
                    btn.classList.replace('btn-success', 'btn-outline-secondary');
                }, 2000);
            });
        });
    }
    makeCopyHandler('btnCopyAccount',  'account');
    makeCopyHandler('btnCopyTracking', 'tracking');

    // 배송 완료 확인
    document.getElementById('btnConfirmDelivery')?.addEventListener('click', function () {
        if (! confirm('배송이 완료되었나요?\n확인 후에는 되돌릴 수 없습니다.')) return;

        const btn  = this;
        const body = new FormData();
        body.append(btn.dataset.csrf, btn.dataset.csrfVal);
        body.append('order_id', btn.dataset.orderId);

        btn.disabled    = true;
        btn.textContent = '처리 중...';

        fetch('/mypage/orders/confirm-delivery', { method: 'POST', body })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || '처리에 실패했습니다.');
                    btn.disabled    = false;
                    btn.innerHTML   = '<i class="bi bi-check2-circle me-1"></i>배송 완료 확인';
                }
            })
            .catch(() => {
                alert('오류가 발생했습니다. 다시 시도해주세요.');
                btn.disabled    = false;
                btn.innerHTML   = '<i class="bi bi-check2-circle me-1"></i>배송 완료 확인';
            });
    });

    document.getElementById('btnCancel')?.addEventListener('click', function () {
        if (! confirm('주문을 취소하시겠습니까?\n취소 후에는 되돌릴 수 없습니다.')) return;

        const btn     = this;
        const body    = new FormData();
        body.append(btn.dataset.csrf, btn.dataset.csrfVal);
        body.append('order_id', btn.dataset.orderId);

        btn.disabled    = true;
        btn.textContent = '처리 중...';

        fetch('/mypage/orders/cancel', { method: 'POST', body })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    window.location.href = '/mypage/orders';
                } else {
                    alert(data.message || '취소에 실패했습니다.');
                    btn.disabled    = false;
                    btn.textContent = '주문 취소';
                }
            })
            .catch(function () {
                alert('오류가 발생했습니다. 다시 시도해주세요.');
                btn.disabled    = false;
                btn.textContent = '주문 취소';
            });
    });
})();
</script>
<?= $this->endSection() ?>
