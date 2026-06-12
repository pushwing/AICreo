<?= $this->extend('layouts/admin') ?>
<?php $pageTitle = '상품 관리' ?>

<?= $this->section('content') ?>

<!-- 일괄 처리용 히든 폼 (테이블과 별도 — 중첩 form 방지) -->
<form id="bulkForm" method="post" action="/admin/products/bulk">
    <?= csrf_field() ?>
    <input type="hidden" name="action" id="bulkAction">
    <input type="hidden" name="status" id="bulkStatusVal">
    <input type="hidden" name="stock"  id="bulkStockVal">
</form>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form method="get" action="/admin/products" class="d-flex gap-2 flex-wrap align-items-center">
        <input type="text" name="keyword" class="form-control form-control-sm" style="width:180px"
               placeholder="상품명 검색" value="<?= esc($keyword ?? '') ?>">
        <select name="status" class="form-select form-select-sm" style="width:120px">
            <option value="">전체 상태</option>
            <?php foreach ($statuses as $val => $label): ?>
            <option value="<?= esc($val) ?>" <?= ($curStatus ?? '') === $val ? 'selected' : '' ?>><?= esc($label) ?></option>
            <?php endforeach; ?>
        </select>
        <?php $isLowStock = ($curStock ?? '') === 'low'; ?>
        <input type="hidden" name="stock" value="<?= $isLowStock ? 'low' : '' ?>">
        <a href="/admin/products?stock=<?= $isLowStock ? '' : 'low' ?><?= $keyword ? '&keyword='.urlencode($keyword) : '' ?>"
           class="btn btn-sm <?= $isLowStock ? 'btn-danger' : 'btn-outline-danger' ?>">
            <i class="bi bi-exclamation-triangle me-1"></i>재고 부족
            <?php if (($lowStockCount ?? 0) > 0): ?>
            <span class="badge bg-white text-danger ms-1"><?= number_format($lowStockCount) ?></span>
            <?php endif; ?>
        </a>
        <button type="submit" class="btn btn-sm btn-outline-secondary">검색</button>
        <?php if ($keyword || $curStatus || $isLowStock): ?>
        <a href="/admin/products" class="btn btn-sm btn-outline-secondary">초기화</a>
        <?php endif; ?>
    </form>
    <div class="d-flex gap-2">
        <a href="/admin/products/categories" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-tags me-1"></i>카테고리
        </a>
        <a href="/admin/products/create" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i>상품 등록
        </a>
    </div>
</div>

