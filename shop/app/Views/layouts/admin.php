<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-name"  content="<?= csrf_token() ?>">
    <meta name="csrf-token" content="<?= csrf_hash() ?>"><?php // session CSRF — 액션 후 page reload 시 갱신 ?>
    <title><?= esc($pageTitle ?? '관리자') ?> | <?= esc($settings['site_name'] ?? 'Admin') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --sidebar-w: 220px; }
        body  { background: #f1f3f5; }
        #sidebar {
            width: var(--sidebar-w); height: 100vh;
            position: fixed; top: 0; left: 0;
            overflow-y: auto; overflow-x: hidden;
            background: #1e2a38; position: fixed; top: 0; left: 0;
        }
        #sidebar .brand { padding: 1rem 1.2rem; color: #fff; font-weight: 700; font-size: 1.1rem; border-bottom: 1px solid #2d3f54; }
        #sidebar .nav-link { color: #a8b7c7; padding: .5rem 1.2rem; font-size: .875rem; }
        #sidebar .nav-link:hover,
        #sidebar .nav-link.active { color: #fff; background: #2d3f54; }
        #sidebar .nav-section { color: #5a7080; font-size: .7rem; padding: .8rem 1.2rem .2rem; letter-spacing: .06em; text-transform: uppercase; }
        #sidebar .nav-section-toggle {
            display: flex; align-items: center; justify-content: space-between;
            width: 100%; background: none; border: none; cursor: pointer;
            color: #5a7080; font-size: .7rem; padding: .8rem 1.2rem .2rem;
            letter-spacing: .06em; text-transform: uppercase;
        }
        #sidebar .nav-section-toggle:hover { color: #8aa0b0; }
        #sidebar .nav-section-toggle .bi-chevron-down { transition: transform .2s; font-size: .65rem; }
        #sidebar .nav-section-toggle.collapsed .bi-chevron-down { transform: rotate(-90deg); }
        #content { margin-left: var(--sidebar-w); padding: 1.5rem; }
        #topbar  { background: #fff; border-bottom: 1px solid #e9ecef; padding: .6rem 1.5rem; margin-left: var(--sidebar-w); position: sticky; top: 0; z-index: 100; }
        .badge-dot { width: 8px; height: 8px; border-radius: 50%; background: #dc3545; display: inline-block; }
        .badge-bronze { background-color: #a0522d; color: #fff; }
        .badge-silver { background-color: #888f94; color: #fff; }
    </style>
    <?= $this->renderSection('styles') ?>
</head>
<body>

<div id="sidebar">
    <div class="brand"><i class="bi bi-grid-3x3-gap-fill me-2"></i><?= esc($settings['site_name'] ?? 'Admin') ?></div>
    <?php
        $uri = uri_string();
        $inContent  = in_array(true, [
            $uri === 'admin/dashboard', $uri === 'admin',
            str_starts_with($uri, 'admin/pages'), str_starts_with($uri, 'admin/boards'),
            str_starts_with($uri, 'admin/posts'), str_starts_with($uri, 'admin/media'),
            str_starts_with($uri, 'admin/banners'), str_starts_with($uri, 'admin/popups'),
            str_starts_with($uri, 'admin/welcome'),
        ]);
        $inShop     = in_array(true, [
            str_starts_with($uri, 'admin/products'), str_starts_with($uri, 'admin/promotions'),
            str_starts_with($uri, 'admin/suppliers'), str_starts_with($uri, 'admin/inventory'),
            str_starts_with($uri, 'admin/orders'), str_starts_with($uri, 'admin/sales'),
            str_starts_with($uri, 'admin/coupons'), str_starts_with($uri, 'admin/points'),
        ]);
        $inOps      = in_array(true, [
            str_starts_with($uri, 'admin/users'), str_starts_with($uri, 'admin/grade'),
            str_starts_with($uri, 'admin/inquiries'), str_starts_with($uri, 'admin/qna'),
            str_starts_with($uri, 'admin/stats'), str_starts_with($uri, 'admin/menus'),
        ]);
        $inSettings = str_starts_with($uri, 'admin/settings');
    ?>
    <nav class="mt-2">
        <!-- 콘텐츠 -->
        <button class="nav-section-toggle <?= $inContent ? '' : 'collapsed' ?>"
                type="button" data-bs-toggle="collapse" data-bs-target="#sec-content"
                aria-expanded="<?= $inContent ? 'true' : 'false' ?>">
            콘텐츠 <i class="bi bi-chevron-down"></i>
        </button>
        <div class="collapse <?= $inContent ? 'show' : '' ?>" id="sec-content">
            <a href="/admin/dashboard" class="nav-link <?= $uri === 'admin/dashboard' || $uri === 'admin' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2 me-2"></i>대시보드
            </a>
            <a href="/admin/pages"  class="nav-link <?= str_starts_with($uri, 'admin/pages')   ? 'active' : '' ?>"><i class="bi bi-file-text me-2"></i>페이지 관리</a>
            <a href="/admin/boards" class="nav-link <?= str_starts_with($uri, 'admin/boards')  ? 'active' : '' ?>"><i class="bi bi-card-list me-2"></i>게시판 관리</a>
            <a href="/admin/posts"  class="nav-link <?= str_starts_with($uri, 'admin/posts')   ? 'active' : '' ?>"><i class="bi bi-file-earmark-text me-2"></i>전체 게시물</a>
            <a href="/admin/media"  class="nav-link <?= str_starts_with($uri, 'admin/media')   ? 'active' : '' ?>"><i class="bi bi-images me-2"></i>미디어</a>
            <a href="/admin/banners" class="nav-link <?= str_starts_with($uri, 'admin/banners') ? 'active' : '' ?>"><i class="bi bi-image me-2"></i>배너 관리</a>
            <a href="/admin/popups"   class="nav-link <?= str_starts_with($uri, 'admin/popups')   ? 'active' : '' ?>"><i class="bi bi-window me-2"></i>팝업 관리</a>
            <a href="/admin/welcome"  class="nav-link <?= str_starts_with($uri, 'admin/welcome')  ? 'active' : '' ?>"><i class="bi bi-layout-text-window-reverse me-2"></i>Welcome 페이지</a>
        </div>

        <!-- 쇼핑 -->
        <button class="nav-section-toggle <?= $inShop ? '' : 'collapsed' ?>"
                type="button" data-bs-toggle="collapse" data-bs-target="#sec-shop"
                aria-expanded="<?= $inShop ? 'true' : 'false' ?>">
            쇼핑 <i class="bi bi-chevron-down"></i>
        </button>
        <div class="collapse <?= $inShop ? 'show' : '' ?>" id="sec-shop">
            <a href="/admin/products" class="nav-link <?= str_starts_with($uri, 'admin/products') && ! str_starts_with($uri, 'admin/products/categories') ? 'active' : '' ?>"><i class="bi bi-bag me-2"></i>상품 관리</a>
            <a href="/admin/products/categories" class="nav-link <?= str_starts_with($uri, 'admin/products/categories') ? 'active' : '' ?>"><i class="bi bi-tags me-2"></i>카테고리 관리</a>
            <a href="/admin/promotions" class="nav-link <?= str_starts_with($uri, 'admin/promotions') ? 'active' : '' ?>"><i class="bi bi-megaphone me-2"></i>기획전 관리</a>
            <a href="/admin/suppliers"  class="nav-link <?= str_starts_with($uri, 'admin/suppliers')  ? 'active' : '' ?>"><i class="bi bi-truck me-2"></i>매입처 관리</a>
            <a href="/admin/inventory"  class="nav-link <?= str_starts_with($uri, 'admin/inventory')  ? 'active' : '' ?>"><i class="bi bi-boxes me-2"></i>재고 관리</a>
            <a href="/admin/orders"     class="nav-link <?= str_starts_with($uri, 'admin/orders')     ? 'active' : '' ?>"><i class="bi bi-receipt me-2"></i>주문 관리</a>
            <a href="/admin/sales"      class="nav-link <?= str_starts_with($uri, 'admin/sales')      ? 'active' : '' ?>"><i class="bi bi-graph-up-arrow me-2"></i>매출 관리</a>
            <a href="/admin/coupons"    class="nav-link <?= str_starts_with($uri, 'admin/coupons')    ? 'active' : '' ?>"><i class="bi bi-ticket-perforated me-2"></i>쿠폰 관리</a>
            <a href="/admin/points"     class="nav-link <?= str_starts_with($uri, 'admin/points')     ? 'active' : '' ?>"><i class="bi bi-star me-2"></i>포인트 관리</a>
        </div>

        <!-- 운영 -->
        <button class="nav-section-toggle <?= $inOps ? '' : 'collapsed' ?>"
                type="button" data-bs-toggle="collapse" data-bs-target="#sec-ops"
                aria-expanded="<?= $inOps ? 'true' : 'false' ?>">
            운영 <i class="bi bi-chevron-down"></i>
        </button>
        <div class="collapse <?= $inOps ? 'show' : '' ?>" id="sec-ops">
            <a href="/admin/users"          class="nav-link <?= str_starts_with($uri, 'admin/users')     ? 'active' : '' ?>"><i class="bi bi-person-lines-fill me-2"></i>회원 관리</a>
            <a href="/admin/grade/platinum" class="nav-link <?= str_starts_with($uri, 'admin/grade')     ? 'active' : '' ?>"><i class="bi bi-trophy-fill me-2"></i>회원 등급</a>
            <a href="/admin/inquiries"      class="nav-link <?= str_starts_with($uri, 'admin/inquiries') ? 'active' : '' ?>">
                <i class="bi bi-envelope me-2"></i>문의글 관리
                <?php if ($unreadInquiries > 0): ?><span class="badge bg-danger ms-1"><?= $unreadInquiries ?></span><?php endif; ?>
            </a>
            <a href="/admin/qna" class="nav-link <?= str_starts_with($uri, 'admin/qna') ? 'active' : '' ?>">
                <i class="bi bi-chat-dots me-2"></i>상품 문의 관리
                <?php if ($unansweredQna > 0): ?><span class="badge bg-danger ms-1"><?= $unansweredQna ?></span><?php endif; ?>
            </a>
            <a href="/admin/stats" class="nav-link <?= str_starts_with($uri, 'admin/stats') ? 'active' : '' ?>"><i class="bi bi-bar-chart-line me-2"></i>접속 통계</a>
            <a href="/admin/menus" class="nav-link <?= str_starts_with($uri, 'admin/menus') ? 'active' : '' ?>"><i class="bi bi-list-ul me-2"></i>메뉴 관리</a>
        </div>

        <!-- 설정 -->
        <button class="nav-section-toggle <?= $inSettings ? '' : 'collapsed' ?>"
                type="button" data-bs-toggle="collapse" data-bs-target="#sec-settings"
                aria-expanded="<?= $inSettings ? 'true' : 'false' ?>">
            설정 <i class="bi bi-chevron-down"></i>
        </button>
        <div class="collapse <?= $inSettings ? 'show' : '' ?>" id="sec-settings">
            <a href="/admin/settings/general" class="nav-link <?= $uri !== 'admin/settings/oauth' && $uri !== 'admin/settings/theme' && ! str_starts_with($uri, 'admin/settings/theme') && str_starts_with($uri, 'admin/settings') ? 'active' : '' ?>"><i class="bi bi-gear me-2"></i>사이트 설정</a>
            <a href="/admin/settings/theme"   class="nav-link <?= str_starts_with($uri, 'admin/settings/theme') ? 'active' : '' ?>"><i class="bi bi-palette me-2"></i>테마 관리</a>
            <a href="/admin/settings/oauth"   class="nav-link <?= $uri === 'admin/settings/oauth' ? 'active' : '' ?>"><i class="bi bi-people me-2"></i>소셜 로그인</a>
        </div>

        <div class="nav-section mt-3">사이트</div>
        <a href="/" target="_blank" class="nav-link"><i class="bi bi-box-arrow-up-right me-2"></i>사이트 보기</a>
        <a href="/auth/logout" class="nav-link text-danger"><i class="bi bi-box-arrow-right me-2"></i>로그아웃</a>
    </nav>

    <script>
    (function () {
        var STORE_KEY = 'adminNavOpen';
        var sections  = ['sec-content', 'sec-shop', 'sec-ops', 'sec-settings'];

        // 활성 섹션은 항상 열림 — localStorage 복원은 비활성 섹션에만 적용
        var saved = {};
        try { saved = JSON.parse(localStorage.getItem(STORE_KEY) || '{}'); } catch (e) {}

        sections.forEach(function (id) {
            var el  = document.getElementById(id);
            var btn = document.querySelector('[data-bs-target="#' + id + '"]');
            if (! el || ! btn) return;

            var isActive = el.classList.contains('show');
            if (isActive) return; // 활성 섹션은 서버 렌더링 상태 유지

            // localStorage에 저장된 상태 복원
            if (saved[id] === true) {
                el.classList.add('show');
                btn.classList.remove('collapsed');
                btn.setAttribute('aria-expanded', 'true');
            }
        });

        // 토글 시 저장
        sections.forEach(function (id) {
            var el = document.getElementById(id);
            if (! el) return;
            el.addEventListener('show.bs.collapse', function () {
                saved[id] = true;
                localStorage.setItem(STORE_KEY, JSON.stringify(saved));
            });
            el.addEventListener('hide.bs.collapse', function () {
                saved[id] = false;
                localStorage.setItem(STORE_KEY, JSON.stringify(saved));
            });
        });
    }());
    </script>
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
