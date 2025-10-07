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
        <div id="loading-indicator" class="flex flex-col items-center justify-center py-16">
            <span class="loading loading-spinner loading-lg text-success"></span>
            <div class="mt-4 text-center">
                <p class="text-base-content font-medium">Memuat data logger...</p>
                <p class="text-base-content/60 text-sm mt-2">Mengambil data dari Firebase</p>
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
// Guard Firebase compat if already present (dashboard loads v9 compat)
if (typeof firebase === 'undefined' || !firebase.apps || !firebase.apps.length) {
    // assume loaded in layout if needed; if not, user must add scripts
}
const firebaseConfigLog = {
    apiKey: "AIzaSyCFx2ZlJRGZfD-P6I84a53yc8D_cyFqvgs",
    authDomain: "hidroganik-monitoring.firebaseapp.com",
    databaseURL: "https://hidroganik-monitoring-default-rtdb.asia-southeast1.firebasedatabase.app",
    projectId: "hidroganik-monitoring",
    storageBucket: "hidroganik-monitoring.firebasestorage.app",
    messagingSenderId: "103705402081",
    appId: "1:103705402081:web:babc15ad263749e80535a0"
};
try { if (!firebase.apps.length) { firebase.initializeApp(firebaseConfigLog); } } catch(e) { console.warn('Firebase init log page:', e); }
const dbLog = firebase.database();

let logDataCache = [];
let currentPage = 1;
const itemsPerPage = 50; // logical page size (for stats + CSV)
let sortColumn = 'timestamp';
let sortDirection = 'desc';
// Removed lazy loading logic
let renderedCount = 0; // how many rows actually painted to DOM
const CHUNK_SIZE = 100; // rows per lazy render chunk
let isRenderingChunk = false;
// Live listeners registry
let liveListenerHandles = [];

// Debounce utility
function debounce(fn, delay=500){ let t; return function(...a){ clearTimeout(t); t=setTimeout(()=>fn.apply(this,a),delay); }; }

function loadLogData() {
    const loadingIndicator = document.getElementById('loading-indicator');
    const noDataMessage = document.getElementById('no-data-message');
    const tableBody = document.getElementById('log-table-body');
    const pagination = document.getElementById('pagination');
    loadingIndicator.classList.remove('hidden');
    noDataMessage.classList.add('hidden');
    pagination.classList.add('hidden');
    tableBody.innerHTML = '';
    // Detach any existing live listeners when reloading with new filters
    detachLiveListeners();
    const startDateInput = document.getElementById('start-date');
    const endDateInput = document.getElementById('end-date');
    const deviceFilter = document.getElementById('device-filter').value;
    if (!startDateInput.value || !endDateInput.value) {
        const endDate = new Date();
        const startDate = new Date();
        startDate.setDate(startDate.getDate() - 7);
        startDateInput.value = startDate.toISOString().split('T')[0];
        endDateInput.value = endDate.toISOString().split('T')[0];
    }
    const startDate = new Date(startDateInput.value);
    const endDate = new Date(endDateInput.value + ' 23:59:59');
    loadFromRealtimeData(startDate, endDate, deviceFilter);
}

