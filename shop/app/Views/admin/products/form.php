<?= $this->extend('layouts/admin') ?>
<?php $pageTitle = $product ? '상품 수정' : '상품 등록' ?>

<?= $this->section('content') ?>

<div class="row g-4">
    <div class="col-lg-8">
        <form method="post"
              action="<?= $product ? "/admin/products/{$product['id']}/edit" : '/admin/products/create' ?>"
              enctype="multipart/form-data"
              id="productForm">
            <?= csrf_field() ?>

            <!-- 기본 정보 -->
            <div class="card mb-3">
                <div class="card-header fw-semibold">기본 정보</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">상품명 <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control"
                               value="<?= esc(old('name', $product['name'] ?? '')) ?>" required
                               oninput="autoSlug(this.value)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">슬러그 <span class="text-danger">*</span></label>
                        <input type="text" name="slug" id="slugInput" class="form-control"
                               value="<?= esc(old('slug', $product['slug'] ?? '')) ?>" required>
                        <div class="form-text">URL에 사용됩니다. 영문·숫자·한글·하이픈만 허용.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">카테고리</label>
                        <?php
                        $curCatId = old('category_id', $product['category_id'] ?? '');
                        ?>
                        <select name="category_id" class="form-select">
                            <option value="">카테고리 없음</option>
                            <?php foreach ($tree as $parent): ?>
                            <option value="<?= $parent['id'] ?>"
                                    <?= $curCatId == $parent['id'] ? 'selected' : '' ?>>
                                <?= esc($parent['name']) ?>
                            </option>
                            <?php foreach ($parent['children'] as $child): ?>
                            <option value="<?= $child['id'] ?>"
                                    <?= $curCatId == $child['id'] ? 'selected' : '' ?>>
                                &nbsp;&nbsp;— <?= esc($child['name']) ?>
                            </option>
                            <?php endforeach; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 가격 / 재고 -->
            <div class="card mb-3">
                <div class="card-header fw-semibold">가격 및 재고</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">정가 (원) <span class="text-danger">*</span></label>
                            <input type="number" name="price" class="form-control" min="0"
                                   value="<?= esc(old('price', $product['price'] ?? 0)) ?>" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">할인가 (원)</label>
                            <input type="number" name="discount_price" class="form-control" min="0"
                                   value="<?= esc(old('discount_price', $product['discount_price'] ?? '')) ?>">
                            <div class="form-text">비워두면 할인 없음</div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">재고 수량 <span class="text-danger">*</span></label>
                            <input type="number" name="stock" class="form-control" min="0"
                                   value="<?= esc(old('stock', $product['stock'] ?? 0)) ?>" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">판매 상태 <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <?php foreach ($statuses as $val => $label): ?>
                                <option value="<?= $val ?>" <?= old('status', $product['status'] ?? 'on_sale') === $val ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 배송비 -->
            <div class="card mb-3">
                <div class="card-header fw-semibold">배송비 설정</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">배송비 유형</label>
                        <select name="shipping_type" class="form-select" id="shippingType"
                                onchange="toggleShippingFields()">
                            <?php foreach ($shippings as $val => $label): ?>
                            <option value="<?= $val ?>"
                                    <?= old('shipping_type', $product['shipping_type'] ?? 'free') === $val ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="shippingFeeField" class="mb-3" style="display:none">
                        <label class="form-label">배송비 (원)</label>
                        <input type="number" name="shipping_fee" class="form-control" min="0"
                               value="<?= esc(old('shipping_fee', $product['shipping_fee'] ?? 0)) ?>">
                    </div>
                    <div id="freeThresholdField" class="mb-3" style="display:none">
                        <label class="form-label">무료배송 기준금액 (원)</label>
                        <input type="number" name="free_threshold" class="form-control" min="0"
                               value="<?= esc(old('free_threshold', $product['free_threshold'] ?? 0)) ?>">
                        <div class="form-text">이 금액 이상 구매 시 무료배송</div>
                    </div>
                </div>
            </div>

            <!-- 상품 상세 -->
            <div class="card mb-3">
                <div class="card-header fw-semibold">상품 상세 내용</div>
                <div class="card-body">
                    <textarea name="description" id="editor" class="form-control" rows="12"><?= esc(old('description', $product['description'] ?? '')) ?></textarea>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?= $product ? '저장' : '등록' ?></button>
                <a href="/admin/products" class="btn btn-outline-secondary">취소</a>
            </div>
        </form>
    </div>

    <!-- 이미지 패널 -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header fw-semibold">상품 이미지</div>
            <div class="card-body">
                <?php if (! empty($images)): ?>
                <div class="row g-2 mb-3" id="imageGrid">
                    <?php foreach ($images as $img): ?>
                    <div class="col-6" id="imgWrap_<?= $img['media_id'] ?>">
                        <div class="position-relative">
                            <img src="<?= esc($img['media_url']) ?>" alt=""
                                 style="width:100%;aspect-ratio:1;object-fit:cover;border-radius:4px;border:2px solid <?= $img['is_primary'] ? '#0d6efd' : '#dee2e6' ?>"
                                 id="imgThumb_<?= $img['media_id'] ?>">
                            <?php if ($img['is_primary']): ?>
                            <span class="badge bg-primary position-absolute" style="top:4px;left:4px">대표</span>
                            <?php endif; ?>
                            <button type="button"
                                    class="btn btn-danger btn-sm position-absolute"
                                    style="top:4px;right:4px;padding:2px 6px"
                                    onclick="deleteImage(<?= $img['id'] ?>, <?= $img['media_id'] ?>)">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="radio" name="primary_media_id"
                                   value="<?= $img['media_id'] ?>" form="productForm"
                                   <?= $img['is_primary'] ? 'checked' : '' ?>>
                            <label class="form-check-label small">대표 이미지</label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="mb-2">
                    <label class="form-label small fw-semibold">이미지 추가</label>
                    <input type="file" name="images[]" class="form-control form-control-sm"
                           accept=".jpg,.jpeg,.png,.gif,.webp" multiple form="productForm">
                    <div class="form-text">jpg, jpeg, png, gif, webp / 최대 5MB / 여러 파일 선택 가능</div>
                    <div class="form-text">첫 번째 업로드된 이미지가 대표 이미지가 됩니다.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function toggleShippingFields() {
    const type = document.getElementById('shippingType').value;
    document.getElementById('shippingFeeField').style.display    = type === 'fixed' ? '' : 'none';
    document.getElementById('freeThresholdField').style.display  = type === 'conditional' ? '' : 'none';
}

function autoSlug(name) {
    const slug = name.toLowerCase()
        .replace(/[^\w가-힣\s-]/g, '')
        .trim()
        .replace(/\s+/g, '-');
    document.getElementById('slugInput').value = slug;
}

function deleteImage(imageId, mediaId) {
    if (! confirm('이미지를 삭제하시겠습니까?')) return;
    fetch(`/admin/products/image/${imageId}/delete`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: '<?= csrf_token() ?>=<?= csrf_hash() ?>',
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('imgWrap_' + mediaId)?.remove();
        } else {
            alert(data.error ?? '삭제 실패');
        }
    });
}

toggleShippingFields();
</script>
<?= $this->endSection() ?>
