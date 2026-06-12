<?= $this->extend('layouts/admin') ?>
<?php $pageTitle = '리뷰 관리' ?>

<?= $this->section('content') ?>

<div class="d-flex gap-2 mb-3 align-items-center">
    <input id="quickFilter" type="text" class="form-control form-control-sm"
           placeholder="상품명 / 작성자 / 내용 검색" style="width:260px">
    <select id="rewardFilter" class="form-select form-select-sm" style="width:120px">
        <option value="">전체</option>
        <option value="1">포인트 지급</option>
        <option value="0">미지급</option>
    </select>
    <div id="rowCount" class="ms-auto text-muted small"></div>
</div>

<div id="reviewsGrid" class="ag-theme-alpine" style="height:620px"></div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.3.4/styles/ag-theme-alpine.css">
<script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.3.4/dist/ag-grid-community.noStyle.min.js"></script>
<script>
(function () {
    function esc(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    var rewardFilterVal = '';
    var csrf = {
        name:  document.querySelector('meta[name="csrf-name"]').content,
        token: document.querySelector('meta[name="csrf-token"]').content,
    };

    var grid = agGrid.createGrid(document.getElementById('reviewsGrid'), {
        columnDefs: [
            { headerName: '번호', field: 'id', width: 80,
              cellRenderer: function(p) { return '<span class="small">' + p.value + '</span>'; }},
            { headerName: '상품', field: 'product_name', width: 200,
              cellRenderer: function(p) {
                  return '<a href="/shop/' + esc(p.data.product_slug) + '" target="_blank" '
                       + 'class="text-decoration-none small">' + esc(p.value) + '</a>';
              }},
            { headerName: '작성자', field: 'author', width: 110,
              cellRenderer: function(p) { return '<span class="small">' + esc(p.value || '') + '</span>'; }},
            { headerName: '리뷰 내용', field: 'content', flex: 1, minWidth: 200,
              cellRenderer: function(p) {
                  var s = p.value || '';
                  if (s.length > 60) s = s.substring(0, 60) + '…';
                  return '<span class="small">' + esc(s) + '</span>';
              }},
            { headerName: '이미지', field: 'image_count', width: 80, type: 'numericColumn',
              cellRenderer: function(p) {
                  if (!p.value) return '<span class="text-muted small">-</span>';
                  return '<span class="badge bg-light text-dark border small">' + p.value + '장</span>';
              }},
            { headerName: '포인트', field: 'is_rewarded', width: 100,
              cellRenderer: function(p) {
                  return p.value
                      ? '<span class="badge bg-warning text-dark">150P 지급</span>'
                      : '<span class="text-muted small">-</span>';
              }},
            { headerName: '작성일', field: 'created_at', width: 110,
              cellRenderer: function(p) {
                  if (!p.value) return '';
                  return '<span class="small text-muted">'
                       + new Date(p.value.replace(' ', 'T')).toLocaleDateString('ko-KR') + '</span>';
              },
              comparator: function(a, b) { return a < b ? -1 : a > b ? 1 : 0; }},
            { headerName: '', width: 80, sortable: false, filter: false, resizable: false,
              cellRenderer: function(p) {
                  return '<button class="btn btn-sm btn-outline-danger" onclick="doDelete(' + p.data.id + ')">삭제</button>';
              }},
        ],
        defaultColDef: { sortable: true, filter: true, resizable: true },
        rowHeight: 46,
        isExternalFilterPresent: function() { return rewardFilterVal !== ''; },
        doesExternalFilterPass: function(node) { return String(node.data.is_rewarded) === rewardFilterVal; },
        onModelUpdated: function(e) {
            document.getElementById('rowCount').textContent = '총 ' + e.api.getDisplayedRowCount().toLocaleString() + '건';
        },
        onGridReady: function(e) {
            fetch('/admin/reviews/json')
                .then(function(r) { return r.json(); })
                .then(function(d) { e.api.setGridOption('rowData', d.data); });
        },
        localeText: { noRowsToShow: '등록된 리뷰가 없습니다.' },
    });

    document.getElementById('quickFilter').addEventListener('input', function() {
        grid.setGridOption('quickFilterText', this.value);
    });

    document.getElementById('rewardFilter').addEventListener('change', function() {
        rewardFilterVal = this.value;
        grid.onFilterChanged();
    });

    window.doDelete = function(id) {
        if (!confirm('리뷰를 삭제하시겠습니까? 지급된 포인트도 회수됩니다.')) return;
        fetch('/admin/reviews/' + id + '/delete', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
            body: csrf.name + '=' + encodeURIComponent(csrf.token),
        }).then(function() { location.reload(); });
    };
}());
</script>
<?= $this->endSection() ?>
