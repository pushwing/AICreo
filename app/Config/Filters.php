<?php

declare(strict_types=1);

namespace Config;

use App\Filters\AuthFilter;
use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\Honeypot;

class Filters extends BaseConfig
{
    public array $aliases = [
        'csrf'     => CSRF::class,
        'toolbar'  => DebugToolbar::class,
        'honeypot' => Honeypot::class,
        'auth'     => AuthFilter::class,   // ← 추가
    ];
    public array $globals = [
        'before' => [
            'csrf' => ['except' => ['api/*', 'board/image-upload', 'admin/media/upload']],
        ],
        'after' => ['toolbar'],
    ];
    public array $methods = [];
    public array $filters = [];
}
