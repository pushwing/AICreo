<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Filters extends BaseConfig
{
    public array $aliases = [
        'csrf'     => \CodeIgniter\Filters\CSRF::class,
        'toolbar'  => \CodeIgniter\Filters\DebugToolbar::class,
        'honeypot' => \CodeIgniter\Filters\Honeypot::class,
        'auth'     => \App\Filters\AuthFilter::class,   // ← 추가
    ];

    public array $globals = [
        'before' => [
            'csrf' => ['except' => ['api/*']],
        ],
        'after'  => ['toolbar'],
    ];

    public array $methods = [];
    public array $filters = [];
}
