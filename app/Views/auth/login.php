<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="row justify-content-center">
    <div class="col-sm-5">
        <div class="card">
            <div class="card-body p-4">
                <h5 class="mb-4">로그인</h5>
                <?php if (session()->has('error')): ?>
                    <div class="alert alert-danger"><?= esc(session('error')) ?></div>
                <?php endif; ?>
                <?php if (session()->has('success')): ?>
                    <div class="alert alert-success"><?= esc(session('success')) ?></div>
                <?php endif; ?>
                <form method="post" action="/auth/login">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <input type="email" name="email" class="form-control" placeholder="이메일"
                               value="<?= old('email') ?>" required>
                    </div>
                    <div class="mb-3">
                        <input type="password" name="password" class="form-control" placeholder="비밀번호" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">로그인</button>
                </form>
                <div class="text-center mt-3 small">
                    <a href="/auth/register" class="text-decoration-none">회원가입</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
