<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\InquiryModel;
use App\Models\OrderModel;
use App\Models\PostModel;
use App\Models\ProductModel;
use App\Models\UserModel;

class DashboardController extends BaseController
{
    public function index()
    {
        $postModel    = new PostModel();
        $userModel    = new UserModel();
        $inquiryModel = new InquiryModel();
        $orderModel   = new OrderModel();
        $productModel = new ProductModel();

        $db = \Config\Database::connect();

        $recentOrders = $db->table('orders o')
            ->select('o.id, o.order_number, o.status, o.total_amount, o.created_at, u.nickname AS user_nickname')
            ->join('users u', 'u.id = o.user_id', 'left')
            ->orderBy('o.id', 'DESC')
            ->limit(5)
            ->get()->getResultArray();

        $lowStockProducts = $productModel
            ->select('id, name, stock, status')
            ->where('stock <=', 5)
            ->where('deleted_at IS NULL', null, false)
            ->orderBy('stock', 'ASC')
            ->findAll(10);

        return $this->render('admin/dashboard/index', [
            'stats' => [
                'total_posts'     => $postModel->countAll(),
                'total_users'     => $userModel->countAll(),
                'total_inquiries' => $inquiryModel->countAll(),
                'unread_inquiries'=> $inquiryModel->getUnreadCount(),
            ],
            'recentInquiries'  => $inquiryModel->orderBy('id', 'DESC')->findAll(5),
            'recentPosts'      => $postModel
                ->select('posts.*, boards.slug as board_slug, boards.name as board_name, users.nickname as user_nickname')
                ->join('boards', 'boards.id = posts.board_id', 'left')
                ->join('users', 'users.id = posts.user_id', 'left')
                ->orderBy('posts.id', 'DESC')
                ->findAll(5),
            'recentOrders'     => $recentOrders,
            'lowStockProducts' => $lowStockProducts,
        ]);
    }
}
