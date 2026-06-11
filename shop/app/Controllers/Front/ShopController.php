<?php

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Models\BannerModel;
use App\Models\CategoryModel;
use App\Models\ProductImageModel;
use App\Models\ProductModel;

class ShopController extends BaseController
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

        return $this->render('shop/detail', [
            'product'         => $product,
            'images'          => $images,
            'shipping_policy' => $this->viewData['settings']['shipping_policy'] ?? '',
        ]);
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
