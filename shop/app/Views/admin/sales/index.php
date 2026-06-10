<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0">매출 관리</h4>
</div>

<!-- ─── 검색 / 필터 ──────────────────────────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="/admin/sales" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small mb-1">기간 구분</label>
                <div class="btn-group" role="group">
                    <?php foreach (['daily' => '일별', 'weekly' => '주별', 'monthly' => '월별'] as $val => $label): ?>
                    <a href="?period=<?= $val ?>&from=<?= esc($from) ?>&to=<?= esc($to) ?>&keyword=<?= esc($keyword) ?>"
                       class="btn btn-sm <?= $period === $val ? 'btn-dark' : 'btn-outline-secondary' ?>">
                        <?= $label ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-1">시작일</label>
                <input type="date" name="from" class="form-control form-control-sm" value="<?= esc($from) ?>">
            </div>
            <div class="col-auto">
                <label class="form-label small mb-1">종료일</label>
                <input type="date" name="to" class="form-control form-control-sm" value="<?= esc($to) ?>">
            </div>
            <input type="hidden" name="period" value="<?= esc($period) ?>">
            <div class="col">
                <label class="form-label small mb-1">검색</label>
                <input type="text" name="keyword" class="form-control form-control-sm"
                       placeholder="주문번호, 수신자명, 회원명, 이메일"
                       value="<?= esc($keyword) ?>">
            </div>
            <div class="col-auto d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-search me-1"></i>조회
                </button>
                <?php if ($keyword !== ''): ?>
                <a href="?period=<?= esc($period) ?>&from=<?= esc($from) ?>&to=<?= esc($to) ?>"
                   class="btn btn-sm btn-outline-secondary">초기화</a>
                <?php endif; ?>
            </div>
            <!-- 빠른 기간 버튼 -->
            <div class="col-12 d-flex gap-1 mt-1">
                <?php
                $quickPeriods = [
                    '이번 달'   => [date('Y-m-01'),                      date('Y-m-d')],
                    '지난 달'   => [date('Y-m-01', strtotime('-1 month')), date('Y-m-t', strtotime('-1 month'))],
                    '최근 7일'  => [date('Y-m-d', strtotime('-6 days')),  date('Y-m-d')],
                    '최근 30일' => [date('Y-m-d', strtotime('-29 days')), date('Y-m-d')],
                    '올해'      => [date('Y-01-01'),                      date('Y-m-d')],
                ];
                foreach ($quickPeriods as $label => [$qFrom, $qTo]):
                    $active = $from === $qFrom && $to === $qTo;
                ?>
                <a href="?period=<?= esc($period) ?>&from=<?= $qFrom ?>&to=<?= $qTo ?>&keyword=<?= esc($keyword) ?>"
                   class="btn btn-xs btn-outline-secondary <?= $active ? 'active' : '' ?>"
                   style="font-size:.75rem;padding:.2rem .55rem">
                    <?= $label ?>
                </a>
                <?php endforeach; ?>
            </div>
        </form>
    </div>
</div>

