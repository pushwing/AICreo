<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">리뷰 관리</h4>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success py-2"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>

<!-- 검색 -->
<form class="row g-2 mb-3" method="get">
    <div class="col-auto">
        <input type="text" name="q" class="form-control form-control-sm"
               placeholder="리뷰내용·닉네임·상품명" value="<?= esc($keyword ?? '') ?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-outline-secondary">검색</button>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-sm table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th style="width:60px">번호</th>
                <th>상품</th>
                <th>작성자</th>
                <th>리뷰 내용</th>
                <th style="width:80px">이미지</th>
                <th style="width:90px">포인트</th>
                <th style="width:100px">작성일</th>
                <th style="width:80px">관리</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($items)): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">등록된 리뷰가 없습니다.</td></tr>
        <?php else: ?>
        <?php foreach ($items as $review): ?>
        <tr>
            <td><?= (int) $review['id'] ?></td>
            <td>
                <a href="/shop/<?= esc($review['product_slug']) ?>" target="_blank" class="text-decoration-none small">
                    <?= esc(mb_strimwidth($review['product_name'], 0, 30, '…')) ?>
                </a>
            </td>
            <td class="small"><?= esc($review['nickname'] ?: $review['username']) ?></td>
            <td class="small"><?= esc(mb_strimwidth($review['content'], 0, 60, '…')) ?></td>
            <td class="small">
                <?php if (! empty($review['images'])): ?>
                <div class="d-flex gap-1 flex-wrap">
                    <?php foreach ($review['images'] as $img): ?>
                    <a href="<?= esc($img['image_path']) ?>" target="_blank">
                        <img src="<?= esc($img['image_path']) ?>" alt=""
                             style="width:36px;height:36px;object-fit:cover;border-radius:3px">
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <span class="text-muted">-</span>
                <?php endif; ?>
            </td>
            <td class="small text-center">
                <?= $review['is_rewarded'] ? '<span class="badge bg-warning text-dark">150P 지급</span>' : '-' ?>
            </td>
            <td class="small text-muted"><?= date('Y.m.d', strtotime($review['created_at'])) ?></td>
            <td>
                <form method="post" action="/admin/reviews/<?= (int) $review['id'] ?>/delete"
                      onsubmit="return confirm('리뷰를 삭제하시겠습니까? 지급된 포인트도 회수됩니다.')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger">삭제</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$totalPages = (int) ceil(($total ?? 0) / max(1, ($perPage ?? 20)));
if ($totalPages > 1):
    $q = esc($keyword ?? '');
?>
<nav>
    <ul class="pagination pagination-sm justify-content-center">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <li class="page-item <?= $p === ($page ?? 1) ? 'active' : '' ?>">
            <a class="page-link" href="?q=<?= $q ?>&page=<?= $p ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?= $this->endSection() ?>
