<?= $this->extend('layouts/admin') ?>
<?php $pageTitle = '쿠폰 관리' ?>
<?php use App\Libraries\GradeService; ?>

<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0">쿠폰 관리</h4>
    <a href="/admin/coupons/create" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>쿠폰 등록
    </a>
</div>


<div class="card overflow-hidden">
    <div class="card-header bg-white">
        <form method="get" class="d-flex gap-2">
            <input type="text" name="keyword" class="form-control form-control-sm"
                   placeholder="쿠폰명, 코드" value="<?= esc($keyword) ?>" style="max-width:240px">
            <button type="submit" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-search"></i>
            </button>
            <?php if ($keyword): ?>
            <a href="/admin/coupons" class="btn btn-sm btn-outline-secondary">초기화</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>쿠폰명</th>
                    <th>코드</th>
                    <th>종류</th>
                    <th>대상 등급</th>
                    <th class="text-end">할인값</th>
                    <th class="text-end">최소주문</th>
                    <th class="text-center">수량</th>
                    <th>유효기간</th>
                    <th class="text-center">상태</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                <tr><td colspan="10" class="text-center py-4 text-muted">등록된 쿠폰이 없습니다.</td></tr>
                <?php else: foreach ($items as $c):
                    $typeLabel = $types[$c['type']] ?? $c['type'];
                    if ($c['type'] === 'free_shipping') {
                        $discLabel = '—';
                    } elseif ($c['type'] === 'fixed') {
                        $discLabel = number_format($c['discount_value']) . '원';
                    } else {
                        $discLabel = $c['discount_value'] . '%';
                        if ((int) $c['max_discount_amount'] > 0)
                            $discLabel .= ' (최대 ' . number_format($c['max_discount_amount']) . '원)';
                    }
                ?>
                <tr>
                    <td class="fw-semibold small"><?= esc($c['name']) ?></td>
                    <td class="font-monospace small"><?= esc($c['code']) ?></td>
                    <td class="small"><?= esc($typeLabel) ?></td>
                    <td class="small">
                        <?php if (! empty($c['target_grade'])): ?>
                            <?php foreach (array_map('trim', explode(',', $c['target_grade'])) as $g): ?>
                                <span class="badge <?= GradeService::BADGE_CLASSES[$g] ?? 'bg-secondary' ?>">
                                    <?= esc(GradeService::LABELS[$g] ?? $g) ?>
                                </span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-muted">전체</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end small"><?= esc($discLabel) ?></td>
                    <td class="text-end small">
                        <?= (int) $c['min_order_amount'] > 0 ? number_format($c['min_order_amount']) . '원' : '—' ?>
                    </td>
                    <td class="text-center small">
                        <?= $c['total_qty'] !== null ? number_format($c['used_count']) . '/' . number_format($c['total_qty']) : '무제한' ?>
                    </td>
                    <td class="small text-muted">
                        <?= $c['starts_at']  ? date('Y년 n월 j일', strtotime($c['starts_at']))  : '' ?>
                        <?= ($c['starts_at'] && $c['expires_at']) ? ' ~ ' : '' ?>
                        <?= $c['expires_at'] ? date('Y년 n월 j일', strtotime($c['expires_at'])) : ($c['starts_at'] ? '~' : '—') ?>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-<?= $c['is_active'] ? 'success' : 'secondary' ?>">
                            <?= $c['is_active'] ? '활성' : '비활성' ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="/admin/coupons/<?= (int) $c['id'] ?>/issue" class="btn btn-xs btn-outline-info"
                           style="font-size:.72rem;padding:.15rem .45rem">발급</a>
                        <a href="/admin/coupons/<?= (int) $c['id'] ?>/edit" class="btn btn-xs btn-outline-secondary"
                           style="font-size:.72rem;padding:.15rem .45rem">수정</a>
                        <form method="post" action="/admin/coupons/<?= (int) $c['id'] ?>/delete"
                              class="d-inline"
                              onsubmit="return confirm('비활성화하시겠습니까?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-xs btn-outline-danger"
                                    style="font-size:.72rem;padding:.15rem .45rem">비활성화</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total > $perPage): ?>
    <div class="card-footer bg-white">
        <nav>
            <ul class="pagination pagination-sm mb-0 justify-content-center">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $p ?>&keyword=<?= esc($keyword) ?>"><?= $p ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