function loadFromRealtimeData(startDate, endDate, deviceFilter) {
    const devices = deviceFilter === 'all' ? ['a','b'] : [deviceFilter.toLowerCase()];
    const startMs = startDate.getTime();
    const endMs = endDate.getTime();
    let allData = []; let loadedDevices = 0;
    devices.forEach(device => {
        let queryRef = dbLog.ref(`kebun-${device}/history`).orderByChild('timestamp').startAt(startMs).endAt(endMs);
        queryRef.once('value').then(snapshot => {
            if (!snapshot.exists()) {
                return dbLog.ref(`kebun-${device}/history`).once('value').then(fullSnap => {
                    if (fullSnap.exists()) { fullSnap.forEach(ch => processChild(device,ch)); }
                });
            } else { snapshot.forEach(ch => processChild(device,ch)); }
        }).catch(err => { console.error('Query error', device, err); }).finally(()=>{ loadedDevices++; if (loadedDevices===devices.length) finalize(); });
    });
    function processChild(device, child) {
        const data = child.val();
        let ts = null;
        if (typeof data.timestamp === 'number') ts = data.timestamp;
        else if (typeof data.timestamp === 'string' && !isNaN(+data.timestamp)) ts = +data.timestamp;
        else if (typeof data.createdAt === 'number') ts = data.createdAt;
        else if (data.date && data.time) { const dt = new Date(`${data.date} ${data.time}`); if (!isNaN(dt.getTime())) ts = dt.getTime(); }
        if (ts === null) return; if (ts < startMs || ts > endMs) return;
        allData.push({
            id: child.key,
            timestamp: ts,
            device: device.toUpperCase(),
            ph: parseFloat(data.ph) || 0,
            tds: parseInt(data.tds) || 0,
            temperature: parseFloat(data.suhu ?? data.temperature) || 0,
            cal_ph_asam: parseFloat(data.cal_ph_asam) || 0,
            cal_ph_netral: parseFloat(data.cal_ph_netral) || 0,
            cal_tds_k: parseFloat(data.cal_tds_k) || 0
        });
    }
    function finalize() {
        logDataCache = allData;
        sortCache();
        displayLogData(logDataCache); updateStatistics(logDataCache);
        document.getElementById('loading-indicator').classList.add('hidden');
        if (!logDataCache.length) document.getElementById('no-data-message').classList.remove('hidden');
        else { document.getElementById('pagination').classList.remove('hidden'); updatePagination(); }
        // Attach live listeners for new incoming history entries within the current range
        attachLiveListeners(devices, startMs, endMs);
    }
}

function clearRenderedRows(){ const tb=document.getElementById('log-table-body'); tb.innerHTML=''; renderedCount=0; }
function displayLogData(data){
    const tb=document.getElementById('log-table-body');
    tb.innerHTML='';
    const startIndex=(currentPage-1)*itemsPerPage;
    const endIndex=Math.min(startIndex+itemsPerPage,data.length);
    const pageData=data.slice(startIndex,endIndex);
    const frag=document.createDocumentFragment();
    pageData.forEach(row=>{
        const tr=document.createElement('tr'); tr.className='table-row hover:bg-gray-50';
        const date=new Date(row.timestamp); const formattedDate=date.toLocaleString('id-ID');
        tr.innerHTML=`<td class="px-4 py-3 text-sm">${formattedDate}</td>
            <td class="px-4 py-3 text-sm"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${row.device==='A'?'bg-blue-100 text-blue-800':'bg-purple-100 text-purple-800'}">${row.device}</span></td>
            <td class="px-4 py-3 text-sm font-mono">${row.ph.toFixed(1)}</td>
            <td class="px-4 py-3 text-sm font-mono">${Math.round(row.tds)} ppm</td>
            <td class="px-4 py-3 text-sm font-mono">${row.temperature.toFixed(1)}°C</td>
            <td class="px-4 py-3 text-sm font-mono">${row.cal_ph_asam.toFixed(4)}</td>
            <td class="px-4 py-3 text-sm font-mono">${row.cal_ph_netral.toFixed(4)}</td>
            <td class="px-4 py-3 text-sm font-mono">${row.cal_tds_k.toFixed(4)}</td>`;
        frag.appendChild(tr);
    });
    tb.appendChild(frag);
    document.getElementById('data-count').textContent=data.length;
}

