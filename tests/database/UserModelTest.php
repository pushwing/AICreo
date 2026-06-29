<?php

declare(strict_types=1);

namespace Tests\Database;

use App\Models\UserModel;
use Tests\Support\DatabaseTestCase;

/**
 * @internal
 */
final class UserModelTest extends DatabaseTestCase
{
    private UserModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new UserModel();
    }

    public function testFindByEmailReturnsSeededAdmin(): void
    {
        $user = $this->model->findByEmail('admin@example.com');

        $this->assertIsArray($user);
        $this->assertSame('admin', $user['role']);
    }

    public function testFindByEmailReturnsNullForUnknown(): void
    {
        $this->assertNull($this->model->findByEmail('nobody@example.com'));
    }

    public function testFindByEmailIgnoresInactiveUser(): void
    {
        $this->model->insert([
            'username'  => 'dormant',
            'email'     => 'dormant@example.com',
            'password'  => password_hash('secret123', PASSWORD_DEFAULT),
            'nickname'  => '휴면',
            'role'      => 'member',
            'is_active' => 0,
        ]);

        $this->assertNull($this->model->findByEmail('dormant@example.com'));
    }

    public function testUpdateLastLoginSetsTimestamp(): void
    {
        $id = $this->model->insert([
            'username' => 'loginuser',
            'email'    => 'login@example.com',
            'password' => password_hash('secret123', PASSWORD_DEFAULT),
            'nickname' => '로그인',
            'role'     => 'member',
        ]);

        $this->model->updateLastLogin((int) $id);

        $this->assertNotNull($this->model->find($id)['last_login']);
    }
}
