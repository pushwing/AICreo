<?php

namespace App\Models;

use CodeIgniter\Model;

class MenuModel extends Model
{
    protected $table         = 'menus';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['parent_id', 'title', 'url', 'target', 'sort_order', 'is_active'];

    /**
     * 트리 구조로 메뉴 반환 (캐시 1시간)
     *
     * @return list<array<string, mixed>>
     */
    public function getTree(): array
    {
        return cache()->remember('nav_menus', 3600, function (): array {
            $all  = $this->where('is_active', 1)->orderBy('sort_order')->findAll();
            $tree = [];
            $map  = [];

            foreach ($all as $item) {
                $item['children'] = [];
                $map[$item['id']] = $item;
            }

            // 참조로 연결해야 부모를 트리에 넣은 뒤에도 자식 추가가 반영된다.
            foreach ($map as $id => $item) {
                if ($item['parent_id'] && isset($map[$item['parent_id']])) {
                    $map[$item['parent_id']]['children'][] = &$map[$id];
                } else {
                    $tree[] = &$map[$id];
                }
            }
            unset($item);

            return $tree;
        });
    }

    public function clearCache(): void
    {
        cache()->delete('nav_menus');
    }
}
