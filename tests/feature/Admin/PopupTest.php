<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\PopupModel;
use Tests\Support\AdminTestCase;

/**
 * @internal
 */
final class PopupTest extends AdminTestCase
{
    private function makePopup(): int
    {
        return (int) (new PopupModel())->insert([
            'title'      => '기존 팝업',
            'show_scope' => 'all',
            'priority'   => 0,
            'is_active'  => 1,
        ]);
    }

    public function testIndexLoads(): void
    {
        $this->withSession($this->adminSession)->get('admin/popups')->assertStatus(200);
    }

    public function testStoreCreatesPopupWithoutImage(): void
    {
        // 팝업 이미지는 선택 사항
        $result = $this->withSession($this->adminSession)->post('admin/popups/create', [
            'title'      => '신규 이벤트',
            'show_scope' => 'all',
        ]);

        $result->assertRedirectTo('/admin/popups');
        $this->assertSame(1, (new PopupModel())->where('title', '신규 이벤트')->countAllResults());
    }

    public function testStoreRequiresTitle(): void
    {
        $result = $this->withSession($this->adminSession)->post('admin/popups/create', [
            'title'      => '',
            'show_scope' => 'all',
        ]);

        $result->assertRedirect();
        $this->assertSame(0, (new PopupModel())->countAllResults());
    }

    public function testStoreLinksSpecificPages(): void
    {
        // show_scope=specific + page_ids(시드 메뉴 id) 연결
        $this->withSession($this->adminSession)->post('admin/popups/create', [
            'title'      => '특정 페이지 팝업',
            'show_scope' => 'specific',
            'page_ids'   => [2, 3],
        ]);

        $model = new PopupModel();
        $id    = (int) $model->where('title', '특정 페이지 팝업')->first()['id'];
        $this->assertEqualsCanonicalizing(['2', '3'], $model->getPageIds($id));
    }

    public function testUpdateChangesFields(): void
    {
        $id = $this->makePopup();

        $result = $this->withSession($this->adminSession)->post("admin/popups/{$id}/edit", [
            'title'      => '수정된 팝업',
            'show_scope' => 'home_only',
        ]);

        $result->assertRedirectTo('/admin/popups');
        $popup = (new PopupModel())->find($id);
        $this->assertSame('수정된 팝업', $popup['title']);
        $this->assertSame('home_only', $popup['show_scope']);
    }

    public function testAdminDeletesPopup(): void
    {
        $id = $this->makePopup();

        $result = $this->withSession($this->adminSession)->post("admin/popups/{$id}/delete");

        $result->assertRedirectTo('/admin/popups');
        $this->assertNull((new PopupModel())->find($id));
    }
}
