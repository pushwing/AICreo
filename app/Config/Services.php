<?php

namespace Config;

use App\Libraries\ThemeView;
use CodeIgniter\Config\BaseService;

class Services extends BaseService
{
    public static function renderer(?string $viewPath = null, ?array $config = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('renderer', $viewPath, $config);
        }

        $viewPath ??= config('Paths')->viewDirectory;
        $config   ??= config('View');

        return new ThemeView($config, $viewPath, static::logger());
    }
}
