<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use Config\Database;

class StatsController extends BaseController
{
    /** GET /admin/stats */
    public function index(): string
    {
        $db = Database::connect();

        $from = $this->request->getGet('from') ?: date('Y-m-d', strtotime('-29 days'));
        $to   = $this->request->getGet('to')   ?: date('Y-m-d');

        // 날짜 유효성 보정
        if ($from > $to) [$from, $to] = [$to, $from];

        $fromDt = $from . ' 00:00:00';
        $toDt   = $to   . ' 23:59:59';

        // 일별 PV / UV
        $dailyRaw = $db->query(
            "SELECT DATE(created_at) AS day,
                    COUNT(*) AS pv,
                    COUNT(DISTINCT ip) AS uv
             FROM access_logs
             WHERE created_at BETWEEN ? AND ?
             GROUP BY DATE(created_at)
             ORDER BY day ASC",
            [$fromDt, $toDt]
        )->getResultArray();

        // 날짜 범위 전체 채우기 (데이터 없는 날 = 0)
        $dailyMap = [];
        foreach ($dailyRaw as $row) {
            $dailyMap[$row['day']] = $row;
        }
        $dailyLabels = $dailyPv = $dailyUv = [];
        $cursor = new \DateTime($from);
        $end    = new \DateTime($to);
        while ($cursor <= $end) {
            $d = $cursor->format('Y-m-d');
            $dailyLabels[] = $cursor->format('m/d');
            $dailyPv[]     = (int) ($dailyMap[$d]['pv'] ?? 0);
            $dailyUv[]     = (int) ($dailyMap[$d]['uv'] ?? 0);
            $cursor->modify('+1 day');
        }

        // 페이지별 순위 (상위 20)
        $topPages = $db->query(
            "SELECT page,
                    COUNT(*) AS hits,
                    COUNT(DISTINCT ip) AS unique_visitors
             FROM access_logs
             WHERE created_at BETWEEN ? AND ?
             GROUP BY page
             ORDER BY hits DESC
             LIMIT 20",
            [$fromDt, $toDt]
        )->getResultArray();

        // 기간 요약
        $summary = $db->query(
            "SELECT COUNT(*) AS total_pv,
                    COUNT(DISTINCT ip) AS total_uv
             FROM access_logs
             WHERE created_at BETWEEN ? AND ?",
            [$fromDt, $toDt]
        )->getRow();

        $today = date('Y-m-d');
        $todayRow = $db->query(
            "SELECT COUNT(*) AS pv, COUNT(DISTINCT ip) AS uv
             FROM access_logs WHERE DATE(created_at) = ?",
            [$today]
        )->getRow();

        return $this->render('admin/stats/index', [
            'from'         => $from,
            'to'           => $to,
            'dailyLabels'  => $dailyLabels,
            'dailyPv'      => $dailyPv,
            'dailyUv'      => $dailyUv,
            'topPages'     => $topPages,
            'totalPv'      => (int) ($summary->total_pv ?? 0),
            'totalUv'      => (int) ($summary->total_uv ?? 0),
            'todayPv'      => (int) ($todayRow->pv ?? 0),
            'todayUv'      => (int) ($todayRow->uv ?? 0),
        ]);
    }
}
