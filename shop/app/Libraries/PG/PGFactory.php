<?php

namespace App\Libraries\PG;

class PGFactory
{
    private static array $map = [
        'bank_transfer' => BankTransferAdapter::class,
        'toss'          => TossPaymentsAdapter::class,
        'inicis'        => InicisAdapter::class,
        'nicepay'       => NicePayAdapter::class,
        'kakaopay'      => KakaoPayAdapter::class,
        'naverpay'      => NaverPayAdapter::class,
        'payco'         => PaycoAdapter::class,
    ];

    public static function make(string $provider): PGInterface
    {
        $class = self::$map[$provider] ?? null;
        if (! $class) {
            throw new \InvalidArgumentException("지원하지 않는 PG: {$provider}");
        }
        return new $class();
    }

    public static function providers(): array
    {
        return array_keys(self::$map);
    }

    public static function labels(): array
    {
        return [
            'bank_transfer' => '무통장입금',
            'toss'          => '토스페이먼츠',
            'inicis'        => 'KG이니시스',
            'nicepay'       => '나이스페이',
            'kakaopay'      => '카카오페이',
            'naverpay'      => '네이버페이',
            'payco'         => 'PAYCO',
        ];
    }
}
