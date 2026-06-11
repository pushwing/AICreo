<?php

namespace App\Libraries\PG;

class PGFactory
{
    public static function make(string $provider): PGInterface
    {
        return match ($provider) {
            'bank_transfer' => new BankTransferAdapter(),
            'toss'          => new TossPaymentsAdapter(),
            'inicis'        => new InicisAdapter(),
            'nicepay'       => new NicePayAdapter(),
            'kakaopay'      => new KakaoPayAdapter(),
            'naverpay'      => new NaverPayAdapter(),
            'payco'         => new PaycoAdapter(),
            default         => throw new \InvalidArgumentException("지원하지 않는 PG: {$provider}"),
        };
    }

    public static function providers(): array
    {
        return ['bank_transfer', 'toss', 'inicis', 'nicepay', 'kakaopay', 'naverpay', 'payco'];
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
