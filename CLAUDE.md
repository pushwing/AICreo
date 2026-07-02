# CLAUDE.md

이 파일은 이 저장소에서 작업할 때 Claude Code(claude.ai/code)에 대한 가이드를 제공합니다.

## 저장소 개요

1인 웹 에이전시를 위한 CodeIgniter 4 기업 홈페이지 템플릿(게시판 CMS / 사이트 빌더)입니다 — 동적 페이지, 게시판 시스템, 문의 폼, 관리자 패널을 제공합니다.

저장소 루트가 하나의 CI4 프로젝트입니다. 모든 `php spark`, `composer`, `git` 명령은 루트에서 실행합니다. 응답·주석·커밋 메시지는 한국어로 작성하며, 커밋은 변경 내용에 맞는 이모지를 접두사로 붙입니다.

## 명령어

```bash
php spark serve              # 개발 서버 실행 (http://localhost:8080)
php spark migrate            # 대기 중인 마이그레이션 전체 실행 (테이블 생성 + 시딩)
php spark migrate:rollback   # 마지막 마이그레이션 배치 롤백
```

**품질 게이트 (커밋 전 필수 — 저장소 루트에서 실행):**
```bash
composer cs          # PHP-CS-Fixer 스타일 점검 (dry-run)
composer cs:fix      # 스타일 자동 정규화
composer analyse     # PHPStan 정적 분석 (레벨 6)
composer test        # PHPUnit (테스트 DB는 MySQL)
composer ci          # cs + analyse + test 한 번에
composer rector:dry  # 코드 현대화 미리보기 (선택), composer rector 로 적용
```

**Cron (운영 — 단 1줄 등록):**
```
* * * * * cd /path/to/app && php spark tasks:run >> /dev/null 2>&1
```
`Config/Tasks.php`가 `settings` 테이블에서 활성화된 잡을 읽어 등록. 활성화·주기는 `/admin/schedule`에서 관리.

## 초기 설정

```bash
cp env .env
# .env 편집: DB 접속 정보, CI_ENVIRONMENT, TinyMCE 키
php spark migrate
# app/Config/App.php: appTimezone = 'Asia/Seoul' 설정
```

기본 관리자 계정: `admin@example.com` / `admin1234!`

Linux 업로드 권한: `chmod -R 755 public/uploads writable`

## 아키텍처

### 테마 시스템

`ThemeView`(`app/Libraries/ThemeView.php`)가 CI4 기본 렌더러를 대체합니다. 뷰 탐색 순서:

1. `app/Views/themes/{active_theme}/{view}.php`
2. `app/Views/themes/default/{view}.php`
3. `app/Views/{view}.php` (관리자 뷰, 콘텐츠 뷰 — 테마 적용 대상 아님)

활성 테마는 `settings.active_theme`에 저장됩니다(캐시됨). 새 테마는 `app/Views/themes/{name}/`와 `public/themes/{name}/`에 파일을 두어 추가하며, default와 다른 부분만 재정의하면 됩니다. `Config/Services.php`가 `ThemeView`를 공유 렌더러로 연결합니다.

### BaseController — 전역 데이터 주입

모든 컨트롤러는 `BaseController`를 상속합니다. 매 요청마다 실행되는 `initController()`가 `$this->viewData`에 다음을 주입합니다:

- `$settings` — 사이트 전역 키-값 설정 (캐시됨)
- `$menus` — 내비게이션 트리 (캐시됨)
- `$authUser` — 세션 기반 사용자 정보 (id, nickname, role, loggedIn)
- `$subLeftBanners` — 활성 사이드바 배너 (캐시됨, 관리자 경로에서는 건너뜀)
- `$activePopups` — 현재 URI에 대한 활성 팝업 (캐시됨)
- `$unreadInquiries` — 읽지 않은 문의 수 (admin 역할만)

컨트롤러에서는 `$this->render('view/path', $extraData)`를 사용 — `$viewData`를 자동으로 병합합니다.

### 인증 & 라우팅

