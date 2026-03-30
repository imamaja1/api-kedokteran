/* ─────────────────────────────────────────
   Presets
   BASE and SESSION_COOKIE are injected inline by tester.blade.php
───────────────────────────────────────── */
const PRESETS = [
    { label:'CSRF Cookie',     method:'GET',  url:'/sanctum/csrf-cookie',       body:'',    icon:'bi-shield-lock'      },
    { label:'Login Mahasiswa', method:'POST', url:`${BASE}/auth/mhs/login`, body:'{"nim":"12345","password":"rahasia"}', icon:'bi-person-badge'     },
    { label:'Login Dosen',     method:'POST', url:`${BASE}/auth/dosen/login`,     body:'{"kode_dosen":"D001","password":"rahasia"}', icon:'bi-person-workspace' },
];
const METHOD_COLOR = { GET:'m-GET', POST:'m-POST', PUT:'m-PUT', PATCH:'m-PATCH', DELETE:'m-DELETE' };

function buildPresets() {
    const grid = document.getElementById('presetsGrid');
    grid.innerHTML = PRESETS.map(p => `
        <button class="preset-btn" onclick="loadPreset(${PRESETS.indexOf(p)})">
            <span class="preset-method ${METHOD_COLOR[p.method] || ''}">${p.method}</span>
            <i class="bi ${p.icon}" style="opacity:.6"></i>${p.label}
        </button>`).join('');
}
function loadPreset(i) {
    const p = PRESETS[i];
    setMethod(p.method);
    document.getElementById('urlInput').value  = p.url;
    document.getElementById('bodyInput').value = p.body || '';
    if (p.body) { document.querySelector('[onclick*="body"]').click(); }
}

/* ─────────────────────────────────────────
   Method select colouring
───────────────────────────────────────── */
function setMethod(m) {
    const sel = document.getElementById('methodSel');
    sel.value = m;
    onMethodChange();
}
function onMethodChange() {
    const sel = document.getElementById('methodSel');
    const m   = sel.value;
    sel.className = `method-select mc-${m}`;
    const bodyTab = document.getElementById('bodyTab');
    bodyTab.style.opacity = ['POST','PUT','PATCH'].includes(m) ? '1' : '.4';
}

/* ─────────────────────────────────────────
   Tabs
───────────────────────────────────────── */
function reqTab(btn, pane) {
    document.querySelectorAll('.req-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.req-pane').forEach(p => p.classList.remove('show'));
    btn.classList.add('active');
    document.getElementById(`pane-${pane}`).classList.add('show');
}
function resTab(btn, pane) {
    document.querySelectorAll('.res-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.res-pane').forEach(p => p.classList.remove('show'));
    btn.classList.add('active');
    document.getElementById(`res-${pane}`).classList.add('show');
}

/* ─────────────────────────────────────────
   Cookie helpers
───────────────────────────────────────── */
function getCookie(name) {
    const val = `; ${document.cookie}`;
    const parts = val.split(`; ${name}=`);
    if (parts.length === 2) return decodeURIComponent(parts.pop().split(';').shift());
    return null;
}
function allCookies() {
    if (!document.cookie) return {};
    return Object.fromEntries(
        document.cookie.split(';').map(c => {
            const [k,...v] = c.trim().split('=');
            return [k, decodeURIComponent(v.join('='))];
        })
    );
}

/* ─────────────────────────────────────────
   Debug refresh
───────────────────────────────────────── */
function refreshDebug() {
    const xsrf    = getCookie('XSRF-TOKEN');
    const session = getCookie(SESSION_COOKIE);
    const xsrfEl  = document.getElementById('dbgXsrf');
    const sessEl  = document.getElementById('dbgSession');

    xsrfEl.textContent  = xsrf    ? xsrf.substring(0,24)+'…'    : '(nggak ada — jalankan CSRF Cookie preset dulu)';
    xsrfEl.className    = `debug-val ${xsrf ? 'ok' : 'warn'}`;
    sessEl.textContent  = session ? session.substring(0,24)+'…' : '(belum login)';
    sessEl.className    = `debug-val ${session ? 'ok' : ''}`;

    const chips = document.getElementById('dbgCookies');
    const all   = allCookies();
    const keys  = Object.keys(all);
    chips.innerHTML = keys.length
        ? keys.map(k => `<span class="cookie-chip" title="${k}=${all[k]}">${k}</span>`).join('')
        : '<span class="text-muted" style="font-size:.78rem">Tidak ada cookies</span>';
}
setInterval(refreshDebug, 2000);

/* ─────────────────────────────────────────
   History
───────────────────────────────────────── */
let history = JSON.parse(localStorage.getItem('tester_history') || '[]');

function saveHistory(method, url, status) {
    history.unshift({ method, url, status, ts: Date.now() });
    if (history.length > 20) history.pop();
    localStorage.setItem('tester_history', JSON.stringify(history));
    renderHistory();
}
function clearHistory() {
    history = []; localStorage.removeItem('tester_history'); renderHistory();
}
function renderHistory() {
    const el = document.getElementById('histList');
    if (!history.length) { el.innerHTML = '<div class="hist-empty">Belum ada history</div>'; return; }
    const statusClass = s => s < 200 ? 'status-1xx' : s < 300 ? 'status-2xx' : s < 400 ? 'status-3xx' : s < 500 ? 'status-4xx' : 'status-5xx';
    el.innerHTML = history.map((h,i) => `
        <div class="hist-item" onclick="loadHistory(${i})">
            <span class="hist-method ${METHOD_COLOR[h.method] || ''}">${h.method}</span>
            <span class="hist-url" title="${h.url}">${h.url}</span>
            ${h.status ? `<span class="hist-code status-pill ${statusClass(h.status)}">${h.status}</span>` : ''}
        </div>`).join('');
}
function loadHistory(i) {
    const h = history[i];
    setMethod(h.method);
    document.getElementById('urlInput').value = h.url;
}

