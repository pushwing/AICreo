<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$totalAmount = (int) ($totalAmount ?? 0);
$totalProduct = (int) ($totalProduct ?? 0);
$shippingFee  = (int) ($shippingFee  ?? 0);
?>

<div class="container py-4">

    <h4 class="fw-bold mb-4">주문서</h4>

    <form id="checkoutForm" novalidate>
        <?= csrf_field() ?>

        <div class="row g-4">

            <!-- ─── 왼쪽: 배송지 + 상품 ──────────────────────────────────────── -->
            <div class="col-lg-8">

                <!-- 배송지 -->
                <div class="card mb-3">
                    <div class="card-header fw-semibold bg-white">
                        <i class="bi bi-geo-alt me-2 text-primary"></i>배송지
                    </div>
                    <div class="card-body">

                        <?php if (! empty($savedAddress)): ?>
                        <div class="alert alert-light border d-flex align-items-start gap-3 mb-3 p-3">
                            <i class="bi bi-house-fill text-primary mt-1"></i>
                            <div class="flex-grow-1 small">
                                <div class="fw-semibold mb-1">
                                    <?= esc($savedAddress['receiver_name']) ?>
                                    <span class="text-muted fw-normal ms-2"><?= esc($savedAddress['receiver_phone']) ?></span>
                                </div>
                                <div class="text-muted">
                                    (<?= esc($savedAddress['zipcode']) ?>)
                                    <?= esc($savedAddress['address1']) ?>
                                    <?= ! empty($savedAddress['address2']) ? ' ' . esc($savedAddress['address2']) : '' ?>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0"
                                    id="btnFillSaved">적용</button>
                        </div>
                        <?php endif; ?>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold">받는 분 <span class="text-danger">*</span></label>
                                <input type="text" name="receiver_name" class="form-control"
                                       placeholder="이름" maxlength="100"
                                       value="<?= esc($savedAddress['receiver_name'] ?? '') ?>"
                                       required>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold">연락처 <span class="text-danger">*</span></label>
                                <input type="tel" name="receiver_phone" class="form-control"
                                       placeholder="010-0000-0000" maxlength="20"
                                       value="<?= esc($savedAddress['receiver_phone'] ?? '') ?>"
                                       required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">주소 <span class="text-danger">*</span></label>
                                <div class="input-group mb-2">
                                    <input type="text" name="zipcode" id="zipcode" class="form-control"
                                           placeholder="우편번호" maxlength="10"
                                           value="<?= esc($savedAddress['zipcode'] ?? '') ?>"
                                           readonly required>
                                    <button type="button" class="btn btn-outline-secondary" id="btnPostcode">
                                        주소 검색
                                    </button>
                                </div>
                                <input type="text" name="address1" id="address1" class="form-control mb-2"
                                       placeholder="기본 주소"
                                       value="<?= esc($savedAddress['address1'] ?? '') ?>"
                                       readonly required>
                                <input type="text" name="address2" id="address2" class="form-control"
                                       placeholder="상세 주소 (동, 호수 등)"
                                       value="<?= esc($savedAddress['address2'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">배송 메모</label>
                                <select class="form-select" id="deliveryMemoSelect">
                                    <option value="">선택 안 함</option>
                                    <option value="문 앞에 놔주세요">문 앞에 놔주세요</option>
                                    <option value="경비실에 맡겨주세요">경비실에 맡겨주세요</option>
                                    <option value="배송 전 연락 주세요">배송 전 연락 주세요</option>
                                    <option value="직접 입력">직접 입력</option>
                                </select>
                                <input type="text" name="delivery_memo_custom" id="deliveryMemoCustom"
                                       class="form-control mt-2 d-none"
                                       placeholder="배송 메모를 입력하세요" maxlength="200">
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" name="save_address" id="saveAddress"
                                           class="form-check-input" value="1">
                                    <label class="form-check-label small" for="saveAddress">
                                        이 배송지를 저장하기
                                    </label>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- 주문 상품 -->
                <div class="card mb-3">
                    <div class="card-header fw-semibold bg-white">
                        <i class="bi bi-bag me-2 text-primary"></i>주문 상품
                        <span class="text-muted fw-normal ms-1">(<?= count($available) ?>개)</span>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach ($available as $item):
                            $price = (int) ($item['discount_price'] ?? $item['price']);
                        ?>
                        <div class="d-flex align-items-center gap-3 p-3 border-bottom">
                            <?php if ($item['primary_image']): ?>
                            <img src="<?= esc($item['primary_image']) ?>" alt=""
                                 style="width:64px;height:64px;object-fit:cover;border-radius:6px;flex-shrink:0">
                            <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center text-muted flex-shrink-0"
                                 style="width:64px;height:64px;background:#f1f3f5;border-radius:6px">
                                <i class="bi bi-image"></i>
                            </div>
                            <?php endif; ?>

                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold small text-truncate mb-1"><?= esc($item['name']) ?></div>
                                <div class="text-muted small">
                                    <?= number_format($price) ?>원 × <?= (int) $item['qty'] ?>개
                                </div>
                            </div>
                            <div class="fw-bold text-end flex-shrink-0">
                                <?= number_format($price * (int) $item['qty']) ?>원
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 결제 수단 -->
                <div class="card">
                    <div class="card-header fw-semibold bg-white">
                        <i class="bi bi-credit-card me-2 text-primary"></i>결제 수단
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <?php foreach ($pgProviders as $key => $label): ?>
                            <div class="col-6 col-sm-4">
                                <input type="radio" class="btn-check" name="pg_provider"
                                       id="pg_<?= $key ?>" value="<?= $key ?>"
                                       <?= $key === array_key_first($pgProviders) ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary w-100 py-3 small fw-semibold"
                                       for="pg_<?= $key ?>">
                                    <?= esc($label) ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- 무통장입금 계좌 안내 (선택 시 표시) -->
                        <?php if (! empty($settings['bank_account'])): ?>
                        <div id="bankTransferInfo" class="d-none mt-3 p-3 bg-light rounded border">
                            <div class="small fw-semibold mb-2"><i class="bi bi-bank me-1"></i>입금 계좌 안내</div>
                            <dl class="row mb-0 small">
                                <dt class="col-4 text-muted fw-normal">은행</dt>
                                <dd class="col-8 mb-1"><?= esc($settings['bank_name'] ?? '—') ?></dd>
                                <dt class="col-4 text-muted fw-normal">계좌번호</dt>
                                <dd class="col-8 mb-1 fw-bold font-monospace"><?= esc($settings['bank_account']) ?></dd>
                                <dt class="col-4 text-muted fw-normal">예금주</dt>
                                <dd class="col-8 mb-0"><?= esc($settings['bank_holder'] ?? '—') ?></dd>
                            </dl>
                            <div class="text-muted mt-2" style="font-size:.75rem">
                                주문 완료 후 안내되는 금액을 정확히 입금해 주세요.
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- ─── 오른쪽: 주문 요약 ──────────────────────────────────────── -->
            <div class="col-lg-4">
                <div class="card sticky-top" style="top:1rem">
                    <div class="card-body">

                        <h6 class="fw-bold mb-3">결제 금액</h6>

                        <div class="d-flex justify-content-between small mb-2">
                            <span class="text-muted">상품 합계</span>
                            <span><?= number_format($totalProduct) ?>원</span>
                        </div>
                        <div class="d-flex justify-content-between small mb-3 pb-3 border-bottom">
                            <span class="text-muted">배송비</span>
                            <span><?= $shippingFee > 0 ? number_format($shippingFee) . '원' : '무료' ?></span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold mb-4">
                            <span>총 결제 금액</span>
                            <span class="fs-5 text-primary"><?= number_format($totalAmount) ?>원</span>
                        </div>

                        <div class="text-muted small mb-3">
                            <i class="bi bi-shield-check me-1"></i>
                            주문 내용을 확인하였으며, 정보 제공 등에 동의합니다.
                        </div>

                        <button type="button" id="btnOrder"
                                class="btn btn-primary w-100 py-3 fw-bold fs-6">
                            <?= number_format($totalAmount) ?>원 결제하기
                        </button>

                    </div>
                </div>
            </div>

        </div>

        <!-- 실제 배송 메모 전송용 hidden -->
        <input type="hidden" name="delivery_memo" id="deliveryMemoFinal">

    </form>

