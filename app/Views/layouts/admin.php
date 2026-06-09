<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle ?? '관리자') ?> | <?= esc($settings['site_name'] ?? 'Admin') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --sidebar-w: 220px; }
        body  { background: #f1f3f5; }
        #sidebar {
            width: var(--sidebar-w); min-height: 100vh;
            background: #1e2a38; position: fixed; top: 0; left: 0;
        }
        #sidebar .brand { padding: 1rem 1.2rem; color: #fff; font-weight: 700; font-size: 1.1rem; border-bottom: 1px solid #2d3f54; }
        #sidebar .nav-link { color: #a8b7c7; padding: .5rem 1.2rem; font-size: .875rem; }
        #sidebar .nav-link:hover,
        #sidebar .nav-link.active { color: #fff; background: #2d3f54; }
        #sidebar .nav-section { color: #5a7080; font-size: .7rem; padding: .8rem 1.2rem .2rem; letter-spacing: .06em; text-transform: uppercase; }
        #content { margin-left: var(--sidebar-w); padding: 1.5rem; }
        #topbar  { background: #fff; border-bottom: 1px solid #e9ecef; padding: .6rem 1.5rem; margin-left: var(--sidebar-w); position: sticky; top: 0; z-index: 100; }
        .badge-dot { width: 8px; height: 8px; border-radius: 50%; background: #dc3545; display: inline-block; }
    </style>
</head>
<body>

<div id="sidebar">
    <div class="brand"><i class="bi bi-grid-3x3-gap-fill me-2"></i><?= esc($settings['site_name'] ?? 'Admin') ?></div>
    <nav class="mt-2">
        <div class="nav-section">콘텐츠</div>
        <a href="/admin/dashboard" class="nav-link <?= uri_string() === 'admin/dashboard' || uri_string() === 'admin' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2 me-2"></i>대시보드
        </a>
        <a href="/admin/pages" class="nav-link <?= str_starts_with(uri_string(), 'admin/pages') ? 'active' : '' ?>">
            <i class="bi bi-file-text me-2"></i>페이지 관리
        </a>
        <a href="/admin/boards" class="nav-link <?= str_starts_with(uri_string(), 'admin/boards') ? 'active' : '' ?>">
            <i class="bi bi-card-list me-2"></i>게시판 관리
        </a>
        <a href="/admin/posts" class="nav-link <?= str_starts_with(uri_string(), 'admin/posts') ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-text me-2"></i>전체 게시물
        </a>
        <a href="/admin/media" class="nav-link <?= str_starts_with(uri_string(), 'admin/media') ? 'active' : '' ?>">
            <i class="bi bi-images me-2"></i>미디어
        </a>

        <a href="/admin/banners" class="nav-link <?= str_starts_with(uri_string(), 'admin/banners') ? 'active' : '' ?>">
            <i class="bi bi-image me-2"></i>배너 관리
        </a>
        <a href="/admin/popups" class="nav-link <?= str_starts_with(uri_string(), 'admin/popups') ? 'active' : '' ?>">
            <i class="bi bi-window me-2"></i>팝업 관리
        </a>

        <div class="nav-section">운영</div>
        <a href="/admin/users" class="nav-link <?= str_starts_with(uri_string(), 'admin/users') ? 'active' : '' ?>">
            <i class="bi bi-person-lines-fill me-2"></i>회원 관리
        </a>
        <a href="/admin/inquiries" class="nav-link <?= str_starts_with(uri_string(), 'admin/inquiries') ? 'active' : '' ?>">
            <i class="bi bi-envelope me-2"></i>문의 수신함
            <?php if ($unreadInquiries > 0): ?>
            <span class="badge bg-danger ms-1"><?= $unreadInquiries ?></span>
            <?php endif; ?>
        </a>
        <a href="/admin/menus" class="nav-link <?= str_starts_with(uri_string(), 'admin/menus') ? 'active' : '' ?>">
            <i class="bi bi-list-ul me-2"></i>메뉴 관리
        </a>

        <div class="nav-section">설정</div>
        <a href="/admin/settings/general" class="nav-link <?= uri_string() !== 'admin/settings/oauth' && uri_string() !== 'admin/settings/theme' && !str_starts_with(uri_string(), 'admin/settings/theme') && str_starts_with(uri_string(), 'admin/settings') ? 'active' : '' ?>">
            <i class="bi bi-gear me-2"></i>사이트 설정
        </a>
        <a href="/admin/settings/theme" class="nav-link <?= str_starts_with(uri_string(), 'admin/settings/theme') ? 'active' : '' ?>">
            <i class="bi bi-palette me-2"></i>테마 관리
        </a>
        <a href="/admin/settings/oauth" class="nav-link <?= uri_string() === 'admin/settings/oauth' ? 'active' : '' ?>">
            <i class="bi bi-people me-2"></i>소셜 로그인
        </a>

        <div class="nav-section mt-3">사이트</div>
        <a href="/" target="_blank" class="nav-link"><i class="bi bi-box-arrow-up-right me-2"></i>사이트 보기</a>
        <a href="/auth/logout" class="nav-link text-danger"><i class="bi bi-box-arrow-right me-2"></i>로그아웃</a>
    </nav>
</div>

<div id="topbar" class="d-flex align-items-center justify-content-between">
    <strong class="text-dark"><?= esc($pageTitle ?? '') ?></strong>
    <span class="text-muted small"><i class="bi bi-person-circle me-1"></i><?= esc($authUser['nickname']) ?></span>
</div>

<div id="content">
    <?php foreach (['success', 'error'] as $type): ?>
        <?php if (session()->has($type)): ?>
        <div class="alert alert-<?= $type === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
            <?= esc(session($type)) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>
    <?php if (session()->has('errors')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?php foreach (session('errors') as $e): ?><div><?= esc($e) ?></div><?php endforeach; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?= $this->renderSection('content') ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
