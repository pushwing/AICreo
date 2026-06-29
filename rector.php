<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/tests',
    ])
    // 뷰 템플릿은 리팩토링 대상에서 제외
    ->withSkip([
        __DIR__ . '/app/Views',
    ])
    // CLAUDE.md 기준 최소 지원 버전(PHP 8.1)에 맞춰 문법 현대화
    ->withPhpSets(php81: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
    )
    ->withImportNames(removeUnusedImports: true);
