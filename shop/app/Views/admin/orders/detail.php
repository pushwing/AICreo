<?= $this->extend('layouts/admin') ?>
<?php $pageTitle = '주문 상세' ?>

<?= $this->section('content') ?>

<?php
$payment  = $order['payment'] ?? null;
$items    = $order['items']   ?? [];

$statusBadge = [
    'pending'           => 'secondary',
    'awaiting_payment'  => 'primary',
    'paid'              => 'success',
    'preparing'         => 'info',
    'shipped'           => 'warning',
    'delivered'         => 'success',
    'cancelled'         => 'danger',
    'expired'           => 'secondary',
    'refund_requested'  => 'warning',
    'refunded'          => 'dark',
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

$currentStatus      = $order['status'];
$next               = $nextStatus[$currentStatus] ?? null;
$canCancel          = in_array($currentStatus, ['pending', 'awaiting_payment', 'paid', 'preparing'], true);
$canRefund          = $currentStatus === 'refund_requested';
$isBankTransfer     = ($payment['pg_provider'] ?? '') === 'bank_transfer';
$canConfirmBank     = $isBankTransfer && $currentStatus === 'awaiting_payment';
?>

<!-- 헤더 -->
<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <a href="/admin/orders" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-chevron-left"></i> 목록
    </a>
    <div>
        <h5 class="fw-bold mb-0">주문 상세</h5>
        <div class="text-muted small"><?= esc($order['order_number']) ?></div>
    </div>
    <div class="ms-auto d-flex align-items-center gap-2 flex-wrap">
        <?php if ($payment): ?>
        <span class="badge bg-light text-dark border fs-6">
            <?= esc($pgLabels[$payment['pg_provider']] ?? $payment['pg_provider']) ?>
            <?php if ($payment['method']): ?>
            <span class="fw-normal text-muted">· <?= esc($payment['method']) ?></span>
            <?php endif; ?>
        </span>
        <?php endif; ?>
        <span class="badge bg-<?= $statusBadge[$currentStatus] ?? 'secondary' ?> fs-6">
            <?= $statusLabels[$currentStatus] ?? $currentStatus ?>
        </span>
    </div>
</div>

<!-- 플래시 -->
<?php if ($flash = session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show py-2">
    <?= esc($flash) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($flash = session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show py-2">
    <?= esc($flash) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">

    <!-- 좌측: 상품 · 배송지 · 결제 -->
    <div class="col-lg-8">

        <!-- 주문 상품 -->
        <div class="card mb-3">
            <div class="card-header fw-semibold bg-white">주문 상품</div>
            <div class="card-body p-0">
                <?php foreach ($items as $item): ?>
                <div class="d-flex align-items-center gap-3 p-3 border-bottom">
                    <div class="flex-grow-1 small">
                        <div class="fw-semibold mb-1"><?= esc($item['product_name']) ?></div>
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
            <div class="card-header fw-semibold bg-white">배송지</div>
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
                <div class="text-muted mt-1">배송 메모: <?= esc($order['delivery_memo']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 결제 정보 -->
        <div class="card mb-3">
            <div class="card-header fw-semibold bg-white">결제 정보</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-4 fw-normal text-muted">회원</dt>
                    <dd class="col-8">
                        <?= esc($order['user_nickname'] ?? '-') ?>
                        <?php if ($order['user_email']): ?>
                        <span class="text-muted ms-1">(<?= esc($order['user_email']) ?>)</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-4 fw-normal text-muted">상품 합계</dt>
                    <dd class="col-8"><?= number_format($order['total_product_price']) ?>원</dd>

                    <dt class="col-4 fw-normal text-muted">배송비</dt>
                    <dd class="col-8"><?= (int) $order['shipping_fee'] > 0 ? number_format($order['shipping_fee']) . '원' : '무료' ?></dd>

                    <dt class="col-4 fw-bold text-dark border-top pt-2 mt-1">총 결제금액</dt>
                    <dd class="col-8 fw-bold text-primary border-top pt-2 mt-1"><?= number_format($order['total_amount']) ?>원</dd>

                    <?php if ($payment): ?>
                    <dt class="col-4 fw-normal text-muted mt-2">결제 수단</dt>
                    <dd class="col-8 mt-2 fw-semibold">
                        <?= esc($pgLabels[$payment['pg_provider']] ?? $payment['pg_provider']) ?>
                        <?php if ($payment['method']): ?>
                        <span class="fw-normal text-muted ms-1">· <?= esc($payment['method']) ?></span>
                        <?php endif; ?>
                    </dd>
                    <?php if ($payment['pg_provider'] !== 'bank_transfer'): ?>
                    <dt class="col-4 fw-normal text-muted">PG TID</dt>
                    <dd class="col-8 font-monospace small text-muted"><?= esc($payment['pg_tid'] ?: '-') ?></dd>
                    <?php endif; ?>
                    <dt class="col-4 fw-normal text-muted">결제 일시</dt>
                    <dd class="col-8">
                        <?= $payment['paid_at'] ? date('Y.m.d H:i', strtotime($payment['paid_at'])) : '<span class="text-warning">미확인</span>' ?>
                    </dd>
                    <?php endif; ?>

                    <dt class="col-4 fw-normal text-muted">주문 일시</dt>
                    <dd class="col-8"><?= date('Y.m.d H:i', strtotime($order['created_at'])) ?></dd>
                </dl>
            </div>
        </div>

    </div>

    <!-- 우측: 관리 패널 -->
    <div class="col-lg-4">

        <!-- 무통장 입금 확인 -->
        <?php if ($canConfirmBank): ?>
        <div class="card mb-3 border-primary">
            <div class="card-header fw-semibold bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-bank me-1"></i>무통장 입금 확인
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    입금 금액: <strong class="text-dark"><?= number_format((int) $order['total_amount']) ?>원</strong><br>
                    입금자명: <strong class="text-dark"><?= esc($order['receiver_name']) ?></strong>
                </p>
                <form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/bank_confirm"
                      onsubmit="return confirm('입금을 확인하셨습니까?\n확인 시 재고가 차감되고 결제 완료 처리됩니다.')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-circle me-1"></i>입금 확인 처리
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- 상태 변경 -->
        <?php if ($next): ?>
        <div class="card mb-3">
            <div class="card-header fw-semibold bg-white">상태 변경</div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    현재: <strong><?= $statusLabels[$currentStatus] ?></strong>
                    → 다음: <strong><?= $statusLabels[$next] ?></strong>
                </p>
                <form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/status">
                    <?= csrf_field() ?>
                    <input type="hidden" name="status" value="<?= esc($next) ?>">
                    <button type="submit" class="btn btn-primary w-100">
                        <?= $statusLabels[$next] ?>으로 변경
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- 송장번호 입력 -->
        <?php if (in_array($currentStatus, ['preparing', 'shipped', 'delivered'], true)): ?>
        <div class="card mb-3">
            <div class="card-header fw-semibold bg-white">송장번호</div>
            <div class="card-body">
                <form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/tracking">
                    <?= csrf_field() ?>
                    <div class="mb-2">
                        <input type="text" name="tracking_company" class="form-control form-control-sm"
                               placeholder="택배사 (예: CJ대한통운)"
                               value="<?= esc($order['tracking_company'] ?? '') ?>">
                    </div>
                    <div class="mb-2">
                        <input type="text" name="tracking_number" class="form-control form-control-sm"
                               placeholder="운송장 번호"
                               value="<?= esc($order['tracking_number'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn btn-outline-primary btn-sm w-100">저장</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- 환불 처리 (B안: PG 콘솔에서 취소 후 상태만 변경) -->
        <?php if ($canRefund): ?>
        <div class="card mb-3 border-warning">
            <div class="card-header fw-semibold bg-warning bg-opacity-10">환불 처리</div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    PG 콘솔에서 취소를 완료한 후 아래 버튼을 눌러 환불 완료 처리하세요.
                    <?php if ($payment): ?>
                    <br>PG TID: <code><?= esc($payment['pg_tid'] ?? '-') ?></code>
                    <br>금액: <strong><?= number_format($payment['amount'] ?? 0) ?>원</strong>
                    <?php endif; ?>
                </p>
                <form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/refund"
                      onsubmit="return confirm('PG 콘솔에서 환불을 완료하셨습니까?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-warning w-100">환불 완료 처리</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- 강제 취소 -->
        <?php if ($canCancel): ?>
        <div class="card mb-3 border-danger">
            <div class="card-header fw-semibold bg-danger bg-opacity-10 text-danger">강제 취소</div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    결제 완료 상태인 경우 재고가 자동 복구됩니다.<br>
                    PG 취소는 직접 처리하세요.
                </p>
                <form method="post" action="/admin/orders/<?= (int) $order['id'] ?>/cancel"
                      onsubmit="return confirm('주문을 강제 취소하시겠습니까?\n이 작업은 되돌릴 수 없습니다.')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger w-100">강제 취소</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>

</div>

<?= $this->endSection() ?>
