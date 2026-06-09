# AICreo — CI4 웹에이전시 보일러플레이트

> 1인 웹에이전시를 위한 CodeIgniter 4 기반 홈페이지 빠른 납품 구조

---

## 개요

클라이언트 홈페이지를 반복 제작할 때 **코어는 재사용하고 껍데기만 교체**하는 방식으로  
단순 홈페이지는 3~5일, 중형 사이트는 1~2주 납품을 목표로 설계된 보일러플레이트입니다.

---

## 주요 기능

### 프론트
| 기능 | 설명 |
|------|------|
| 동적 페이지 | 슬러그 기반 자동 라우팅, 레이아웃 선택 (기본 / 문의 / 랜딩) |
| 홈페이지 | 히어로 · 서비스 소개 · 최신 공지 · CTA 섹션 |
| 문의폼 | 이름·이메일·연락처·내용 입력 → DB 저장 + 이메일 자동 발송 |
| 회원 인증 | 회원가입 · 로그인 · 로그아웃 |
| 반응형 GNB | DB 메뉴 기반 드롭다운 네비게이션 (Bootstrap 5) |
| SEO | OG태그 · 메타 설명 · Google Analytics · 네이버 웹마스터 자동 삽입 |

### 게시판
| 기능 | 설명 |
|------|------|
| 다중 게시판 | 슬러그별 독립 게시판 (공지사항 · 자유 · 문의 등) |
| 권한 관리 | 읽기·쓰기 권한을 **비회원 / 회원 / 관리자** 단위로 게시판별 설정 |
| 위지윅 에디터 | TinyMCE 6 기반 글쓰기·수정, 이미지 붙여넣기·업로드 지원 |
| 파일 첨부 | 이미지 + 일반 파일 복수 첨부, 확장자 화이트리스트 보안, 최대 10MB |
| 비회원 게시 | 이름 + 비밀번호 입력으로 작성·수정·삭제 |
| 댓글 | 회원·비회원 댓글, 소프트 삭제 |
| 검색 | 제목 / 내용 / 제목+내용 키워드 검색 |
| 공지글 | 관리자 전용 상단 고정 공지 (최대 5개 표시) |
| 비밀글 | 작성자·관리자만 열람 가능 |

### 관리자 패널 (`/admin`)
| 메뉴 | 기능 |
|------|------|
| 대시보드 | 게시글·회원·문의 통계, 미읽음 문의 알림 |
| 페이지 관리 | TinyMCE 에디터 기반 페이지 CRUD, SEO 메타 설정 |
| 게시판 관리 | 게시판 생성·수정, 권한·첨부 허용 설정 |
| 전체 게시물 | 전체 게시판 게시물 통합 조회, 게시판 필터·키워드 검색, 강제 삭제 |
| 회원 관리 | 회원 목록 검색·필터, 역할(관리자/일반) 변경, 활성 상태·닉네임 수정, 삭제 |
| 메뉴 관리 | GNB 메뉴 추가·수정·삭제, 2단계 드롭다운 지원 |
| 미디어 라이브러리 | 드래그 업로드, 이미지 경로 복사 |
| 문의 수신함 | 문의 목록·상세 확인, 이메일로 바로 답장 |
| 사이트 설정 | 기본 · 연락처 · SNS · SEO · 푸터 정보 관리 |
| 테마 관리 | ZIP 업로드로 테마 설치 · 설치된 테마 목록 확인 · 클릭 한 번으로 테마 전환 |

---

## 기술 스택

| 영역 | 선택 |
|------|------|
| 백엔드 | CodeIgniter 4 |
| 프론트 | Bootstrap 5 · Bootstrap Icons |
| 에디터 | TinyMCE 6 (CDN · API 키 `.env` 관리) |
| DB | MySQL / MariaDB |
| 인증 | CI4 Session 기반 |
| 캐시 | CI4 File Cache (설정·메뉴) |
| 테마 | 폴더 기반 멀티 테마 (레이아웃·컴포넌트·CSS/JS 분리) |

---

## 디렉토리 구조

