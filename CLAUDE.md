# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Overview

A CodeIgniter 4 corporate homepage template (a board CMS / site builder) for a one-person web agency — dynamic pages, a board system, an inquiry form, and an admin panel.

## Commands

```bash
php spark serve              # Start dev server (http://localhost:8080)
php spark migrate            # Run all pending migrations (creates tables + seeds)
php spark migrate:rollback   # Roll back last migration batch
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
