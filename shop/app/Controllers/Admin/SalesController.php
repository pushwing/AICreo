<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class SalesController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();

        $period  = $this->request->getGet('period')  ?? 'daily';
        $keyword = trim($this->request->getGet('keyword') ?? '');
        $from    = $this->request->getGet('from') ?? date('Y-m-01');
        $to      = $this->request->getGet('to')   ?? date('Y-m-d');

        if (! in_array($period, ['daily', 'weekly', 'monthly'], true)) {
            $period = 'daily';
        }

        // ── 공통 베이스 빌더 (paid 주문 + 결제 정보 조인) ─────────────────────
        $base = $db->table('orders o')
            ->select('o.id, o.order_number, o.total_amount, o.created_at, o.receiver_name,
                      u.nickname, u.email,
                      p.method AS payment_method, p.pg_provider')
            ->join('users u',    'u.id = o.user_id', 'left')
            ->join('payments p', 'p.order_id = o.id', 'left')
            ->where('o.status', 'paid')
            ->where('DATE(o.created_at) >=', $from)
            ->where('DATE(o.created_at) <=', $to);

        if ($keyword !== '') {
            $base->groupStart()
                ->like('o.order_number', $keyword)
                ->orLike('o.receiver_name', $keyword)
                ->orLike('u.nickname', $keyword)
                ->orLike('u.email', $keyword)
                ->groupEnd();
        }

        // ── 기간별 매출 집계 ──────────────────────────────────────────────────
        $groupExpr = match ($period) {
            'weekly'  => "DATE_FORMAT(DATE_SUB(o.created_at, INTERVAL (DAYOFWEEK(o.created_at)-2+7)%7 DAY), '%Y-%m-%d')",
            'monthly' => "DATE_FORMAT(o.created_at, '%Y-%m')",
            default   => "DATE(o.created_at)",
        };

        $periodRows = (clone $base)
            ->select("{$groupExpr} AS period_key, COUNT(o.id) AS order_count, SUM(o.total_amount) AS revenue", false)
            ->groupBy('period_key')
            ->orderBy('period_key', 'DESC')
            ->get()->getResultArray();

        // ── 결제수단별 집계 ────────────────────────────────────────────────────
        $methodRows = (clone $base)
            ->select('p.pg_provider, p.method, COUNT(o.id) AS order_count, SUM(o.total_amount) AS revenue', false)
            ->groupBy('p.pg_provider, p.method')
            ->orderBy('revenue', 'DESC')
            ->get()->getResultArray();

        // ── 요약 카드 ──────────────────────────────────────────────────────────
        $summary = (clone $base)
            ->select('COUNT(o.id) AS total_orders, SUM(o.total_amount) AS total_revenue, AVG(o.total_amount) AS avg_order', false)
            ->get()->getRowArray();

        // ── 주문 목록 (검색 결과 / 최근 50건) ────────────────────────────────
        $orders = (clone $base)
            ->orderBy('o.id', 'DESC')
            ->limit(50)
            ->get()->getResultArray();

        $pgLabels = [
            'bank_transfer' => '무통장입금',
            'toss'          => '토스페이먼츠',
            'inicis'        => 'KG이니시스',
            'nicepay'       => '나이스페이',
            'kakaopay'      => '카카오페이',
            'naverpay'      => '네이버페이',
            'payco'         => 'PAYCO',
        ];

        return $this->render('admin/sales/index', compact(
            'period', 'keyword', 'from', 'to',
            'periodRows', 'methodRows', 'summary', 'orders', 'pgLabels'
        ));
    }
}
