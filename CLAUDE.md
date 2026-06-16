# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository Overview

Two independent CodeIgniter 4 boilerplates for a one-person web agency:

| Directory | Purpose |
|-----------|---------|
| `default/` | Corporate homepage template (pages, boards, inquiry form, admin panel) |
| `shop/` | E-commerce template — built on top of `default/`, adds cart, orders, PG payments |

Each directory is a standalone CI4 project. Work inside the appropriate directory.

## Commands

All commands run from within the template directory (e.g., `cd shop`).

```bash
php spark serve              # Start dev server (http://localhost:8080)
php spark migrate            # Run all pending migrations (creates tables + seeds)
php spark migrate:rollback   # Roll back last migration batch
php spark orders:expire      # Manually expire pending orders older than 30 min
php spark orders:expire 60   # Expire pending orders older than 60 min
```

**Cron (production — 단 1줄 등록):**
```
* * * * * cd /path/to/shop && php spark tasks:run >> /dev/null 2>&1
```
`Config/Tasks.php`가 `settings` 테이블에서 활성화된 잡을 읽어 등록. 활성화·주기는 `/admin/schedule`에서 관리.

## Initial Setup

```bash
cp env .env
# Edit .env: DB credentials, CI_ENVIRONMENT, TinyMCE key, PG keys
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
- `$cartCount` — cart item count for logged-in users (shop only)
- `$unreadInquiries` — unread inquiry count (admin role only)

Use `$this->render('view/path', $extraData)` in controllers — it merges `$viewData` automatically.

### Auth & Routing

- Auth filter alias: `auth` → `App\Filters\AuthFilter`
- Usage: `['filter' => 'auth:member']` or `['filter' => 'auth:admin']`
- All `/admin/*` routes require `auth:admin`
- Cart view/edit/delete require `auth:member`; `cart/add` (POST) is open to guests (stored in session)
- Dynamic page catch-all `(:segment)` must stay last in `Routes.php`

### CSRF Exceptions

PG callbacks receive POST from PG servers without CSRF tokens. These routes are excluded in `Config/Filters.php`:
- `payment/callback/*`
- `board/image-upload`
- `admin/media/upload`

### PG Payment Layer (shop only)

`PGInterface` defines three methods: `buildPaymentParams()`, `confirm()`, `cancel()`. Adapters in `app/Libraries/PG/`:

| Adapter | PG |
|---------|---|
| `TossPaymentsAdapter` | 토스페이먼츠 |
| `InicisAdapter` | KG이니시스 |
| `NicePayAdapter` | 나이스페이 |
| `KakaoPayAdapter` | 카카오페이 |
| `NaverPayAdapter` | 네이버페이 |
| `BankTransferAdapter` | 무통장입금 |

`PGFactory::create(string $provider)` resolves the adapter. PG keys are in `Config/PG.php`, all values read from `.env`.

### Stock Management (shop only)

**Rule: stock is only decremented at PG success callback (or admin bank transfer confirm). Never at cart-add time.**

`OrderModel::confirmPaid()` and `confirmBankTransfer()` use a two-layer concurrency guard inside a transaction:
1. `SELECT stock ... FOR UPDATE` — row-level lock
2. `UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?` — conditional update; 0 affected rows = rollback

`payments.pg_tid` has a UNIQUE constraint — duplicate PG callbacks are silently rejected.

Order status flow (single-direction, enforced in `OrderModel::updateStatus()`):
```
pending → [PG paid] → paid → preparing → shipped → delivered
pending → [bank transfer] → awaiting_payment → [admin confirm] → paid
paid/preparing → cancelled (restores stock)
refund_requested → refunded
delivered → [member, within 7 days] → return_requested → [admin approve] → return_approved → [admin confirm refund] → refunded
                                                        → [admin reject]  → delivered
```

`delivered_at` is set when status transitions to `delivered`. Return window is 7 days from `delivered_at` (null = legacy orders, always allowed).

`pending` orders not confirmed within 30 min are moved to `expired` by the scheduler (no stock was held).

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

### DB Schema Summary (shop)

```
users               — member / admin roles, social login fields
settings            — key-value site config (active_theme, smtp, etc.)
menus               — 2-level navigation tree
pages               — slug-based dynamic pages
boards / posts / post_files / post_comments  — board system
inquiries           — contact form submissions
banners / popups / popup_pages               — marketing overlays
media               — media library
categories          — product categories (parent_id for hierarchy)
products            — price, discount_price, stock, status, shipping_*
product_images      — multiple images per product, is_primary flag
cart_items          — user_id OR session_id (guest carts)
orders              — order header, status, shipping snapshot
order_items         — product snapshot at order time (name, price, qty)
shipping_addresses  — saved addresses per user
payments            — pg_tid UNIQUE, raw PG response stored as JSON
stock_logs          — audit trail for inventory adjustments
```
