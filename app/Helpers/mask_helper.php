<?php

declare(strict_types=1);

if (! function_exists('mask_name')) {
    /**
     * 공개 노출용 작성자명 마스킹 (개인정보·색인 방지).
     * 첫 글자와 끝 글자만 남기고 가운데를 * 로 치환.
     *   홍길동 → 홍*동 / 김철수 → 김*수 / 홍길 → 홍* / 홍 → 홍
     */
    function mask_name(?string $name): string
    {
        $name = trim((string) $name);
        $len  = mb_strlen($name);

        if ($len <= 1) {
            return $name;
        }

        if ($len === 2) {
            return mb_substr($name, 0, 1) . '*';
        }

        return mb_substr($name, 0, 1) . str_repeat('*', $len - 2) . mb_substr($name, -1);
    }
}
