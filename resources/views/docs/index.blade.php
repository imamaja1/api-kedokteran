@extends('docs.layouts')
@section('title', 'API Docs')

@push('styles')
<link rel="stylesheet" href="{{ asset('docs/index.css') }}">
@endpush

{{-- ─── Sidebar ─── --}}
@section('sidebar')
<div class="sidebar-label">API Reference</div>
@foreach($sections as $section)
<a href="#section-{{ $section->id }}" class="sidebar-section-title">
    <span class="dot"></span>{{ $section->title }}
</a>
@foreach($section->endpoints as $ep)
<a href="#ep-{{ $ep->id }}" class="sidebar-ep-link">
    <span class="sidebar-badge sb-{{ $ep->method }}">{{ $ep->method }}</span>
    {{ $ep->title }}
</a>
@endforeach
@endforeach
@endsection

{{-- ─── Content ─── --}}
@section('content')
<div class="docs-content">

    <div class="docs-hero">
        <h1><i class="bi bi-file-earmark-code text-primary me-2"></i>API Documentation</h1>
        <p>Complete reference for SIAKAD Kedokteran API &bull; Base URL: <code>{{ url('/api') }}</code></p>
    </div>

    {{-- Mobile section quick-nav (hidden on desktop, sidebar handles it) --}}
    @if($sections->isNotEmpty())
    <details class="mobile-section-nav">
        <summary>
            <span><i class="bi bi-list-ul me-2"></i>Navigasi Seksi</span>
            <i class="bi bi-chevron-down chevron"></i>
        </summary>
        <div class="mobile-section-nav-list">
            @foreach($sections as $section)
            <a href="#section-{{ $section->id }}">
                <span class="sidebar-badge sb-{{ $section->endpoints->first()?->method ?? 'GET' }}"
                    style="font-size:.6rem">{{ $section->endpoints->count() }}</span>
                {{ $section->title }}
            </a>
            @endforeach
        </div>
    </details>
    @endif

    @forelse($sections as $section)
    <div id="section-{{ $section->id }}">
        <h2 class="section-anchor-heading">
            {{ $section->title }}
            <span class="section-pill">{{ $section->endpoints->count() }} endpoints</span>
        </h2>
        <hr class="section-rule">

        @foreach($section->endpoints as $ep)
        <div class="ep-card" id="ep-{{ $ep->id }}">

            {{-- Header --}}
            <div class="ep-header">
                <span class="method-badge m-{{ $ep->method }}" style="margin-top:.1rem">{{ $ep->method }}</span>
                <div class="ep-meta">
                    <p class="ep-title">{{ $ep->title }}</p>
                    @if($ep->description)<p class="ep-desc">{{ $ep->description }}</p>@endif
                </div>
                <a href="{{ route('admin.tester') }}?method={{ $ep->method }}&url={{ urlencode($ep->url) }}"
                    class="btn-try" title="Buka di API Tester">
                    <i class="bi bi-send-fill"></i> <span>Try it</span>
                </a>
            </div>

            {{-- Endpoint --}}
            <div class="ep-block-label">
                <i class="bi bi-link-45deg"></i> Endpoint
            </div>
            <pre class="ep-code"><span class="ck">url</span>    <span class="cp">:</span> <span class="cv">{{ $ep->url }}</span>
<span class="ck">method</span> <span class="cp">:</span> <span class="cv">{{ strtolower($ep->method) }}</span></pre>

            {{-- Request --}}
            @if($ep->headers || $ep->body)
            <div class="ep-block-label">
                <i class="bi bi-arrow-up-circle"></i> Request
            </div>
            <pre class="ep-code">@if($ep->headers)<span class="cc">// Headers</span>
{{ $ep->headers }}
@endif
@if($ep->body)<span class="cc">// Body</span>
{{ $ep->body }}
@endif</pre>
            @endif

            {{-- Response --}}
            @if($ep->response_example)
            <div class="ep-block-label">
                <i class="bi bi-arrow-down-circle"></i> Response
            </div>
            <pre class="ep-code ep-code-resp">{{ $ep->response_example }}</pre>
            @endif

        </div>
        @endforeach
    </div>
    @empty
    <div class="text-center py-5 text-muted">
        <i class="bi bi-journal-x fs-2 d-block mb-3"></i>
        <p>Belum ada dokumentasi API.</p>
        <a href="{{ route('login') }}">Masuk ke admin panel</a> untuk menambahkan.
    </div>
    @endforelse

</div>
@endsection

@push('scripts')
<script src="{{ asset('docs/index.js') }}"></script>
@endpush