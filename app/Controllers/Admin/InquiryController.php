<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\InquiryModel;

class InquiryController extends BaseController
{
    private InquiryModel $model;

    public function __construct()
    {
        $this->model = new InquiryModel();
    }

    public function index()
    {
        $page       = (int) ($this->request->getGet('page') ?? 1);
        $limit      = 20;
        $offset     = ($page - 1) * $limit;
        $total      = $this->model->countAll();

        return $this->render('admin/inquiries/index', [
            'inquiries'   => $this->model->orderBy('id', 'DESC')->findAll($limit, $offset),
            'totalPages'  => (int) ceil($total / $limit),
            'currentPage' => $page,
            'total'       => $total,
        ]);
    }

    public function view(int $id)
    {
        $inquiry = $this->model->find($id);
        if (! $inquiry) return redirect()->to('/admin/inquiries');

        $this->model->markRead($id);

        return $this->render('admin/inquiries/view', ['inquiry' => $inquiry]);
    }

    public function delete(int $id)
    {
        $this->model->delete($id);
        return redirect()->to('/admin/inquiries')->with('success', '삭제되었습니다.');
    }
}
