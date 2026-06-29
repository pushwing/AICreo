<?php

declare(strict_types=1);

namespace Tests\Support;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * DB 연동 모델 테스트 공용 베이스.
 *
 * 앱 마이그레이션(시드 포함)은 테스트 실행당 1회만 수행하고,
 * 각 테스트는 트랜잭션으로 감싸 롤백하여 격리한다
 * (DatabaseTestTrait 은 자동 롤백을 제공하지 않으므로 수동 처리).
 *
 * @internal
 */
abstract class DatabaseTestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace   = 'App';

    protected function setUp(): void
    {
        parent::setUp();
        $this->db->transBegin();
    }

    protected function tearDown(): void
    {
        $this->db->transRollback();
        parent::tearDown();
    }
}
