<?php

declare(strict_types=1);

namespace Tests\Database;

use App\Models\MenuModel;
use Tests\Support\DatabaseTestCase;

/**
 * @internal
 */
final class MenuModelTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // 시드 메뉴를 비워 결정적인 트리를 구성한다.
        $this->db->table('menus')->where('id >', 0)->delete();
        cache()->delete('nav_menus');
    }

    public function testGetTreeNestsChildrenUnderParent(): void
    {
        $model    = new MenuModel();
        $parentId = (int) $model->insert([
            'parent_id'  => null,
            'title'      => '회사소개',
            'url'        => '/about',
            'sort_order' => 1,
            'is_active'  => 1,
        ]);
        $model->insert([
            'parent_id'  => $parentId,
            'title'      => '연혁',
            'url'        => '/about/history',
            'sort_order' => 1,
            'is_active'  => 1,
        ]);
        cache()->delete('nav_menus');

        $tree = (new MenuModel())->getTree();

        $this->assertCount(1, $tree);
        $this->assertSame('회사소개', $tree[0]['title']);
        $this->assertCount(1, $tree[0]['children']);
        $this->assertSame('연혁', $tree[0]['children'][0]['title']);
    }

    public function testGetTreeExcludesInactiveMenus(): void
    {
        (new MenuModel())->insert([
            'parent_id'  => null,
            'title'      => '비활성',
            'url'        => '/hidden',
            'sort_order' => 1,
            'is_active'  => 0,
        ]);
        cache()->delete('nav_menus');

        $this->assertCount(0, (new MenuModel())->getTree());
    }
}
