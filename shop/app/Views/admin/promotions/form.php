<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php $isEdit = $promotion !== null; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><?= $isEdit ? '기획전 수정' : '기획전 등록' ?></h4>
    <a href="/admin/promotions" class="btn btn-outline-secondary btn-sm">목록</a>
</div>

<form method="post" action="/admin/promotions/<?= $isEdit ? $promotion['id'].'/edit' : 'create' ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="products_json" id="productsJson" value="[]">

    <div class="row g-4">
        <!-- 좌측: 기본 정보 + 에디터 -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">기본 정보</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">제목 <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="titleInput" class="form-control"
                               value="<?= esc($promotion['title'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">슬러그 (URL) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text text-muted small">/promotion/</span>
                            <input type="text" name="slug" id="slugInput" class="form-control"
                                   value="<?= esc($promotion['slug'] ?? '') ?>" required
                                   pattern="[a-z0-9\-]+" title="영문 소문자·숫자·하이픈만 사용 가능">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnGenSlug">자동생성</button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">배너 이미지</label>
                        <?php if (! empty($promotion['banner_image'])): ?>
                        <div class="mb-2">
                            <img src="<?= base_url(esc($promotion['banner_image'])) ?>" alt="배너 이미지"
                                 class="img-thumbnail" style="max-height:100px">
                        </div>
                        <?php endif; ?>
                        <input type="file" name="banner_image_file" class="form-control form-control-sm"
                               accept="image/jpeg,image/png,image/gif">
                        <div class="form-text">jpg, png, gif / 최대 2MB<?= ! empty($promotion['banner_image']) ? ' (새 파일 선택 시 교체)' : '' ?></div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">기획전 설명</div>
                <div class="card-body">
                    <textarea name="description" id="editor" class="form-control" rows="12"><?= $isEdit ? $promotion['description'] : '' ?></textarea>
                </div>
            </div>
        </div>

        <!-- 우측: 설정 -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">노출 설정</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">상태</label>
                        <select name="is_active" class="form-select form-select-sm">
                            <option value="1" <?= ($promotion['is_active'] ?? 1) == 1 ? 'selected' : '' ?>>활성</option>
                            <option value="0" <?= ($promotion['is_active'] ?? 1) == 0 ? 'selected' : '' ?>>비활성</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">접근 가능 등급</label>
                        <select name="grade_access" class="form-select form-select-sm">
                            <?php
                            $gradeOpts = ['all' => '전체 공개', 'bronze' => 'Bronze 이상', 'silver' => 'Silver 이상', 'gold' => 'Gold 이상', 'platinum' => 'Platinum 전용'];
                            foreach ($gradeOpts as $val => $label):
                                $sel = ($promotion['grade_access'] ?? 'all') === $val ? 'selected' : '';
                            ?>
                            <option value="<?= $val ?>" <?= $sel ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">시작일</label>
                        <input type="date" name="start_date" class="form-control form-control-sm"
                               value="<?= esc($promotion['start_date'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">종료일</label>
                        <input type="date" name="end_date" class="form-control form-control-sm"
                               value="<?= esc($promotion['end_date'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">정렬 순서</label>
                        <input type="number" name="sort_order" class="form-control form-control-sm"
                               value="<?= (int) ($promotion['sort_order'] ?? 0) ?>">
                    </div>
                </div>
            </div>

            <!-- 상품 구성 -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">상품 구성</div>
                <div class="card-body p-2">
                    <!-- 검색 -->
                    <div class="input-group input-group-sm mb-2">
                        <input type="text" id="prodSearchQ" class="form-control" placeholder="상품명 검색">
                        <select id="prodSearchCat" class="form-select" style="max-width:120px">
                            <option value="">전체 카테고리</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= esc($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-outline-secondary" id="btnProdSearch">검색</button>
                    </div>

                    <!-- 검색 결과 -->
                    <div id="searchResults" class="mb-2" style="max-height:220px;overflow-y:auto;display:none">
                        <table class="table table-sm table-hover mb-0 small align-middle">
                            <tbody id="searchResultBody"></tbody>
                        </table>
                    </div>

                    <!-- 선택된 상품 -->
                    <div class="small fw-semibold text-muted mb-1">선택된 상품</div>
                    <div id="selectedProducts" style="max-height:260px;overflow-y:auto">
                        <table class="table table-sm mb-0 small">
                            <tbody id="selectedBody">
                                <?php foreach ($products as $i => $prod): ?>
                                <tr data-id="<?= $prod['id'] ?>">
                                    <td class="text-truncate" style="max-width:120px"><?= esc($prod['name']) ?></td>
                                    <td style="width:50px">
                                        <input type="number" class="form-control form-control-sm sort-input"
                                               value="<?= (int) $prod['sort_order'] ?>" style="width:50px">
                                    </td>
                                    <td style="width:30px">
                                        <button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove">✕</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><?= $isEdit ? '저장' : '등록' ?></button>
        <a href="/admin/promotions" class="btn btn-outline-secondary">취소</a>
    </div>
</form>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.tiny.cloud/1/<?= config('Editor')->tinymceApiKey ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
tinymce.init({
    selector: '#editor',
    language: 'ko_KR',
    height: 400,
    plugins: 'lists link image table code',
    toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image | table | code',
    images_upload_url: '/admin/media/upload',
    automatic_uploads: true,
    file_picker_types: 'image',
    convert_urls: false,
    relative_urls: false,
    document_base_url: '<?= base_url('/') ?>',
});

// ── 슬러그 자동생성 ────────────────────────────────────────────────────────────
function koreanToRoman(str) {
    const cho  = ['g','kk','n','d','tt','r','m','b','pp','s','ss','','j','jj','ch','k','t','p','h'];
    const jung = ['a','ae','ya','yae','eo','e','yeo','ye','o','wa','wae','oe','yo','u','wo','we','wi','yu','eu','ui','i'];
    const jong = ['','k','k','k','n','n','n','t','l','k','m','p','l','l','p','l','m','p','p','t','t','ng','t','t','k','t','p','t'];
    let result = '';
    for (let i = 0; i < str.length; i++) {
        const code = str.charCodeAt(i);
        if (code >= 0xAC00 && code <= 0xD7A3) {
            const offset  = code - 0xAC00;
            const choIdx  = Math.floor(offset / (21 * 28));
            const jungIdx = Math.floor((offset % (21 * 28)) / 28);
            const jongIdx = offset % 28;
            result += cho[choIdx] + jung[jungIdx] + jong[jongIdx];
        } else {
            result += str[i];
        }
    }
    return result;
}

document.getElementById('btnGenSlug').addEventListener('click', function () {
    const title = koreanToRoman(document.getElementById('titleInput').value.trim());
    const slug  = title
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-+|-+$/g, '')
        .substring(0, 100);
    document.getElementById('slugInput').value = slug;
});