- 인증 필터 별칭: `auth` → `App\Filters\AuthFilter`
- 사용법: `['filter' => 'auth:member']` 또는 `['filter' => 'auth:admin']`
- 모든 `/admin/*` 경로는 `auth:admin` 필요
- 동적 페이지 catch-all `(:segment)`는 `Routes.php`에서 반드시 맨 마지막에 위치

### CSRF 예외

다음 경로는 CSRF 토큰 없이 POST를 받으며(에디터 / 미디어 업로드), `Config/Filters.php`에서 제외됩니다:
- `board/image-upload`
- `admin/media/upload`

### 캐싱 전략

CI4 파일 캐시를 다음에 사용합니다:
- `site_settings` — 전체 설정 키-값 맵 (`SettingModel`)
- `nav_menus` — 메뉴 트리 (`MenuModel`)
- `active_banners_{position}` — 위치별 배너 (`BannerModel`)
- `active_popups` — 전체 활성 팝업 + 페이지 URL 매핑 (`PopupModel`)

모델 콜백(`afterInsert/Update/Delete`)이 관리자 쓰기 시 해당 캐시 키를 무효화합니다. 배너/팝업 만료는 캐시된 데이터에 대해 PHP에서 검사하므로 시간 기반 캐시 무효화가 필요 없습니다.

### 소셜 로그인 (OAuth)

`AbstractOAuthProvider` 기반 클래스와 `GoogleProvider`, `NaverProvider`, `KakaoProvider`로 구성됩니다. `OAuthFactory::create(string $provider)`가 프로바이더를 해석합니다. 키는 `Config/OAuth.php`에 있습니다(`.env`에서 읽음).

### 파일 업로드

| 클래스 | 용도 |
|-------|-------|
| `FileUploader` | 게시글 첨부파일 — 확장자 화이트리스트, 최대 10 MB, 랜덤 hex 파일명 |
| `ImageUploader` | 배너 / 팝업 이미지 — 이미지 전용, 최대 2 MB |
| `MediaUploader` | 관리자 미디어 라이브러리 — 드래그 앤 드롭, `media` 테이블에 경로 저장 |

### DB 스키마 요약

```
users               — 회원 / 관리자 역할, 소셜 로그인 필드
settings            — 키-값 사이트 설정 (active_theme, smtp 등)
menus               — 2단계 내비게이션 트리
pages               — slug 기반 동적 페이지
boards / posts / post_files / post_comments  — 게시판 시스템
inquiries           — 문의 폼 제출
banners / popups / popup_pages               — 마케팅 오버레이
media               — 미디어 라이브러리
```

## 코딩 표준

- **PHP 8.5+ 필수** (`composer.json`의 `require`/`platform` 고정). typed property, match, arrow function 등 적극 사용.
- **PSR-12 준수**, 파일 상단에 `declare(strict_types=1)` 선언. 함수 인자·반환 타입을 항상 명시 (PHPStan 레벨 6 통과).
- **비즈니스 로직은 Controller가 아닌 Model/Library로 캡슐화.** Controller는 입력 검증 → 위임 → 응답만 담당.
- 모든 Model은 `$allowedFields`를 명시하고, 뷰는 네이티브 PHP 대체 문법과 `esc()`를 사용.
- DB 접근은 Query Builder만 사용 — 문자열 연결 raw SQL 금지.

## 브랜치 & CI 워크플로우

1. `feature/*` 브랜치에서 작업 후 `dev`로 PR.
2. `dev`에서 테스트·리뷰 후 `main`으로 PR.
3. `main`/`dev` 대상 push·PR 시 GitHub Actions(`.github/workflows/ci.yml`)가 실행:
   - **quality 잡** — `composer cs` (스타일) · `composer analyse` (PHPStan) · `composer test` (PHPUnit), PHP 8.5 / MySQL 8.0.
   - **coverage 잡** — 커버리지 리포트를 PR 코멘트로 게시.
4. 로컬에서 push 전 `composer ci`로 동일 검증을 먼저 통과시킬 것.
