<?php

declare(strict_types=1);

namespace Tests\Database;

use App\Models\InquiryModel;
use Tests\Support\DatabaseTestCase;

/**
 * @internal
 */
final class InquiryModelTest extends DatabaseTestCase
{
    private InquiryModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new InquiryModel();
    }

    private function makeInquiry(int $isRead = 0): int
    {
        return (int) $this->model->insert([
            'name'       => '홍길동',
            'email'      => 'gildong@example.com',
            'subject'    => '문의합니다',
            'message'    => '내용입니다',
            'ip_address' => '127.0.0.1',
            'is_read'    => $isRead,
        ]);
    }

    public function testGetUnreadCountCountsOnlyUnread(): void
    {
        $this->makeInquiry(0);
        $this->makeInquiry(0);
        $this->makeInquiry(1);

        $this->assertSame(2, $this->model->getUnreadCount());
    }

    public function testMarkReadFlipsFlag(): void
    {
        $id = $this->makeInquiry(0);
        $this->assertSame(1, $this->model->getUnreadCount());

        $this->model->markRead($id);

        $this->assertSame(0, $this->model->getUnreadCount());
        $this->assertSame(1, (int) $this->model->find($id)['is_read']);
    }
}
