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

**Git 훅 활성화 (클론 후 1회):**
```bash
git config core.hooksPath .githooks
```
`.githooks/pre-commit`이 커밋 직전 스테이징된 PHP 파일에 PHP-CS-Fixer(`composer cs:fix` 규칙)를 자동 적용합니다. 건너뛰려면 `git commit --no-verify`.

## 상세 규칙 (모듈)

아래 규칙 문서는 `.claude/rules/`로 분리되어 있으며, 각 임포트는 Claude Code가 자동으로 불러옵니다.

- **아키텍처** (테마 시스템, BaseController, 인증·라우팅, CSRF 예외, 캐싱, OAuth, 파일 업로드, DB 스키마): @.claude/rules/architecture.md
- **코딩 표준** (PHP 8.5 / PSR-12 / 계층 분리 / Query Builder): @.claude/rules/code-style.md
- **브랜치 & CI 워크플로우** (feature→dev→main, GitHub Actions): @.claude/rules/git-workflow.md
