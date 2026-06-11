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

    public function index(): string
    {
        $page   = max(1, (int) ($this->request->getGet('page') ?? 1));
        $filter = $this->request->getGet('filter') ?? '';
        $limit  = 20;

        $builder = $this->model->builder();
        if ($filter === 'unread') {
            $builder->where('is_read', 0);
        }

        $total      = (clone $builder)->countAllResults(false);
        $inquiries  = $builder->orderBy('id', 'DESC')
                              ->limit($limit, ($page - 1) * $limit)
                              ->get()->getResultArray();

        return $this->render('admin/inquiries/index', [
            'inquiries'   => $inquiries,
            'totalPages'  => (int) ceil($total / $limit),
            'currentPage' => $page,
            'total'       => $total,
            'filter'      => $filter,
            'unreadCount' => $this->model->getUnreadCount(),
            'totalAll'    => $this->model->countAll(),
        ]);
    }

    public function view(int $id): \CodeIgniter\HTTP\RedirectResponse|string
    {
        $inquiry = $this->model->find($id);
        if (! $inquiry) return redirect()->to('/admin/inquiries');

        $this->model->markRead($id);

        return $this->render('admin/inquiries/view', ['inquiry' => $inquiry]);
    }

    public function delete(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $this->model->delete($id);
        return redirect()->to('/admin/inquiries')->with('success', '삭제되었습니다.');
    }
}
