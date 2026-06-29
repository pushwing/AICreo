<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\UserModel;
use Tests\Support\AdminTestCase;

/**
 * @internal
 */
final class UserTest extends AdminTestCase
{
    private function makeMember(): int
    {
        return (int) (new UserModel())->insert([
            'username' => 'target',
            'email'    => 'target@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'nickname' => '대상회원',
            'role'     => 'member',
        ]);
    }

    public function testAdminUpdatesMemberRole(): void
    {
        $id = $this->makeMember();

        $result = $this->withSession($this->adminSession)->post("admin/users/{$id}/edit", [
            'nickname'  => '승격회원',
            'role'      => 'admin',
            'is_active' => 1,
        ]);

        $result->assertRedirectTo('/admin/users');
        $this->assertSame('admin', (new UserModel())->find($id)['role']);
    }

    public function testAdminCannotEditOwnAccount(): void
    {
        // 세션 user_id = 1 (시드 관리자) 자기 자신 수정 시도
        $result = $this->withSession($this->adminSession)->post('admin/users/1/edit', [
            'nickname'  => '변경시도',
            'role'      => 'member',
            'is_active' => 1,
        ]);

        $result->assertRedirect();
        $this->assertSame('admin', (new UserModel())->find(1)['role']);
    }

    public function testAdminDeletesMember(): void
    {
        $id = $this->makeMember();

        $result = $this->withSession($this->adminSession)->post("admin/users/{$id}/delete");

        $result->assertRedirectTo('/admin/users');
        $this->assertNull((new UserModel())->find($id));
    }

    public function testAdminCannotDeleteOwnAccount(): void
    {
        $result = $this->withSession($this->adminSession)->post('admin/users/1/delete');

        $result->assertRedirect();
        $this->assertNotNull((new UserModel())->find(1));
    }
}
