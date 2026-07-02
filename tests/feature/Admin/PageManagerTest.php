<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\PageModel;
use Tests\Support\AdminTestCase;

/**
 * @internal
 */
final class PageManagerTest extends AdminTestCase
{
    public function testAdminCreatesPage(): void
    {
        $result = $this->withSession($this->adminSession)->post('admin/pages/create', [
            'slug'    => 'about-us',
            'title'   => '회사소개',
            'content' => '<p>내용</p>',
        ]);

        $result->assertRedirectTo('/admin/pages');
        $this->assertSame('회사소개', (new PageModel())->getBySlug('about-us')['title']);
    }

    public function testCreateRejectsInvalidSlug(): void
    {
        $result = $this->withSession($this->adminSession)->post('admin/pages/create', [
            'slug'  => '한글 슬러그',
            'title' => '잘못된 슬러그',
        ]);

        $result->assertRedirect();
        $this->assertSame(0, (new PageModel())->where('title', '잘못된 슬러그')->countAllResults());
    }

    public function testCreateRejectsDuplicateSlug(): void
    {
        $model = new PageModel();
        $model->insert(['slug' => 'dup-page', 'title' => '기존', 'status' => 'published']);

        $result = $this->withSession($this->adminSession)->post('admin/pages/create', [
            'slug'  => 'dup-page',
            'title' => '중복',
        ]);

        $result->assertRedirect();
        $this->assertSame(1, $model->where('slug', 'dup-page')->countAllResults());
    }

    public function testAdminDeletesPage(): void
    {
        $model = new PageModel();
        $id    = (int) $model->insert(['slug' => 'temp-page', 'title' => '임시', 'status' => 'published']);

        $result = $this->withSession($this->adminSession)->post("admin/pages/{$id}/delete");

        $result->assertRedirectTo('/admin/pages');
        $this->assertNull($model->find($id));
    }
}
