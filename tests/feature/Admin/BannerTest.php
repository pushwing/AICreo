<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\BannerModel;
use Tests\Support\AdminTestCase;

/**
 * @internal
 */
final class BannerTest extends AdminTestCase
{
    private function makeBanner(): int
    {
        return (int) (new BannerModel())->insert([
            'image_path' => 'uploads/banners/sample.jpg',
            'position'   => 'main_top',
            'priority'   => 0,
            'is_active'  => 1,
        ]);
    }

    public function testIndexLoads(): void
    {
        $this->withSession($this->adminSession)->get('admin/banners')->assertStatus(200);
    }

    public function testStoreRejectsInvalidPosition(): void
    {
        $result = $this->withSession($this->adminSession)->post('admin/banners/create', [
            'position' => 'nowhere',
        ]);

        $result->assertRedirect();
        $this->assertSame(0, (new BannerModel())->countAllResults());
    }

    public function testStoreRequiresImage(): void
    {
        // position 은 유효하지만 이미지 미첨부
        $result = $this->withSession($this->adminSession)->post('admin/banners/create', [
            'position' => 'main_top',
        ]);

        $result->assertRedirect();
        $this->assertSame(0, (new BannerModel())->countAllResults());
    }

    public function testUpdateChangesFieldsKeepingImage(): void
    {
        $id = $this->makeBanner();

        $result = $this->withSession($this->adminSession)->post("admin/banners/{$id}/edit", [
            'position'  => 'sub_left',
            'link_url'  => 'https://example.com',
            'priority'  => 3,
            'is_active' => 1,
        ]);

        $result->assertRedirectTo('/admin/banners');
        $banner = (new BannerModel())->find($id);
        $this->assertSame('sub_left', $banner['position']);
        $this->assertSame('https://example.com', $banner['link_url']);
        $this->assertSame('uploads/banners/sample.jpg', $banner['image_path']);
    }

    public function testAdminDeletesBanner(): void
    {
        $id = $this->makeBanner();

        $result = $this->withSession($this->adminSession)->post("admin/banners/{$id}/delete");

        $result->assertRedirectTo('/admin/banners');
        $this->assertNull((new BannerModel())->find($id));
    }
}