function updateStatistics(data) {
    if (!data.length) { document.getElementById('total-records').textContent='0';document.getElementById('avg-ph').textContent='0.0';document.getElementById('avg-tds').textContent='0';document.getElementById('avg-temp').textContent='0.0°C';return; }
    const totalRecords = data.length;
    const avgPh = data.reduce((s,i)=>s+i.ph,0)/totalRecords;
    const avgTds = data.reduce((s,i)=>s+i.tds,0)/totalRecords;
    const avgTemp = data.reduce((s,i)=>s+i.temperature,0)/totalRecords;
    document.getElementById('total-records').textContent = totalRecords.toLocaleString();
    document.getElementById('avg-ph').textContent = avgPh.toFixed(1);
    document.getElementById('avg-tds').textContent = Math.round(avgTds).toLocaleString();
    document.getElementById('avg-temp').textContent = avgTemp.toFixed(1)+'°C';
}

function updatePagination() {
    const totalPages = Math.ceil(logDataCache.length / itemsPerPage);
    const startItem = (currentPage - 1) * itemsPerPage + 1;
    const endItem = Math.min(currentPage * itemsPerPage, logDataCache.length);
    document.getElementById('showing-from').textContent = startItem;
    document.getElementById('showing-to').textContent = endItem;
    document.getElementById('total-entries').textContent = logDataCache.length;
    document.getElementById('prev-page').disabled = currentPage === 1;
    document.getElementById('next-page').disabled = currentPage === totalPages;
}
function changePage(dir) { const totalPages = Math.ceil(logDataCache.length / itemsPerPage); const newPage = currentPage + dir; if (newPage>=1 && newPage<=totalPages){ currentPage=newPage; displayLogData(logDataCache); updatePagination(); } }

function sortTable(column) {
    if (sortColumn===column) sortDirection = sortDirection==='asc'?'desc':'asc'; else { sortColumn=column; sortDirection='desc'; }
    sortCache();
    currentPage=1; displayLogData(logDataCache); updatePagination();
}

// Keep cache sorted according to current sort settings
function sortCache(){
    logDataCache.sort((a,b)=>{
        let aVal=a[sortColumn]; let bVal=b[sortColumn];
        if (sortColumn!=='timestamp'){
            if (typeof aVal==='string'){ aVal=aVal.toLowerCase(); }
            if (typeof bVal==='string'){ bVal=bVal.toLowerCase(); }
        }
        if (aVal===bVal) return 0;
        return sortDirection==='asc' ? (aVal>bVal?1:-1) : (aVal<bVal?1:-1);
    });
}

// Live updates: attach and detach listeners based on current filters
function detachLiveListeners(){
    try {
        liveListenerHandles.forEach(h=>{ try { h.ref.off('child_added', h.handler); } catch(e){} });
    } finally {
        liveListenerHandles = [];
    }
}

function attachLiveListeners(devices, startMs, endMs){
    // Determine latest timestamp per device from current cache to avoid duplicates
    const latestByDevice = {};
    for (const row of logDataCache){
        const key = (row.device||'').toString().toLowerCase();
        const ts = row.timestamp||0;
        if (!latestByDevice[key] || ts>latestByDevice[key]) latestByDevice[key]=ts;
    }
    devices.forEach(dev=>{
        const baseStart = Math.max(startMs, (latestByDevice[dev]||startMs)) + 1;
        const ref = dbLog.ref(`kebun-${dev}/history`).orderByChild('timestamp').startAt(baseStart);
        const handler = (snap)=>{
            const data = snap.val(); if(!data) return;
            // Compute timestamp robustly
            let ts = null;
            if (typeof data.timestamp === 'number') ts = data.timestamp;
            else if (typeof data.timestamp === 'string' && !isNaN(+data.timestamp)) ts = +data.timestamp;
            else if (typeof data.createdAt === 'number') ts = data.createdAt;
            else if (data.date && data.time) { const dt = new Date(`${data.date} ${data.time}`); if (!isNaN(dt.getTime())) ts = dt.getTime(); }
            if (ts === null) return;
            if (ts < startMs || ts > endMs) return; // outside current filter range
            const row = {
                id: snap.key,
                timestamp: ts,
                device: dev.toUpperCase(),
                ph: parseFloat(data.ph) || 0,
                tds: parseInt(data.tds) || 0,
                temperature: parseFloat(data.suhu ?? data.temperature) || 0,
                cal_ph_asam: parseFloat(data.cal_ph_asam) || 0,
                cal_ph_netral: parseFloat(data.cal_ph_netral) || 0,
                cal_tds_k: parseFloat(data.cal_tds_k) || 0
            };
            logDataCache.push(row);
            sortCache();
            updateStatistics(logDataCache);
            displayLogData(logDataCache);
            updatePagination();
        };
        ref.on('child_added', handler);
        liveListenerHandles.push({ref, handler});
    });
}

