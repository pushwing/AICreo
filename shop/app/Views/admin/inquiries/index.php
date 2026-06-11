<?= $this->extend('layouts/admin') ?>
<?php $pageTitle = '문의 수신함' ?>
<?= $this->section('content') ?>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?= $filter === '' ? 'active' : '' ?>" href="/admin/inquiries">
            전체 <span class="badge bg-secondary ms-1"><?= number_format($totalAll) ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $filter === 'unread' ? 'active' : '' ?>" href="/admin/inquiries?filter=unread">
            미읽음
            <?php if ($unreadCount > 0): ?>
            <span class="badge bg-danger ms-1"><?= $unreadCount ?></span>
            <?php endif; ?>
        </a>
    </li>
</ul>

<div class="card border-0 shadow-sm">
    <table class="table table-hover mb-0 small">
        <thead class="table-light">
            <tr><th></th><th>이름</th><th>이메일</th><th>제목</th><th>날짜</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($inquiries as $inq): ?>
            <tr class="<?= ! $inq['is_read'] ? 'fw-semibold' : '' ?>">
                <td><?php if (! $inq['is_read']): ?><span class="badge bg-danger">NEW</span><?php endif; ?></td>
                <td><?= esc($inq['name']) ?></td>
                <td><?= esc($inq['email']) ?></td>
                <td>
                    <a href="/admin/inquiries/<?= $inq['id'] ?>" class="text-decoration-none text-dark">
                        <?= esc($inq['subject'] ?: mb_substr($inq['message'], 0, 30)) ?>
                    </a>
                </td>
                <td><?= date('Y년 n월 j일', strtotime($inq['created_at'])) ?></td>
                <td>
                    <form method="post" action="/admin/inquiries/<?= $inq['id'] ?>/delete" class="d-inline" onsubmit="return confirm('삭제?')">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-danger">삭제</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($inquiries)): ?>
                <tr><td colspan="6" class="text-center py-5 text-muted">문의가 없습니다</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-3 d-flex justify-content-center">
    <ul class="pagination pagination-sm">
        <?php
        $qs = $filter ? '&filter=' . $filter : '';
        for ($p = 1; $p <= $totalPages; $p++):
        ?>
        <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $p ?><?= $qs ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?= $this->endSection() ?>
