<?php

namespace App\Models;

use CodeIgniter\Model;

class InquiryModel extends Model
{
    protected $table      = 'inquiries';
    protected $primaryKey = 'id';
    protected $useTimestamps  = true;
    protected $updatedField   = '';
    protected $allowedFields  = [
        'name', 'email', 'phone', 'subject', 'message', 'ip_address', 'is_read',
    ];

    public function getUnreadCount(): int
    {
        return $this->where('is_read', 0)->countAllResults();
    }

    public function markRead(int $id): void
    {
        $this->update($id, ['is_read' => 1]);
    }
}
