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
| `app/Libraries/ImageUploader.php` | 이미지 업로드 처리 (`new ImageUploader('banners')`) |
| `app/Controllers/Admin/BannerController.php` | 관리자 CRUD |
| `app/Controllers/BaseController.php` | `$subLeftBanners` 전역 주입 (admin 경로 제외) |
| `app/Controllers/Front/HomeController.php` | `$mainTopBanners`, `$mainBotBanners` 홈 전달 |
| `app/Views/admin/banners/list.php` | 관리자 목록 뷰 |
| `app/Views/admin/banners/form.php` | 등록/수정 폼 |
| `app/Views/themes/default/components/banner_slot.php` | 메인 배너 렌더링 컴포넌트 |
| `app/Views/themes/default/layouts/main.php` | default 레이아웃 서브 좌측 배너 슬롯 |

### 테마별 추가 구현 파일 (spring / dark)
| 파일 | 설명 |
|---|---|
| `app/Views/themes/spring/layouts/main.php` | 홈/서브 조건 분기, 서브 좌측 배너 사이드바 |
| `app/Views/themes/spring/pages/home.php` | 메인 상단/하단 배너 섹션 포함 홈 뷰 |
| `public/themes/spring/css/style.css` | `.sp-main-banner-*`, `.sp-banner-img` 스타일 |
| `dark.zip / views/layouts/main.php` | 서브 좌측 배너 사이드바(`dk-layout`) |
| `dark.zip / views/pages/home.php` | 메인 상단/하단 배너 섹션 포함 홈 뷰 |
| `dark.zip / public/css/style.css` | `dk-*` 레이아웃/배너 스타일 추가 |

### 주요 트러블슈팅
| 항목 | 원인 | 해결 |
|---|---|---|
| 서브 좌측 배너 미표시 | spring 테마 layouts에 하드코딩된 플레이스홀더 | DB 데이터 기반 렌더링으로 교체 |
| 배너 시간 조건 오작동 | PHP 서버 UTC, 사용자 입력 KST | `App.php` `appTimezone = 'Asia/Seoul'` 설정 |
| CI4 NULL 조건 쿼리 오류 | `groupStart/orWhere` NULL 호환 이슈 | raw where 문자열로 변경 |
| 홈 리디자인 후 서브 배너 소실 | 레이아웃 전체에서 사이드바 제거 | `uri_string()` 조건으로 홈만 제외 |

---

## 2. 팝업 관리 ✅ 완료

### 등록 항목
- 제목
- 이미지 업로드 (jpg, jpeg, png, gif / 최대 2MB)
- 텍스트 본문 (WYSIWYG 에디터)
- 시작/종료일 설정
- 운영/미운영 상태 (수동 처리)
- 노출 위치: 전체(메인+서브) / 메인 전용 / 특정 페이지 (메뉴 목록에서 복수 선택)
- 화면 표시 좌표 (X, Y — left/top px 입력)
- 우선순위 번호 (숫자가 낮을수록 우선, 동일 위치 팝업 겹침 방지용)

### 리스트 표시 항목
- 썸네일 미리보기
- 운영 중: 초록 뱃지 / 미운영: 회색 뱃지
- 노출 위치
- 표시 좌표 (X, Y)
- 우선순위
- 시작/종료일

### 기술 사항
- 팝업 크기: 이미지 비율 자동 (width 고정, height auto)
- 동일 페이지에 복수 팝업: 우선순위 순으로 정렬 후 좌표 기반 개별 표시 (겹침 없음)
- 닫기 동작: X 버튼 즉시 닫기 + "오늘 하루 보지 않기" (쿠키 24h, 체크 시 즉시 닫힘)
- 특정 페이지 지정: `menus` 테이블 기반 체크박스 다중 선택 → `popup_pages` 중간 테이블
- 좌표 기본값: X=20, Y=20 (미입력 시)

### 구현 파일
| 파일 | 설명 |
|---|---|
| `app/Database/Migrations/2024-01-01-000008_CreatePopupsTable.php` | popups + popup_pages 테이블 |
| `app/Models/PopupModel.php` | 모델, `getActiveForPage()`, `syncPages()` (트랜잭션) |
| `app/Libraries/ImageUploader.php` | 이미지 업로드 (`new ImageUploader('popups')`) |
| `app/Controllers/Admin/PopupController.php` | 관리자 CRUD |
| `app/Controllers/BaseController.php` | `$activePopups` 전역 주입 |
| `app/Views/admin/popups/list.php` | 관리자 목록 뷰 |
| `app/Views/admin/popups/form.php` | 등록/수정 폼 (TinyMCE + 이미지 + 메뉴 체크박스) |
| `app/Views/themes/default/components/popups.php` | 프론트 팝업 오버레이 렌더링 |
| `public/themes/default/js/popup.js` | 표시/닫기/쿠키 JS |
| `app/Views/themes/default/layouts/main.php` | 팝업 컴포넌트 삽입 |
| `app/Views/themes/spring/layouts/main.php` | spring 테마 팝업 컴포넌트 삽입 |
| `dark.zip / views/layouts/main.php` | dark 테마 팝업 컴포넌트 삽입 |
| `dark.zip / public/css/style.css` | `.site-popup` 다크 스타일 추가 |

