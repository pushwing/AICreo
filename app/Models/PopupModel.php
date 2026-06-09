<?php

namespace App\Models;

use CodeIgniter\Model;

class PopupModel extends Model
{
    protected $table         = 'popups';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'title', 'image_path', 'content', 'show_scope',
        'pos_x', 'pos_y', 'priority', 'is_active', 'started_at', 'ended_at',
    ];

    public const SCOPES = [
        'all'       => '전체 페이지',
        'home_only' => '홈 전용',
        'specific'  => '특정 페이지',
    ];

    /**
     * 현재 URI 기준으로 노출할 팝업 목록 반환
     */
    public function getActiveForPage(string $uri): array
    {
        $now    = date('Y-m-d H:i:s');
        $isHome = ($uri === '' || $uri === '/');

        $scopes = ['all'];
        if ($isHome) {
            $scopes[] = 'home_only';
        }

        $general = (new self())
            ->where('is_active', 1)
            ->where("(started_at IS NULL OR started_at <= '{$now}')")
            ->where("(ended_at IS NULL OR ended_at >= '{$now}')")
            ->whereIn('show_scope', $scopes)
            ->orderBy('priority', 'ASC')
            ->findAll();

        $db = \Config\Database::connect();
        $specific = $db->table('popups p')
            ->select('p.*')
            ->join('popup_pages pp', 'pp.popup_id = p.id')
            ->join('menus m', 'm.id = pp.menu_id')
            ->where('p.is_active', 1)
            ->where("(p.started_at IS NULL OR p.started_at <= '{$now}')")
            ->where("(p.ended_at IS NULL OR p.ended_at >= '{$now}')")
            ->where('p.show_scope', 'specific')
            ->where('m.url', '/' . ltrim($uri, '/'))
            ->orderBy('p.priority', 'ASC')
            ->get()->getResultArray();

        $seen   = [];
        $result = [];
        foreach (array_merge($general, $specific) as $popup) {
            if (! isset($seen[$popup['id']])) {
                $seen[$popup['id']] = true;
                $result[]           = $popup;
            }
        }
        usort($result, fn($a, $b) => $a['priority'] <=> $b['priority']);

        return $result;
    }

    /**
     * 팝업에 연결된 menu_id 배열 반환
     */
    public function getPageIds(int $popupId): array
    {
        $rows = \Config\Database::connect()
            ->table('popup_pages')
            ->where('popup_id', $popupId)
            ->get()->getResultArray();
        return array_column($rows, 'menu_id');
    }

    /**
     * 팝업-페이지 연결 동기화 (트랜잭션)
     */
    public function syncPages(int $popupId, array $menuIds): void
    {
        $db = \Config\Database::connect();
        $db->transStart();

        $db->table('popup_pages')->where('popup_id', $popupId)->delete();

        if (! empty($menuIds)) {
            $rows = array_map(fn($menuId) => [
                'popup_id' => $popupId,
                'menu_id'  => (int) $menuId,
            ], $menuIds);
            $db->table('popup_pages')->insertBatch($rows);
        }

        $db->transComplete();
    }

    public function deleteWithFile(int $id): bool
    {
        $popup = $this->find($id);
        if (! $popup) return false;

        if ($popup['image_path']) {
            $fullPath = FCPATH . $popup['image_path'];
            if (file_exists($fullPath)) unlink($fullPath);
        }

        \Config\Database::connect()->table('popup_pages')->where('popup_id', $id)->delete();

        return (bool) $this->delete($id);
    }
}