```
app/
├── Config/
│   ├── Routes.php          # 전체 라우팅
│   ├── Filters.php         # auth:member / auth:admin 필터
│   ├── Services.php        # ThemeView를 기본 렌더러로 등록
│   ├── OAuth.php           # 소셜 로그인 설정
│   └── Editor.php          # TinyMCE API 키 (.env 참조)
├── Controllers/
│   ├── BaseController.php  # 설정·메뉴·세션 전역 주입
│   ├── Front/
│   │   ├── HomeController.php      # 홈
│   │   ├── PageController.php      # 동적 페이지 + 문의폼
│   │   ├── BoardController.php     # 게시판 전체
│   │   └── AuthController.php      # 로그인/회원가입
│   └── Admin/
│       ├── DashboardController.php
│       ├── PageManagerController.php
│       ├── BoardManagerController.php
│       ├── PostController.php          # 전체 게시물 관리
│       ├── UserController.php          # 회원 관리
│       ├── MenuController.php
│       ├── MediaController.php
│       ├── SettingController.php       # 테마 탭 포함
│       └── InquiryController.php
├── Models/                 # 11개 도메인 모델
├── Filters/AuthFilter.php
├── Libraries/
│   ├── ThemeView.php       # 테마 경로 우선 해석 렌더러
│   ├── FileUploader.php    # 게시판 첨부 (보안 확장자 검증)
│   ├── MediaUploader.php   # 미디어 라이브러리 업로드
│   └── SeoHelper.php       # OG태그 + GA 자동 생성
├── Database/Migrations/    # 6개 (테이블 생성 + 기본 데이터 + 테마 설정)
└── Views/
    ├── themes/             # ★ 테마 폴더 (클라이언트별 교체)
    │   ├── default/        # 기본 테마
    │   │   ├── layouts/main.php
    │   │   └── components/ # navbar / footer / contact_form
    │   └── {테마명}/       # 새 테마: 바꾸고 싶은 파일만 추가
    │       ├── layouts/main.php
    │       └── components/navbar.php
    ├── layouts/admin.php   # 관리자 레이아웃 (테마 적용 안 함)
    ├── pages/              # home / default / contact
    ├── board/              # list / view / write
    ├── auth/               # login / register / profile
    └── admin/              # 관리자 뷰 전체
public/
└── themes/
    ├── default/            # 기본 테마 CSS / JS
    │   ├── css/style.css
    │   └── js/main.js
    └── {테마명}/           # 새 테마 CSS / JS / preview.png
```

---

## DB 테이블

| 테이블 | 설명 |
|--------|------|
| `users` | 회원 (admin / member 역할) |
| `settings` | 사이트 전역 설정 (key-value) |
| `pages` | 동적 페이지 |
| `menus` | 네비게이션 메뉴 |
| `media` | 미디어 라이브러리 |
| `inquiries` | 문의 수신함 |
| `boards` | 게시판 설정 |
| `posts` | 게시글 (소프트 삭제) |
| `post_files` | 첨부파일 |
| `post_comments` | 댓글 (소프트 삭제) |

---

## 설치 방법

### 1. CI4 프로젝트 생성
```bash
composer create-project codeigniter4/appstarter my-project
cd my-project
```

### 2. 이 저장소 파일 덮어쓰기
```bash
# app/ public/ 폴더를 CI4 프로젝트에 복사
```

### 3. 환경 설정
```bash
cp env .env
```
`.env` 파일에서 DB 정보 및 기타 설정 수정:
```
CI_ENVIRONMENT = development
database.default.hostname = localhost
database.default.database = your_db_name
database.default.username = your_db_user
database.default.password = your_db_password
database.default.DBDriver = MySQLi

# TinyMCE API 키 (https://www.tiny.cloud 에서 발급)
editor.tinymce_api_key = your-tinymce-api-key
```

### 4. 마이그레이션 실행
```bash
php spark migrate
```
테이블 생성과 기본 데이터(게시판 3개 + 관리자 계정)가 한 번에 처리됩니다.

### 5. 업로드 폴더 권한 (Linux)
```bash
chmod -R 755 public/uploads
chmod -R 755 writable
```

### 6. 개발 서버 실행
```bash
php spark serve
```

---

## 기본 계정

| 구분 | 값 |
|------|----|
| 이메일 | `admin@example.com` |
| 비밀번호 | `admin1234!` |

> 최초 로그인 후 반드시 비밀번호를 변경하세요.

---

## 주요 URL

| URL | 설명 |
|-----|------|
| `/` | 홈 |
| `/about` | 회사소개 (동적 페이지) |
| `/service` | 서비스 (동적 페이지) |
| `/contact` | 문의하기 |
| `/board/notice` | 공지사항 |
| `/board/free` | 자유게시판 |
| `/board/qna` | 문의게시판 |
| `/auth/login` | 로그인 |
| `/auth/register` | 회원가입 |
| `/admin` | 관리자 대시보드 |
| `/admin/settings/general` | 사이트 설정 |