<!-- ─── 요약 카드 ─────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small mb-1">실 매출 (결제금액 합계)</div>
                <div class="fs-4 fw-bold text-primary">
                    <?= number_format((int) ($summary['total_revenue'] ?? 0)) ?>원
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small mb-1">GMV (할인 전 합계)</div>
                <div class="fs-4 fw-bold">
                    <?= number_format((int) ($summary['total_gmv'] ?? 0)) ?>원
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small mb-1">총 할인액 (쿠폰+포인트)</div>
                <div class="fs-4 fw-bold text-danger">
                    <?= number_format((int) ($summary['total_discount'] ?? 0)) ?>원
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="text-muted small mb-1">총 주문 수 / 평균 결제액</div>
                <div class="fs-5 fw-bold"><?= number_format((int) ($summary['total_orders'] ?? 0)) ?>건</div>
                <div class="small text-muted"><?= number_format((int) ($summary['avg_order'] ?? 0)) ?>원/건</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">

    <!-- ─── 기간별 매출 ────────────────────────────────────────────────────── -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">
                <?= ['daily' => '일별', 'weekly' => '주별', 'monthly' => '월별'][$period] ?> 매출
            </div>
            <?php if (empty($periodRows)): ?>
            <div class="card-body text-center text-muted py-5">데이터가 없습니다.</div>
            <?php else: ?>
            <div style="max-height:420px;overflow-y:auto">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th><?= ['daily' => '날짜', 'weekly' => '주 시작일', 'monthly' => '월'][$period] ?></th>
                            <th class="text-end">주문 수</th>
                            <th class="text-end">GMV</th>
                            <th class="text-end">할인</th>
                            <th class="text-end">실 매출</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($periodRows as $row): ?>
                        <tr>
                            <td class="small"><?= esc($row['period_key']) ?></td>
                            <td class="text-end small"><?= number_format($row['order_count']) ?>건</td>
                            <td class="text-end small text-muted"><?= number_format($row['gmv']) ?>원</td>
                            <td class="text-end small text-danger"><?= $row['total_discount'] > 0 ? '-' . number_format($row['total_discount']) : '—' ?>원</td>
                            <td class="text-end fw-semibold"><?= number_format($row['revenue']) ?>원</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ─── 결제수단별 매출 ───────────────────────────────────────────────── -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">결제수단별 매출</div>
            <?php if (empty($methodRows)): ?>
            <div class="card-body text-center text-muted py-5">데이터가 없습니다.</div>
            <?php else:
                $totalRevenue = array_sum(array_column($methodRows, 'revenue'));
            ?>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>결제수단</th>
                            <th class="text-end">건수</th>
                            <th class="text-end">매출</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($methodRows as $row):
                            $pgLabel  = $pgLabels[$row['pg_provider']] ?? $row['pg_provider'];
                            $method   = $row['method'] ?? '';
                            $label    = $pgLabel . ($method && $method !== $pgLabel ? ' · ' . $method : '');
                            $pct      = $totalRevenue > 0 ? round($row['revenue'] / $totalRevenue * 100) : 0;
                        ?>
                        <tr>
                            <td class="small">
                                <?= esc($label) ?>
                                <div class="progress mt-1" style="height:3px">
                                    <div class="progress-bar" style="width:<?= $pct ?>%"></div>
                                </div>
                            </td>
                            <td class="text-end small"><?= number_format($row['order_count']) ?>건</td>
                            <td class="text-end small fw-semibold"><?= number_format($row['revenue']) ?>원</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ─── 주문 목록 ─────────────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-header bg-white d-flex align-items-center justify-content-between">
        <span class="fw-semibold">결제 완료 주문 목록</span>
        <span class="text-muted small">최대 50건 표시</span>
    </div>
    <?php if (empty($orders)): ?>
    <div class="card-body text-center text-muted py-5">데이터가 없습니다.</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>주문번호</th>
                    <th>주문일시</th>
                    <th>수신자</th>
                    <th>회원</th>
                    <th>결제수단</th>
                    <th class="text-end">GMV</th>
                    <th class="text-end">실 매출</th>
                    <th class="text-end">상품금액<br><span class="fw-normal text-muted" style="font-size:.7rem">(배송비 제외)</span></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order):
                    $pgLabel = $pgLabels[$order['pg_provider']] ?? ($order['pg_provider'] ?? '—');
                ?>
                <tr>
                    <td class="small font-monospace">
                        <a href="/admin/orders/<?= (int) $order['id'] ?>" class="text-decoration-none">
                            <?= esc($order['order_number']) ?>
                        </a>
                    </td>
                    <td class="small text-muted"><?= date('Y.m.d H:i', strtotime($order['created_at'])) ?></td>
                    <td class="small"><?= esc($order['receiver_name']) ?></td>
                    <td class="small"><?= esc($order['nickname'] ?? $order['email'] ?? '—') ?></td>
                    <td class="small"><?= esc($pgLabel) ?><?= $order['payment_method'] ? ' · ' . esc($order['payment_method']) : '' ?></td>
                    <td class="text-end small text-muted"><?= number_format($order['total_amount']) ?>원</td>
                    <td class="text-end fw-semibold small"><?= number_format($order['payable_amount']) ?>원</td>
                    <td class="text-end small text-success"><?= number_format(max(0, (int)$order['payable_amount'] - (int)$order['shipping_fee'])) ?>원</td>
                    <td class="text-end">
                        <a href="/admin/orders/<?= (int) $order['id'] ?>" class="btn btn-xs btn-outline-secondary"
                           style="font-size:.72rem;padding:.15rem .45rem">상세</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
