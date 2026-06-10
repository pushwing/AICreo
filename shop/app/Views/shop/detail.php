<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$isSoldOut    = $product['status'] === 'sold_out' || $product['stock'] === 0;
$hasDiscount  = $product['discount_price'] !== null;
$displayPrice = $hasDiscount ? $product['discount_price'] : $product['price'];
$discountRate = $hasDiscount ? round((1 - $product['discount_price'] / $product['price']) * 100) : 0;
$primaryImage = null;
$extraImages  = [];
foreach ($images as $img) {
    if ($img['is_primary']) $primaryImage = $img;
    else $extraImages[] = $img;
}
if (! $primaryImage && ! empty($images)) {
    $primaryImage = array_shift($images);
}
$allImages = $primaryImage ? array_merge([$primaryImage], $extraImages) : [];
?>

<div class="container py-5">

    <!-- 상단: 이미지 + 구매 정보 -->
    <div class="row g-5 mb-5">

        <!-- 이미지 영역 -->
        <div class="col-lg-6">
            <?php if (! empty($allImages)): ?>

            <!-- 메인 Carousel -->
            <div id="productCarousel" class="carousel slide mb-3" data-bs-ride="false">
                <div class="carousel-inner rounded" style="background:#f8f9fa">
                    <?php foreach ($allImages as $i => $img): ?>
                    <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                        <img src="<?= esc($img['media_url']) ?>"
                             alt="<?= esc($img['alt'] ?? $product['name']) ?>"
                             class="d-block w-100"
                             style="aspect-ratio:1;object-fit:cover">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($allImages) > 1): ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
                <?php endif; ?>
            </div>

            <!-- 썸네일 목록 -->
            <?php if (count($allImages) > 1): ?>
            <div class="d-flex gap-2 flex-wrap">
                <?php foreach ($allImages as $i => $img): ?>
                <button type="button"
                        class="thumb-btn border rounded p-0 <?= $i === 0 ? 'border-dark border-2' : 'border-secondary' ?>"
                        style="width:64px;height:64px;overflow:hidden;background:none"
                        data-bs-target="#productCarousel"
                        data-bs-slide-to="<?= $i ?>"
                        onclick="setActiveThumb(this)">
                    <img src="<?= esc($img['media_url']) ?>"
                         alt=""
                         style="width:100%;height:100%;object-fit:cover">
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="rounded d-flex align-items-center justify-content-center text-muted"
                 style="aspect-ratio:1;background:#f1f3f5">
                <i class="bi bi-image fs-1"></i>
            </div>
            <?php endif; ?>
        </div>

        <!-- 구매 정보 영역 -->
        <div class="col-lg-6">

            <!-- 카테고리 브레드크럼 -->
            <?php if (! empty($product['category_name'])): ?>
            <div class="text-muted small mb-2"><?= esc($product['category_name']) ?></div>
            <?php endif; ?>

            <!-- 상품명 -->
            <h2 class="fw-bold mb-3"><?= esc($product['name']) ?></h2>

            <!-- 가격 -->
            <div class="mb-4">
                <?php if ($hasDiscount): ?>
                <div class="text-muted text-decoration-line-through mb-1">
                    <?= number_format($product['price']) ?>원
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="fs-3 fw-bold text-danger"><?= number_format($displayPrice) ?>원</span>
                    <span class="badge bg-danger fs-6"><?= $discountRate ?>% 할인</span>
                </div>
                <?php else: ?>
                <span class="fs-3 fw-bold"><?= number_format($displayPrice) ?>원</span>
                <?php endif; ?>
            </div>

            <!-- 배송비 -->
            <div class="mb-4 pb-4 border-bottom">
                <div class="d-flex gap-2 align-items-center small">
                    <span class="text-muted" style="width:70px">배송비</span>
                    <?php if ($product['shipping_type'] === 'free'): ?>
                    <span class="text-success fw-semibold">무료배송</span>
                    <?php elseif ($product['shipping_type'] === 'fixed'): ?>
                    <span><?= number_format($product['shipping_fee']) ?>원</span>
                    <?php else: ?>
                    <span>
                        <?= number_format($product['free_threshold']) ?>원 이상 무료 /
                        미만 <?= number_format($product['shipping_fee']) ?>원
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 재고 / 품절 표시 -->
            <?php if ($isSoldOut): ?>
            <div class="alert alert-secondary py-2 mb-4">
                <i class="bi bi-exclamation-circle me-1"></i>현재 품절된 상품입니다.
            </div>
            <?php else: ?>
            <div class="text-muted small mb-4">재고 <strong class="text-dark"><?= number_format($product['stock']) ?></strong>개</div>
            <?php endif; ?>

            <!-- 수량 선택 -->
            <div class="d-flex align-items-center gap-3 mb-4">
                <span class="text-muted small" style="width:70px">수량</span>
                <div class="input-group" style="width:130px">
                    <button class="btn btn-outline-secondary" type="button" id="qtyMinus" <?= $isSoldOut ? 'disabled' : '' ?>>−</button>
                    <input type="number" id="qtyInput" class="form-control text-center"
                           value="1" min="1" max="<?= (int) $product['stock'] ?>"
                           <?= $isSoldOut ? 'disabled' : '' ?>>
                    <button class="btn btn-outline-secondary" type="button" id="qtyPlus" <?= $isSoldOut ? 'disabled' : '' ?>>+</button>
                </div>
            </div>

            <!-- 총 금액 -->
            <?php if (! $isSoldOut): ?>
            <div class="d-flex align-items-center gap-2 mb-4">
                <span class="text-muted small" style="width:70px">합계</span>
                <span class="fs-5 fw-bold" id="totalPrice"><?= number_format($displayPrice) ?>원</span>
            </div>
            <?php endif; ?>

            <!-- 구매 버튼 -->
            <div class="d-grid gap-2">
                <?php if ($isSoldOut): ?>
                <button class="btn btn-secondary btn-lg" disabled>품절</button>
                <?php else: ?>
                <button class="btn btn-primary btn-lg" disabled title="장바구니 기능 준비 중">
                    <i class="bi bi-bag-plus me-1"></i>장바구니 담기
                </button>
                <button class="btn btn-dark btn-lg" disabled title="바로구매 기능 준비 중">
                    바로구매
                </button>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- 하단: 상세정보 탭 -->
    <div class="row">
        <div class="col-12">
            <ul class="nav nav-tabs mb-0" id="detailTabs">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabDesc">상세정보</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabShipping">배송·교환·반품</button>
                </li>
            </ul>
            <div class="tab-content border border-top-0 rounded-bottom p-4">
                <div class="tab-pane fade show active" id="tabDesc">
                    <?php if (! empty($product['description'])): ?>
                    <div class="product-desc">
                        <?= $product['description'] ?>
                    </div>
                    <?php else: ?>
                    <p class="text-muted">상세 내용이 없습니다.</p>
                    <?php endif; ?>
                </div>
                <div class="tab-pane fade" id="tabShipping">
                    <?php if (! empty($shipping_policy)): ?>
                    <div style="white-space:pre-line"><?= esc($shipping_policy) ?></div>
                    <?php else: ?>
                    <p class="text-muted">배송·교환·반품 안내가 없습니다.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<style>
