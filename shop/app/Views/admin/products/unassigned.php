<?= $this->extend('layouts/admin') ?>
<?php $pageTitle = '미분류 상품 카테고리 배정' ?>

<?= $this->section('content') ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= esc(session()->getFlashdata('success')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= esc(session()->getFlashdata('error')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="post" action="/admin/products/assign-category" id="assignForm">
    <?= csrf_field() ?>

<div class="row g-4">
    <!-- 미분류 상품 목록 -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                <span>
                    미분류 상품
                    <span class="badge bg-secondary ms-1"><?= number_format($total) ?>개</span>
                </span>
                <a href="/admin/products" class="btn btn-sm btn-outline-secondary">← 상품 목록</a>
            </div>

            <?php if (empty($items)): ?>
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-check-circle fs-3 d-block mb-2 text-success"></i>
                미분류 상품이 없습니다.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px">
                                <input type="checkbox" id="checkAll" class="form-check-input">
                            </th>
                            <th style="width:60px"></th>
                            <th>상품명</th>
                            <th>가격</th>
                            <th>재고</th>
                            <th>상태</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $p): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="product_ids[]"
                                       value="<?= $p['id'] ?>" class="form-check-input chk-product">
                            </td>
                            <td>
                                <?php if (!empty($p['primary_image'])): ?>
                                <img src="<?= base_url($p['primary_image']) ?>" width="40" height="40"
                                     class="rounded object-fit-cover" style="object-fit:cover">
                                <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                     style="width:40px;height:40px">
                                    <i class="bi bi-image text-muted"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/admin/products/<?= $p['id'] ?>/edit" target="_blank"
                                   class="text-decoration-none text-dark">
                                    <?= esc($p['name']) ?>
                                </a>
                            </td>
                            <td class="text-nowrap"><?= number_format((int)$p['price']) ?>원</td>
                            <td><?= number_format((int)$p['stock']) ?></td>
                            <td>
                                <?php
                                $badgeMap = ['on_sale'=>'success','sold_out'=>'secondary','hidden'=>'light text-dark border'];
                                $badge = $badgeMap[$p['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $badge ?>"><?= $statuses[$p['status']] ?? $p['status'] ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="card-footer">
                <nav>
                    <ul class="pagination pagination-sm mb-0 justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- 카테고리 선택 + 적용 -->
    <div class="col-lg-4">
        <div class="card sticky-top" style="top:80px">
            <div class="card-header fw-semibold">배정할 카테고리 선택</div>
            <div class="card-body">
                <div class="alert alert-info small py-2 mb-3">
                    기존 카테고리를 유지하면서 선택한 카테고리를 <strong>추가</strong>합니다.
                </div>

                <div class="border rounded p-2 mb-3" style="max-height:280px;overflow-y:auto">
                    <?php if (empty($tree)): ?>
                    <span class="text-muted small">등록된 카테고리가 없습니다.</span>
                    <?php endif; ?>
                    <?php foreach ($tree as $parent): ?>
                    <div class="mb-1">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="category_ids[]"
                                   value="<?= $parent['id'] ?>"
                                   id="acat_<?= $parent['id'] ?>">
                            <label class="form-check-label fw-semibold" for="acat_<?= $parent['id'] ?>">
                                <?= esc($parent['name']) ?>
                            </label>
                        </div>
                        <?php foreach ($parent['children'] as $child): ?>
                        <div class="form-check ms-3">
                            <input class="form-check-input" type="checkbox"
                                   name="category_ids[]"
                                   value="<?= $child['id'] ?>"
                                   id="acat_<?= $child['id'] ?>">
                            <label class="form-check-label text-muted" for="acat_<?= $child['id'] ?>">
                                — <?= esc($child['name']) ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div id="selectionSummary" class="text-muted small mb-3">
                    선택된 상품 <strong id="selectedCount">0</strong>개
                </div>

                <button type="submit" class="btn btn-primary w-100" id="btnApply" disabled>
                    <i class="bi bi-tag me-1"></i>선택 상품에 카테고리 적용
                </button>
            </div>
        </div>
    </div>
</div>

</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const checkAll = document.getElementById('checkAll');
const products  = document.querySelectorAll('.chk-product');
const countEl   = document.getElementById('selectedCount');
const btnApply  = document.getElementById('btnApply');

function updateState() {
    const checked = document.querySelectorAll('.chk-product:checked').length;
    countEl.textContent = checked;
    btnApply.disabled   = checked === 0;
    checkAll.indeterminate = checked > 0 && checked < products.length;
    checkAll.checked       = checked === products.length && products.length > 0;
}

checkAll.addEventListener('change', function() {
    products.forEach(c => c.checked = this.checked);
    updateState();
});

products.forEach(c => c.addEventListener('change', updateState));

document.getElementById('assignForm').addEventListener('submit', function(e) {
    const cats = document.querySelectorAll('input[name="category_ids[]"]:checked').length;
    if (cats === 0) {
        e.preventDefault();
        alert('배정할 카테고리를 하나 이상 선택해주세요.');
    }
});
</script>
<?= $this->endSection() ?>
