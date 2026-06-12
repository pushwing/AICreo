<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\MediaUploader;
use App\Models\CategoryModel;
use App\Models\ProductImageModel;
use App\Models\ProductModel;
use App\Models\ProductSkuModel;
use App\Models\StockLogModel;
use Config\Database;

class ProductController extends BaseController
{
    private ProductModel      $productModel;
    private CategoryModel     $categoryModel;
    private ProductImageModel $imageModel;
    private ProductSkuModel   $skuModel;

    public function __construct()
    {
        $this->productModel  = new ProductModel();
        $this->categoryModel = new CategoryModel();
        $this->imageModel    = new ProductImageModel();
        $this->skuModel      = new ProductSkuModel();
    }

    public function index(): string
    {
        $lowStockThreshold = (int) ($this->viewData['settings']['low_stock_threshold'] ?? 5);

        $params = [
            'keyword'             => $this->request->getGet('keyword'),
            'status'              => $this->request->getGet('status'),
            'stock'               => $this->request->getGet('stock'),
            'page'                => $this->request->getGet('page'),
            'low_stock_threshold' => $lowStockThreshold,
        ];
        $result = $this->productModel->getAdminList($params);

        $this->imageModel->attachPrimaryImages($result['items']);

        $lowStockCount = $this->productModel
            ->where('stock <=', $lowStockThreshold)
            ->where('deleted_at IS NULL', null, false)
            ->countAllResults();

        return $this->render('admin/products/list', array_merge($result, [
            'statuses'          => ProductModel::STATUSES,
            'keyword'           => $params['keyword'],
            'curStatus'         => $params['status'],
            'curStock'          => $params['stock'],
            'lowStockThreshold' => $lowStockThreshold,
            'lowStockCount'     => $lowStockCount,
        ]));
    }

