<?= $this->extend('layouts/admin') ?>
<?php $pageTitle = '주문 관리' ?>

<?= $this->section('content') ?>

<?php
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
?>

<!-- 검색 폼 -->
<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <input type="text" name="q" class="form-control form-control-sm"
               placeholder="주문번호 / 수취인명 / 이메일"
               value="<?= esc($keyword) ?>" style="min-width:240px">
    </div>
    <div class="col-auto">
        <select name="status" class="form-select form-select-sm">
            <option value="">전체 상태</option>
            <?php foreach ($statusLabels as $val => $label): ?>
            <option value="<?= $val ?>" <?= $status === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-secondary btn-sm">검색</button>
        <a href="/admin/orders" class="btn btn-outline-secondary btn-sm">초기화</a>
    </div>
    <div class="col-auto ms-auto d-flex align-items-center text-muted small">
        총 <?= number_format($total) ?>건
    </div>
</form>


<!-- 목록 테이블 -->
<div class="card overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <?php
            $pgLabels = [
                'bank_transfer' => '무통장',
                'toss'          => '토스',
                'inicis'        => '이니시스',
                'nicepay'       => '나이스페이',
                'kakaopay'      => '카카오페이',
                'naverpay'      => '네이버페이',
                'payco'         => 'PAYCO',
            ];
            ?>
            <thead class="table-light">
                <tr>
                    <th>주문번호</th>
                    <th>주문일시</th>
                    <th>회원</th>
                    <th>수취인</th>
                    <th>결제수단</th>
                    <th>결제금액</th>
                    <th>상태</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">주문이 없습니다.</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($items as $order): ?>
                <tr>
                    <td class="small fw-semibold"><?= esc($order['order_number']) ?></td>
                    <td class="small text-muted"><?= date('Y년 n월 j일 G시 i분', strtotime($order['created_at'])) ?></td>
                    <td class="small">
                        <?= esc($order['user_nickname'] ?? '-') ?>
                        <?php if ($order['user_email']): ?>
                        <br><span class="text-muted"><?= esc($order['user_email']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="small"><?= esc($order['receiver_name']) ?></td>
                    <td class="small">
                        <?php if ($order['pg_provider']): ?>
                        <span class="badge bg-light text-dark border">
                            <?= esc($pgLabels[$order['pg_provider']] ?? $order['pg_provider']) ?>
                        </span>
                        <?php if ($order['payment_method']): ?>
                        <span class="text-muted" style="font-size:.7rem"><?= esc($order['payment_method']) ?></span>
                        <?php endif; ?>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="small"><?= number_format($order['total_amount']) ?>원</td>
                    <td>
                        <span class="badge bg-<?= $statusBadge[$order['status']] ?? 'secondary' ?>">
                            <?= $statusLabels[$order['status']] ?? $order['status'] ?>
                        </span>
                    </td>
                    <td>
                        <a href="/admin/orders/<?= (int) $order['id'] ?>"
                           class="btn btn-sm btn-outline-secondary">상세</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 페이징 -->
<?php if ($totalPages > 1): ?>
<nav class="mt-3 d-flex justify-content-center">
    <ul class="pagination pagination-sm mb-0">
        <?php if ($currentPage > 1): ?>
        <li class="page-item">
            <a class="page-link" href="?q=<?= esc($keyword) ?>&status=<?= esc($status) ?>&page=<?= $currentPage - 1 ?>">‹</a>
        </li>
        <?php endif; ?>
        <?php
        $start = max(1, $currentPage - 2);
        $end   = min($totalPages, $currentPage + 2);
        for ($i = $start; $i <= $end; $i++):
        ?>
        <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
            <a class="page-link" href="?q=<?= esc($keyword) ?>&status=<?= esc($status) ?>&page=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
        <?php if ($currentPage < $totalPages): ?>
        <li class="page-item">
            <a class="page-link" href="?q=<?= esc($keyword) ?>&status=<?= esc($status) ?>&page=<?= $currentPage + 1 ?>">›</a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<?= $this->endSection() ?>
