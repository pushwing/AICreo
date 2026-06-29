<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\MenuModel;
use Tests\Support\AdminTestCase;

/**
 * @internal
 */
final class MenuTest extends AdminTestCase
{
    public function testAdminCreatesMenu(): void
    {
        $result = $this->withSession($this->adminSession)->post('admin/menus', [
            'title'      => '오시는길',
            'url'        => '/location',
            'sort_order' => 5,
        ]);

        $result->assertRedirectTo('/admin/menus');
        $this->assertSame(1, (new MenuModel())->where('url', '/location')->countAllResults());
    }

    public function testAdminDeletesMenu(): void
    {
        $model = new MenuModel();
        $id    = (int) $model->insert([
            'parent_id'  => null,
            'title'      => '임시메뉴',
            'url'        => '/temp',
            'sort_order' => 9,
            'is_active'  => 1,
        ]);

        $result = $this->withSession($this->adminSession)->post("admin/menus/{$id}/delete");

        $result->assertRedirectTo('/admin/menus');
        $this->assertNull($model->find($id));
    }
}
