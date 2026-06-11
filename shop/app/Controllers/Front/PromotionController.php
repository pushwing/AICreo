<?php

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Models\PromotionModel;
use App\Models\UserModel;

class PromotionController extends BaseController
{
    /** GET /promotion/:slug */
    public function detail(string $slug): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $model     = new PromotionModel();
        $promotion = $model->getActiveBySlug($slug);

        if (! $promotion) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $userId    = (int) session()->get('user_id');
        $userGrade = null;
        if ($userId > 0) {
            $user      = (new UserModel())->find($userId);
            $userGrade = $user['grade'] ?? 'bronze';
        }

        if (! $model->checkGradeAccess($promotion['grade_access'], $userGrade)) {
            if (! $userId) {
                return redirect()->to('/auth/login?redirect=' . urlencode(current_url()));
            }
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $products = $model->getProducts((int) $promotion['id']);

        return $this->render('promotions/detail', [
            'promotion' => $promotion,
            'products'  => $products,
        ]);
    }
}
