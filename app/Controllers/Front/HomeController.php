<?php

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Models\BoardModel;
use App\Models\PostModel;

class HomeController extends BaseController
{
    public function index()
    {
        // 홈에 최신 공지 3개 노출
        $boardModel = new BoardModel();
        $postModel  = new PostModel();

        $noticeBoard = $boardModel->getBySlug('notice');
        $latestPosts = [];
        if ($noticeBoard) {
            $latestPosts = $postModel
                ->where('board_id', $noticeBoard['id'])
                ->orderBy('id', 'DESC')
                ->findAll(3);
        }

        return $this->render('pages/home', [
            'page'        => [
                'title'     => $this->viewData['settings']['site_name'] ?? '',
                'meta_desc' => $this->viewData['settings']['site_desc'] ?? '',
            ],
            'latestPosts' => $latestPosts,
        ]);
    }
}
