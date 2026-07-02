# CI4 게시판 모듈 설치 가이드

## 1. 설치
```bash
composer create-project codeigniter4/appstarter my-project
cd my-project
```

## 2. 이 모듈 파일 복사
`app/` 폴더 내 파일을 모두 복사

## 3. .env 설정
```
CI_ENVIRONMENT = development
database.default.hostname = localhost
database.default.database = your_db
database.default.username = root
database.default.password = 
database.default.DBDriver = MySQLi
```

## 4. 타임존 설정
`app/Config/App.php`에서 한국 시간대로 변경:
```php
public string $appTimezone = 'Asia/Seoul';
```

## 5. 마이그레이션 실행
```bash
php spark migrate
```

## 6. 기본 계정
- 이메일: admin@example.com
- 비밀번호: admin1234!

## 7. 업로드 폴더 권한 (Linux)
```bash
chmod -R 755 public/uploads
```

## 8. 테스트 (PHPUnit)
운영과 동일한 MySQL 스키마로 테스트하므로 **운영 DB와 분리된 테스트 DB**가 필요합니다.

```bash
# 1) 테스트 DB 생성 (운영 DB와 다른 이름)
php spark db:create ci4-agency_test

# 2) .env 에 테스트 DB 자격증명 추가 (운영 DB와 분리, 데이터가 매 실행 초기화됨)
#   database.tests.hostname = localhost
#   database.tests.database = ci4-agency_test
#   database.tests.username = <user>
#   database.tests.password = <pass>
#   database.tests.DBDriver = MySQLi

# 3) 실행
composer test        # = phpunit
composer ci          # 코드 스타일 + 정적분석 + 테스트
composer coverage    # 커버리지 측정(텍스트 + build/coverage-html/) — Xdebug/pcov 필요
```
> CI(GitHub Actions)는 MySQL 서비스 컨테이너를 띄워 동일하게 검증하며, 커버리지를 측정해 잡 요약과 PR 코멘트로 리포트합니다.

## URL 구조
| URL | 설명 |
|-----|------|
| /board/notice | 공지사항 목록 |
| /board/free | 자유게시판 목록 |
| /board/qna | 문의게시판 목록 |
| /board/{slug}/write | 글쓰기 |
| /board/{slug}/{id} | 글 보기 |
| /board/file/{id}/download | 파일 다운로드 |
| /admin/boards | 게시판 관리 (관리자) |
| /auth/login | 로그인 |
| /auth/register | 회원가입 |