### 주요 트러블슈팅
| 항목 | 원인 | 해결 |
|---|---|---|
| spring 팝업 `position:static` | spring CSS에 팝업 스타일 미포함 | `spring/css/style.css`에 `.site-popup` 블록 추가 |

---

## 3. 성능 개선 ✅ 완료

### 배너·팝업 조회 캐싱
- 매 프론트 요청마다 실행되던 배너 1쿼리 + 팝업 2쿼리를 캐시로 제거 (캐시 1시간)
- 활성 데이터 전체를 캐시하고 **노출 기간(started_at/ended_at)·스코프 판정은 PHP에서 처리** → 캐시가 살아있어도 시간 경과에 따른 노출/종료 정확히 동작
- 팝업은 URI별 캐시 키 증가 없이 단일 키(`active_popups`)로 팝업 + 페이지 URL 매핑 저장
- 캐시 무효화: 모델 콜백(`afterInsert/Update/Delete`) + `syncPages()` → 관리자 수정 즉시 반영
- 부수 효과: 날짜 문자열 SQL 직접 보간 패턴 제거

### DB 인덱스 추가 (`php spark migrate` 필요)
| 인덱스 | 용도 |
|---|---|
| `posts (board_id, is_notice, id)` | 게시판 목록·페이징·카운트 — filesort 제거 |
| `posts (deleted_at)` / `post_comments (deleted_at)` | 소프트 삭제 `deleted_at IS NULL` 조건 |
| `banners (position, is_active)` | 캐시 미스 시 활성 배너 조회 |
| `popups (is_active, show_scope)` | 캐시 미스 시 활성 팝업 조회 |
| `inquiries (is_read)` | 관리자 전 페이지 미읽음 문의 카운트 |

### 구현 파일
| 파일 | 설명 |
|---|---|
| `app/Models/BannerModel.php` | `getActiveByPosition()` 캐싱 + 무효화 콜백 |
| `app/Models/PopupModel.php` | `getActiveForPage()` 캐싱 + 무효화 콜백 |
| `app/Database/Migrations/2026-06-10-000001_AddPerformanceIndexes.php` | 인덱스 마이그레이션 |

### 비고
- 기존 캐시(설정 `site_settings`, 메뉴 `nav_menus`)와 합쳐 일반 페이지는 게시판 데이터 외 반복 쿼리 없음
- 게시판 검색은 `LIKE '%키워드%'` 구조라 인덱스 불가 — 수만 건 이상 쌓이면 FULLTEXT 인덱스 전환 검토

---

## 4. 쇼핑 기능 (shop 템플릿) ⬜ 예정

### 4-1. 상품 목록 페이지

#### 등록 항목 (관리자)
- 상품명 · 카테고리 · 가격 · 할인가 · 재고 수량
- 대표 이미지 + 추가 이미지 다중 업로드
- 상품 상태: 판매중 / 품절 / 숨김
- 상품 상세 내용 (WYSIWYG 에디터)
- 배송비 설정 (무료 / 고정 금액 / 조건부 무료)

#### 프론트 노출
- 카테고리별 필터링 · 가격순/신상품순 정렬
- 페이징 · 검색 (상품명)
- 품절 배지 자동 표시 (`products.stock = 0` 기준)
- **품절 여부 표시는 캐시** — `products.stock` 캐시로 목록 조회 부하 감소, 실제 차감은 항상 DB 직접 처리

#### 구현 파일 (예정)
| 파일 | 설명 |
|---|---|
| `app/Database/Migrations/*_CreateShopTables.php` | products · categories · product_images 테이블 |
| `app/Models/ProductModel.php` | 상품 모델, 목록·검색·재고 처리 |
| `app/Controllers/Admin/ProductController.php` | 관리자 상품 CRUD |
| `app/Controllers/Front/ShopController.php` | 프론트 상품 목록 |
| `app/Views/shop/list.php` | 상품 목록 뷰 |

---

