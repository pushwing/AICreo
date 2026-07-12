# 브랜치 & CI 워크플로우

1. `feature/*` 브랜치에서 작업 후 `dev`로 PR.
2. `dev`에서 테스트·리뷰 후 `main`으로 PR.
3. `main`/`dev` 대상 push·PR 시 GitHub Actions(`.github/workflows/ci.yml`)가 실행:
   - **quality 잡** — `composer cs` (스타일) · `composer analyse` (PHPStan) · `composer test` (PHPUnit), PHP 8.5 / MySQL 8.0.
   - **coverage 잡** — 커버리지 리포트를 PR 코멘트로 게시.
4. 로컬에서 push 전 `composer ci`로 동일 검증을 먼저 통과시킬 것.

## 예외 — 문서 전용 변경

`*.md`, `docs/`, `CLAUDE.md`, `.claude/` 등 **문서·설정 문서만 수정하는 경우**에는 `feature/*` 브랜치와 PR 절차를 거치지 않고 `dev`에 직접 커밋·푸시합니다. 코드(PHP 등) 변경이 한 줄이라도 섞이면 이 예외는 적용되지 않으며, 위 1~4의 브랜치·PR 흐름을 따릅니다.