---

## 납품 워크플로우

```
1. 저장소 clone → CI4 프로젝트에 복사                    (5분)
2. .env DB 설정 + php spark migrate                    (5분)
3. /admin 로그인 → 사이트 설정 입력                      (10분)
4. 메뉴 편집 → 페이지 콘텐츠 작성                         (1~2시간)
5. public/themes/{클라이언트명}/ 추가 후 CSS/JS 커스텀    (30분~)
   + app/Views/themes/{클라이언트명}/ 에 레이아웃 오버라이드 (필요 시)
6. /admin/settings/theme 에서 테마 전환 (클릭 한 번)      (1분)
7. 서버 배포                                            (30분~)
```

---

## 새 클라이언트 적용 시 변경 항목

| 항목 | 위치 |
|------|------|
| 회사명 · 연락처 · 주소 | `/admin/settings/general`, `/admin/settings/contact` |
| SNS 링크 | `/admin/settings/sns` |
| GA · 네이버 인증 | `/admin/settings/seo` |
| 네비게이션 메뉴 | `/admin/menus` |
| 페이지 내용 | `/admin/pages` |
| 게시판 권한 | `/admin/boards` |
| 로고 · 파비콘 | 미디어 업로드 후 경로를 설정에 입력 |
| 테마 디자인 | `public/themes/{테마명}/css/style.css` + `app/Views/themes/{테마명}/` 추가 → `/admin/settings/theme` 에서 전환 |

---

## 보안 사항

- 파일 업로드: 확장자 화이트리스트 적용 (php, exe 등 실행파일 차단)
- 저장 파일명: `bin2hex(random_bytes(16))` 랜덤 변환
- 관리자 라우트: `auth:admin` 필터로 전체 보호
- 비밀번호: `password_hash()` / `password_verify()` 사용
- CSRF: CI4 기본 CSRF 필터 적용

---

## 테마 추가 방법

### 방법 A — 관리자 ZIP 업로드 (권장)

`/admin/settings/theme` 에서 ZIP 파일을 업로드하면 자동으로 압축 해제 후 설치됩니다.

**ZIP 패키징 구조**

```
my-theme.zip
├── views/                           → app/Views/themes/my-theme/ 로 복사
│   ├── layouts/
│   │   └── main.php                 ★ 필수
│   └── components/
│       ├── navbar.php
│       ├── footer.php
│       └── contact_form.php
└── public/                          → public/themes/my-theme/ 로 복사
    ├── css/
    │   └── style.css                ★ 필수
    ├── js/
    │   └── main.js
    └── preview.png                  (관리자 UI 미리보기 이미지)
```

업로드 시 자동으로 수행되는 검사:

| 검사 항목 | 내용 |
|-----------|------|
| 필수 파일 | `views/layouts/main.php`, `public/css/style.css` 존재 여부 |
| Zip-slip 방지 | `..` · `/` · `\` 시작 경로 차단 |
| 확장자 화이트리스트 | `views/` → `.php` 만 허용 / `public/` → CSS·JS·이미지·폰트만 허용 |
| 예약어 보호 | 테마명 `default` 사용 불가 |

설치 완료 후 테마 카드에서 **이 테마 적용** 버튼으로 즉시 전환할 수 있습니다.

---

### 방법 B — 직접 폴더 배치

서버에 직접 접근 가능한 경우 아래 두 폴더를 만들면 관리자 목록에 자동으로 표시됩니다.

```
# 에셋 (필수)
public/themes/{테마명}/css/style.css
public/themes/{테마명}/js/main.js
public/themes/{테마명}/preview.png   ← 관리자 미리보기 이미지 (선택)

# 레이아웃·컴포넌트 (바꾸고 싶은 파일만 — 없으면 default 폴백)
app/Views/themes/{테마명}/layouts/main.php
app/Views/themes/{테마명}/components/navbar.php
app/Views/themes/{테마명}/components/footer.php
```

이후 `/admin/settings/theme` 에서 해당 테마를 선택하면 즉시 적용됩니다.  
콘텐츠 뷰(`board/`, `auth/`, `admin/` 등)는 테마에 영향을 받지 않습니다.

---

## 샘플 테마

저장소에 `dark.zip` 샘플 테마가 포함되어 있습니다.  
`/admin/settings/theme` 에서 바로 업로드해 테마 시스템을 테스트할 수 있습니다.

| 항목 | default | dark |
|------|---------|------|
| 네비게이션 | 흰 배경 + 하단 선 | `#0f172a` 다크 네이비 |
| 포인트 컬러 | Bootstrap 파랑 `#0d6efd` | 인디고 `#6366f1` |
| 버튼 | 파란 계열 | 인디고 계열 |
| 푸터 | 다크 | 다크 + 인디고 섹션 타이틀 |
| SNS 아이콘 | 정적 | hover 시 인디고 변색 + 위 이동 |
| 포함 파일 | — | layouts, components 전체 + CSS/JS |