### 4-2. 상품 상세 페이지

#### 기능
- 대표 이미지 슬라이더 + 썸네일
- 가격 / 할인가 / 할인율 표시
- 수량 선택 · 바로구매 · 장바구니 담기 버튼
- 상품 상세 내용 탭 (상세정보 / 배송·교환·반품 안내)
- 재고 0 시 버튼 비활성화

#### 구현 파일 (예정)
| 파일 | 설명 |
|---|---|
| `app/Controllers/Front/ShopController.php` | `detail()` 메서드 추가 |
| `app/Views/shop/detail.php` | 상품 상세 뷰 |

---

### 4-3. 장바구니

#### 기능
- 로그인 회원: DB 저장 (`cart_items` 테이블)
- 비로그인: 세션 저장 → 로그인 시 DB 병합
- 수량 변경 · 개별 삭제 · 전체 삭제
- 선택 합계 금액 실시간 계산
- 배송비 조건 표시

#### 재고 처리 원칙
- **장바구니 담기 시점엔 재고 차감하지 않음** — 결제 확정 시점에만 차감 (오픈마켓 표준 방식)
- 담기 시점에 재고 0이면 "품절" 안내 후 담기 차단
- 장바구니 목록 조회 시 현재 재고 재확인 → 품절 전환된 상품 표시

#### 구현 파일 (예정)
| 파일 | 설명 |
|---|---|
| `app/Database/Migrations/*_CreateCartTable.php` | cart_items 테이블 |
| `app/Models/CartModel.php` | 장바구니 모델 (세션·DB 통합) |
| `app/Controllers/Front/CartController.php` | 담기 · 수정 · 삭제 · 목록 |
| `app/Views/shop/cart.php` | 장바구니 뷰 |

---

### 4-4. 주문 (결제)

#### 기능
- 장바구니 → 주문서 작성 (배송지 · 받는 사람 · 연락처)
- 최근 배송지 자동 입력 (로그인 회원)
- 쿠폰 · 포인트 적용란 (선택)
- 결제 수단 선택 → PG 연동
- 주문 완료 후 재고 차감 + 주문 이력 저장

#### 재고 차감 메커니즘
결제 확정 시점에 **트랜잭션 + 행 잠금 + 조건부 UPDATE** 2중 방어로 동시 주문 충돌을 차단한다.

```
트랜잭션 시작
  ↓
SELECT stock FROM products WHERE id = ? FOR UPDATE  ← 행 잠금
  ↓
재고 부족? → 롤백 → "품절" 응답
  ↓ 충분
orders + order_items INSERT  (상태: pending)
  ↓
UPDATE products
  SET stock = stock - ?
  WHERE id = ? AND stock >= ?          ← 동시 요청 충돌 방어선
  ↓
영향 행 0? → 롤백 → "재고 부족" 응답
  ↓ 1행
트랜잭션 커밋 → PG 결제창 호출
```

- `FOR UPDATE`: 동시 요청이 같은 행을 동시에 차감하는 것을 직렬화
- `AND stock >= ?`: 잠금 사이에 끼어든 요청도 영향 행 0으로 차단 (이중 방어)
- 재고 차감 타이밍은 **PG 결제 성공 콜백** 시점 (`paid` 상태 전환 시)

#### 재고 복구 시나리오
| 상황 | 처리 |
|---|---|
| 결제창 이탈 / 시간 초과 | `orders` 상태 `pending` 유지 → 스케줄러가 N분 후 `expired` 처리 |
| PG 결제 실패 | 콜백에서 `failed` 상태 저장, 차감 없음 (차감은 성공 콜백에서만) |
| 주문 취소 (마이페이지) | `stock = stock + 수량` 복구 + 주문 상태 `cancelled` |
| 관리자 강제 취소 | 동일하게 재고 복구 처리 |

#### 이중 결제 방지
- `payments` 테이블 `pg_tid` (PG 거래 ID) 컬럼에 `UNIQUE` 제약
- PG 콜백 중복 수신 시 INSERT 실패 → 이미 처리된 거래로 무시

#### 구현 파일 (예정)
| 파일 | 설명 |
|---|---|
| `app/Database/Migrations/*_CreateOrderTables.php` | orders · order_items · shipping_addresses · payments 테이블 |
| `app/Models/OrderModel.php` | 주문 모델, 재고 차감 트랜잭션 포함 |
| `app/Models/ProductModel.php` | `decreaseStock()` — FOR UPDATE + 조건부 UPDATE |
| `app/Controllers/Front/OrderController.php` | 주문서 · 결제 요청 · 완료 처리 |
| `app/Controllers/Front/PaymentController.php` | PG 콜백 수신 · 금액 검증 · 재고 차감 · 이중처리 방지 |
| `app/Views/shop/checkout.php` | 주문서 뷰 |
| `app/Views/shop/order_complete.php` | 주문 완료 뷰 |

