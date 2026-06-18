<?php

declare(strict_types=1);

// PHPStan이 CI4 경로 상수를 인식할 수 있도록 정의 (realpath로 심볼릭링크 해소)
defined('HOMEPATH')   || define('HOMEPATH', realpath(__DIR__) . DIRECTORY_SEPARATOR);
defined('CONFIGPATH') || define('CONFIGPATH', realpath(__DIR__ . '/app/Config') . DIRECTORY_SEPARATOR);
defined('PUBLICPATH') || define('PUBLICPATH', realpath(__DIR__ . '/public') . DIRECTORY_SEPARATOR);

require_once __DIR__ . '/vendor/codeigniter4/framework/system/Test/bootstrap.php';
