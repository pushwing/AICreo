# AiCreo 개발 로드맵

## 1. 배너 관리 ✅ 완료

### 등록 항목
- 배너 이미지 업로드 (jpg, jpeg, png, gif / 최대 2MB)
- 시작/종료 날짜 설정
- 운영/미운영 상태 (수동 처리)
- 배너 위치: 메인 상단, 메인 하단, 서브 좌측, 서브 우측
- 링크 URL + 열기 방식 (새 창 / 현재 창)
- 노출 우선순위 번호 (숫자가 낮을수록 우선)

### 리스트 표시 항목
- 썸네일 이미지
- 운영 중: 초록 뱃지 / 미운영: 회색 뱃지
- 시작/종료일
- 위치
- 우선순위

### 기술 사항
- 메인 배너 가로: 1000px (CSS max-width: 100% 반응형)
- 서브 배너 가로: 300px / `.sp-banner-slot` 내부 `<a>` 태그로 삽입 (최대 3개)
- 서브 좌측 배너: 모든 프론트 페이지 레이아웃에 고정 노출
- 메인 배너: 홈(/) 페이지 전용
- 종료일 지난 배너: 프론트에서 자동 숨김 (날짜 조건 쿼리)

### 구현 파일
| 파일 | 설명 |
|---|---|
| `app/Database/Migrations/2024-01-01-000007_CreateBannersTable.php` | banners 테이블 |
| `app/Models/BannerModel.php` | 배너 모델 |
| `app/Libraries/BannerUploader.php` | 이미지 업로드 처리 |
| `app/Controllers/Admin/BannerController.php` | 관리자 CRUD |
| `app/Controllers/BaseController.php` | `$subLeftBanners` 전역 주입 (admin 경로 제외) |
| `app/Controllers/Front/HomeController.php` | `$mainTopBanners`, `$mainBotBanners` 홈 전달 |
| `app/Views/admin/banners/list.php` | 관리자 목록 뷰 |
| `app/Views/admin/banners/form.php` | 등록/수정 폼 |
| `app/Views/themes/default/components/banner_slot.php` | 메인 배너 렌더링 컴포넌트 |
| `app/Views/themes/default/layouts/main.php` | default 레이아웃 서브 좌측 배너 슬롯 |

### Spring 테마 추가 구현 파일
| 파일 | 설명 |
|---|---|
| `app/Views/themes/spring/layouts/main.php` | 홈/서브 조건 분기, 서브 좌측 배너 사이드바 |
| `app/Views/themes/spring/pages/home.php` | 메인 상단/하단 배너 섹션 포함 홈 뷰 |
| `public/themes/spring/css/style.css` | `.sp-main-banner-*`, `.sp-banner-img` 스타일 |

### 주요 트러블슈팅
| 항목 | 원인 | 해결 |
|---|---|---|
| 서브 좌측 배너 미표시 | spring 테마 layouts에 하드코딩된 플레이스홀더 | DB 데이터 기반 렌더링으로 교체 |
| 배너 시간 조건 오작동 | PHP 서버 UTC, 사용자 입력 KST | `App.php` `appTimezone = 'Asia/Seoul'` 설정 |
| CI4 NULL 조건 쿼리 오류 | `groupStart/orWhere` NULL 호환 이슈 | raw where 문자열로 변경 |
| 홈 리디자인 후 서브 배너 소실 | 레이아웃 전체에서 사이드바 제거 | `uri_string()` 조건으로 홈만 제외 |

---

## 2. 팝업 관리 ⬜ 예정

### 등록 항목
- 팝업 내용 입력
- 이미지 업로드
- 시작/종료일 설정
- 운영/미운영 상태 설정
- 뜨는 순서 설정
- 노출 위치: 메인, 서브, 특정 페이지 (메뉴 기반)

### 리스트 표시 항목
- 미리보기
- 운영 중 여부
- 시작/종료일
- 위치
- 순서