<!-- 일괄 편집 액션 바 (1개 이상 선택 시 표시) -->
<div id="bulkBar" class="card mb-3 border-primary d-none">
    <div class="card-body py-2 px-3 d-flex align-items-center flex-wrap gap-3">
        <span class="fw-semibold text-primary small"><span id="bulkCount">0</span>개 선택됨</span>
        <div class="vr"></div>
        <!-- 상태 변경 -->
        <div class="d-flex align-items-center gap-1">
            <span class="small text-muted">상태:</span>
            <select id="bulkStatusSel" class="form-select form-select-sm" style="width:100px">
                <?php foreach ($statuses as $val => $label): ?>
                <option value="<?= esc($val) ?>"><?= esc($label) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-outline-primary" onclick="submitBulk('status')">변경</button>
        </div>
        <div class="vr"></div>
        <!-- 재고 설정 -->
        <div class="d-flex align-items-center gap-1">
            <span class="small text-muted">재고:</span>
            <input id="bulkStockInput" type="number" min="0" class="form-control form-control-sm" style="width:80px" placeholder="0">
            <button class="btn btn-sm btn-outline-primary" onclick="submitBulk('stock')">설정</button>
        </div>
        <div class="vr"></div>
        <!-- 삭제 -->
        <button class="btn btn-sm btn-outline-danger" onclick="submitBulk('delete')">
            <i class="bi bi-trash me-1"></i>삭제
        </button>
        <button class="btn btn-sm btn-link text-muted ms-auto p-0" onclick="clearSelection()">선택 해제</button>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:40px">
                        <input type="checkbox" id="checkAll" class="form-check-input">
                    </th>
                    <th style="width:60px">이미지</th>
                    <th>상품명</th>
                    <th>카테고리</th>
                    <th>가격</th>
                    <th>재고</th>
                    <th>상태</th>
                    <th>등록일</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">등록된 상품이 없습니다.</td></tr>
                <?php endif; ?>
                <?php foreach ($items as $p): ?>
                <tr>
                    <td>
                        <input type="checkbox" class="form-check-input rowCheck" value="<?= $p['id'] ?>">
                    </td>
                    <td>
                        <?php if (! empty($p['primary_image'])): ?>
                        <img src="<?= esc($p['primary_image']) ?>" alt=""
                             style="width:48px;height:48px;object-fit:cover;border-radius:4px">
                        <?php else: ?>
                        <div style="width:48px;height:48px;background:#f1f3f5;border-radius:4px;display:flex;align-items:center;justify-content:center">
                            <i class="bi bi-image text-muted"></i>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="fw-semibold"><?= esc($p['name']) ?></div>
                        <div class="text-muted small">/shop/<?= esc($p['slug']) ?></div>
                    </td>
                    <td class="small text-muted"><?= esc($p['category_name'] ?? '—') ?></td>
                    <td class="small">
                        <?php if ($p['discount_price']): ?>
                        <span class="text-decoration-line-through text-muted"><?= number_format($p['price']) ?>원</span><br>
                        <span class="text-danger fw-semibold"><?= number_format($p['discount_price']) ?>원</span>
                        <?php else: ?>
                        <?= number_format($p['price']) ?>원
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="stock-display small <?= $p['stock'] <= ($lowStockThreshold ?? 5) ? 'text-danger fw-semibold' : '' ?>"
                              data-id="<?= $p['id'] ?>"
                              title="클릭하여 수정"
                              style="cursor:pointer;border-bottom:1px dashed #aaa">
                            <?= number_format($p['stock']) ?>
                        </span>
                        <input type="number" min="0"
                               class="stock-input form-control form-control-sm d-none"
                               data-id="<?= $p['id'] ?>"
                               data-csrf="<?= csrf_token() ?>"
                               data-csrf-val="<?= csrf_hash() ?>"
                               value="<?= $p['stock'] ?>"
                               style="width:80px;display:inline-block">
                    </td>
                    <td>
                        <?php $statusClass = ['on_sale' => 'success', 'sold_out' => 'warning', 'hidden' => 'secondary'][$p['status']] ?? 'secondary' ?>
                        <span class="badge bg-<?= $statusClass ?>"><?= esc($statuses[$p['status']] ?? $p['status']) ?></span>
                    </td>
                    <td class="small text-muted"><?= date('Y년 n월 j일', strtotime($p['created_at'])) ?></td>
                    <td class="text-end">
                        <a href="/admin/products/<?= $p['id'] ?>/edit" class="btn btn-sm btn-outline-secondary">수정</a>
                        <form method="post" action="/admin/products/<?= $p['id'] ?>/delete" class="d-inline"
                              onsubmit="return confirm('상품을 삭제하시겠습니까?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger">삭제</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php for ($pg = 1; $pg <= $totalPages; $pg++): ?>
        <li class="page-item <?= $pg === $currentPage ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $pg ?><?= $keyword ? '&keyword='.urlencode($keyword) : '' ?><?= $curStatus ? '&status='.urlencode($curStatus) : '' ?>">
                <?= $pg ?>
            </a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<div class="text-muted small mt-2">총 <?= number_format($total) ?>개 상품</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    /* ── 인라인 재고 편집 (기존) ── */
    var threshold = <?= (int) ($lowStockThreshold ?? 5) ?>;

    document.querySelectorAll('.stock-display').forEach(function (span) {
        span.addEventListener('click', function () {
            var input = document.querySelector('.stock-input[data-id="' + span.dataset.id + '"]');
            span.classList.add('d-none');
            input.classList.remove('d-none');
            input.focus();
            input.select();
        });
    });

    function saveStock(input) {
        var span     = document.querySelector('.stock-display[data-id="' + input.dataset.id + '"]');
        var newStock = parseInt(input.value, 10);

        if (isNaN(newStock) || newStock < 0) {
            input.classList.add('d-none');
            span.classList.remove('d-none');
            return;
        }

        fetch('/admin/products/' + input.dataset.id + '/stock', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: input.dataset.csrf + '=' + encodeURIComponent(input.dataset.csrfVal) + '&stock=' + newStock
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                span.textContent = data.stock.toLocaleString();
                span.classList.toggle('text-danger', data.stock <= threshold);
                span.classList.toggle('fw-semibold', data.stock <= threshold);
                if (data.csrf_hash) input.dataset.csrfVal = data.csrf_hash;
            }
        })
        .finally(function () {
            input.classList.add('d-none');
            span.classList.remove('d-none');
        });
    }

    document.querySelectorAll('.stock-input').forEach(function (input) {
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter')  { e.preventDefault(); saveStock(input); }
            if (e.key === 'Escape') {
                input.classList.add('d-none');
                document.querySelector('.stock-display[data-id="' + input.dataset.id + '"]').classList.remove('d-none');
            }
        });
        input.addEventListener('blur', function () { saveStock(input); });
    });

    /* ── 일괄 편집 체크박스 ── */
    var checkAll = document.getElementById('checkAll');
    var bulkBar  = document.getElementById('bulkBar');
    var bulkCount = document.getElementById('bulkCount');

    function getChecked() {
        return Array.from(document.querySelectorAll('.rowCheck:checked'));
    }

    function updateBar() {
        var checked = getChecked();
        bulkCount.textContent = checked.length;
        bulkBar.classList.toggle('d-none', checked.length === 0);
        checkAll.indeterminate = checked.length > 0 && checked.length < document.querySelectorAll('.rowCheck').length;
        checkAll.checked = checked.length > 0 && checked.length === document.querySelectorAll('.rowCheck').length;
    }

    checkAll.addEventListener('change', function () {
        document.querySelectorAll('.rowCheck').forEach(function (cb) { cb.checked = checkAll.checked; });
        updateBar();
    });

    document.querySelectorAll('.rowCheck').forEach(function (cb) {
        cb.addEventListener('change', updateBar);
    });
}());