function exportToCSV() {
    if (!logDataCache.length) { alert('Tidak ada data untuk diekspor'); return; }
    const headers = ['Waktu','Perangkat','pH','TDS (ppm)','Suhu (°C)','Cal pH Asam','Cal pH Netral','Cal TDS K'];
    const csvData = logDataCache.map(r=>{
        const date = new Date(r.timestamp); const formattedDate = date.toLocaleString('id-ID');
        return [formattedDate, 'Perangkat '+r.device, r.ph.toFixed(1), Math.round(r.tds), r.temperature.toFixed(1), r.cal_ph_asam.toFixed(4), r.cal_ph_netral.toFixed(4), r.cal_tds_k.toFixed(4)];
    });
    const csvContent=[headers,...csvData].map(row=>row.map(c=>`"${c}"`).join(',')).join('\n');
    const blob=new Blob([csvContent],{type:'text/csv;charset=utf-8;'}); const link=document.createElement('a'); const url=URL.createObjectURL(blob);
    link.setAttribute('href',url); const now=new Date(); const filename=`hidroganik_data_${now.getFullYear()}-${(now.getMonth()+1+'').padStart(2,'0')}-${(now.getDate()+'').padStart(2,'0')}.csv`;
    link.setAttribute('download',filename); link.style.visibility='hidden'; document.body.appendChild(link); link.click(); document.body.removeChild(link);
    const exportBtn=document.getElementById('export-csv'); const original=exportBtn.innerHTML; exportBtn.innerHTML='✅ Exported!'; exportBtn.disabled=true; setTimeout(()=>{exportBtn.innerHTML=original; exportBtn.disabled=false;},2000);
}

document.addEventListener('DOMContentLoaded', ()=>{
        const debouncedReload = debounce(()=>{ currentPage=1; loadLogData(); },600);
        document.getElementById('start-date').addEventListener('input', debouncedReload);
        document.getElementById('end-date').addEventListener('input', debouncedReload);
        document.getElementById('device-filter').addEventListener('change', debouncedReload);
    document.getElementById('export-csv').addEventListener('click', exportToCSV);
    document.getElementById('prev-page').addEventListener('click', ()=>changePage(-1));
    document.getElementById('next-page').addEventListener('click', ()=>changePage(1));
    loadLogData();
        initLiveClock();
        initLogo();
});

// Live clock for navbar
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

// Improved logo loader using same logic as calibration page
function initLogo(){
    const logoImg=document.getElementById('company-logo');
    if(!logoImg) return; const fallback=document.getElementById('logo-fallback');
    const base = (typeof APP_BASE_URL!=='undefined') ? APP_BASE_URL : '';
    const sources=[
        base + '/assets/logo.png',
        base + '/assets/logo.jpg',
        base + '/assets/logo.svg',
        base + '/logo.png',
        base + '/logo.jpg'
    ];
    let loaded=false;
    sources.forEach(src=>{
        if(loaded) return; const test=new Image(); test.onload=()=>{ if(!loaded){ logoImg.src=src; logoImg.classList.remove('hidden'); if(fallback) fallback.style.display='none'; loaded=true; } }; test.src=src; });
    // If still not loaded after 3s keep fallback icon
    setTimeout(()=>{ if(!loaded && fallback){ fallback.style.display='flex'; } },3000);
}
</script>
<?= $this->endSection() ?>