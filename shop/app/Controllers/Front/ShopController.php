<?php

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Models\BannerModel;
use App\Models\CategoryModel;
use App\Models\ProductImageModel;
use App\Models\ProductModel;
use App\Models\ProductQnaModel;

class ShopController extends BaseController
{
    private ProductModel      $productModel;
    private CategoryModel     $categoryModel;
    private ProductImageModel $imageModel;
    private ProductQnaModel   $qnaModel;

    public function __construct()
    {
        $this->productModel  = new ProductModel();
        $this->categoryModel = new CategoryModel();
        $this->imageModel    = new ProductImageModel();
        $this->qnaModel      = new ProductQnaModel();
    }

    public function home(): string
    {
        $bannerModel = new BannerModel();
        $newProducts = $this->productModel->getLatest(8);
        $this->imageModel->attachPrimaryImages($newProducts);

        $discountedProducts = $this->productModel->getDiscounted(8);
        $this->imageModel->attachPrimaryImages($discountedProducts);

        return $this->render('shop/home', [
            'mainTopBanners'    => $bannerModel->getActiveByPosition('main_top'),
            'mainBotBanners'    => $bannerModel->getActiveByPosition('main_bottom'),
            'newProducts'       => $newProducts,
            'discountedProducts' => $discountedProducts,
        ]);
    }

    public function detail(string $slug): string
    {
        $product = $this->productModel
            ->where('slug', $slug)
            ->whereIn('status', ['on_sale', 'sold_out'])
            ->first();

        if (! $product) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // 재고는 캐시 아닌 DB 직접 조회 (이슈 #3 원칙)
        $freshStock = $this->productModel->db
            ->table('products')
            ->select('stock')
            ->where('id', $product['id'])
            ->get()->getRow();

        $product['stock'] = $freshStock ? (int) $freshStock->stock : 0;
        $product['status'] = $product['stock'] === 0 ? 'sold_out' : $product['status'];

        $images = $this->imageModel->getByProduct($product['id']);

        $qnaPerPage = 10;
        $qnaPage    = max(1, (int) ($this->request->getGet('qna_page') ?? 1));
        $qnaData    = $this->qnaModel->getByProduct($product['id'], $qnaPage, $qnaPerPage);

        return $this->render('shop/detail', [
            'product'         => $product,
            'images'          => $images,
            'shipping_policy' => $this->viewData['settings']['shipping_policy'] ?? '',
            'qnaItems'        => $qnaData['items'],
            'qnaTotal'        => $qnaData['total'],
            'qnaPage'         => $qnaPage,
            'qnaPerPage'      => $qnaPerPage,
        ]);
    }

    /** POST /shop/:slug/qna */
    public function qnaStore(string $slug): \CodeIgniter\HTTP\ResponseInterface
    {
        $userId  = (int) session()->get('user_id');
        if (! $userId) {
            return $this->response->setJSON(['success' => false, 'message' => '로그인이 필요합니다.']);
        }

        $product = $this->productModel->where('slug', $slug)->whereIn('status', ['on_sale', 'sold_out'])->first();
        if (! $product) {
            return $this->response->setJSON(['success' => false, 'message' => '상품을 찾을 수 없습니다.']);
        }

        $title   = trim($this->request->getPost('title') ?? '');
        $content = trim($this->request->getPost('content') ?? '');
        if ($title === '' || $content === '') {
            return $this->response->setJSON(['success' => false, 'message' => '제목과 내용을 입력해주세요.']);
        }

        $this->qnaModel->insert([
            'product_id' => $product['id'],
            'user_id'    => $userId,
            'title'      => $title,
            'content'    => $content,
            'is_secret'  => (int) $this->request->getPost('is_secret') === 1 ? 1 : 0,
        ]);

        return $this->response->setJSON(['success' => true, 'message' => '문의가 등록되었습니다.']);
    }

    /** POST /shop/:slug/qna/:id/delete */
    public function qnaDelete(string $slug, int $id): \CodeIgniter\HTTP\ResponseInterface
    {
        $userId = (int) session()->get('user_id');
        if (! $userId) {
            return $this->response->setJSON(['success' => false, 'message' => '로그인이 필요합니다.']);
        }

        $qna = $this->qnaModel->where('id', $id)->where('user_id', $userId)->first();
        if (! $qna) {
            return $this->response->setJSON(['success' => false, 'message' => '삭제할 수 없습니다.']);
        }

        $this->qnaModel->delete($id);
        return $this->response->setJSON(['success' => true]);
    }

    public function index(): string
    {
        $params = [
            'keyword'     => $this->request->getGet('keyword'),
            'category_id' => $this->request->getGet('category_id'),
            'sort'        => $this->request->getGet('sort'),
            'page'        => $this->request->getGet('page'),
            'per_page'    => 12,
        ];

        $result = $this->productModel->getList($params);

        $this->imageModel->attachPrimaryImages($result['items']);

        return $this->render('shop/list', array_merge($result, [
            'tree'       => $this->categoryModel->getTree(),
            'keyword'    => $params['keyword'],
            'curCat'     => (int) $params['category_id'],
            'curSort'    => $params['sort'],
        ]));
    }
}
