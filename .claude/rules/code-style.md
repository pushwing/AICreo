# 코딩 표준

- **PHP 8.5+ 필수** (`composer.json`의 `require`/`platform` 고정). typed property, match, arrow function 등 적극 사용.
- **PSR-12 준수**, 파일 상단에 `declare(strict_types=1)` 선언. 함수 인자·반환 타입을 항상 명시 (PHPStan 레벨 6 통과).
- **비즈니스 로직은 Controller가 아닌 Model/Library로 캡슐화.** Controller는 입력 검증 → 위임 → 응답만 담당.
- 모든 Model은 `$allowedFields`를 명시하고, 뷰는 네이티브 PHP 대체 문법과 `esc()`를 사용.
- DB 접근은 Query Builder만 사용 — 문자열 연결 raw SQL 금지.