/* ─────────────────────────────────────────
   Send request
───────────────────────────────────────── */
async function sendRequest() {
    const method  = document.getElementById('methodSel').value;
    const url     = document.getElementById('urlInput').value.trim();
    const xsrf    = getCookie('XSRF-TOKEN') || '';

    if (!url) { document.getElementById('urlInput').focus(); return; }

    // Parse headers
    let headers = {};
    try {
        const raw = document.getElementById('headersInput').value.trim();
        if (raw) headers = JSON.parse(raw);
    } catch { alert('Header JSON tidak valid.'); return; }

    // Inject Sanctum / Content-Type
    headers['Accept']         = headers['Accept'] || 'application/json';
    headers['Content-Type']   = 'application/json';
    if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;

    // Parse body
    let body = undefined;
    if (['POST','PUT','PATCH'].includes(method)) {
        const raw = document.getElementById('bodyInput').value.trim();
        body = raw || undefined;
    }

    // UI: loading
    const sendBtn  = document.getElementById('sendBtn');
    const spinner  = document.getElementById('spinner');
    const sendIcon = document.getElementById('sendIcon');
    const sendLbl  = document.getElementById('sendLabel');
    sendBtn.disabled = true; spinner.style.display = 'inline-block';
    sendIcon.style.display = 'none'; sendLbl.textContent = 'Sending…';

    // Clear prev response
    document.getElementById('resEmpty').style.display    = 'none';
    document.getElementById('resBodyCode').style.display = 'none';
    document.getElementById('resTabs').style.display     = 'none';
    document.getElementById('copyBtn').style.display     = 'none';
    document.getElementById('timeEl').style.display      = 'none';
    document.getElementById('statusEl').className        = 'status-placeholder';
    document.getElementById('statusEl').textContent      = 'Mengirim…';

    const t0 = performance.now();
    let res, responseText;

    try {
        res = await fetch(url, {
            method,
            headers,
            body,
            credentials: 'include',
        });
        responseText = await res.text();
    } catch (err) {
        document.getElementById('statusEl').className   = 'status-pill status-5xx';
        document.getElementById('statusEl').textContent = `Network Error: ${err.message}`;
        resetBtn(); return;
    }

    const elapsed = Math.round(performance.now() - t0);

    // Status
    const s = res.status;
    const cls = s < 200 ? 'status-1xx' : s < 300 ? 'status-2xx' : s < 400 ? 'status-3xx' : s < 500 ? 'status-4xx' : 'status-5xx';
    const statusEl = document.getElementById('statusEl');
    statusEl.className   = `status-pill ${cls}`;
    statusEl.textContent = `${s} ${res.statusText}`;

    const timeEl = document.getElementById('timeEl');
    document.getElementById('timeVal').textContent = `${elapsed} ms`;
    timeEl.style.display = 'flex';

    // Body
    let pretty = responseText;
    try { pretty = JSON.stringify(JSON.parse(responseText), null, 2); } catch {}
    const codeEl = document.getElementById('resBodyCode');
    codeEl.textContent = pretty;
    codeEl.style.display = 'block';
    document.getElementById('copyBtn').style.display = 'inline-flex';

    // Response headers
    let hdrs = '';
    res.headers.forEach((v, k) => { hdrs += `${k}: ${v}\n`; });
    document.getElementById('resHeadersCode').textContent = hdrs;

    document.getElementById('resTabs').style.display = 'flex';
    // ensure body pane active
    document.querySelectorAll('.res-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.res-pane').forEach(p => p.classList.remove('show'));
    document.querySelector('.res-tab').classList.add('active');
    document.getElementById('res-body').classList.add('show');

    // History
    saveHistory(method, url, s);

    resetBtn();
    refreshDebug();
}

function resetBtn() {
    const sendBtn  = document.getElementById('sendBtn');
    const spinner  = document.getElementById('spinner');
    const sendIcon = document.getElementById('sendIcon');
    const sendLbl  = document.getElementById('sendLabel');
    sendBtn.disabled = false; spinner.style.display = 'none';
    sendIcon.style.display = ''; sendLbl.textContent = 'Send';
}

async function copyResponse() {
    const text = document.getElementById('resBodyCode').textContent;
    try {
        await navigator.clipboard.writeText(text);
        const btn = document.getElementById('copyBtn');
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Copied!';
        setTimeout(() => { btn.innerHTML = '<i class="bi bi-clipboard"></i> Copy'; }, 1500);
    } catch {}
}

/* ─────────────────────────────────────────
   Init
───────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    buildPresets();
    renderHistory();
    refreshDebug();
    onMethodChange();

    // Support `?method=GET&url=...` from Docs "Try it" links
    const params = new URLSearchParams(location.search);
    if (params.get('url'))    document.getElementById('urlInput').value = params.get('url');
    if (params.get('method')) setMethod(params.get('method'));
});
