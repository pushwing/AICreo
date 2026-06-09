<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="mb-2">
    <a href="/board/<?= esc($board['slug']) ?>" class="text-decoration-none text-muted small">
        <i class="bi bi-arrow-left"></i> <?= esc($board['name']) ?> 목록
    </a>
</div>

<div class="card">
    <div class="card-header bg-white">
        <strong><?= $post ? '게시글 수정' : '글쓰기' ?></strong>
    </div>
    <div class="card-body">
        <?php if (session()->has('errors')): ?>
        <div class="alert alert-danger">
            <?php foreach (session('errors') as $err): ?><div><?= esc($err) ?></div><?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="post"
              action="<?= $post ? "/board/{$board['slug']}/{$post['id']}/edit" : "/board/{$board['slug']}/write" ?>"
              enctype="multipart/form-data">
            <?= csrf_field() ?>

            <!-- 비회원 입력 -->
            <?php if (! session()->get('user_id')): ?>
            <div class="row g-2 mb-3">
                <div class="col-sm-3">
                    <input type="text" name="author_name" class="form-control form-control-sm"
                           placeholder="이름 *" value="<?= old('author_name') ?>" required>
                </div>
                <div class="col-sm-3">
                    <input type="password" name="author_password" class="form-control form-control-sm"
                           placeholder="비밀번호 (수정/삭제용) *" required>
                </div>
            </div>
            <?php endif; ?>

            <!-- 관리자 옵션 -->
            <?php if (session()->get('user_role') === 'admin'): ?>
            <div class="d-flex gap-3 mb-3">
                <div class="form-check">
                    <input type="checkbox" name="is_notice" value="1" id="is_notice" class="form-check-input"
                           <?= ($post && $post['is_notice']) ? 'checked' : '' ?>>
                    <label for="is_notice" class="form-check-label small">공지글로 등록</label>
                </div>
            </div>
            <?php endif; ?>

            <div class="mb-3">
                <div class="form-check mb-2">
                    <input type="checkbox" name="is_secret" value="1" id="is_secret" class="form-check-input"
                           <?= ($post && $post['is_secret']) ? 'checked' : '' ?>>
                    <label for="is_secret" class="form-check-label small">비밀글</label>
                </div>
                <input type="text" name="title" class="form-control" placeholder="제목 *"
                       value="<?= esc(old('title', $post['title'] ?? '')) ?>" required>
            </div>

            <div class="mb-3">
                <textarea name="content" class="form-control" rows="12" placeholder="내용 *" required><?= esc(old('content', $post['content'] ?? '')) ?></textarea>
            </div>

            <!-- 기존 파일 목록 (수정 시) -->
            <?php if (! empty($files)): ?>
            <div class="mb-3">
                <label class="form-label small text-muted">기존 첨부파일</label>
                <?php foreach ($files as $file): ?>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <input type="checkbox" name="delete_files[]" value="<?= $file['id'] ?>" id="del_<?= $file['id'] ?>">
                    <label for="del_<?= $file['id'] ?>" class="small mb-0">
                        <?= $file['is_image'] ? '<i class="bi bi-image"></i>' : '<i class="bi bi-file-earmark"></i>' ?>
                        <?= esc($file['original_name']) ?>
                        <span class="text-danger small">(체크 시 삭제)</span>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- 파일 첨부 -->
            <?php if ($board['allow_file'] || $board['allow_image']): ?>
            <div class="mb-3">
                <label class="form-label small">
                    파일 첨부
                    <span class="text-muted">(최대 10MB / 복수 선택 가능</span>
                    <?php if ($board['allow_image'] && !$board['allow_file']): ?>
                        <span class="text-muted">, 이미지만 허용)</span>
                    <?php elseif (!$board['allow_image'] && $board['allow_file']): ?>
                        <span class="text-muted">, 이미지 제외)</span>
                    <?php else: ?>
                        <span class="text-muted">)</span>
                    <?php endif; ?>
                </label>
                <input type="file" name="attachments[]" class="form-control form-control-sm" multiple
                       accept="<?= $board['allow_image'] ? 'image/*,' : '' ?>.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.txt,.hwp">
                <div class="form-text text-muted" id="fileNames"></div>
            </div>
            <?php endif; ?>

            <div class="d-flex gap-2 justify-content-end">
                <a href="/board/<?= esc($board['slug']) ?>" class="btn btn-outline-secondary btn-sm">취소</a>
                <button type="submit" class="btn btn-primary btn-sm"><?= $post ? '수정 완료' : '등록' ?></button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
// 선택한 파일명 표시
document.querySelector('input[type=file]')?.addEventListener('change', function() {
    const names = Array.from(this.files).map(f => f.name).join(', ');
    document.getElementById('fileNames').textContent = names || '';
});
</script>
<?= $this->endSection() ?>
