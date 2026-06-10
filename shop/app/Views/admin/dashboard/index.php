<?= $this->extend('layouts/admin') ?>
<?php $pageTitle = '대시보드' ?>
<?= $this->section('content') ?>

<!-- 통계 카드 -->
<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['label' => '총 게시글',  'value' => $stats['total_posts'],     'icon' => 'bi-card-text',  'color' => 'primary', 'href' => '/admin/posts'],
        ['label' => '총 회원',    'value' => $stats['total_users'],     'icon' => 'bi-people',     'color' => 'success', 'href' => '/admin/users'],
        ['label' => '전체 문의',  'value' => $stats['total_inquiries'], 'icon' => 'bi-envelope',   'color' => 'info',    'href' => '/admin/inquiries'],
        ['label' => '미읽음 문의','value' => $stats['unread_inquiries'],'icon' => 'bi-bell',       'color' => 'warning', 'href' => '/admin/inquiries?filter=unread'],
    ];
    foreach ($cards as $c):
    ?>
    <div class="col-sm-6 col-xl-3">
        <a href="<?= $c['href'] ?>" class="card border-0 shadow-sm text-decoration-none">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="bg-<?= $c['color'] ?> bg-opacity-10 rounded p-3">
                    <i class="bi <?= $c['icon'] ?> fs-4 text-<?= $c['color'] ?>"></i>
                </div>
                <div>
                    <div class="text-muted small"><?= $c['label'] ?></div>
                    <div class="fs-4 fw-bold text-dark"><?= number_format($c['value']) ?></div>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- 최근 주문 + 재고 부족 -->
<div class="row g-3 mb-3">
    <!-- 최근 주문 -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between">
                <strong>최근 주문</strong>
                <a href="/admin/orders" class="small text-decoration-none">전체보기</a>
            </div>
            <div class="list-group list-group-flush">
                <?php
                $statusLabels = [
                    'awaiting_payment' => ['label' => '입금대기', 'class' => 'warning'],
                    'paid'             => ['label' => '결제완료', 'class' => 'success'],
                    'preparing'        => ['label' => '준비중',   'class' => 'info'],
                    'shipped'          => ['label' => '배송중',   'class' => 'primary'],
                    'delivered'        => ['label' => '배송완료', 'class' => 'secondary'],
                    'cancelled'        => ['label' => '취소',     'class' => 'danger'],
                    'refund_requested' => ['label' => '환불요청', 'class' => 'danger'],
                    'refunded'         => ['label' => '환불완료', 'class' => 'secondary'],
                ];
                foreach ($recentOrders as $order):
                    $sl = $statusLabels[$order['status']] ?? ['label' => $order['status'], 'class' => 'secondary'];
                ?>
                <a href="/admin/orders/<?= $order['id'] ?>"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <div class="small">
                        <span class="badge bg-<?= $sl['class'] ?> me-1"><?= $sl['label'] ?></span>
                        <span class="text-dark"><?= esc($order['order_number']) ?></span>
                        <span class="text-muted ms-1"><?= esc($order['user_nickname']) ?></span>
                    </div>
                    <div class="text-end flex-shrink-0 ms-2">
                        <div class="small fw-semibold"><?= number_format($order['total_amount']) ?>원</div>
                        <div class="text-muted" style="font-size:.75rem"><?= substr($order['created_at'], 0, 10) ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
                <?php if (empty($recentOrders)): ?>
                    <div class="list-group-item text-muted small text-center py-3">주문이 없습니다</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 재고 부족 (5개 이하) -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between">
                <strong>재고 부족 <span class="text-danger small fw-normal">5개 이하</span></strong>
                <a href="/admin/inventory" class="small text-decoration-none">재고관리</a>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($lowStockProducts as $prod): ?>
                <a href="/admin/products/<?= $prod['id'] ?>/edit"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <span class="small text-truncate" style="max-width:240px"><?= esc($prod['name']) ?></span>
                    <span class="badge <?= $prod['stock'] == 0 ? 'bg-danger' : 'bg-warning text-dark' ?> flex-shrink-0">
                        <?= $prod['stock'] == 0 ? '품절' : $prod['stock'].'개' ?>
                    </span>
                </a>
                <?php endforeach; ?>
                <?php if (empty($lowStockProducts)): ?>
                    <div class="list-group-item text-muted small text-center py-3">재고 부족 상품이 없습니다</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- 최근 문의 -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between">
                <strong>최근 문의</strong>
                <a href="/admin/inquiries" class="small text-decoration-none">전체보기</a>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($recentInquiries as $inq): ?>
                <a href="/admin/inquiries/<?= $inq['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <div>
                        <?php if (! $inq['is_read']): ?><span class="badge bg-danger me-1">NEW</span><?php endif; ?>
                        <span class="small"><?= esc($inq['name']) ?></span>
                        <span class="text-muted small ms-1"><?= esc($inq['subject'] ?: mb_substr($inq['message'], 0, 20)) ?></span>
                    </div>
                    <span class="text-muted small"><?= substr($inq['created_at'], 0, 10) ?></span>
                </a>
                <?php endforeach; ?>
                <?php if (empty($recentInquiries)): ?>
                    <div class="list-group-item text-muted small text-center py-3">문의가 없습니다</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 최근 게시글 -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between">
                <strong>최근 게시글</strong>
                <a href="/admin/posts" class="small text-decoration-none">전체보기</a>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($recentPosts as $post): ?>
                <a href="/board/<?= esc($post['board_slug']) ?>/<?= $post['id'] ?>"
                   target="_blank"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <div class="text-truncate" style="max-width:240px">
                        <span class="badge bg-light text-dark border me-1 small"><?= esc($post['board_name']) ?></span>
                        <span class="small text-dark"><?= esc($post['title']) ?></span>
                    </div>
                    <span class="text-muted small flex-shrink-0 ms-2"><?= substr($post['created_at'], 0, 10) ?></span>
                </a>
                <?php endforeach; ?>
                <?php if (empty($recentPosts)): ?>
                    <div class="list-group-item text-muted small text-center py-3">게시글이 없습니다</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