/* ── 일괄 액션 제출 ── */
function submitBulk(action) {
    var checked = Array.from(document.querySelectorAll('.rowCheck:checked'));
    if (checked.length === 0) { alert('상품을 선택해주세요.'); return; }

    if (action === 'status') {
        document.getElementById('bulkStatusVal').value = document.getElementById('bulkStatusSel').value;
    }
    if (action === 'stock') {
        var s = document.getElementById('bulkStockInput').value.trim();
        if (s === '' || parseInt(s, 10) < 0) { alert('재고 수량을 올바르게 입력해주세요.'); return; }
        document.getElementById('bulkStockVal').value = s;
    }
    if (action === 'delete') {
        if (!confirm(checked.length + '개 상품을 삭제하시겠습니까?')) return;
    }

    var form = document.getElementById('bulkForm');
    document.getElementById('bulkAction').value = action;

    // 기존 IDs 제거 후 재추가
    form.querySelectorAll('input[name="ids[]"]').forEach(function (el) { el.remove(); });
    checked.forEach(function (cb) {
        var inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = cb.value;
        form.appendChild(inp);
    });

    form.submit();
}

function clearSelection() {
    document.querySelectorAll('.rowCheck').forEach(function (cb) { cb.checked = false; });
    document.getElementById('checkAll').checked = false;
    document.getElementById('checkAll').indeterminate = false;
    document.getElementById('bulkBar').classList.add('d-none');
}
</script>
<?= $this->endSection() ?>
