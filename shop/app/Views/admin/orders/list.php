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
    <div class="col-auto ms-auto d-flex align-items-center gap-2">
        <span class="text-muted small">총 <?= number_format($total) ?>건</span>
        <a href="/admin/orders/tracking-upload" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-truck me-1"></i>송장 일괄 등록
        </a>
        <a href="/admin/orders/export?q=<?= urlencode($keyword) ?>&status=<?= urlencode($status) ?>"
           class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i>엑셀 다운로드
        </a>
    </div>
</form>


<!-- 일괄 변경 액션 바 (체크박스 선택 시 표시) -->
<div id="bulkActionBar" class="d-none bg-light border rounded p-2 mb-3 d-flex align-items-center gap-2 flex-wrap">
    <span class="small fw-semibold" id="selectedCount">0개 선택됨</span>
    <select id="bulkStatus" class="form-select form-select-sm" style="width:150px">
        <option value="">→ 상태 선택</option>
        <?php foreach ($statusLabels as $val => $label): ?>
        <option value="<?= $val ?>"><?= $label ?></option>
        <?php endforeach; ?>
    </select>
    <button id="btnBulkUpdate" class="btn btn-sm btn-primary">일괄 변경</button>
    <button id="btnClearSelection" class="btn btn-sm btn-outline-secondary">선택 해제</button>
    <span class="text-muted small ms-1">※ 허용되지 않는 전환은 자동 건너뜁니다</span>
</div>

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
                    <th style="width:36px"><input type="checkbox" id="checkAll" title="전체 선택"></th>
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
                    <td colspan="9" class="text-center text-muted py-4">주문이 없습니다.</td>
                </tr>
                <?php endif; ?>
                <?php foreach ($items as $order): ?>
                <tr>
                    <td><input type="checkbox" class="order-check" value="<?= (int) $order['id'] ?>"></td>
                    <td class="small fw-semibold">
                        <?= esc($order['order_number']) ?>
                        <?php if (($order['memo_count'] ?? 0) > 0): ?>
                        <i class="bi bi-sticky text-warning ms-1" title="내부 메모 있음"></i>
                        <?php endif; ?>
                    </td>
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

<?= $this->section('scripts') ?>
<script>
(function () {
    var bar          = document.getElementById('bulkActionBar');
    var checkAll     = document.getElementById('checkAll');
    var selectedText = document.getElementById('selectedCount');

    function getChecked() {
        return document.querySelectorAll('.order-check:checked');
    }

    function updateBar() {
        var checked = getChecked();
        if (checked.length > 0) {
            bar.classList.remove('d-none');
            selectedText.textContent = checked.length + '개 선택됨';
        } else {
            bar.classList.add('d-none');
        }
        checkAll.indeterminate = checked.length > 0 && checked.length < document.querySelectorAll('.order-check').length;
        checkAll.checked = checked.length > 0 && checked.length === document.querySelectorAll('.order-check').length;
    }

    checkAll.addEventListener('change', function () {
        document.querySelectorAll('.order-check').forEach(function (cb) { cb.checked = checkAll.checked; });
        updateBar();
    });

    document.querySelectorAll('.order-check').forEach(function (cb) {
        cb.addEventListener('change', updateBar);
    });

    document.getElementById('btnClearSelection').addEventListener('click', function () {
        document.querySelectorAll('.order-check').forEach(function (cb) { cb.checked = false; });
        checkAll.checked = false;
        bar.classList.add('d-none');
    });

    document.getElementById('btnBulkUpdate').addEventListener('click', function () {
        var status = document.getElementById('bulkStatus').value;
        if (! status) { alert('변경할 상태를 선택해주세요.'); return; }

        var ids = Array.from(getChecked()).map(function (cb) { return cb.value; });
        if (ids.length === 0) { alert('주문을 선택해주세요.'); return; }

        if (! confirm(ids.length + '개 주문 상태를 변경하시겠습니까?')) return;

        var body = '<?= csrf_token() ?>=' + encodeURIComponent('<?= csrf_hash() ?>')
                 + '&status=' + encodeURIComponent(status)
                 + ids.map(function (id) { return '&order_ids[]=' + id; }).join('');

        fetch('/admin/orders/bulk-status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: body
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            alert(data.message || (data.success ? '처리 완료' : '처리 실패'));
            if (data.success) location.reload();
        })
        .catch(function () { alert('오류가 발생했습니다.'); });
    });
}());
</script>
<?= $this->endSection() ?>