.thumb-btn { cursor: pointer; transition: border-color .15s; }
.thumb-btn:hover { border-color: #343a40 !important; }
.product-desc img { max-width: 100%; height: auto; }
</style>
<script>
(function () {
    const unitPrice = <?= (int) $displayPrice ?>;
    const maxQty    = <?= (int) $product['stock'] ?>;
    const qtyInput  = document.getElementById('qtyInput');
    const totalEl   = document.getElementById('totalPrice');

    function updateTotal() {
        if (! qtyInput || ! totalEl) return;
        const qty = Math.max(1, Math.min(parseInt(qtyInput.value) || 1, maxQty));
        qtyInput.value = qty;
        totalEl.textContent = (unitPrice * qty).toLocaleString('ko-KR') + '원';
    }

    document.getElementById('qtyMinus')?.addEventListener('click', function () {
        qtyInput.value = Math.max(1, parseInt(qtyInput.value) - 1);
        updateTotal();
    });
    document.getElementById('qtyPlus')?.addEventListener('click', function () {
        qtyInput.value = Math.min(maxQty, parseInt(qtyInput.value) + 1);
        updateTotal();
    });
    qtyInput?.addEventListener('input', updateTotal);
})();

function setActiveThumb(btn) {
    document.querySelectorAll('.thumb-btn').forEach(function (b) {
        b.classList.remove('border-dark', 'border-2');
        b.classList.add('border-secondary');
    });
    btn.classList.remove('border-secondary');
    btn.classList.add('border-dark', 'border-2');
}

// 캐러셀 슬라이드 전환 시 썸네일 동기화
document.getElementById('productCarousel')?.addEventListener('slide.bs.carousel', function (e) {
    const thumbs = document.querySelectorAll('.thumb-btn');
    thumbs.forEach(function (b, i) {
        b.classList.toggle('border-dark', i === e.to);
        b.classList.toggle('border-2',    i === e.to);
        b.classList.toggle('border-secondary', i !== e.to);
    });
});
</script>
<?= $this->endSection() ?>
