<?php

namespace App\Models;

use CodeIgniter\Model;

class BannerModel extends Model
{
    protected $table         = 'banners';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'image_path', 'link_url', 'link_target', 'position',
        'priority', 'is_active', 'started_at', 'ended_at',
    ];

    public const POSITIONS = [
        'main_top'    => '메인 상단',
        'main_bottom' => '메인 하단',
        'sub_left'    => '서브 좌측',
        'sub_right'   => '서브 우측',
    ];

    public function getActiveByPosition(string $position): array
    {
        $now = date('Y-m-d H:i:s');
        return $this->where('position', $position)
            ->where('is_active', 1)
            ->where("(started_at IS NULL OR started_at <= '{$now}')")
            ->where("(ended_at IS NULL OR ended_at >= '{$now}')")
            ->orderBy('priority', 'ASC')
            ->findAll();
    }

    public function deleteWithFile(int $id): bool
    {
        $banner = $this->find($id);
        if (! $banner) return false;

        $fullPath = FCPATH . $banner['image_path'];
        if (file_exists($fullPath)) unlink($fullPath);

        return (bool) $this->delete($id);
    }
}
