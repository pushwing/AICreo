<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ProductReviewModel;

class ReviewController extends BaseController
{
    private ProductReviewModel $model;

    public function __construct()
    {
        $this->model = new ProductReviewModel();
    }

    /** GET /admin/reviews */
    public function index(): string
    {
        $result = $this->model->adminGetAll([
            'keyword' => trim($this->request->getGet('q') ?? ''),
            'page'    => $this->request->getGet('page') ?? 1,
        ]);

        return $this->render('admin/reviews/list', array_merge($result, [
            'keyword' => trim($this->request->getGet('q') ?? ''),
        ]));
    }

    /** POST /admin/reviews/:id/delete */
    public function delete(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $this->model->deleteReview($id);
        return redirect()->to('/admin/reviews')->with('success', '리뷰가 삭제되었습니다.');
    }
}
