<?php

namespace Tests\Unit;

use App\Models\ProductSkuModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * ProductSkuModel — getOptionsAndSkus(G), saveOptionsAndSkus(S), deleteByProduct(D), findForProduct(F)
 */
final class ProductSkuTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $DBGroup = 'tests';
    protected $migrate = false;
    protected $refresh = false;

    private ProductSkuModel $model;

    private array $cleanup = ['products' => []];

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new ProductSkuModel();
    }

    protected function tearDown(): void
    {
        $db = db_connect();

        if ($this->cleanup['products'] !== []) {
            $skuRows = $db->table('product_skus')
                ->whereIn('product_id', $this->cleanup['products'])
                ->get()->getResultArray();
            if ($skuRows) {
                $db->table('product_sku_values')
                    ->whereIn('sku_id', array_column($skuRows, 'id'))
                    ->delete();
            }
            $db->table('product_skus')->whereIn('product_id', $this->cleanup['products'])->delete();

            $optRows = $db->table('product_options')
                ->whereIn('product_id', $this->cleanup['products'])
                ->get()->getResultArray();
            if ($optRows) {
                $db->table('product_option_values')
                    ->whereIn('option_id', array_column($optRows, 'id'))
                    ->delete();
            }
            $db->table('product_options')->whereIn('product_id', $this->cleanup['products'])->delete();
            $db->table('products')->whereIn('id', $this->cleanup['products'])->delete();
        }

        $this->cleanup = ['products' => []];
        parent::tearDown();
    }

    // ── 헬퍼 ──────────────────────────────────────────────────────────────────

    private function insertProduct(): int
    {
        $db = db_connect();
        $db->table('products')->insert([
            'name'           => '테스트상품_' . uniqid(),
            'slug'           => 'test-sku-' . uniqid(),
            'price'          => 10000,
            'cost_price'     => 5000,
            'supplier_id'    => null,
            'stock'          => 10,
            'status'         => 'on_sale',
            'shipping_type'  => 'free',
            'shipping_fee'   => 0,
            'free_threshold' => 0,
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $db->insertID();
        $this->cleanup['products'][] = $id;
        return $id;
    }

    // ── G: getOptionsAndSkus ──────────────────────────────────────────────────

    public function testGetOptionsAndSkus_noOptions_returnsEmpty(): void
    {
        $productId = $this->insertProduct();

        $result = $this->model->getOptionsAndSkus($productId);

        $this->assertSame(['options' => [], 'skus' => []], $result);
    }

    public function testGetOptionsAndSkus_singleOption_singleSku_returnsCorrectStructure(): void
    {
        $productId = $this->insertProduct();
        $this->model->saveOptionsAndSkus($productId, [
            'options' => [
                ['name' => '색상', 'values' => [['tmp_id' => 'c1', 'value' => '빨강']]],
            ],
            'skus' => [
                ['price_diff' => 0, 'stock' => 5, 'sku_code' => null, 'value_tmp_ids' => ['c1']],
            ],
        ]);

        $result = $this->model->getOptionsAndSkus($productId);

        $this->assertCount(1, $result['options']);
        $this->assertSame('색상', $result['options'][0]['name']);
        $this->assertCount(1, $result['options'][0]['values']);
        $this->assertSame('빨강', $result['options'][0]['values'][0]['value']);
        $this->assertCount(1, $result['skus']);
        $this->assertSame('색상:빨강', $result['skus'][0]['option_label']);
    }

    public function testGetOptionsAndSkus_multiOption_skuLabelCombined(): void
    {
        $productId = $this->insertProduct();
        $this->model->saveOptionsAndSkus($productId, [
            'options' => [
                ['name' => '색상', 'values'  => [['tmp_id' => 'c1', 'value' => '빨강']]],
                ['name' => '사이즈', 'values' => [['tmp_id' => 's1', 'value' => 'S']]],
            ],
            'skus' => [
                ['price_diff' => 500, 'stock' => 3, 'sku_code' => 'RED-S', 'value_tmp_ids' => ['c1', 's1']],
            ],
        ]);

        $result = $this->model->getOptionsAndSkus($productId);
        $sku    = $result['skus'][0];

        $this->assertSame('색상:빨강/사이즈:S', $sku['option_label']);
        $this->assertSame('RED-S', $sku['sku_code']);
        $this->assertEquals(500, (int) $sku['price_diff']);
    }

    public function testGetOptionsAndSkus_values_orderedBySortOrder(): void
    {
        $productId = $this->insertProduct();
        $this->model->saveOptionsAndSkus($productId, [
            'options' => [
                ['name' => '사이즈', 'values' => [
                    ['tmp_id' => 's1', 'value' => 'S'],
                    ['tmp_id' => 's2', 'value' => 'M'],
                    ['tmp_id' => 's3', 'value' => 'L'],
                ]],
            ],
            'skus' => [
                ['price_diff' => 0, 'stock' => 10, 'sku_code' => null, 'value_tmp_ids' => ['s1']],
            ],
        ]);

        $result = $this->model->getOptionsAndSkus($productId);
        $values = array_column($result['options'][0]['values'], 'value');

        $this->assertSame(['S', 'M', 'L'], $values);
    }

    // ── S: saveOptionsAndSkus ─────────────────────────────────────────────────

    public function testSaveOptionsAndSkus_emptyOptions_savesNothing(): void
    {
        $productId = $this->insertProduct();
        $db        = db_connect();

        $this->model->saveOptionsAndSkus($productId, ['options' => [], 'skus' => []]);

        $this->assertSame(0, (int) $db->table('product_options')->where('product_id', $productId)->countAllResults());
        $this->assertSame(0, (int) $db->table('product_skus')->where('product_id', $productId)->countAllResults());
    }

    public function testSaveOptionsAndSkus_allTablesPopulated(): void
    {
        $productId = $this->insertProduct();
        $db        = db_connect();

        $this->model->saveOptionsAndSkus($productId, [
            'options' => [['name' => '색상', 'values' => [['tmp_id' => 'c1', 'value' => '파랑']]]],
            'skus'    => [['price_diff' => 0, 'stock' => 7, 'sku_code' => null, 'value_tmp_ids' => ['c1']]],
        ]);

        $this->assertSame(1, (int) $db->table('product_options')->where('product_id', $productId)->countAllResults());
        $this->assertSame(1, (int) $db->table('product_skus')->where('product_id', $productId)->countAllResults());

        $skuId = (int) $db->table('product_skus')->where('product_id', $productId)->get()->getRowArray()['id'];
        $this->assertSame(1, (int) $db->table('product_sku_values')->where('sku_id', $skuId)->countAllResults());
    }

    public function testSaveOptionsAndSkus_resave_replacesExistingData(): void
    {
        $productId = $this->insertProduct();
        $db        = db_connect();

        $this->model->saveOptionsAndSkus($productId, [
            'options' => [['name' => '색상', 'values' => [['tmp_id' => 'c1', 'value' => '빨강']]]],
            'skus'    => [['price_diff' => 0, 'stock' => 5, 'sku_code' => null, 'value_tmp_ids' => ['c1']]],
        ]);

        $this->model->saveOptionsAndSkus($productId, [
            'options' => [['name' => '사이즈', 'values' => [['tmp_id' => 'z1', 'value' => 'XL']]]],
            'skus'    => [
                ['price_diff' => 1000, 'stock' => 2, 'sku_code' => 'XL',  'value_tmp_ids' => ['z1']],
                ['price_diff' => 2000, 'stock' => 1, 'sku_code' => 'XXL', 'value_tmp_ids' => ['z1']],
            ],
        ]);

        $opts = $db->table('product_options')->where('product_id', $productId)->get()->getResultArray();
        $this->assertCount(1, $opts);
        $this->assertSame('사이즈', $opts[0]['name']);
        $this->assertSame(2, (int) $db->table('product_skus')->where('product_id', $productId)->countAllResults());
    }

    public function testSaveOptionsAndSkus_priceDiffAndStock_savedCorrectly(): void
    {
        $productId = $this->insertProduct();
        $db        = db_connect();

        $this->model->saveOptionsAndSkus($productId, [
            'options' => [['name' => '색상', 'values' => [['tmp_id' => 'c1', 'value' => '검정']]]],
            'skus'    => [['price_diff' => -500, 'stock' => 99, 'sku_code' => 'BLK', 'value_tmp_ids' => ['c1']]],
        ]);

        $sku = $db->table('product_skus')->where('product_id', $productId)->get()->getRowArray();
        $this->assertEquals(-500, (int) $sku['price_diff']);
        $this->assertSame(99, (int) $sku['stock']);
        $this->assertSame('BLK', $sku['sku_code']);
    }

    // ── D: deleteByProduct ────────────────────────────────────────────────────

    public function testDeleteByProduct_deletesAllRelatedRows(): void
    {
        $productId = $this->insertProduct();
        $db        = db_connect();

        $this->model->saveOptionsAndSkus($productId, [
            'options' => [['name' => '색상', 'values' => [['tmp_id' => 'c1', 'value' => '흰색']]]],
            'skus'    => [['price_diff' => 0, 'stock' => 3, 'sku_code' => null, 'value_tmp_ids' => ['c1']]],
        ]);

        $this->model->deleteByProduct($productId);

        $this->assertSame(0, (int) $db->table('product_options')->where('product_id', $productId)->countAllResults());
        $this->assertSame(0, (int) $db->table('product_skus')->where('product_id', $productId)->countAllResults());
    }

    public function testDeleteByProduct_noOptions_noError(): void
    {
        $productId = $this->insertProduct();

        $this->model->deleteByProduct($productId);

        $this->assertSame(0, (int) db_connect()->table('product_options')->where('product_id', $productId)->countAllResults());
    }

    // ── F: findForProduct ─────────────────────────────────────────────────────

    public function testFindForProduct_matchingSku_returnsRow(): void
    {
        $productId = $this->insertProduct();
        $this->model->saveOptionsAndSkus($productId, [
            'options' => [['name' => '색상', 'values' => [['tmp_id' => 'c1', 'value' => '노랑']]]],
            'skus'    => [['price_diff' => 0, 'stock' => 4, 'sku_code' => null, 'value_tmp_ids' => ['c1']]],
        ]);
        $skuId = (int) db_connect()->table('product_skus')->where('product_id', $productId)->get()->getRowArray()['id'];

        $result = $this->model->findForProduct($skuId, $productId);

        $this->assertNotNull($result);
        $this->assertSame($skuId, (int) $result['id']);
    }

    public function testFindForProduct_wrongProduct_returnsNull(): void
    {
        $productId1 = $this->insertProduct();
        $productId2 = $this->insertProduct();

        $this->model->saveOptionsAndSkus($productId1, [
            'options' => [['name' => '색상', 'values' => [['tmp_id' => 'c1', 'value' => '녹색']]]],
            'skus'    => [['price_diff' => 0, 'stock' => 2, 'sku_code' => null, 'value_tmp_ids' => ['c1']]],
        ]);
        $skuId = (int) db_connect()->table('product_skus')->where('product_id', $productId1)->get()->getRowArray()['id'];

        $result = $this->model->findForProduct($skuId, $productId2);

        $this->assertNull($result);
    }

    public function testFindForProduct_nonExistentSku_returnsNull(): void
    {
        $productId = $this->insertProduct();

        $result = $this->model->findForProduct(999999, $productId);

        $this->assertNull($result);
    }
}