</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- 카카오 주소 검색 -->
<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<!-- 토스페이먼츠 JS SDK -->
<script src="https://js.tosspayments.com/v1/payment"></script>

<script>
(function () {
    const CSRF_NAME = '<?= csrf_token() ?>';
    let   csrfHash  = '<?= csrf_hash() ?>';

    // ─── 저장된 배송지 적용 ────────────────────────────────────────────────────
    document.getElementById('btnFillSaved')?.addEventListener('click', function () {
        <?php if (! empty($savedAddress)): ?>
        document.querySelector('[name=receiver_name]').value  = '<?= esc($savedAddress['receiver_name'], 'js') ?>';
        document.querySelector('[name=receiver_phone]').value = '<?= esc($savedAddress['receiver_phone'], 'js') ?>';
        document.getElementById('zipcode').value   = '<?= esc($savedAddress['zipcode'], 'js') ?>';
        document.getElementById('address1').value  = '<?= esc($savedAddress['address1'], 'js') ?>';
        document.getElementById('address2').value  = '<?= esc($savedAddress['address2'] ?? '', 'js') ?>';
        <?php endif; ?>
    });

    // ─── 카카오 우편번호 검색 ──────────────────────────────────────────────────
    document.getElementById('btnPostcode')?.addEventListener('click', function () {
        new daum.Postcode({
            oncomplete: function (data) {
                document.getElementById('zipcode').value  = data.zonecode;
                document.getElementById('address1').value = data.roadAddress || data.jibunAddress;
                document.getElementById('address2').focus();
            }
        }).open();
    });

    // ─── 배송 메모 직접 입력 ───────────────────────────────────────────────────
    document.getElementById('deliveryMemoSelect')?.addEventListener('change', function () {
        const custom = document.getElementById('deliveryMemoCustom');
        custom.classList.toggle('d-none', this.value !== '직접 입력');
    });

    // ─── 무통장입금 계좌 안내 토글 ───────────────────────────────────────────────
    document.querySelectorAll('[name=pg_provider]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            const info = document.getElementById('bankTransferInfo');
            if (info) info.classList.toggle('d-none', this.value !== 'bank_transfer');
        });
    });
    // 초기값이 bank_transfer인 경우
    (function () {
        const checked = document.querySelector('[name=pg_provider]:checked');
        const info = document.getElementById('bankTransferInfo');
        if (info && checked?.value === 'bank_transfer') info.classList.remove('d-none');
    })();

    // ─── 폼 유효성 검사 ────────────────────────────────────────────────────────
    function validate() {
        const required = ['receiver_name', 'receiver_phone', 'zipcode', 'address1'];
        for (const name of required) {
            const el = document.querySelector('[name=' + name + ']');
            if (! el || ! el.value.trim()) {
                el?.focus();
                alert(el?.placeholder || name + '을(를) 입력해주세요.');
                return false;
            }
        }
        if (! document.querySelector('[name=pg_provider]:checked')) {
            alert('결제 수단을 선택해주세요.');
            return false;
        }
        return true;
    }

    // ─── 주문 생성 → PG 결제창 ────────────────────────────────────────────────
    document.getElementById('btnOrder')?.addEventListener('click', async function () {
        if (! validate()) return;

        const btn      = this;
        btn.disabled   = true;
        btn.textContent = '처리 중...';

        // 배송 메모 병합
        const memoSel  = document.getElementById('deliveryMemoSelect').value;
        const memoCus  = document.getElementById('deliveryMemoCustom').value.trim();
        document.getElementById('deliveryMemoFinal').value =
            memoSel === '직접 입력' ? memoCus : (memoSel || '');

        const form = document.getElementById('checkoutForm');
        const body = new FormData(form);

        try {
            const res  = await fetch('/order/create', { method: 'POST', body });
            const data = await res.json();

            // CSRF 토큰 갱신
            if (res.headers.get('X-CSRF-TOKEN')) {
                csrfHash = res.headers.get('X-CSRF-TOKEN');
            }

            if (! data.success) {
                alert(data.message || '주문 생성에 실패했습니다.');
                btn.disabled   = false;
                btn.textContent = '<?= number_format($totalAmount) ?>원 결제하기';
                return;
            }

            await launchPG(data.pgParams);

        } catch (e) {
            alert('오류가 발생했습니다. 다시 시도해주세요.');
            btn.disabled   = false;
            btn.textContent = '<?= number_format($totalAmount) ?>원 결제하기';
        }
    });

    // ─── PG별 결제창 실행 ─────────────────────────────────────────────────────
    async function launchPG(p) {
        const pg = p.pg;

        if (pg === 'toss') {
            const toss = TossPayments(p.clientKey);
            await toss.requestPayment('카드', {
                amount:      p.amount,
                orderId:     p.orderId,
                orderName:   p.orderName,
                customerName: p.customerName,
                successUrl:  location.origin + '/payment/callback/toss?order_id=' + p.orderId,
                failUrl:     location.origin + '/order/fail/' + p.orderNumber,
            });
            return;
        }

        if (pg === 'kakaopay') {
            // 카카오페이는 준비(ready) 완료 후 redirectUrl로 이동
            if (p.error) { alert('카카오페이 오류: ' + p.error); return; }
            location.href = p.redirectUrl;
            return;
        }

        if (pg === 'naverpay') {
            location.href = p.returnUrl.replace('returnUrl', '') ||
                '/payment/callback/naverpay?order_id=' + p.orderId + '&paymentId=' + (p.paymentId || '');
            // 실제로는 네이버페이 JS SDK로 버튼 생성 후 클릭 유도
            alert('네이버페이 결제창을 엽니다. (실제 SDK 연동 필요)');
            return;
        }

        if (pg === 'payco') {
            location.href = p.returnUrl ||
                '/payment/callback/payco?order_id=' + p.orderId;
            return;
        }

        // inicis / nicepay — 서버사이드 폼 POST 방식
        if (pg === 'inicis' || pg === 'nicepay') {
            const frm = document.createElement('form');
            frm.method = 'post';
            frm.action = pg === 'inicis'
                ? 'https://stdpay.inicis.com/stdjs/INIStdPay.js'   // 실제 엔드포인트로 교체
                : 'https://pay.nicepay.co.kr/v1/js/';               // 실제 엔드포인트로 교체
            Object.entries(p).forEach(function ([k, v]) {
                if (k === 'pg') return;
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = k;
                input.value = v;
                frm.appendChild(input);
            });
            document.body.appendChild(frm);
            frm.submit();
            return;
        }

        if (pg === 'bank_transfer') {
            location.href = p.redirectUrl;
            return;
        }

        alert('지원하지 않는 PG입니다: ' + pg);
    }

})();
</script>
<?= $this->endSection() ?>
