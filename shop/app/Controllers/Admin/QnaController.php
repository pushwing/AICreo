<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ProductQnaModel;

class QnaController extends BaseController
{
    private ProductQnaModel $model;

    public function __construct()
    {
        $this->model = new ProductQnaModel();
    }

    /** GET /admin/qna */
    public function index(): string
    {
        $result = $this->model->adminGetAll([
            'keyword'  => trim($this->request->getGet('q') ?? ''),
            'answered' => $this->request->getGet('answered') ?? '',
            'page'     => $this->request->getGet('page') ?? 1,
        ]);

        return $this->render('admin/qna/list', array_merge($result, [
            'keyword'  => trim($this->request->getGet('q') ?? ''),
            'answered' => $this->request->getGet('answered') ?? '',
        ]));
    }

    /** POST /admin/qna/:id/answer */
    public function answer(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $answer = trim($this->request->getPost('answer') ?? '');

        if ($answer === '') {
            return redirect()->back()->with('error', '답변 내용을 입력해주세요.');
        }

        if (! $this->model->find($id)) {
            return redirect()->to('/admin/qna')->with('error', '문의를 찾을 수 없습니다.');
        }

        $this->model->update($id, [
            'answer'      => $answer,
            'is_answered' => 1,
            'answered_at' => date('Y-m-d H:i:s'),
            'answered_by' => (int) session()->get('user_id'),
        ]);

        return redirect()->back()->with('success', '답변이 등록되었습니다.');
    }

    /** POST /admin/qna/:id/delete */
    public function delete(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $this->model->delete($id);
        return redirect()->to('/admin/qna')->with('success', '문의가 삭제되었습니다.');
    }
}
