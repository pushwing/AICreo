<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Models\ProductImageModel;
use App\Models\StockLogModel;

class InventoryController extends BaseController
{
    private ProductModel      $productModel;
    private ProductImageModel $imageModel;
    private StockLogModel     $logModel;

    public function __construct()
    {
        $this->productModel = new ProductModel();
        $this->imageModel   = new ProductImageModel();
        $this->logModel     = new StockLogModel();
    }

    public function index(): string
    {
        $keyword  = $this->request->getGet('keyword') ?? '';
        $filter   = $this->request->getGet('filter')  ?? '';
        $page     = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage  = 20;

        $db      = \Config\Database::connect();
        $builder = $db->table('products')
            ->select('products.*, categories.name as category_name')
            ->join('categories', 'categories.id = products.category_id', 'left')
            ->where('products.deleted_at IS NULL');

        if ($keyword) {
            $builder->like('products.name', $keyword);
        }

        match ($filter) {
            'sold_out' => $builder->where('products.stock', 0),
            'low'      => $builder->where('products.stock >', 0)->where('products.stock <=', 10),
            default    => null,
        };

        $builder->orderBy('products.stock', 'ASC');

        $total      = (clone $builder)->countAllResults();
        $offset     = ($page - 1) * $perPage;
        $items      = $builder->limit($perPage, $offset)->get()->getResultArray();
        $totalPages = (int) ceil($total / $perPage);

        $this->imageModel->attachPrimaryImages($items);

        // 요약 통계
        $db      = \Config\Database::connect();
        $summary = [
            'total'    => $db->table('products')->where('deleted_at IS NULL', null, false)->countAllResults(),
            'sold_out' => $db->table('products')->where('deleted_at IS NULL', null, false)->where('stock', 0)->countAllResults(),
            'low'      => $db->table('products')->where('deleted_at IS NULL', null, false)->where('stock >', 0)->where('stock <=', 10)->countAllResults(),
        ];

        return $this->render('admin/inventory/index', [
            'items'       => $items,
            'total'       => $total,
            'totalPages'  => $totalPages,
            'currentPage' => $page,
            'keyword'     => $keyword,
            'filter'      => $filter,
            'summary'     => $summary,
        ]);
    }

    public function adjust(int $productId): \CodeIgniter\HTTP\ResponseInterface
    {
        $product = $this->productModel->find($productId);
        if (! $product) {
            return $this->response->setJSON(['success' => false, 'error' => '상품을 찾을 수 없습니다.']);
        }

        $type   = $this->request->getPost('type') ?? 'adjust';
        $qty    = (int) $this->request->getPost('quantity');
        $note   = $this->request->getPost('note') ?? '';

        $before = (int) $product['stock'];

        $after = match ($type) {
            'in'  => $before + abs($qty),
            'out' => max(0, $before - abs($qty)),
            default => max(0, $before + $qty),
        };

        $this->productModel->update($productId, [
            'stock'  => $after,
            'status' => $after === 0 ? 'sold_out' : ($product['status'] === 'sold_out' ? 'on_sale' : $product['status']),
        ]);

        $adminId = session()->get('user_id');
        $this->logModel->record($productId, $type, $qty, $before, $after, $note ?: null, $adminId);

        return $this->response->setJSON([
            'success'      => true,
            'stock_after'  => $after,
            'status'       => $after === 0 ? 'sold_out' : ($product['status'] === 'sold_out' ? 'on_sale' : $product['status']),
        ]);
    }

    public function logs(int $productId): \CodeIgniter\HTTP\ResponseInterface
    {
        $product = $this->productModel->find($productId);
        if (! $product) {
            return $this->response->setJSON(['success' => false]);
        }

        $logs = $this->logModel->getByProduct($productId, 50);

        return $this->response->setJSON([
            'success'      => true,
            'product_name' => $product['name'],
            'stock'        => $product['stock'],
            'logs'         => $logs,
            'types'        => StockLogModel::TYPES,
        ]);
    }
}
