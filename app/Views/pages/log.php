<?= $this->extend('layouts/main') ?>
<?= $this->section('head_extra') ?>
<style>
    .table-row:hover { background-color:#f9fafb; }
    .loading-spinner { border:3px solid #f3f3f3;border-top:3px solid #10b981;border-radius:50%;width:40px;height:40px;animation:spin 1s linear infinite }
    @keyframes spin {0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="card bg-base-100 shadow-lg mb-6">
    <div class="card-body">
        <h2 class="card-title text-success mb-2">Filter Data</h2>
        <div class="space-y-4">
            <div class="grid gap-4 md:grid-cols-4">
                <div class="form-control min-w-0">
                    <label class="label"><span class="label-text font-medium">Tanggal Mulai</span></label>
                    <input type="date" id="start-date" class="input input-bordered input-success w-full leading-tight" />
                </div>
                <div class="form-control min-w-0">
                    <label class="label"><span class="label-text font-medium">Tanggal Akhir</span></label>
                    <input type="date" id="end-date" class="input input-bordered input-success w-full leading-tight" />
                </div>
                <div class="form-control min-w-0">
                    <label class="label"><span class="label-text font-medium">Perangkat</span></label>
                    <select id="device-filter" class="select select-bordered select-success w-full">
                        <option value="all">Semua Perangkat</option>
                        <option value="A">Perangkat A</option>
                        <option value="B">Perangkat B</option>
                    </select>
                </div>
                <div class="form-control min-w-0 flex flex-col justify-end">
                    <button id="export-csv" class="btn btn-success w-full gap-2 text-white" aria-label="Export CSV">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" />
                        </svg>
                        <span>Export CSV</span>
                    </button>
                </div>
            </div>
        </div>
        <div class="divider text-success font-semibold">Statistik Data</div>
        <div class="stats stats-vertical lg:stats-horizontal shadow-lg w-full">
            <div class="stat bg-info/10">
                <div class="stat-title">Total Records</div>
                <div class="stat-value text-info" id="total-records">0</div>
            </div>
            <div class="stat bg-success/10">
                <div class="stat-title">Avg pH</div>
                <div class="stat-value text-success" id="avg-ph">0.0</div>
            </div>
            <div class="stat bg-warning/10">
                <div class="stat-title">Avg TDS</div>
                <div class="flex items-end gap-1"><span class="stat-value text-warning" id="avg-tds">0</span><span class="stat-value text-warning leading-none">ppm</span></div>
            </div>
            <div class="stat bg-error/10">
                <div class="stat-title">Avg Temperature</div>
                <div class="stat-value text-error" id="avg-temp">0.0°C</div>
            </div>
        </div>
    </div>
</div>
<div class="card bg-base-100 shadow-lg">
    <div class="card-header bg-base-200 p-4 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-success">Data History</h3>
        <div class="badge badge-success badge-outline"><span id="data-count">0</span> entries</div>
    </div>
    <div class="card-body p-0">
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full" id="log-table">
                <thead class="bg-base-200">
                    <tr>
                        <th class="cursor-pointer hover:bg-base-300" onclick="sortTable('timestamp')">Waktu</th>
                        <th class="cursor-pointer hover:bg-base-300" onclick="sortTable('device')">Perangkat</th>
                        <th class="cursor-pointer hover:bg-base-300" onclick="sortTable('ph')">pH</th>
                        <th class="cursor-pointer hover:bg-base-300" onclick="sortTable('tds')">TDS (ppm)</th>
                        <th class="cursor-pointer hover:bg-base-300" onclick="sortTable('temperature')">Suhu (°C)</th>
                        <th class="cursor-pointer hover:bg-base-300" onclick="sortTable('cal_ph_asam')">Cal pH Asam</th>
                        <th class="cursor-pointer hover:bg-base-300" onclick="sortTable('cal_ph_netral')">Cal pH Netral</th>
                        <th class="cursor-pointer hover:bg-base-300" onclick="sortTable('cal_tds_k')">Cal TDS K</th>
                    </tr>
                </thead>
                <tbody id="log-table-body"></tbody>
            </table>
        </div>
                <div id="loading-indicator" class="flex flex-col items-center justify-center py-16 hidden">
                        <span class="loading loading-spinner loading-lg text-success"></span>
                        <div class="mt-4 text-center">
                                <p class="text-base-content font-medium">Memuat data logger...</p>
                        </div>
                </div>
                <div id="no-data-message" class="hidden">
            <div class="hero min-h-[200px]">
                <div class="hero-content text-center">
                    <div class="text-6xl mb-4">📭</div>
                    <h1 class="text-2xl font-bold text-base-content">Tidak ada data</h1>
                                        <p class="py-4 text-base-content/70">Tidak ada data yang ditemukan untuk filter yang dipilih</p>
                    <button class="btn btn-primary" onclick="loadLogData()">🔄 Coba Lagi</button>
                </div>
            </div>
        </div>
        <div id="pagination" class="hidden bg-base-200 p-4 flex justify-between items-center">
            <div class="text-sm text-base-content">Showing <span class="font-semibold" id="showing-from">1</span> to <span class="font-semibold" id="showing-to">10</span> of <span class="font-semibold" id="total-entries">0</span> entries</div>
            <div class="join">
                <button id="prev-page" class="join-item btn btn-sm" disabled>← Previous</button>
                <button id="next-page" class="join-item btn btn-sm" disabled>Next →</button>
            </div>
        </div>
    </div>
    <div class="mt-6 text-center text-base md:text-md text-gray-900 bg-white rounded-lg p-4 shadow-sm border border-gray-200 font-regular">
        Terima Kasih kepada DPPM Kemendiktisaintek dan LPPM Universitas Lambung Mangkurat
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('body_end') ?>
<script>
// Log page powered by MySQL via /api/telemetry
let currentPage = 1;
let perPage = 25;
let currentSort = 'desc'; // server-side sort by timestamp_ms
let currentItems = [];

document.addEventListener('DOMContentLoaded', ()=>{
    initLiveClock();
    initLogo();
    initFilters();
    loadLogData();
});

function initFilters(){
    const startInput = document.getElementById('start-date');
    const endInput = document.getElementById('end-date');
    const deviceSel = document.getElementById('device-filter');
    // default to last 7 days
    const now = new Date();
    const endStr = now.toISOString().slice(0,10);
    const past = new Date(now.getTime() - 6*24*60*60*1000);
    const startStr = past.toISOString().slice(0,10);
    if (startInput && !startInput.value) startInput.value = startStr;
    if (endInput && !endInput.value) endInput.value = endStr;
    if (deviceSel && !deviceSel.value) deviceSel.value = 'all';

    startInput?.addEventListener('change', onFilterChange);
    endInput?.addEventListener('change', onFilterChange);
    deviceSel?.addEventListener('change', onFilterChange);

    document.getElementById('prev-page')?.addEventListener('click', ()=>{ if(currentPage>1){ currentPage--; loadLogData(); } });
    document.getElementById('next-page')?.addEventListener('click', ()=>{ currentPage++; loadLogData(); });
    document.getElementById('export-csv')?.addEventListener('click', exportCsv);
}

function onFilterChange(){
    currentPage = 1;
    loadLogData();
}

async function loadLogData(){
    const loading = document.getElementById('loading-indicator');
    const noData = document.getElementById('no-data-message');
    const table = document.getElementById('log-table');
    loading?.classList.remove('hidden');
    noData?.classList.add('hidden');
    if (table) table.style.display = '';

    const start = document.getElementById('start-date')?.value || '';
    const end = document.getElementById('end-date')?.value || '';
    const deviceVal = document.getElementById('device-filter')?.value || 'all';
    const device = (deviceVal==='A' || deviceVal==='B') ? deviceVal : 'all';

    const params = new URLSearchParams({
        start, end,
        device,
        page: String(currentPage),
        perPage: String(perPage),
        sort: currentSort
    });
    try{
        const res = await fetch(`/api/telemetry?${params.toString()}`, {headers:{'Accept':'application/json'}});
        const data = await res.json();
        currentItems = Array.isArray(data.items) ? data.items : [];
        renderStats(data.stats || {});
        renderTable(currentItems);
        renderPagination(data.page||1, data.perPage||perPage, data.total||0);
    }catch(err){
        console.error('Gagal memuat data', err);
        showNoData('Gagal memuat data dari server');
    } finally {
        loading?.classList.add('hidden');
    }
}

function renderStats(stats){
    document.getElementById('total-records').textContent = String(stats.total ?? '');
    document.getElementById('avg-ph').textContent = (stats.avg_ph ?? 0).toFixed ? (stats.avg_ph).toFixed(2) : String(stats.avg_ph ?? '0.00');
    document.getElementById('avg-tds').textContent = String(stats.avg_tds ?? '0');
    document.getElementById('avg-temp').textContent = (stats.avg_suhu ?? 0).toFixed ? `${(stats.avg_suhu).toFixed(2)}°C` : `${stats.avg_suhu ?? '0.00'}°C`;
}

function renderTable(items){
    const body = document.getElementById('log-table-body');
    const countEl = document.getElementById('data-count');
    if (!body) return;
    body.innerHTML = '';
    items.forEach(row=>{
        const tr = document.createElement('tr');
        tr.className = 'table-row';
        const dt = row.timestamp_ms ? new Date(row.timestamp_ms) : null;
        const timeStr = dt ? dt.toLocaleString('id-ID',{hour12:false}) : (row.created_at || '-');
        tr.innerHTML = `
            <td>${timeStr}</td>
            <td>${row.device ?? '-'}</td>
            <td>${fmtNum(row.ph, 2)}</td>
            <td>${fmtNum(row.tds, 0)}</td>
            <td>${fmtNum(row.suhu, 2)}</td>
            <td>${fmtNum(row.cal_ph_asam, 4)}</td>
            <td>${fmtNum(row.cal_ph_netral, 4)}</td>
            <td>${fmtNum(row.cal_tds_k, 4)}</td>
        `;
        body.appendChild(tr);
    });
    countEl && (countEl.textContent = String(items.length));
    if (items.length === 0) showNoData();
}

function renderPagination(page, perPageVal, total){
    const pag = document.getElementById('pagination');
    const fromEl = document.getElementById('showing-from');
    const toEl = document.getElementById('showing-to');
    const totalEl = document.getElementById('total-entries');
    const prevBtn = document.getElementById('prev-page');
    const nextBtn = document.getElementById('next-page');
    // also update total in stats card
    const totalRecords = document.getElementById('total-records');
    if (totalRecords) totalRecords.textContent = String(total);

    totalEl && (totalEl.textContent = String(total));
    const from = total === 0 ? 0 : ((page-1)*perPageVal + 1);
    const to = Math.min(page*perPageVal, total);
    fromEl && (fromEl.textContent = String(from));
    toEl && (toEl.textContent = String(to));
    pag?.classList.toggle('hidden', total === 0);
    prevBtn && (prevBtn.disabled = page <= 1);
    const maxPage = Math.ceil(total / perPageVal);
    nextBtn && (nextBtn.disabled = page >= maxPage);
    currentPage = page; perPage = perPageVal;
}

function showNoData(msg){
    const noData = document.getElementById('no-data-message');
    noData?.classList.remove('hidden');
    if (msg) {
        const p = noData?.querySelector('p'); if (p) p.textContent = msg;
    }
}

// Sort current page locally by given field (client-side only)
function sortTable(field){
    if (!Array.isArray(currentItems) || currentItems.length === 0) return;
    const dir = (sortTable.lastKey === field && sortTable.lastDir === 'asc') ? 'desc' : 'asc';
    sortTable.lastKey = field; sortTable.lastDir = dir;
    const sign = dir === 'asc' ? 1 : -1;
    currentItems.sort((a,b)=>{
        const va = (a[field] ?? 0); const vb = (b[field] ?? 0);
        if (va < vb) return -1*sign; if (va > vb) return 1*sign; return 0;
    });
    renderTable(currentItems);
}

async function exportCsv(){
    const start = document.getElementById('start-date')?.value || '';
    const end = document.getElementById('end-date')?.value || '';
    const deviceVal = document.getElementById('device-filter')?.value || 'all';
    const device = (deviceVal==='A' || deviceVal==='B') ? deviceVal : 'all';
    const params = new URLSearchParams({ start, end, device, page: '1', perPage: '1000', sort: 'desc' });
    try{
        const res = await fetch(`/api/telemetry?${params.toString()}`, {headers:{'Accept':'application/json'}});
        const data = await res.json();
        const rows = Array.isArray(data.items) ? data.items : [];
        const csv = toCsv(rows);
        const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `telemetry_${start||'all'}_${end||'all'}_${device}.csv`;
        document.body.appendChild(a); a.click(); a.remove();
        URL.revokeObjectURL(url);
    }catch(e){ console.error('Export CSV gagal', e); }
}

function toCsv(rows){
    const headers = ['timestamp','device','ph','tds','suhu','cal_ph_asam','cal_ph_netral','cal_tds_k','kebun','created_at'];
    const lines = [headers.join(',')];
    rows.forEach(r=>{
        const dt = r.timestamp_ms ? new Date(r.timestamp_ms).toISOString() : '';
        const line = [
            dt,
            r.device ?? '',
            safeCsv(r.ph),
            safeCsv(r.tds),
            safeCsv(r.suhu),
            safeCsv(r.cal_ph_asam),
            safeCsv(r.cal_ph_netral),
            safeCsv(r.cal_tds_k),
            r.kebun ?? '',
            r.created_at ?? ''
        ].join(',');
        lines.push(line);
    });
    return lines.join('\n');
}

function safeCsv(v){
    if (v === null || v === undefined) return '';
    const s = String(v);
    if (s.includes(',') || s.includes('"') || s.includes('\n')){
        return '"' + s.replace(/"/g,'""') + '"';
    }
    return s;
}

function fmtNum(v, digits){
    if (v === null || v === undefined || isNaN(v)) return '-';
    if (typeof v === 'number' && Number.isFinite(v)) return v.toFixed(digits);
    const n = Number(v); return Number.isFinite(n) ? n.toFixed(digits) : '-';
}

function initLiveClock(){
  function tick(){
    const now=new Date();
    const t=now.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
    const d=now.toLocaleDateString('id-ID',{weekday:'short',year:'numeric',month:'short',day:'numeric'});
    const c=document.getElementById('real-time-clock'); if(c) c.textContent=t;
    const cm=document.getElementById('real-time-clock-mobile'); if(cm) cm.textContent=t;
    const de=document.getElementById('real-time-date'); if(de) de.textContent=d;
    const dem=document.getElementById('real-time-date-mobile'); if(dem) dem.textContent=d;
  }
  tick(); setInterval(tick,1000);
}
function initLogo(){
  const logoImg=document.getElementById('company-logo');
  if(!logoImg) return; const fallback=document.getElementById('logo-fallback');
  const base = (typeof APP_BASE_URL!=='undefined') ? APP_BASE_URL : '';
  const sources=[base + '/assets/logo.png',base + '/assets/logo.jpg',base + '/assets/logo.svg',base + '/logo.png',base + '/logo.jpg'];
  let loaded=false;
  sources.forEach(src=>{ if(loaded) return; const test=new Image(); test.onload=()=>{ if(!loaded){ logoImg.src=src; logoImg.classList.remove('hidden'); if(fallback) fallback.style.display='none'; loaded=true; } }; test.src=src; });
  setTimeout(()=>{ if(!loaded && fallback){ fallback.style.display='flex'; } },3000);
}
</script>
<?= $this->endSection() ?>