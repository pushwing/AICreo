<?= $this->extend('layouts/admin') ?>
<?php $pageTitle = '사이트 설정' ?>
<?= $this->section('content') ?>

<!-- 탭 -->
<ul class="nav nav-tabs mb-4">
    <?php foreach (['general' => '기본', 'contact' => '연락처', 'sns' => 'SNS', 'seo' => 'SEO', 'footer' => '푸터', 'shop' => '쇼핑', 'grade' => '등급/포인트'] as $g => $label): ?>
    <li class="nav-item">
        <a class="nav-link <?= $group === $g ? 'active' : '' ?>" href="/admin/settings/<?= $g ?>"><?= $label ?></a>
    </li>
    <?php endforeach; ?>
    <li class="nav-item">
        <a class="nav-link" href="/admin/settings/pg">결제수단</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="/admin/settings/oauth">소셜 로그인</a>
    </li>
</ul>

<div class="card border-0 shadow-sm" style="max-width:600px">
    <div class="card-body p-4">
        <form method="post" action="/admin/settings/<?= esc($group) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <?php foreach ($settings as $s): ?>
            <div class="mb-3">
                <label class="form-label small fw-semibold"><?= esc($s['label']) ?></label>
                <?php if ($s['type'] === 'textarea'): ?>
                    <textarea name="<?= esc($s['key']) ?>" class="form-control form-control-sm" rows="3"><?= esc($s['value']) ?></textarea>
                <?php elseif ($s['type'] === 'carriers'): ?>
                    <?php $carriers = json_decode($s['value'] ?? '[]', true) ?: []; ?>
                    <div class="carriers-editor" data-key="<?= esc($s['key']) ?>">
                        <div class="carriers-chips d-flex flex-wrap gap-1 mb-2 p-2 border rounded bg-white" style="min-height:42px">
                            <?php foreach ($carriers as $c): ?>
                            <span class="badge bg-secondary d-flex align-items-center gap-1 fs-6 fw-normal px-2 py-1">
                                <?= esc($c) ?>
                                <input type="hidden" name="<?= esc($s['key']) ?>[]" value="<?= esc($c) ?>">
                                <button type="button" class="btn-close btn-close-white ms-1" style="font-size:.6rem" aria-label="삭제"></button>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <div class="input-group input-group-sm" style="max-width:300px">
                            <input type="text" class="form-control carrier-input" placeholder="배송업체명 입력">
                            <button type="button" class="btn btn-outline-secondary carrier-add-btn">추가</button>
                        </div>
                    </div>
                    <div class="form-text">배송업체를 추가하면 주문 송장 입력 시 셀렉트박스에 표시됩니다.</div>
                <?php elseif ($s['type'] === 'image'): ?>
                    <?php if ($s['value']): ?>
                        <div class="mb-1"><img src="/<?= esc($s['value']) ?>" style="max-height:60px" class="img-thumbnail"></div>
                    <?php endif; ?>
                    <input type="text" name="<?= esc($s['key']) ?>" class="form-control form-control-sm"
                           value="<?= esc($s['value']) ?>" placeholder="uploads/media/... 경로 입력">
                    <div class="form-text">미디어 라이브러리에서 이미지 경로를 복사하세요.</div>
                <?php else: ?>
                    <input type="text" name="<?= esc($s['key']) ?>" class="form-control form-control-sm" value="<?= esc($s['value']) ?>">
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <div class="text-end">
                <button type="submit" class="btn btn-primary btn-sm px-4">저장</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    document.querySelectorAll('.carriers-editor').forEach(function (editor) {
        var key      = editor.dataset.key;
        var chips    = editor.querySelector('.carriers-chips');
        var input    = editor.querySelector('.carrier-input');
        var addBtn   = editor.querySelector('.carrier-add-btn');

        function makeChip(name) {
            name = name.trim();
            if (! name) return;

            // 중복 방지
            var exists = Array.from(chips.querySelectorAll('input[type=hidden]'))
                .some(function (h) { return h.value === name; });
            if (exists) { input.value = ''; input.focus(); return; }

            var span = document.createElement('span');
            span.className = 'badge bg-secondary d-flex align-items-center gap-1 fs-6 fw-normal px-2 py-1';

            var text = document.createTextNode(name + ' ');
            span.appendChild(text);

            var hidden = document.createElement('input');
            hidden.type  = 'hidden';
            hidden.name  = key + '[]';
            hidden.value = name;
            span.appendChild(hidden);

            var closeBtn = document.createElement('button');
            closeBtn.type      = 'button';
            closeBtn.className = 'btn-close btn-close-white ms-1';
            closeBtn.style.fontSize = '.6rem';
            closeBtn.setAttribute('aria-label', '삭제');
            closeBtn.addEventListener('click', function () { span.remove(); });
            span.appendChild(closeBtn);

            chips.appendChild(span);
            input.value = '';
            input.focus();
        }

        addBtn.addEventListener('click', function () { makeChip(input.value); });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); makeChip(input.value); }
        });

        // 기존 칩 삭제 버튼 이벤트
        chips.querySelectorAll('.btn-close').forEach(function (btn) {
            btn.addEventListener('click', function () { btn.closest('span').remove(); });
        });
    });
}());
</script>
<?= $this->endSection() ?>
