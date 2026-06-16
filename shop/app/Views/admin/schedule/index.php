<?= $this->extend('layouts/admin') ?>
<?php $pageTitle = '배치 작업 관리' ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="mb-0">배치 작업 관리</h4>
        <p class="text-muted small mb-0 mt-1">스케줄러에 등록된 자동 실행 작업을 활성화 / 비활성화합니다.</p>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= esc(session()->getFlashdata('success')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= esc(session()->getFlashdata('error')) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">작업명</th>
                    <th>키</th>
                    <th class="text-center">상태</th>
                    <th class="text-center pe-4">토글</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($jobs as $job): ?>
                <tr>
                    <td class="ps-4 fw-semibold"><?= esc($job['label']) ?></td>
                    <td><code class="text-secondary small"><?= esc($job['key']) ?></code></td>
                    <td class="text-center">
                        <?php if ($job['value'] === '1'): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">활성</span>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">비활성</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center pe-4">
                        <form method="post" action="/admin/schedule/<?= esc($job['key']) ?>/toggle" class="d-inline">
                            <?= csrf_field() ?>
                            <?php if ($job['value'] === '1'): ?>
                                <button type="submit" class="btn btn-sm btn-outline-secondary">비활성화</button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-sm btn-outline-success">활성화</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($jobs)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">등록된 배치 작업이 없습니다.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
