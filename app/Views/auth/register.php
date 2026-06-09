<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container">
<div class="row justify-content-center my-5">
    <div class="col-sm-5">
        <div class="card">
            <div class="card-body p-4">
                <h5 class="mb-4">회원가입</h5>
                <?php if (session()->has('errors')): ?>
                    <div class="alert alert-danger">
                        <?php foreach (session('errors') as $e): ?><div><?= esc($e) ?></div><?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form method="post" action="/auth/register">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <input type="email" name="email" class="form-control" placeholder="이메일 *"
                               value="<?= old('email') ?>" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" name="nickname" class="form-control" placeholder="닉네임 *"
                               value="<?= old('nickname') ?>" required>
                    </div>
                    <div class="mb-3">
                        <input type="password" name="password" class="form-control" placeholder="비밀번호 (8자 이상) *" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">가입하기</button>
                </form>
                <div class="text-center mt-3 small">
                    <a href="/auth/login" class="text-decoration-none">로그인으로 돌아가기</a>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?= $this->endSection() ?>
