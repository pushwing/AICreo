<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class SupplierController extends BaseController
{
    public function index(): string
    {
        $db        = \Config\Database::connect();
        $suppliers = $db->table('suppliers')->orderBy('name', 'ASC')->get()->getResultArray();

        return $this->render('admin/suppliers/list', compact('suppliers'));
    }

    public function create(): string
    {
        return $this->render('admin/suppliers/form', ['supplier' => null]);
    }

    public function store(): \CodeIgniter\HTTP\RedirectResponse
    {
        $db = \Config\Database::connect();
        $db->table('suppliers')->insert([
            'name'           => trim($this->request->getPost('name') ?? ''),
            'contact_person' => trim($this->request->getPost('contact_person') ?? '') ?: null,
            'phone'          => trim($this->request->getPost('phone') ?? '') ?: null,
            'email'          => trim($this->request->getPost('email') ?? '') ?: null,
            'memo'           => trim($this->request->getPost('memo') ?? '') ?: null,
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('/admin/suppliers')->with('success', '매입처가 등록되었습니다.');
    }

    public function edit(int $id): \CodeIgniter\HTTP\RedirectResponse|string
    {
        $db       = \Config\Database::connect();
        $supplier = $db->table('suppliers')->where('id', $id)->get()->getRowArray();
        if (! $supplier) {
            return redirect()->to('/admin/suppliers')->with('error', '매입처를 찾을 수 없습니다.');
        }
        return $this->render('admin/suppliers/form', compact('supplier'));
    }

    public function update(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $db = \Config\Database::connect();
        $db->table('suppliers')->where('id', $id)->update([
            'name'           => trim($this->request->getPost('name') ?? ''),
            'contact_person' => trim($this->request->getPost('contact_person') ?? '') ?: null,
            'phone'          => trim($this->request->getPost('phone') ?? '') ?: null,
            'email'          => trim($this->request->getPost('email') ?? '') ?: null,
            'memo'           => trim($this->request->getPost('memo') ?? '') ?: null,
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        return redirect()->to('/admin/suppliers')->with('success', '저장되었습니다.');
    }

    public function delete(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $db = \Config\Database::connect();
        $db->table('products')->where('supplier_id', $id)->update(['supplier_id' => null]);
        $db->table('suppliers')->where('id', $id)->delete();
        return redirect()->to('/admin/suppliers')->with('success', '삭제되었습니다.');
    }
}
