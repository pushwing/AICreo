<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\MenuModel;

class MenuController extends BaseController
{
    private MenuModel $menuModel;

    public function __construct()
    {
        $this->menuModel = new MenuModel();
    }

    public function index()
    {
        return $this->render('admin/menus/index', [
            'menus' => $this->menuModel->orderBy('sort_order')->findAll(),
        ]);
    }

    public function store()
    {
        $this->menuModel->insert([
            'parent_id'  => $this->request->getPost('parent_id') ?: null,
            'title'      => $this->request->getPost('title'),
            'url'        => $this->request->getPost('url'),
            'target'     => $this->request->getPost('target') ?: '_self',
            'sort_order' => (int) $this->request->getPost('sort_order'),
            'is_active'  => 1,
        ]);
        $this->menuModel->clearCache();

        return redirect()->to('/admin/menus')->with('success', '메뉴가 추가되었습니다.');
    }

    public function update(int $id)
    {
        $this->menuModel->update($id, [
            'title'      => $this->request->getPost('title'),
            'url'        => $this->request->getPost('url'),
            'target'     => $this->request->getPost('target') ?: '_self',
            'sort_order' => (int) $this->request->getPost('sort_order'),
            'is_active'  => (int) $this->request->getPost('is_active'),
        ]);
        $this->menuModel->clearCache();

        return redirect()->to('/admin/menus')->with('success', '수정되었습니다.');
    }

    public function delete(int $id)
    {
        $this->menuModel->delete($id);
        $this->menuModel->clearCache();

        return redirect()->to('/admin/menus')->with('success', '삭제되었습니다.');
    }
}
