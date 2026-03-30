@extends('docs.layouts')
@section('title', 'API Tester')

@push('styles')
<link rel="stylesheet" href="{{ asset('docs/tester.css') }}">
@endpush

@section('content')
<div class="tester-wrap">

    {{-- ── URL BAR (top full-width) ── --}}
    <div class="t-card url-bar-card">
        <div class="t-card-body">
            <div class="url-bar">
                <select class="method-select mc-GET" id="methodSel" onchange="onMethodChange()">
                    <option>GET</option>
                    <option>POST</option>
                    <option>PUT</option>
                    <option>PATCH</option>
                    <option>DELETE</option>
                </select>
                <input type="url" class="url-input" id="urlInput" placeholder="{{ url('/api') }}/endpoint"
                    value="{{ url('/api') }}">
                <button class="btn-send" id="sendBtn" onclick="sendRequest()">
                    <span class="spinner-ring" id="spinner"></span>
                    <i class="bi bi-send-fill" id="sendIcon"></i>
                    <span id="sendLabel">Send</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── REQUEST PANEL (left) ── --}}
    <div style="display:flex; flex-direction:column; gap:1.2rem;">

        {{-- Headers & Body --}}
        <div class="t-card">
            <div class="t-card-head">
                <i class="bi bi-code-square t-card-icon"></i>
                <h6>Request</h6>
            </div>
            <div class="t-card-body">
                <div class="req-tabs">
                    <button class="req-tab active" onclick="reqTab(this,'headers')">
                        <i class="bi bi-list-ul"></i> Headers
                    </button>
                    <button class="req-tab" id="bodyTab" onclick="reqTab(this,'body')">
                        <i class="bi bi-braces"></i> Body
                    </button>
                </div>

                <div class="req-pane show" id="pane-headers">
                    <textarea class="t-textarea" id="headersInput" rows="5"
                        placeholder='{"Accept": "application/json"}'>{
  "Accept": "application/json"
}</textarea>
                    <p class="textarea-hint">
                        <i class="bi bi-info-circle me-1"></i>
                        <code>X-XSRF-TOKEN</code> dan <code>Cookie</code> disisipkan otomatis.
                    </p>
                </div>

                <div class="req-pane" id="pane-body">
                    <textarea class="t-textarea" id="bodyInput" rows="5" placeholder='{"key": "value"}'></textarea>
                    <p class="textarea-hint">JSON body — hanya untuk POST/PUT/PATCH.</p>
                </div>
            </div>
        </div>

        {{-- Quick Presets --}}
        <div class="t-card">
            <div class="t-card-head">
                <i class="bi bi-lightning-charge-fill t-card-icon"></i>
                <h6>Quick Presets</h6>
            </div>
            <div class="t-card-body">
                <div class="presets-grid" id="presetsGrid"></div>
            </div>
        </div>

        {{-- Debug --}}
        <div class="t-card">
            <div class="t-card-head">
                <i class="bi bi-shield-check t-card-icon"></i>
                <h6>Debug — Sanctum Cookies</h6>
                <span class="ms-auto badge bg-light text-secondary border" style="font-size:.67rem"
                    id="dbgRefreshBadge">
                    auto-refresh 2s
                </span>
            </div>
            <div class="t-card-body">
                <div class="debug-row">
                    <span class="debug-key">XSRF Token</span>
                    <span class="debug-val" id="dbgXsrf">—</span>
                </div>
                <div class="debug-row">
                    <span class="debug-key">Session</span>
                    <span class="debug-val" id="dbgSession">—</span>
                </div>
                <div class="debug-row">
                    <span class="debug-key">Base URL</span>
                    <span class="debug-val ok">{{ url('/') }}</span>
                </div>
                <div class="debug-row">
                    <span class="debug-key">All Cookies</span>
                    <div class="cookie-chips" id="dbgCookies"></div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── RESPONSE PANEL (right) ── --}}
    <div style="display:flex; flex-direction:column; gap:1.2rem;">

        {{-- Response --}}
        <div class="t-card" style="flex:1">
            <div class="res-status-bar">
                <span id="statusEl" class="status-placeholder">Klik Send untuk melihat respons</span>
                <span class="time-badge" id="timeEl" style="display:none">
                    <i class="bi bi-clock"></i><span id="timeVal"></span>
                </span>
                <button onclick="copyResponse()" id="copyBtn"
                    style="display:none;margin-left:auto;font-size:.75rem;padding:.28rem .7rem;border:1px solid #e5e8ef;border-radius:6px;background:#f9fafb;cursor:pointer;color:#374151;"
                    title="Copy response body">
                    <i class="bi bi-clipboard"></i> Copy
                </button>
            </div>
            <div class="res-tabs" id="resTabs" style="display:none">
                <button class="res-tab active" onclick="resTab(this,'body')">Body</button>
                <button class="res-tab" onclick="resTab(this,'headers')">Headers</button>
            </div>
            <div class="res-pane show" id="res-body">
                <div class="res-empty" id="resEmpty">
                    <i class="bi bi-inbox d-block mb-2"></i>
                    Respons akan tampil di sini
                </div>
                <pre class="res-code" id="resBodyCode" style="display:none"></pre>
            </div>
            <div class="res-pane" id="res-headers">
                <pre class="res-code" id="resHeadersCode"></pre>
            </div>
        </div>

        {{-- History --}}
        <div class="t-card">
            <div class="t-card-head">
                <i class="bi bi-clock-history t-card-icon"></i>
                <h6>History</h6>
                <button onclick="clearHistory()"
                    style="margin-left:auto;font-size:.72rem;background:none;border:none;color:#9ca3af;cursor:pointer;"
                    title="Clear history">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <div class="t-card-body" style="padding:.6rem .8rem;max-height:230px;overflow-y:auto;" id="histList">
                <div class="hist-empty">Belum ada history</div>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
    const BASE = '{{ url("/api") }}';
    const SESSION_COOKIE = '{{ config("session.cookie","laravel_session") }}';
</script>
<script src="{{ asset('docs/tester.js') }}"></script>
@endpush