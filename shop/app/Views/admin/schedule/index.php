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


<div class="mt-4">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light border-0 d-flex align-items-center gap-2"
             role="button" data-bs-toggle="collapse" data-bs-target="#cronHelp" aria-expanded="false">
            <i class="bi bi-question-circle text-primary"></i>
            <span class="fw-semibold">크론(Cron) 등록 방법</span>
            <i class="bi bi-chevron-down ms-auto small text-muted"></i>
        </div>
        <div class="collapse" id="cronHelp">
            <div class="card-body pt-0 pb-4 px-4">

                <p class="text-muted small mt-3 mb-2">
                    이 페이지의 활성/비활성 설정은 실제 크론이 서버에 등록되어 있어야 동작합니다.<br>
                    아래 단계에 따라 서버에 크론을 <strong>최초 1회</strong> 등록해 주세요.
                </p>

                <h6 class="mt-4 mb-2 fw-semibold">1단계 — 크론탭 편집기 열기</h6>
                <pre class="bg-dark text-light rounded p-3 small mb-0">crontab -e</pre>

                <h6 class="mt-4 mb-2 fw-semibold">2단계 — 스케줄러 실행 항목 추가</h6>
                <pre class="bg-dark text-light rounded p-3 small mb-0">* * * * * cd /path/to/shop &amp;&amp; php spark schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</pre>
                <p class="text-muted small mt-2 mb-0">
                    <code>/path/to/shop</code> 을 실제 프로젝트 경로로 변경하세요. (예: <code>/var/www/html/shop</code>)<br>
                    1분마다 실행되며, 각 배치의 주기는 <code>app/Config/Scheduler.php</code> 에서 관리합니다.
                </p>

                <h6 class="mt-4 mb-2 fw-semibold">3단계 — 등록 확인</h6>
                <pre class="bg-dark text-light rounded p-3 small mb-0">crontab -l</pre>

                <hr class="my-4">

                <h6 class="mb-3 fw-semibold">배치 작업 동작 구조</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered small mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>크론</th>
                                <th>스케줄러</th>
                                <th>커맨드</th>
                                <th>이 페이지 설정</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>schedule:run</code> 매분 실행</td>
                                <td><code>Scheduler.php</code> 주기 확인</td>
                                <td>해당 커맨드 호출</td>
                                <td>비활성이면 커맨드 내부에서 스킵</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted small mt-2 mb-0">
                    크론은 항상 <code>schedule:run</code> 하나만 등록합니다.
                    개별 작업의 실행 주기는 <code>Scheduler.php</code>, 실행 여부는 이 페이지에서 제어합니다.
                </p>

                <hr class="my-4">

                <h6 class="mb-2 fw-semibold">새 배치 작업 추가 시 체크리스트</h6>
                <ol class="small text-muted mb-0 ps-3">
                    <li class="mb-1"><code>app/Commands/</code> 에 커맨드 클래스 작성, <code>run()</code> 첫 줄에 활성화 가드 추가</li>
                    <li class="mb-1"><code>app/Config/Scheduler.php</code> 에 실행 주기 등록</li>
                    <li class="mb-1">마이그레이션으로 <code>settings</code> 테이블에 <code>group=schedule</code> 키 추가 (초기값 <code>1</code>)</li>
                    <li>크론 재등록 불필요 — 기존 <code>schedule:run</code> 크론이 자동 인식</li>
                </ol>

            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
