<?= $this->extend('layouts/admin') ?>
<?php $pageTitle = '테마 관리' ?>
<?= $this->section('content') ?>

<!-- 탭 -->
<ul class="nav nav-tabs mb-4">
    <?php foreach (['general' => '기본', 'contact' => '연락처', 'sns' => 'SNS', 'seo' => 'SEO', 'footer' => '푸터', 'theme' => '테마'] as $g => $label): ?>
    <li class="nav-item">
        <a class="nav-link <?= $group === $g ? 'active' : '' ?>" href="/admin/settings/<?= $g ?>"><?= $label ?></a>
    </li>
    <?php endforeach; ?>
    <li class="nav-item">
        <a class="nav-link" href="/admin/settings/oauth">소셜 로그인</a>
    </li>
</ul>

<div style="max-width:720px">

    <!-- 업로드 카드 -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <h6 class="fw-semibold mb-1"><i class="bi bi-upload me-2"></i>테마 업로드</h6>
            <p class="text-muted small mb-3">
                ZIP 파일로 패키징된 테마를 업로드합니다.
                파일명이 테마 이름이 됩니다 (예: <code>my-theme.zip</code> → <code>my-theme</code>).
            </p>

            <form method="post" action="/admin/settings/theme/upload" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="d-flex gap-2 align-items-start">
                    <div class="flex-grow-1">
                        <input type="file" name="theme_zip" class="form-control form-control-sm" accept=".zip" required>
                        <div class="form-text">
                            필수: <code>views/layouts/main.php</code> · <code>public/css/style.css</code>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary text-nowrap">업로드 · 설치</button>
                </div>
            </form>

            <!-- ZIP 구조 안내 (접기/펼치기) -->
            <div class="mt-3">
                <a class="small text-muted" data-bs-toggle="collapse" href="#zipStructure" role="button">
                    <i class="bi bi-info-circle me-1"></i>ZIP 구조 보기
                </a>
                <div class="collapse mt-2" id="zipStructure">
                    <pre class="bg-light rounded p-3 small mb-0">my-theme.zip
├── views/                       ← app/Views/themes/my-theme/ 로 복사
│   ├── layouts/
│   │   └── main.php             ★ 필수
│   └── components/
│       ├── navbar.php
│       ├── footer.php
│       └── contact_form.php
└── public/                      ← public/themes/my-theme/ 로 복사
    ├── css/
    │   └── style.css            ★ 필수
    ├── js/
    │   └── main.js
    └── preview.png              (관리자 미리보기 이미지)</pre>
                </div>
            </div>
        </div>
    </div>

    <p class="text-muted small mb-3">설치된 테마 목록 — 직접 폴더를 추가해도 자동으로 표시됩니다.</p>

    <div class="row g-3">
        <?php foreach ($availableThemes as $theme): ?>
        <div class="col-sm-6">
            <div class="card border-2 <?= $activeTheme === $theme['name'] ? 'border-primary' : 'border-light' ?> shadow-sm h-100">
                <!-- 미리보기 영역 -->
                <div class="card-img-top d-flex align-items-center justify-content-center bg-light"
                     style="height:120px; overflow:hidden; border-bottom:1px solid #e9ecef">
                    <?php
                    $preview = FCPATH . "themes/{$theme['name']}/preview.png";
                    if (is_file($preview)): ?>
                        <img src="/themes/<?= esc($theme['name']) ?>/preview.png"
                             style="width:100%;height:120px;object-fit:cover" alt="">
                    <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="bi bi-palette fs-2"></i>
                            <div class="small mt-1"><?= esc($theme['label']) ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <strong class="fs-6"><?= esc($theme['label']) ?></strong>
                        <?php if ($activeTheme === $theme['name']): ?>
                            <span class="badge bg-primary">적용 중</span>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex gap-2 mb-3">
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-file-earmark-code me-1"></i>레이아웃
                            <?= $theme['has_layout'] ? '<i class="bi bi-check text-success"></i>' : '<i class="bi bi-x text-muted"></i>' ?>
                        </span>
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-palette2 me-1"></i>CSS
                            <?= $theme['has_css'] ? '<i class="bi bi-check text-success"></i>' : '<i class="bi bi-x text-muted"></i>' ?>
                        </span>
                    </div>

                    <?php if ($activeTheme !== $theme['name']): ?>
                    <form method="post" action="/admin/settings/theme">
                        <?= csrf_field() ?>
                        <input type="hidden" name="active_theme" value="<?= esc($theme['name']) ?>">
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">이 테마 적용</button>
                    </form>
                    <?php else: ?>
                        <button class="btn btn-sm btn-primary w-100" disabled>현재 테마</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($availableThemes)): ?>
        <div class="col-12">
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <code>app/Views/themes/</code> 폴더에 테마가 없습니다.
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
