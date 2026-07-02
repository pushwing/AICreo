<?php

declare(strict_types=1);

namespace Tests\Support;

use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * 컨트롤러 HTTP(Feature) 테스트 공용 베이스.
 *
 * - 앱 마이그레이션(시드 포함)은 실행당 1회, 각 테스트는 트랜잭션 롤백으로 격리
 * - CSRF 필터는 토큰 없는 POST 를 redirect-back 시키므로 테스트에서는 비활성화
 *
 * @internal
 */
abstract class FeatureTestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace   = 'App';

    protected function setUp(): void
    {
        parent::setUp();
        $this->db->transBegin();

        // CSRF 전역 필터 제거 (HTTP 테스트는 토큰을 동봉하지 않음)
        $filters = config('Filters');
        unset($filters->globals['before']['csrf']);
        Factories::injectMock('config', 'Filters', $filters);
    }

    protected function tearDown(): void
    {
        $this->db->transRollback();
        parent::tearDown();
    }
}
