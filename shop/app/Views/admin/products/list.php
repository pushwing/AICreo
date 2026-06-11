<?= $this->extend('layouts/admin') ?>
<?php $pageTitle = '상품 관리' ?>

<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form method="get" action="/admin/products" class="d-flex gap-2 flex-wrap">
        <input type="text" name="keyword" class="form-control form-control-sm" style="width:180px"
               placeholder="상품명 검색" value="<?= esc($keyword ?? '') ?>">
        <select name="status" class="form-select form-select-sm" style="width:120px">
            <option value="">전체 상태</option>
            <?php foreach ($statuses as $val => $label): ?>
            <option value="<?= esc($val) ?>" <?= ($curStatus ?? '') === $val ? 'selected' : '' ?>><?= esc($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-sm btn-outline-secondary">검색</button>
        <?php if ($keyword || $curStatus): ?>
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

<div class="card">
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
                    <td class="small <?= $p['stock'] == 0 ? 'text-danger' : '' ?>"><?= number_format($p['stock']) ?></td>
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
