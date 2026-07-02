# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Overview

A CodeIgniter 4 corporate homepage template (a board CMS / site builder) for a one-person web agency — dynamic pages, a board system, an inquiry form, and an admin panel.

저장소 루트가 하나의 CI4 프로젝트입니다. 모든 `php spark`, `composer`, `git` 명령은 루트에서 실행합니다. 응답·주석·커밋 메시지는 한국어로 작성하며, 커밋은 변경 내용에 맞는 이모지를 접두사로 붙입니다.

## Commands

```bash
php spark serve              # Start dev server (http://localhost:8080)
php spark migrate            # Run all pending migrations (creates tables + seeds)
php spark migrate:rollback   # Roll back last migration batch
```

**Quality gate (커밋 전 필수 — 저장소 루트에서 실행):**
```bash
composer cs          # PHP-CS-Fixer 스타일 점검 (dry-run)
composer cs:fix      # 스타일 자동 정규화
composer analyse     # PHPStan 정적 분석 (레벨 6)
composer test        # PHPUnit (테스트 DB는 MySQL)
composer ci          # cs + analyse + test 한 번에
composer rector:dry  # 코드 현대화 미리보기 (선택), composer rector 로 적용
```

**Cron (production — 단 1줄 등록):**
```
* * * * * cd /path/to/app && php spark tasks:run >> /dev/null 2>&1
```
`Config/Tasks.php`가 `settings` 테이블에서 활성화된 잡을 읽어 등록. 활성화·주기는 `/admin/schedule`에서 관리.

## Initial Setup

```bash
cp env .env
# Edit .env: DB credentials, CI_ENVIRONMENT, TinyMCE key
php spark migrate
# app/Config/App.php: set appTimezone = 'Asia/Seoul'
```

Default admin: `admin@example.com` / `admin1234!`

Upload permission on Linux: `chmod -R 755 public/uploads writable`

## Architecture

### Theme System

`ThemeView` (`app/Libraries/ThemeView.php`) replaces CI4's default renderer. View resolution order:

1. `app/Views/themes/{active_theme}/{view}.php`
2. `app/Views/themes/default/{view}.php`
3. `app/Views/{view}.php` (admin views, content views — never themeable)

The active theme is stored in `settings.active_theme` (cached). Add a new theme by placing files in `app/Views/themes/{name}/` and `public/themes/{name}/` — only override what differs from default. `Config/Services.php` wires `ThemeView` as the shared renderer.

### BaseController — Global Data Injection

Every controller extends `BaseController`. Its `initController()` runs on every request and injects into `$this->viewData`:

- `$settings` — site-wide key-value config (cached)
- `$menus` — navigation tree (cached)
- `$authUser` — session-based user info (id, nickname, role, loggedIn)
- `$subLeftBanners` — active sidebar banners (cached, skipped on admin routes)
- `$activePopups` — active popups for current URI (cached)
- `$unreadInquiries` — unread inquiry count (admin role only)

Use `$this->render('view/path', $extraData)` in controllers — it merges `$viewData` automatically.

### Auth & Routing

- Auth filter alias: `auth` → `App\Filters\AuthFilter`
- Usage: `['filter' => 'auth:member']` or `['filter' => 'auth:admin']`
- All `/admin/*` routes require `auth:admin`
- Dynamic page catch-all `(:segment)` must stay last in `Routes.php`

### CSRF Exceptions

These routes receive POST without CSRF tokens (editor / media uploads) and are excluded in `Config/Filters.php`:
- `board/image-upload`
- `admin/media/upload`

### Caching Strategy

CI4 file cache is used for:
- `site_settings` — all settings key-value map (`SettingModel`)
- `nav_menus` — menu tree (`MenuModel`)
- `active_banners_{position}` — banners by position (`BannerModel`)
- `active_popups` — all active popups + page URL mappings (`PopupModel`)

Model callbacks (`afterInsert/Update/Delete`) invalidate the relevant cache key on admin write. Banner/popup expiry is checked in PHP against the cached data — no time-based cache invalidation needed.

### Social Login (OAuth)

`AbstractOAuthProvider` base class with `GoogleProvider`, `NaverProvider`, `KakaoProvider`. `OAuthFactory::create(string $provider)` resolves the provider. Keys in `Config/OAuth.php` (read from `.env`).

### File Uploads

| Class | Usage |
|-------|-------|
| `FileUploader` | Board post attachments — extension whitelist, 10 MB max, random hex filenames |
| `ImageUploader` | Banner / popup images — image-only, 2 MB max |
| `MediaUploader` | Admin media library — drag-and-drop, stores path in `media` table |

### DB Schema Summary

```
users               — member / admin roles, social login fields
settings            — key-value site config (active_theme, smtp, etc.)
menus               — 2-level navigation tree
pages               — slug-based dynamic pages
boards / posts / post_files / post_comments  — board system
inquiries           — contact form submissions
banners / popups / popup_pages               — marketing overlays
media               — media library
```

## Coding Standards

- **PHP 8.5+ 필수** (`composer.json`의 `require`/`platform` 고정). typed property, match, arrow function 등 적극 사용.
- **PSR-12 준수**, 파일 상단에 `declare(strict_types=1)` 선언. 함수 인자·반환 타입을 항상 명시 (PHPStan 레벨 6 통과).
- **비즈니스 로직은 Controller가 아닌 Model/Library로 캡슐화.** Controller는 입력 검증 → 위임 → 응답만 담당.
- 모든 Model은 `$allowedFields`를 명시하고, 뷰는 네이티브 PHP 대체 문법과 `esc()`를 사용.
- DB 접근은 Query Builder만 사용 — 문자열 연결 raw SQL 금지.

## Branch & CI Workflow

1. `feature/*` 브랜치에서 작업 후 `dev`로 PR.
2. `dev`에서 테스트·리뷰 후 `main`으로 PR.
3. `main`/`dev` 대상 push·PR 시 GitHub Actions(`.github/workflows/ci.yml`)가 실행:
   - **quality 잡** — `composer cs` (스타일) · `composer analyse` (PHPStan) · `composer test` (PHPUnit), PHP 8.5 / MySQL 8.0.
   - **coverage 잡** — 커버리지 리포트를 PR 코멘트로 게시.
4. 로컬에서 push 전 `composer ci`로 동일 검증을 먼저 통과시킬 것.
