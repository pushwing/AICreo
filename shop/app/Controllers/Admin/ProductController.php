<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\MediaUploader;
use App\Models\CategoryModel;
use App\Models\ProductImageModel;
use App\Models\ProductModel;

class ProductController extends BaseController
{
    private ProductModel      $productModel;
    private CategoryModel     $categoryModel;
    private ProductImageModel $imageModel;

    public function __construct()
    {
        $this->productModel  = new ProductModel();
        $this->categoryModel = new CategoryModel();
        $this->imageModel    = new ProductImageModel();
    }

    public function index(): string
    {
        $params  = ['keyword' => $this->request->getGet('keyword'), 'status' => $this->request->getGet('status'), 'page' => $this->request->getGet('page')];
        $result  = $this->productModel->getAdminList($params);

        $this->imageModel->attachPrimaryImages($result['items']);

        return $this->render('admin/products/list', array_merge($result, [
            'statuses'  => ProductModel::STATUSES,
            'keyword'   => $params['keyword'],
            'curStatus' => $params['status'],
        ]));
    }

    public function create(): string
    {
        return $this->render('admin/products/form', [
            'product'   => null,
            'images'    => [],
            'tree'      => $this->categoryModel->getTree(),
            'statuses'  => ProductModel::STATUSES,
            'shippings' => ProductModel::SHIPPING_TYPES,
        ]);
    }

    public function store(): \CodeIgniter\HTTP\RedirectResponse
    {
        if (! $this->validate($this->validationRules())) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = $this->collectData();
        $id   = $this->productModel->insert($data);

        $this->handleImages($id);

        return redirect()->to('/admin/products')->with('success', '상품이 등록되었습니다.');
    }

    public function edit(int $id): \CodeIgniter\HTTP\RedirectResponse|string
    {
        $product = $this->productModel->find($id);
        if (! $product) return redirect()->to('/admin/products')->with('error', '상품을 찾을 수 없습니다.');

        return $this->render('admin/products/form', [
            'product'   => $product,
            'images'    => $this->imageModel->getByProduct($id),
            'tree'      => $this->categoryModel->getTree(),
            'statuses'  => ProductModel::STATUSES,
            'shippings' => ProductModel::SHIPPING_TYPES,
        ]);
    }

    public function update(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $product = $this->productModel->find($id);
        if (! $product) return redirect()->to('/admin/products')->with('error', '상품을 찾을 수 없습니다.');

        if (! $this->validate($this->validationRules($id))) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->productModel->update($id, $this->collectData($id));
        $this->handleImages($id);

        return redirect()->to('/admin/products')->with('success', '저장되었습니다.');
    }

    public function delete(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $product = $this->productModel->find($id);
        if (! $product) return redirect()->to('/admin/products')->with('error', '상품을 찾을 수 없습니다.');

        $this->productModel->delete($id);
        return redirect()->to('/admin/products')->with('success', '삭제되었습니다.');
    }

    // ── 카테고리 CRUD ─────────────────────────────────────────────────────────

    public function categories(): string
    {
        return $this->render('admin/products/categories', [
            'tree' => $this->categoryModel->getTree(),
        ]);
    }

    public function categoryStore(): \CodeIgniter\HTTP\RedirectResponse
    {
        $rules = ['name' => 'required|max_length[100]'];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $name     = $this->request->getPost('name');
        $parentId = $this->request->getPost('parent_id') ?: null;

        $this->categoryModel->insert([
            'parent_id'  => $parentId,
            'name'       => $name,
            'slug'       => $this->categoryModel->generateSlug($name),
            'sort_order' => (int) $this->request->getPost('sort_order'),
            'is_active'  => $this->request->getPost('is_active') ? 1 : 0,
        ]);

        return redirect()->to('/admin/products/categories')->with('success', '카테고리가 추가되었습니다.');
    }