// ── 상품 선택 ──────────────────────────────────────────────────────────────────
const selectedMap = {};  // product_id → {name, sort_order}

<?php foreach ($products as $i => $prod): ?>
selectedMap[<?= $prod['id'] ?>] = { name: <?= json_encode($prod['name']) ?>, sort_order: <?= (int) $prod['sort_order'] ?> };
<?php endforeach; ?>

function renderSelected() {
    const tbody = document.getElementById('selectedBody');
    tbody.innerHTML = '';
    let order = 0;
    for (const [id, item] of Object.entries(selectedMap)) {
        const tr = document.createElement('tr');
        tr.dataset.id = id;
        tr.innerHTML = `
            <td class="text-truncate" style="max-width:120px" title="${escHtml(item.name)}">${escHtml(item.name)}</td>
            <td style="width:55px"><input type="number" class="form-control form-control-sm sort-input" value="${item.sort_order}" style="width:55px"></td>
            <td style="width:30px"><button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove">✕</button></td>`;
        tbody.appendChild(tr);
        order++;
    }
    syncJson();

    tbody.querySelectorAll('.sort-input').forEach(input => {
        input.addEventListener('change', function () {
            const id = this.closest('tr').dataset.id;
            if (selectedMap[id]) selectedMap[id].sort_order = parseInt(this.value) || 0;
            syncJson();
        });
    });
    tbody.querySelectorAll('.btn-remove').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.closest('tr').dataset.id;
            delete selectedMap[id];
            renderSelected();
        });
    });
}

function syncJson() {
    const arr = Object.entries(selectedMap).map(([id, item]) => ({
        product_id: parseInt(id), sort_order: item.sort_order,
    }));
    document.getElementById('productsJson').value = JSON.stringify(arr);
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

renderSelected();

document.getElementById('btnProdSearch').addEventListener('click', function () {
    const q   = document.getElementById('prodSearchQ').value.trim();
    const cat = document.getElementById('prodSearchCat').value;
    fetch('/admin/promotions/product-search?q=' + encodeURIComponent(q) + '&category_id=' + encodeURIComponent(cat))
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('searchResultBody');
            tbody.innerHTML = '';
            (data.products || []).forEach(p => {
                const tr = document.createElement('tr');
                const thumb = p.primary_image
                    ? `<img src="${escHtml(p.primary_image)}" style="width:36px;height:36px;object-fit:cover;border-radius:4px">`
                    : `<span class="text-muted" style="display:inline-block;width:36px;height:36px;background:#f0f0f0;border-radius:4px;line-height:36px;text-align:center">-</span>`;
                tr.innerHTML = `<td style="width:44px">${thumb}</td>
                    <td class="text-truncate" style="max-width:120px">${escHtml(p.name)}</td>
                    <td class="text-end text-muted">${Number(p.price).toLocaleString()}원</td>
                    <td><button type="button" class="btn btn-sm btn-link p-0 text-primary btn-add" data-id="${p.id}" data-name="${escHtml(p.name)}">추가</button></td>`;
                tbody.appendChild(tr);
            });
            document.getElementById('searchResults').style.display = data.products.length ? '' : 'none';

            tbody.querySelectorAll('.btn-add').forEach(btn => {
                btn.addEventListener('click', function () {
                    const id   = this.dataset.id;
                    const name = this.dataset.name;
                    if (! selectedMap[id]) {
                        selectedMap[id] = { name, sort_order: Object.keys(selectedMap).length };
                        renderSelected();
                    }
                });
            });
        });
});
document.getElementById('prodSearchQ').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); document.getElementById('btnProdSearch').click(); }
});
</script>
<?= $this->endSection() ?>
