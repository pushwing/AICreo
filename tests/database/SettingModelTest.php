<?php

declare(strict_types=1);

namespace Tests\Database;

use App\Models\SettingModel;
use Tests\Support\DatabaseTestCase;

/**
 * @internal
 */
final class SettingModelTest extends DatabaseTestCase
{
    private SettingModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        cache()->delete('site_settings');
        $this->model = new SettingModel();
    }

    public function testGetAllAsMapReturnsKeyValuePairs(): void
    {
        $map = $this->model->getAllAsMap();

        $this->assertArrayHasKey('site_name', $map);
        $this->assertArrayHasKey('email', $map);
    }

    public function testGetGroupReturnsOnlyRequestedGroup(): void
    {
        $rows = $this->model->getGroup('contact');

        $this->assertNotEmpty($rows);

        foreach ($rows as $row) {
            $this->assertSame('contact', $row['group']);
        }
    }

    public function testSaveSettingsUpdatesExistingKey(): void
    {
        $this->model->saveSettings(['site_name' => '새 사이트명']);

        $this->assertSame('새 사이트명', $this->model->getAllAsMap()['site_name']);
    }

    public function testSaveSettingsInsertsNewKey(): void
    {
        $this->model->saveSettings(['brand_new_key' => 'value!']);

        $this->assertSame('value!', $this->model->getAllAsMap()['brand_new_key']);
    }

    public function testSaveSettingsInvalidatesCache(): void
    {
        // 캐시 워밍
        $this->model->getAllAsMap();
        $this->model->saveSettings(['site_name' => '변경됨']);

        // 캐시가 비워졌으므로 새 값이 보여야 함
        $this->assertSame('변경됨', cache()->get('site_settings')['site_name'] ?? $this->model->getAllAsMap()['site_name']);
    }
}