    /** GET /admin/products/json */
    public function json(): \CodeIgniter\HTTP\ResponseInterface
    {
        $db        = \Config\Database::connect();
        $rows      = $db->table('products')
            ->select('products.id, products.name, products.slug, products.price, products.discount_price,
                      products.stock, products.status, products.is_featured, products.created_at, categories.name AS category_name')
            ->join('categories', 'categories.id = products.category_id', 'left')
            ->where('products.deleted_at IS NULL')
            ->orderBy('products.id', 'DESC')
            ->get()->getResultArray();
        $this->imageModel->attachPrimaryImages($rows);
        $threshold = (int) ($this->viewData['settings']['low_stock_threshold'] ?? 5);

        $data = array_map(fn($p) => [
            'id'             => (int) $p['id'],
            'name'           => $p['name'],
            'slug'           => $p['slug'],
            'category_name'  => $p['category_name'] ?? '',
            'price'          => (int) $p['price'],
            'discount_price' => $p['discount_price'] ? (int) $p['discount_price'] : 0,
            'stock'          => (int) $p['stock'],
            'status'         => $p['status'],
            'primary_image'  => $p['primary_image'] ?? '',
            'created_at'     => $p['created_at'],
            'is_low_stock'   => (int) ($p['stock'] <= $threshold),
            'is_featured'    => (int) $p['is_featured'],
        ], $rows);

        return $this->response->setJSON(['data' => $data]);
    }

    /** POST /admin/products/bulk — 상품 일괄 편집 (상태·재고·삭제) */
    public function bulk(): \CodeIgniter\HTTP\RedirectResponse
    {
        $ids    = array_values(array_filter(array_map('intval', (array) $this->request->getPost('ids'))));
        $action = $this->request->getPost('action');

        if (empty($ids)) {
            return redirect()->back()->with('error', '상품을 선택해주세요.');
        }

        $db = \Config\Database::connect();

        switch ($action) {
            case 'status':
                $status = $this->request->getPost('status');
                if (! array_key_exists($status, ProductModel::STATUSES)) {
                    return redirect()->back()->with('error', '올바른 상태 값이 아닙니다.');
                }
                $db->table('products')
                   ->whereIn('id', $ids)
                   ->update(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
                return redirect()->back()->with('success', count($ids) . '개 상품 상태가 변경되었습니다.');

            case 'stock':
                $stock = $this->request->getPost('stock');
                if (! is_numeric($stock) || (int) $stock < 0) {
                    return redirect()->back()->with('error', '올바른 재고 수량을 입력해주세요.');
                }
                $stock    = (int) $stock;
                $adminId  = (int) session()->get('user_id');
                $logModel = new StockLogModel();
                foreach ($ids as $id) {
                    $product = $this->productModel->find($id);
                    if (! $product) continue;
                    $oldStock = (int) $product['stock'];
                    $this->productModel->update($id, ['stock' => $stock]);
                    $logModel->record($id, 'adjust', abs($stock - $oldStock), $oldStock, $stock, '관리자 일괄 재고 조정', $adminId);
                }
                return redirect()->back()->with('success', count($ids) . '개 상품 재고가 변경되었습니다.');

            case 'delete':
                foreach ($ids as $id) {
                    $this->productModel->delete($id);
                }
                return redirect()->back()->with('success', count($ids) . '개 상품이 삭제되었습니다.');

            default:
                return redirect()->back()->with('error', '올바른 액션이 아닙니다.');
        }
    }

    /** POST /admin/products/:id/stock — 인라인 재고 수정 */
    public function updateStock(int $id): \CodeIgniter\HTTP\ResponseInterface
    {
        $product = $this->productModel->find($id);
        if (! $product) {
            return $this->response->setJSON(['success' => false, 'message' => '상품을 찾을 수 없습니다.']);
        }

        $newStock = $this->request->getPost('stock');
        if (! is_numeric($newStock) || (int) $newStock < 0) {
            return $this->response->setJSON(['success' => false, 'message' => '올바른 재고 수량을 입력해주세요.']);
        }

        $newStock = (int) $newStock;
        $oldStock = (int) $product['stock'];
        $adminId  = (int) session()->get('user_id');

        $this->productModel->update($id, ['stock' => $newStock]);

        (new StockLogModel())->record(
            $id, 'adjust',
            abs($newStock - $oldStock),
            $oldStock, $newStock,
            '관리자 재고 조정',
            $adminId
        );

        return $this->response->setJSON(['success' => true, 'stock' => $newStock]);
    }

    public function toggleFeatured(int $id): \CodeIgniter\HTTP\ResponseInterface
    {
        $product = $this->productModel->find($id);
        if (! $product) {
            return $this->response->setJSON(['success' => false, 'message' => '상품을 찾을 수 없습니다.']);
        }
        $newVal = $product['is_featured'] ? 0 : 1;
        $this->productModel->update($id, ['is_featured' => $newVal]);
        return $this->response->setJSON(['success' => true, 'is_featured' => $newVal]);
    }

    public function create(): string
    {
        return $this->render('admin/products/form', [
            'product'        => null,
            'images'         => [],
            'tree'           => $this->categoryModel->getTree(),
            'statuses'       => ProductModel::STATUSES,
            'shippings'      => ProductModel::SHIPPING_TYPES,
            'suppliers'      => Database::connect()->table('suppliers')->orderBy('name')->get()->getResultArray(),
            'optionsAndSkus' => ['options' => [], 'skus' => []],
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
        $this->handleOptions($id);

        return redirect()->to('/admin/products')->with('success', '상품이 등록되었습니다.');
    }

    public function edit(int $id): \CodeIgniter\HTTP\RedirectResponse|string
    {
        $product = $this->productModel->find($id);
        if (! $product) return redirect()->to('/admin/products')->with('error', '상품을 찾을 수 없습니다.');

        return $this->render('admin/products/form', [
            'product'        => $product,
            'images'         => $this->imageModel->getByProduct($id),
            'tree'           => $this->categoryModel->getTree(),
            'statuses'       => ProductModel::STATUSES,
            'shippings'      => ProductModel::SHIPPING_TYPES,
            'suppliers'      => Database::connect()->table('suppliers')->orderBy('name')->get()->getResultArray(),
            'optionsAndSkus' => $this->skuModel->getOptionsAndSkus($id),
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
        $this->handleOptions($id);

        return redirect()->to('/admin/products')->with('success', '저장되었습니다.');
    }

    public function copy(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $product = $this->productModel->find($id);
        if (! $product) return redirect()->to('/admin/products')->with('error', '상품을 찾을 수 없습니다.');

        $db  = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        $newName = $product['name'] . ' (복사)';
        $newSlug = $this->productModel->generateSlug($newName);

        $newId = $this->productModel->insert([
            'category_id'    => $product['category_id'],
            'supplier_id'    => $product['supplier_id'],
            'name'           => $newName,
            'slug'           => $newSlug,
            'price'          => $product['price'],
            'cost_price'     => $product['cost_price'],
            'discount_price' => $product['discount_price'],
            'stock'          => 0,
            'status'         => 'hidden',
            'description'    => $product['description'],
            'shipping_type'  => $product['shipping_type'],
            'shipping_fee'   => $product['shipping_fee'],
            'free_threshold' => $product['free_threshold'],
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        // 이미지 복사 (media_id 참조만 공유, 파일 복사 없음)
        foreach ($db->table('product_images')->where('product_id', $id)->get()->getResultArray() as $img) {
            $db->table('product_images')->insert([
                'product_id' => $newId,
                'media_id'   => $img['media_id'],
                'is_primary' => $img['is_primary'],
                'sort_order' => $img['sort_order'],
                'created_at' => $now,
            ]);
        }

        // 옵션 복사: options → option_values → skus → sku_values
        $optionIdMap      = [];
        $optionValueIdMap = [];
        foreach ($db->table('product_options')->where('product_id', $id)->get()->getResultArray() as $opt) {
            $db->table('product_options')->insert(['product_id' => $newId, 'name' => $opt['name'], 'sort_order' => $opt['sort_order']]);
            $newOptId                   = (int) $db->insertID();
            $optionIdMap[(int) $opt['id']] = $newOptId;

            foreach ($db->table('product_option_values')->where('option_id', $opt['id'])->get()->getResultArray() as $val) {
                $db->table('product_option_values')->insert(['option_id' => $newOptId, 'value' => $val['value'], 'sort_order' => $val['sort_order']]);
                $optionValueIdMap[(int) $val['id']] = (int) $db->insertID();
            }
        }

        foreach ($db->table('product_skus')->where('product_id', $id)->get()->getResultArray() as $sku) {
            $db->table('product_skus')->insert(['product_id' => $newId, 'price_diff' => $sku['price_diff'], 'stock' => 0, 'sku_code' => $sku['sku_code']]);
            $newSkuId = (int) $db->insertID();

            foreach ($db->table('product_sku_values')->where('sku_id', $sku['id'])->get()->getResultArray() as $sv) {
                $newValId = $optionValueIdMap[(int) $sv['option_value_id']] ?? null;
                if ($newValId) {
                    $db->table('product_sku_values')->insert(['sku_id' => $newSkuId, 'option_value_id' => $newValId]);
                }
            }
        }

        return redirect()->to("/admin/products/{$newId}/edit")->with('success', '상품이 복사되었습니다. 내용을 확인하고 저장해주세요.');
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
            'name'       => 'required|max_length[200]',
            'slug'       => $slugRule,
            'price'      => 'required|integer|greater_than_equal_to[0]',
            'stock'      => 'required|integer|greater_than_equal_to[0]',
            'cost_price' => 'permit_empty|decimal|greater_than_equal_to[0]',
        ];
    }

    private function collectData(?int $productId = null): array
    {
        $name = $this->request->getPost('name');
        $slug = $this->request->getPost('slug') ?: $this->productModel->generateSlug($name, $productId);

        return [
            'category_id'    => $this->request->getPost('category_id') ?: null,
            'supplier_id'    => $this->request->getPost('supplier_id') ?: null,
            'name'           => $name,
            'slug'           => $slug,
            'price'          => (int) $this->request->getPost('price'),
            'cost_price'     => (float) $this->request->getPost('cost_price'),
            'discount_price' => $this->request->getPost('discount_price') !== '' ? (int) $this->request->getPost('discount_price') : null,
            'stock'          => (int) $this->request->getPost('stock'),
            'status'         => $this->request->getPost('status'),
            'description'    => $this->request->getPost('description'),
            'shipping_type'  => $this->request->getPost('shipping_type'),
            'shipping_fee'   => (int) $this->request->getPost('shipping_fee'),
            'free_threshold' => (int) $this->request->getPost('free_threshold'),
        ];
    }

    private function handleOptions(int $productId): void
    {
        $json = $this->request->getPost('options_json');
        if (! $json) {
            // 옵션 JSON이 없으면 기존 옵션 전체 삭제 (옵션 제거)
            $this->skuModel->deleteByProduct($productId);
            return;
        }

        $data = json_decode($json, true);
        if (! is_array($data)) return;

        $this->skuModel->saveOptionsAndSkus($productId, $data);
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