    public function categoryUpdate(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $category = $this->categoryModel->find($id);
        if (! $category) return redirect()->to('/admin/products/categories')->with('error', '카테고리를 찾을 수 없습니다.');

        $rules = ['name' => 'required|max_length[100]'];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->categoryModel->update($id, [
            'parent_id'  => $this->request->getPost('parent_id') ?: null,
            'name'       => $this->request->getPost('name'),
            'sort_order' => (int) $this->request->getPost('sort_order'),
            'is_active'  => $this->request->getPost('is_active') ? 1 : 0,
        ]);

        return redirect()->to('/admin/products/categories')->with('success', '저장되었습니다.');
    }

    public function categoryDelete(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $hasProducts = $this->productModel->withDeleted()->where('category_id', $id)->countAllResults();
        if ($hasProducts) {
            return redirect()->to('/admin/products/categories')->with('error', '해당 카테고리에 상품이 있어 삭제할 수 없습니다.');
        }
        $hasChildren = $this->categoryModel->where('parent_id', $id)->countAllResults();
        if ($hasChildren) {
            return redirect()->to('/admin/products/categories')->with('error', '하위 카테고리가 있어 삭제할 수 없습니다.');
        }

        $this->categoryModel->delete($id);
        return redirect()->to('/admin/products/categories')->with('success', '삭제되었습니다.');
    }

    // ── 이미지 삭제 (Ajax) ────────────────────────────────────────────────────

    public function imageDelete(int $imageId): \CodeIgniter\HTTP\ResponseInterface
    {
        $image = $this->imageModel->find($imageId);
        if (! $image) {
            return $this->response->setJSON(['success' => false, 'error' => '이미지를 찾을 수 없습니다.']);
        }

        $this->imageModel->delete($imageId);
        return $this->response->setJSON(['success' => true]);
    }

    // ── private 헬퍼 ─────────────────────────────────────────────────────────

    private function validationRules(?int $excludeId = null): array
    {
        $slugRule = 'required|max_length[220]|is_unique[products.slug' . ($excludeId ? ",id,{$excludeId}" : '') . ']';
        return [
            'name'  => 'required|max_length[200]',
            'slug'  => $slugRule,
            'price' => 'required|integer|greater_than_equal_to[0]',
            'stock' => 'required|integer|greater_than_equal_to[0]',
        ];
    }

    private function collectData(?int $productId = null): array
    {
        $name = $this->request->getPost('name');
        $slug = $this->request->getPost('slug') ?: $this->productModel->generateSlug($name, $productId);

        return [
            'category_id'    => $this->request->getPost('category_id') ?: null,
            'name'           => $name,
            'slug'           => $slug,
            'price'          => (int) $this->request->getPost('price'),
            'discount_price' => $this->request->getPost('discount_price') !== '' ? (int) $this->request->getPost('discount_price') : null,
            'stock'          => (int) $this->request->getPost('stock'),
            'status'         => $this->request->getPost('status'),
            'description'    => $this->request->getPost('description'),
            'shipping_type'  => $this->request->getPost('shipping_type'),
            'shipping_fee'   => (int) $this->request->getPost('shipping_fee'),
            'free_threshold' => (int) $this->request->getPost('free_threshold'),
        ];
    }

    private function handleImages(int $productId): void
    {
        $uploader   = new MediaUploader();
        $files      = $this->request->getFiles();
        $newImages  = $files['images'] ?? [];
        $existCount = $this->imageModel->where('product_id', $productId)->countAllResults();

        foreach ($newImages as $file) {
            if (! $file->isValid() || $file->hasMoved()) continue;

            $result = $uploader->upload($file, $this->request->getPost('name'));
            if (! $result['success']) continue;

            $this->imageModel->insert([
                'product_id' => $productId,
                'media_id'   => $result['id'],
                'is_primary' => $existCount === 0 ? 1 : 0,
                'sort_order' => $existCount,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $existCount++;
        }

        // 대표 이미지 변경 요청
        $primaryMediaId = $this->request->getPost('primary_media_id');
        if ($primaryMediaId) {
            $this->imageModel->setPrimary($productId, (int) $primaryMediaId);
        }
    }
}
