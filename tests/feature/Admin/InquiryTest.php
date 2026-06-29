<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\InquiryModel;
use Tests\Support\AdminTestCase;

/**
 * @internal
 */
final class InquiryTest extends AdminTestCase
{
    private function makeInquiry(): int
    {
        return (int) (new InquiryModel())->insert([
            'name'       => '문의자',
            'email'      => 'asker@example.com',
            'subject'    => '제목',
            'message'    => '내용',
            'ip_address' => '127.0.0.1',
            'is_read'    => 0,
        ]);
    }

    public function testViewMarksInquiryAsRead(): void
    {
        $id = $this->makeInquiry();

        $result = $this->withSession($this->adminSession)->get("admin/inquiries/{$id}");

        $result->assertStatus(200);
        $this->assertSame(1, (int) (new InquiryModel())->find($id)['is_read']);
    }

    public function testAdminDeletesInquiry(): void
    {
        $id = $this->makeInquiry();

        $result = $this->withSession($this->adminSession)->post("admin/inquiries/{$id}/delete");

        $result->assertRedirectTo('/admin/inquiries');
        $this->assertNull((new InquiryModel())->find($id));
    }
}
