<?= $this->extend('layouts/admin') ?>
<?php $pageTitle = '회원 관리' ?>
<?php use App\Libraries\GradeService; ?>

<?= $this->section('content') ?>

<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <input type="text" name="q" class="form-control form-control-sm" placeholder="닉네임 / 이메일 / 휴대폰 검색"
               value="<?= esc($keyword) ?>">
    </div>
    <div class="col-auto">
        <select name="role" class="form-select form-select-sm">
            <option value="">전체 역할</option>
            <option value="member" <?= $role === 'member' ? 'selected' : '' ?>>일반회원</option>
            <option value="admin"  <?= $role === 'admin'  ? 'selected' : '' ?>>관리자</option>
        </select>
    </div>
    <div class="col-auto">
        <select name="status" class="form-select form-select-sm">
            <option value="">전체 상태</option>
            <option value="1"          <?= $status === '1'          ? 'selected' : '' ?>>활성</option>
            <option value="unverified" <?= $status === 'unverified' ? 'selected' : '' ?>>이메일 미인증</option>
            <option value="0"          <?= $status === '0'          ? 'selected' : '' ?>>비활성 (차단)</option>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-secondary btn-sm">검색</button>
        <a href="/admin/users" class="btn btn-outline-secondary btn-sm">초기화</a>
    </div>
    <div class="col-auto ms-auto d-flex align-items-center text-muted small">
        총 <?= number_format($total) ?>명
    </div>
</form>

<div class="card overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>닉네임</th>
                    <th>이메일</th>
                    <th>휴대폰</th>
                    <th>역할</th>
                    <th>등급</th>
                    <th>상태</th>
                    <th>가입일</th>
                    <th>최근 로그인</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">회원이 없습니다.</td></tr>
                <?php endif; ?>
                <?php foreach ($users as $u): ?>
                <?php $isUnverified = ! $u['is_active'] && ! empty($u['email_verify_token']); ?>
                <tr>
                    <td class="text-muted small"><?= $u['id'] ?></td>
                    <td><?= esc($u['nickname']) ?></td>
                    <td class="small"><?= esc($u['email']) ?></td>
                    <td class="small text-muted"><?= $u['phone'] ? esc($u['phone']) : '-' ?></td>
                    <td>
                        <?php if ($u['role'] === 'admin'): ?>
                            <span class="badge bg-danger">관리자</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">일반</span>
                        <?php endif; ?>
                        <?php if ($u['social_provider']): ?>
                            <span class="badge bg-info text-dark"><?= esc($u['social_provider']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php $grade = $u['grade'] ?? 'bronze'; ?>
                        <span class="badge <?= GradeService::BADGE_CLASSES[$grade] ?>">
                            <i class="bi <?= GradeService::ICONS[$grade] ?> me-1"></i><?= GradeService::LABELS[$grade] ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($u['is_active']): ?>
                            <span class="badge bg-success">활성</span>
                        <?php elseif ($isUnverified): ?>
                            <span class="badge bg-warning text-dark">이메일 미인증</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">비활성</span>
                        <?php endif; ?>
                    </td>
                    <td class="small text-muted"><?= date('Y-n-j', strtotime($u['created_at'])) ?></td>
                    <td class="small text-muted"><?= $u['last_login'] ? date('Y-n-j', strtotime($u['last_login'])) : '-' ?></td>
                    <td class="text-nowrap">
                        <a href="/admin/users/<?= $u['id'] ?>/edit" class="btn btn-sm btn-outline-secondary">수정</a>
                        <?php if ($isUnverified): ?>
                        <form method="post" action="/admin/users/<?= $u['id'] ?>/resend-verify" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-warning" title="인증 메일 재발송">재발송</button>
                        </form>
                        <?php endif; ?>
                        <?php if ((int)$u['id'] !== (int)session()->get('user_id')): ?>
                        <form method="post" action="/admin/users/<?= $u['id'] ?>/delete" class="d-inline"
                              onsubmit="return confirm('정말 삭제하시겠습니까?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-danger">삭제</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php
        $qs = http_build_query(array_filter(['q' => $keyword, 'role' => $role, 'status' => $status]));
        $qs = $qs ? '&' . $qs : '';
        ?>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?><?= $qs ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<?= $this->endSection() ?>
