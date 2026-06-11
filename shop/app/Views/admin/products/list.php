<?= $this->extend('layouts/admin') ?>
<?php $pageTitle = '상품 관리' ?>

<?= $this->section('content') ?>

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
        <!-- 재고 부족 필터 탭 -->
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

<div class="card overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
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
                <tr><td colspan="8" class="text-center text-muted py-4">등록된 상품이 없습니다.</td></tr>
                <?php endif; ?>
                <?php foreach ($items as $p): ?>
                <tr>
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
            if (e.key === 'Enter') { e.preventDefault(); saveStock(input); }
            if (e.key === 'Escape') {
                input.classList.add('d-none');
                document.querySelector('.stock-display[data-id="' + input.dataset.id + '"]').classList.remove('d-none');
            }
        });
        input.addEventListener('blur', function () { saveStock(input); });
    });
}());
</script>
<?= $this->endSection() ?>
