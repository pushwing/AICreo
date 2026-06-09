<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\InquiryModel;
use App\Models\PostModel;
use App\Models\UserModel;

class DashboardController extends BaseController
{
    public function index()
    {
        $postModel    = new PostModel();
        $userModel    = new UserModel();
        $inquiryModel = new InquiryModel();

        return $this->render('admin/dashboard/index', [
            'stats' => [
                'total_posts'    => $postModel->countAll(),
                'total_users'    => $userModel->countAll(),
                'total_inquiries'=> $inquiryModel->countAll(),
                'unread_inquiries'=> $inquiryModel->getUnreadCount(),
            ],
            'recentInquiries' => $inquiryModel->orderBy('id', 'DESC')->findAll(5),
            'recentPosts'     => $postModel->orderBy('id', 'DESC')->findAll(5),
        ]);
    }
}
