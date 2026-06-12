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
                    <?php
                        $carriers = json_decode($s['value'] ?? '[]', true) ?: [];
                        $carriersText = implode("\n", $carriers);
                    ?>
                    <textarea name="<?= esc($s['key']) ?>" class="form-control form-control-sm" rows="5"
                              placeholder="한 줄에 업체명 하나씩 입력"><?= esc($carriersText) ?></textarea>
                    <div class="form-text">한 줄에 배송업체 하나씩 입력합니다. 주문 송장 입력 시 셀렉트박스로 표시됩니다.</div>
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
