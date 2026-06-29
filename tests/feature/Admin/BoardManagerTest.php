<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\BoardModel;
use Tests\Support\AdminTestCase;

/**
 * @internal
 */
final class BoardManagerTest extends AdminTestCase
{
    public function testAdminCreatesBoard(): void
    {
        $result = $this->withSession($this->adminSession)->post('admin/boards/create', [
            'slug'             => 'gallery',
            'name'             => '갤러리',
            'read_permission'  => 'guest',
            'write_permission' => 'member',
        ]);

        $result->assertRedirectTo('/admin/boards');
        $this->assertSame('갤러리', (new BoardModel())->getBySlug('gallery')['name']);
    }

    public function testCreateRejectsDuplicateSlug(): void
    {
        // notice 는 시드 게시판
        $result = $this->withSession($this->adminSession)->post('admin/boards/create', [
            'slug' => 'notice',
            'name' => '중복공지',
        ]);

        $result->assertRedirect();
        $this->assertSame(1, (new BoardModel())->where('slug', 'notice')->countAllResults());
    }

    public function testAdminUpdatesBoard(): void
    {
        $boardId = (int) (new BoardModel())->getBySlug('free')['id'];

        $result = $this->withSession($this->adminSession)->post("admin/boards/{$boardId}/edit", [
            'name'             => '수정된 자유게시판',
            'read_permission'  => 'guest',
            'write_permission' => 'member',
            'is_active'        => 1,
        ]);

        $result->assertRedirectTo('/admin/boards');
        $this->assertSame('수정된 자유게시판', (new BoardModel())->find($boardId)['name']);
    }
}