---

### 4-5. PG 연동

#### 지원 예정 PG
| PG | 연동 방식 |
|---|---|
| 토스페이먼츠 | 클라이언트 SDK + 서버 승인 API |
| KG이니시스 | 표준결제창 (INIStdPay.js) |
| 나이스페이 | 나이스페이 JS SDK |

#### 흐름
1. 주문서 → 클라이언트에서 PG SDK 결제창 호출
2. 결제 완료 콜백 → 서버에서 PG API로 **금액 재검증** (위변조 방지)
3. 검증 통과 → 주문 상태 `paid` 저장 + 재고 차감
4. 실패/취소 → 주문 상태 `failed` 처리

#### 구현 파일 (예정)
| 파일 | 설명 |
|---|---|
| `app/Libraries/PG/AbstractPGProvider.php` | PG 공통 인터페이스 |
| `app/Libraries/PG/TossPayProvider.php` | 토스페이먼츠 구현체 |
| `app/Libraries/PG/InicisProvider.php` | KG이니시스 구현체 |
| `app/Libraries/PG/NicePayProvider.php` | 나이스페이 구현체 |
| `app/Controllers/Front/PaymentController.php` | 결제 요청 · 콜백 · 검증 |
| `app/Config/PG.php` | PG 키 설정 (.env 참조) |

---

### 4-6. 주문 목록 / 주문 상세 (마이페이지)

#### 주문 목록
- 기간 필터 (1개월 / 3개월 / 전체)
- 주문 상태별 탭: 결제완료 · 배송준비 · 배송중 · 배송완료 · 취소/반품
- 주문번호 · 상품명 · 결제금액 · 상태 표시

#### 주문 상세
- 주문 상품 목록 + 수량 + 금액
- 배송지 정보
- 결제 수단 · 결제 금액 내역
- 취소 요청 버튼 (결제완료 상태에서만 활성)

#### 구현 파일 (예정)
| 파일 | 설명 |
|---|---|
| `app/Controllers/Front/MyPageController.php` | 주문 목록 · 상세 · 취소 요청 |
| `app/Views/shop/orders/list.php` | 주문 목록 뷰 |
| `app/Views/shop/orders/detail.php` | 주문 상세 뷰 |

---

### 4-7. 주문 관리 (관리자)

#### 기능
- 전체 주문 목록 · 상태별 필터 · 키워드 검색
- 주문 상태 변경 (결제확인 → 배송준비 → 배송중 → 배송완료)
- 송장번호 입력
- 주문 강제 취소 · 환불 처리

#### 구현 파일 (예정)
| 파일 | 설명 |
|---|---|
| `app/Controllers/Admin/OrderController.php` | 관리자 주문 관리 |
| `app/Views/admin/orders/list.php` | 관리자 주문 목록 |
| `app/Views/admin/orders/detail.php` | 관리자 주문 상세 |

---

### DB 스키마 개요 (예정)

```
products           — 상품 기본 정보 (가격·재고·상태)
product_images     — 상품 이미지 (대표·추가, 순서)
categories         — 카테고리 (계층 구조)
cart_items         — 장바구니 (user_id 또는 session_id)
orders             — 주문 헤더 (주문번호·상태·결제금액)
order_items        — 주문 상품 라인 (상품·수량·단가 스냅샷)
shipping_addresses — 배송지 (주문 시 스냅샷 + 회원 저장 주소)
payments           — 결제 이력, pg_tid UNIQUE (이중 결제 방지)
```

### 재고 관련 핵심 설계 원칙 요약

| 원칙 | 내용 |
|---|---|
| 차감 타이밍 | 장바구니 담기 X, **PG 결제 성공 콜백 시점에만** 차감 |
| 동시성 방어 | `FOR UPDATE` 행 잠금 + `WHERE stock >= ?` 조건부 UPDATE 2중 방어 |
| 품절 표시 | 목록 조회는 캐시, 실제 차감은 항상 DB 직접 |
| 재고 복구 | 취소·실패·만료 모두 명시적으로 `stock + 수량` 복구 |
| 이중 결제 | `payments.pg_tid` UNIQUE 제약으로 PG 콜백 중복 처리 차단 |
| 만료 처리 | 결제창 이탈 주문(`pending`)은 스케줄러로 N분 후 `expired` 전환 |
