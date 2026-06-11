<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container py-4">

    <!-- 헤더 -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <h4 class="mb-0 fw-bold">쇼핑</h4>
        <form method="get" action="/shop" class="d-flex gap-2">
            <input type="text" name="keyword" class="form-control form-control-sm" style="width:200px"
                   placeholder="상품명 검색" value="<?= esc($keyword ?? '') ?>">
            <?php if ($curCat): ?>
            <input type="hidden" name="category_id" value="<?= $curCat ?>">
            <?php endif; ?>
            <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
    </div>

    <div class="row g-4">

        <!-- 카테고리 사이드바 -->
        <div class="col-lg-2 col-md-3">
            <div class="list-group list-group-flush">
                <a href="/shop" class="list-group-item list-group-item-action <?= ! $curCat ? 'active' : '' ?> py-2">
                    전체
                </a>
                <?php foreach ($tree as $parent): ?>
                <a href="/shop?category_id=<?= $parent['id'] ?>"
                   class="list-group-item list-group-item-action py-2 fw-semibold <?= $curCat == $parent['id'] ? 'active' : '' ?>">
                    <?= esc($parent['name']) ?>
                </a>
                <?php foreach ($parent['children'] as $child): ?>
                <a href="/shop?category_id=<?= $child['id'] ?>"
                   class="list-group-item list-group-item-action py-1 ps-4 small <?= $curCat == $child['id'] ? 'active' : '' ?>">
                    — <?= esc($child['name']) ?>
                </a>
                <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 상품 목록 -->
        <div class="col-lg-10 col-md-9">

            <!-- 정렬 -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted small">총 <?= number_format($total) ?>개 상품</span>
                <div class="d-flex gap-1">
                    <?php
                    $sorts = ['latest' => '최신순', 'price_asc' => '가격 낮은순', 'price_desc' => '가격 높은순'];
                    $safeGet = array_intersect_key($_GET, array_flip(['keyword', 'category_id']));
                    foreach ($sorts as $sortVal => $sortLabel):
                        $qs = http_build_query(array_merge($safeGet, ['sort' => $sortVal, 'page' => 1]));
                    ?>
                    <a href="/shop?<?= $qs ?>"
                       class="btn btn-sm <?= $curSort === $sortVal ? 'btn-dark' : 'btn-outline-secondary' ?>">
                        <?= $sortLabel ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (empty($items)): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-bag-x fs-1 d-block mb-2"></i>
                <?= $keyword ? '검색 결과가 없습니다.' : '등록된 상품이 없습니다.' ?>
            </div>
            <?php else: ?>

            <!-- 상품 그리드 -->
            <div class="row row-cols-2 row-cols-sm-3 row-cols-lg-4 g-3">
                <?php foreach ($items as $p):
                    $isSoldOut = $p['status'] === 'sold_out' || $p['stock'] == 0;
                    $displayPrice = $p['discount_price'] ?? $p['price'];
                    $hasDiscount  = $p['discount_price'] !== null;
                ?>
                <div class="col">
                    <a href="/shop/<?= esc($p['slug']) ?>" class="text-decoration-none text-dark">
                        <div class="card h-100 product-card">
                            <!-- 이미지 -->
                            <div class="position-relative" style="aspect-ratio:1;overflow:hidden;background:#f8f9fa">
                                <?php if ($p['primary_image']): ?>
                                <img src="<?= esc($p['primary_image']) ?>" alt="<?= esc($p['name']) ?>"
                                     style="width:100%;height:100%;object-fit:cover"
                                     loading="lazy">
                                <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                                    <i class="bi bi-image fs-1"></i>
                                </div>
                                <?php endif; ?>
                                <!-- 품절 배지 -->
                                <?php if ($isSoldOut): ?>
                                <div class="position-absolute inset-0 d-flex align-items-center justify-content-center"
                                     style="background:rgba(0,0,0,.4)">
                                    <span class="badge bg-dark fs-6">품절</span>
                                </div>
                                <?php endif; ?>
                                <!-- 할인율 배지 -->
                                <?php if ($hasDiscount && ! $isSoldOut):
                                    $rate = round((1 - $p['discount_price'] / $p['price']) * 100);
                                ?>
                                <span class="badge bg-danger position-absolute" style="top:8px;right:8px"><?= $rate ?>%</span>
                                <?php endif; ?>
                            </div>
                            <!-- 정보 -->
                            <div class="card-body p-2">
                                <div class="small text-muted mb-1"><?= esc($p['category_name'] ?? '') ?></div>
                                <div class="fw-semibold text-truncate" style="font-size:.9rem"><?= esc($p['name']) ?></div>
                                <div class="mt-1">
                                    <?php if ($hasDiscount): ?>
                                    <span class="text-muted text-decoration-line-through small"><?= number_format($p['price']) ?>원</span>
                                    <span class="text-danger fw-bold ms-1"><?= number_format($displayPrice) ?>원</span>
                                    <?php else: ?>
                                    <span class="fw-bold"><?= number_format($displayPrice) ?>원</span>
                                    <?php endif; ?>
                                </div>
                                <!-- 배송비 뱃지 -->
                                <?php if ($p['shipping_type'] === 'free'): ?>
                                <span class="badge bg-light text-success border border-success small mt-1">무료배송</span>
                                <?php elseif ($p['shipping_type'] === 'conditional'): ?>
                                <span class="badge bg-light text-secondary border small mt-1"><?= number_format((int)$p['free_threshold']) ?>원 이상 무료</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- 페이지네이션 -->
            <?php if ($totalPages > 1):
                $baseQs = array_intersect_key($_GET, array_flip(['keyword', 'category_id', 'sort']));
            ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($pg = 1; $pg <= $totalPages; $pg++): ?>
                    <li class="page-item <?= $pg === $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="/shop?<?= http_build_query(array_merge($baseQs, ['page' => $pg])) ?>">
                            <?= $pg ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?= $this->endSection() ?>