---

## 변경 이력

### 2026-06-09 (최근 추가)

| 항목 | 변경 내용 |
|------|----------|
| **dark 샘플 테마** | `dark.zip` 추가 — 다크 네이비 네비게이션, 인디고 포인트, 다크 푸터. `/admin/settings/theme` 업로드로 즉시 테스트 가능 |
| **테마 ZIP 업로드** | `/admin/settings/theme` 에서 ZIP 파일 업로드 → 압축 해제 → 자동 설치. 필수 파일 체크(`views/layouts/main.php`, `public/css/style.css`), Zip-slip 방지, 확장자 화이트리스트, `default` 예약어 보호 |
| **테마 시스템** | `ThemeView` 렌더러 도입 — `app/Views/themes/{테마명}/` 폴더 기반 레이아웃·컴포넌트 교체 지원. 해석 순서: 활성 테마 → default 테마 → 원본 경로. `Config/Services.php`로 CI4 기본 렌더러 교체 |
| **테마 관리 UI** | `/admin/settings/theme` 탭 추가 — 설치된 테마 카드 목록 표시, 클릭 한 번으로 전환. `preview.png` 있으면 미리보기 표시 |
| **views 구조 개편** | `layouts/main.php`, `components/` → `themes/default/` 로 이동. 콘텐츠 뷰(`board/`, `auth/`, `admin/`)는 테마와 분리 유지 |
| **DB 마이그레이션** | `settings` 테이블에 `active_theme` 키 추가 (migration #6) |
| 관리자 회원 관리 | `/admin/users` 추가 — 닉네임·이메일 검색, 역할·활성 상태 필터, 역할 변경 및 비활성 처리, 삭제 (본인 계정 보호) |
| 관리자 전체 게시물 | `/admin/posts` 추가 — 게시판 필터 + 키워드 검색, 공지·비밀 배지, 강제 삭제 (첨부파일 포함) |
| 게시판 관리 뷰 수정 | `admin/board/list.php`, `admin/board/form.php`가 `layouts/main`을 잘못 참조해 사이드바가 사라지던 문제 수정 → `layouts/admin`으로 교체 |

### 2026-06-09

| 항목 | 변경 내용 |
|------|----------|
| 파일 다운로드 | `file_get_contents()` → CI4 `DownloadResponse` 스트리밍으로 교체, 대용량 파일 메모리 낭비 제거 |
| 비회원 수정 흐름 | `POST board/{slug}/{id}/verify` 엔드포인트 추가. 수정 폼 진입 전 비밀번호 인증 → 세션 토큰 발급 순서로 흐름 완성 |
| 공지글 상한 | `getList()` 내 공지글 조회에 `findAll(5)` 상한 추가, 공지 다수 시 풀스캔 방지 |
| 파일 업로드 정리 | `store()` 내 `getFiles()` + `getFileMultiple()` 중복 호출 제거, `getFileMultiple()` 단일 사용으로 통일 |
| 로그인 후 리다이렉트 | 글쓰기 미로그인 시 `flashdata` → `setTempdata(300s)` 로 변경, 로그인 후 원래 페이지로 정상 복귀 |
| 게시판·인증 뷰 폭 | `board/list`, `view`, `write`, `auth/login`, `register`에 Bootstrap `container` 래퍼 추가 |
| 카드 테두리 깨짐 | `.card` CSS를 `border-radius` 직접 지정 → `--bs-card-border-radius` / `--bs-card-inner-border-radius` CSS 변수로 교체 |
| 카드 내부 깨짐 | `.card`에 `overflow: hidden` 추가, 둥근 모서리 밖 배경 삐져나옴 방지 |
| 게시판 위지윅 에디터 | 글쓰기·수정에 TinyMCE 6 적용, 이미지 붙여넣기·업로드용 `POST board/image-upload` 엔드포인트 추가 (로그인 필수) |
| TinyMCE API 키 | `Config/Editor.php` 추가, `.env`의 `editor.tinymce_api_key`로 관리 |

---

## License

MIT
