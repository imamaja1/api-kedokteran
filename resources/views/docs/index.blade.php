<!DOCTYPE html>
<html class="dark" lang="id">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>API Documentation &amp; Live Tester — SIAKAD Kedokteran</title>

    {{-- Google Fonts: Geist, JetBrains Mono, Material Symbols --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@100..900&family=JetBrains+Mono:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    {{-- Tailwind CSS CDN & Custom Theme Tokens --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "background": "#0b1326",
                        "surface": "#0b1326",
                        "surface-dim": "#0b1326",
                        "surface-bright": "#31394d",
                        "surface-container-lowest": "#060e20",
                        "surface-container-low": "#131b2e",
                        "surface-container": "#171f33",
                        "surface-container-high": "#222a3d",
                        "surface-container-highest": "#2d3449",
                        "on-surface": "#dae2fd",
                        "on-surface-variant": "#c7c4d8",
                        "outline": "#918fa1",
                        "outline-variant": "#464555",
                        "primary": "#c3c0ff",
                        "primary-container": "#4f46e5",
                        "on-primary": "#1d00a5",
                        "on-primary-container": "#dad7ff",
                        "secondary": "#89ceff",
                        "secondary-container": "#00a2e6",
                        "tertiary": "#4ae176",
                        "tertiary-fixed": "#6bff8f",
                        "tertiary-container": "#007030",
                        "on-tertiary": "#003915",
                        "error": "#ffb4ab",
                        "error-container": "#93000a"
                    },
                    fontFamily: {
                        "body-md": ["Geist", "sans-serif"],
                        "body-sm": ["Geist", "sans-serif"],
                        "title-md": ["Geist", "sans-serif"],
                        "headline-lg": ["Geist", "sans-serif"],
                        "headline-xl": ["Geist", "sans-serif"],
                        "code-sm": ["'JetBrains Mono'", "monospace"],
                        "code-md": ["'JetBrains Mono'", "monospace"],
                        "code-lg": ["'JetBrains Mono'", "monospace"],
                        "label-badge": ["'JetBrains Mono'", "monospace"]
                    }
                }
            }
        };
    </script>
    <style>
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #060e20;
        }
        ::-webkit-scrollbar-thumb {
            background: #222d45;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #3b4866;
        }
        pre, code {
            font-family: 'JetBrains Mono', monospace;
        }
        .method-badge-GET { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
        .method-badge-POST { background: rgba(74, 225, 118, 0.15); color: #4ae176; border: 1px solid rgba(74, 225, 118, 0.3); }
        .method-badge-PUT { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .method-badge-PATCH { background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }
        .method-badge-DELETE { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

        /* ─── 3-Panel Resizable Gutter Styles (PRD F-01..F-12) ─── */
        .panel-gutter {
            touch-action: none;
            user-select: none;
            -webkit-user-select: none;
            position: relative;
        }
        .panel-gutter::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: -4px;
            right: -4px;
            z-index: 30;
        }
        .panel-gutter:hover,
        .panel-gutter.is-dragging {
            background-color: rgba(99, 102, 241, 0.35) !important;
        }
        .panel-gutter:hover .gutter-handle,
        .panel-gutter.is-dragging .gutter-handle {
            background-color: #c3c0ff !important;
            box-shadow: 0 0 8px rgba(195, 192, 255, 0.6);
        }
        body.is-resizing {
            user-select: none !important;
            -webkit-user-select: none !important;
            cursor: col-resize !important;
        }
        body.is-resizing iframe,
        body.is-resizing input,
        body.is-resizing textarea,
        body.is-resizing select {
            pointer-events: none !important;
        }
    </style>
</head>

<body class="bg-background font-body-md text-on-surface antialiased selection:bg-primary-container selection:text-on-primary-container overflow-hidden">

    {{-- ─── TOPBAR HEADER ─── --}}
    <header class="fixed top-0 left-0 right-0 z-50 h-14 bg-surface/95 backdrop-blur-xl border-b border-[#222d45] flex items-center justify-between px-4 sm:px-6 shadow-[0_1px_12px_rgba(0,0,0,0.35)]">
        {{-- Brand & Env --}}
        <div class="flex items-center gap-3">
            <button id="btn-toggle-sidebar" class="lg:hidden p-1.5 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface">
                <span class="material-symbols-outlined text-[20px]">menu</span>
            </button>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-primary-container flex items-center justify-center shadow-inner">
                    <span class="material-symbols-outlined text-on-primary-container text-[20px]">terminal</span>
                </div>
                <div class="flex flex-col">
                    <span class="font-headline-lg text-[15px] font-bold tracking-tight text-on-surface flex items-center gap-2">
                        API SISKA <span class="text-xs font-normal text-outline hidden sm:inline">• SIAKAD Kedokteran</span>
                    </span>
                </div>
            </div>
            <div class="h-4 w-px bg-surface-variant hidden sm:block"></div>
            <a href="{{ url('/admin/docs/penilaian') }}" 
               class="flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-3 py-1 rounded-full bg-surface-container-high hover:bg-primary-container/20 text-on-surface hover:text-primary border border-[#222d45] hover:border-primary/50 transition-all duration-200 text-[11px] sm:text-[12px] font-semibold group shadow-sm"
               title="Buka Dokumentasi Lengkap Mekanisme Penilaian Blok Kedokteran">
                <span class="w-2 h-2 rounded-full bg-secondary animate-pulse"></span>
                <span class="material-symbols-outlined text-[15px] text-secondary group-hover:text-primary transition-colors">schema</span>
                <span class="hidden sm:inline tracking-tight">Mekanisme Penilaian Blok</span>
                <span class="sm:hidden font-medium">Penilaian Blok</span>
                <span class="material-symbols-outlined text-[14px] text-outline group-hover:text-primary group-hover:translate-x-0.5 transition-all">arrow_forward</span>
            </a>
        </div>



        {{-- Mobile View Mode Switcher (Docs vs Tester on screen < 1024px) --}}
        <div class="flex lg:hidden items-center bg-surface-container-low p-1 rounded-lg border border-[#222d45]">
            <button id="tab-btn-docs" onclick="switchMobilePane('docs')" class="px-3 py-1 rounded font-body-sm text-[12px] font-semibold bg-primary-container text-on-primary-container">
                Dokumentasi
            </button>
            <button id="tab-btn-tester" onclick="switchMobilePane('tester')" class="px-3 py-1 rounded font-body-sm text-[12px] font-semibold text-outline hover:text-on-surface">
                Live Tester
            </button>
        </div>

        {{-- Right Actions --}}
        <div class="flex items-center gap-2">
            {{-- Panel Layout Controls (Desktop >= 1024px) --}}
            <button id="btn-reset-layout" onclick="resetPanelLayout()" 
                class="hidden lg:flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface-variant hover:text-on-surface text-body-sm font-medium border border-[#222d45] hover:border-outline transition-colors"
                title="Reset tata letak ketiga panel ke ukuran awal (F-07)">
                <span class="material-symbols-outlined text-[16px] text-secondary">restart_alt</span>
                <span class="hidden xl:inline text-xs">Reset Layout</span>
            </button>
            <button id="btn-toggle-tester" onclick="toggleTesterPanel()" 
                class="hidden lg:flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface-variant hover:text-on-surface text-body-sm font-medium border border-[#222d45] hover:border-outline transition-colors"
                title="Tutup / Buka panel Live API Tester (F-11, Alt+T)">
                <span id="btn-toggle-tester-icon" class="material-symbols-outlined text-[16px] text-tertiary">right_panel_close</span>
                <span id="btn-toggle-tester-label" class="hidden xl:inline text-xs">Tester</span>
            </button>

            @auth
            <span class="hidden sm:flex items-center gap-1.5 text-body-sm text-on-surface-variant font-medium px-2">
                <span class="material-symbols-outlined text-[18px] text-primary">account_circle</span>
                <span>{{ auth()->user()->name }}</span>
            </span>
            @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-1 px-3 py-1.5 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface text-body-sm font-medium border border-[#222d45] transition-colors" title="Kembali ke Dashboard Admin">
                <span class="material-symbols-outlined text-[16px]">dashboard</span>
                <span class="hidden sm:inline">Admin Panel</span>
            </a>
            @endif
            <form action="{{ route('logout') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="p-1.5 rounded-lg bg-surface-container hover:bg-error/20 hover:text-error text-outline transition-colors" title="Logout">
                    <span class="material-symbols-outlined text-[18px]">logout</span>
                </button>
            </form>
            @else
            <a href="{{ route('login') }}" class="px-3 py-1.5 rounded-lg bg-primary-container hover:bg-primary-container/80 text-on-primary-container text-body-sm font-semibold flex items-center gap-1">
                <span class="material-symbols-outlined text-[16px]">login</span>
                <span>Login</span>
            </a>
            @endauth
        </div>
    </header>

    {{-- ─── 3-COLUMN WORKSPACE CONTAINER ─── --}}
    <main class="pt-14 h-screen bg-background overflow-hidden">
        <div class="h-[calc(100vh-3.5rem)] flex flex-row overflow-hidden w-full select-none" id="workspace-container">

            {{-- ==================== KOLOM 1: ENDPOINT EXPLORER (285px default) ==================== --}}
            <aside id="col-sidebar" style="width: 285px;" class="shrink-0 flex flex-col bg-surface-container-lowest border-r border-[#222d45] select-text transition-transform duration-200 z-40 fixed lg:static inset-y-14 left-0 -translate-x-full lg:translate-x-0 shadow-2xl lg:shadow-none">
                
                {{-- Explorer Header & Filter Bar --}}
                <div class="p-3 border-b border-[#222d45] flex flex-col gap-2.5 bg-surface-container-low/60">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-primary text-[18px]">account_tree</span>
                            <span class="font-label-badge text-[11px] tracking-wider text-outline uppercase font-bold">Seksi &amp; Endpoint</span>
                        </div>
                        <span class="px-2 py-0.5 rounded bg-surface-container-high font-code-sm text-[11px] text-secondary font-semibold" id="total-routes-badge">
                            {{ $sections->sum(fn($s) => $s->endpoints->count()) }} Routes
                        </span>
                    </div>

                    {{-- Search Input with Shortcut / --}}
                    <div class="relative flex items-center">
                        <span class="material-symbols-outlined absolute left-2.5 text-outline text-[16px]">filter_list</span>
                        <input id="route-search-input" 
                            class="w-full h-8 pl-8 pr-7 rounded bg-surface-container-lowest font-code-sm text-[12px] text-on-surface placeholder:text-outline/70 focus:outline-none focus:ring-1 focus:ring-primary-container border border-[#222d45] focus:border-primary-container" 
                            placeholder="Cari path / judul..." type="text">
                        <kbd class="absolute right-2 px-1.5 py-0.5 rounded bg-surface-container text-[10px] font-code-sm text-outline border border-[#2d3748]">/</kbd>
                    </div>

                    {{-- Filter Method Chips --}}
                    <div class="flex items-center gap-1 overflow-x-auto pb-0.5 scrollbar-none" id="method-filter-bar">
                        <button onclick="setMethodFilter('ALL')" class="method-chip active px-2 py-0.5 rounded font-label-badge text-[10px] bg-primary-container text-on-primary-container font-bold" data-method="ALL">ALL</button>
                        <button onclick="setMethodFilter('GET')" class="method-chip px-1.5 py-0.5 rounded font-label-badge text-[10px] text-blue-400 bg-blue-500/10 hover:bg-blue-500/20 font-bold" data-method="GET">GET</button>
                        <button onclick="setMethodFilter('POST')" class="method-chip px-1.5 py-0.5 rounded font-label-badge text-[10px] text-tertiary bg-tertiary/10 hover:bg-tertiary/20 font-bold" data-method="POST">POST</button>
                        <button onclick="setMethodFilter('PUT')" class="method-chip px-1.5 py-0.5 rounded font-label-badge text-[10px] text-amber-400 bg-amber-400/10 hover:bg-amber-400/20 font-bold" data-method="PUT">PUT</button>
                        <button onclick="setMethodFilter('DELETE')" class="method-chip px-1.5 py-0.5 rounded font-label-badge text-[10px] text-red-400 bg-red-400/10 hover:bg-red-400/20 font-bold" data-method="DELETE">DEL</button>
                    </div>
                </div>

                {{-- Tree View List (Sections & Endpoints) --}}
                <div class="flex-1 overflow-y-auto px-2 py-3 space-y-2 font-code-sm" id="sidebar-tree">
                    {{-- ─── PINNED WORKFLOWS SECTION (F3) ─── --}}
                    @if(!empty($workflows))
                    <div class="mb-3 pb-2 border-b border-[#222d45]" id="workflows-sidebar-section">
                        <div class="px-2 py-1 flex items-center justify-between text-outline">
                            <span class="flex items-center gap-1.5 font-label-badge text-[11px] uppercase tracking-wider text-secondary font-bold">
                                <span class="material-symbols-outlined text-[15px]">alt_route</span>
                                Alur Bisnis Utama
                            </span>
                            <span class="px-1.5 py-0.2 rounded bg-secondary/10 text-secondary text-[10px] font-bold">{{ count($workflows) }} Alur</span>
                        </div>
                        <div class="mt-1 space-y-0.5">
                            @foreach($workflows as $wf)
                            <button onclick="selectWorkflow('{{ $wf['id'] }}')" 
                                id="nav-wf-{{ $wf['id'] }}"
                                class="wf-nav-item w-full flex items-center gap-2 px-2 py-1.5 rounded hover:bg-surface-container text-on-surface-variant hover:text-on-surface transition-all text-left group">
                                <span class="material-symbols-outlined text-[16px] text-secondary group-hover:text-primary shrink-0">{{ $wf['icon'] }}</span>
                                <div class="truncate flex-1 min-w-0">
                                    <div class="text-[12px] font-semibold truncate group-hover:text-on-surface font-body-sm">{{ $wf['title'] }}</div>
                                    <div class="text-[10px] text-outline truncate">{{ $wf['badge'] }}</div>
                                </div>
                            </button>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @forelse($sections as $sIdx => $section)
                    <div class="section-group" data-section-id="{{ $section->id }}" data-section-title="{{ strtolower($section->title) }}">
                        {{-- Section Accordion Header --}}
                        <button onclick="toggleSectionAccordion({{ $section->id }})" 
                            class="w-full flex items-center justify-between px-2 py-1.5 rounded text-on-surface hover:bg-surface-container-high font-title-md text-[12px] text-left transition-colors font-medium group">
                            <span class="flex items-center gap-1.5 truncate">
                                <span class="material-symbols-outlined text-[16px] text-secondary group-hover:text-primary transition-colors accordion-icon-{{ $section->id }}">folder_open</span>
                                <span class="font-body-sm font-semibold truncate">{{ $section->title }}</span>
                            </span>
                            <span class="font-code-sm text-[10px] text-outline px-1.5 py-0.5 rounded bg-surface-container shrink-0">
                                {{ $section->endpoints->count() }}
                            </span>
                        </button>

                        {{-- Endpoints List in Section --}}
                        <div class="mt-1 space-y-0.5 pl-2.5 border-l border-[#222d45] ml-3.5 accordion-content-{{ $section->id }}">
                            @foreach($section->endpoints as $ep)
                            <a href="javascript:void(0)" 
                                onclick="selectEndpoint({{ $ep->id }})"
                                id="nav-ep-{{ $ep->id }}"
                                data-ep-id="{{ $ep->id }}"
                                data-method="{{ $ep->method }}"
                                data-path="{{ strtolower($ep->url) }}"
                                data-title="{{ strtolower($ep->title) }}"
                                class="ep-nav-item flex items-center justify-between px-2 py-1.5 rounded hover:bg-surface-container text-on-surface-variant hover:text-on-surface transition-all font-code-sm text-[11px] group">
                                <div class="flex items-center gap-1.5 truncate min-w-0">
                                    <span class="px-1.5 py-0.2 rounded font-label-badge text-[9px] font-bold method-badge-{{ $ep->method }}">
                                        {{ $ep->method }}
                                    </span>
                                    <span class="truncate font-medium group-hover:text-on-surface text-on-surface-variant">{{ $ep->url }}</span>
                                </div>
                                <span class="active-indicator w-1.5 h-1.5 rounded-full bg-tertiary animate-pulse shrink-0 hidden"></span>
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8 text-outline text-body-sm">
                        Belum ada data dokumentasi.
                    </div>
                    @endforelse
                </div>

                {{-- Bottom Gateway Info --}}
                <div class="p-3 border-t border-[#222d45] bg-surface-container-lowest flex flex-col gap-1">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-tertiary"></span>
                            <span class="font-label-badge text-[11px] text-on-surface uppercase font-bold">API Gateway Status</span>
                        </div>
                        <span class="font-code-sm text-[11px] text-tertiary font-semibold" id="dbg-gateway-latency">OK</span>
                    </div>
                    <div class="flex items-center justify-between text-outline text-[11px] font-code-sm">
                        <span>Engine: Laravel {{ app()->version() }}</span>
                        <span class="text-secondary font-medium">Sanctum SPA</span>
                    </div>
                </div>
            </aside>

            {{-- Backdrop for mobile sidebar --}}
            <div id="sidebar-backdrop" onclick="toggleMobileSidebar()" class="fixed inset-0 bg-black/60 z-30 hidden lg:hidden"></div>

            {{-- ─── Gutter 1: Pemisah Kolom Kiri dan Tengah (PRD F-01) ─── --}}
            <div id="gutter-left"
                role="separator"
                tabindex="0"
                aria-orientation="vertical"
                aria-label="Pemisah panel seksi dan endpoint"
                aria-valuenow="285"
                aria-valuemin="200"
                aria-valuemax="480"
                class="panel-gutter hidden lg:flex items-center justify-center w-1.5 shrink-0 bg-surface-container-low hover:bg-primary-container active:bg-primary cursor-col-resize z-20 transition-colors border-x border-[#222d45] focus:outline-none focus:ring-1 focus:ring-primary group"
                title="Tarik untuk mengubah lebar panel navigasi (Klik 2x untuk reset)">
                <div class="gutter-handle w-0.5 h-8 rounded-full bg-outline/40 transition-colors"></div>
            </div>

            {{-- ==================== KOLOM 2: DOKUMENTASI SPESIFIKASI (Fluid flex-1) ==================== --}}
            <main id="col-specs" class="flex-1 flex flex-col min-w-0 lg:min-w-[420px] bg-surface overflow-y-auto select-text">
                
                {{-- Sticky Ribbon Header for Active Endpoint --}}
                <div class="sticky top-0 z-20 bg-surface/95 backdrop-blur px-4 sm:px-6 py-3 border-b border-[#222d45] flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center gap-2.5 truncate min-w-0">
                        <span id="spec-ribbon-method" class="px-2.5 py-1 rounded font-code-md text-[12px] font-bold method-badge-GET border">GET</span>
                        <span id="spec-ribbon-url" class="font-code-lg text-[14px] sm:text-[15px] text-on-surface font-semibold tracking-tight truncate">/api/endpoint</span>
                        <span class="px-2 py-0.5 rounded-full bg-tertiary/10 text-tertiary font-label-badge text-[10px] uppercase font-bold tracking-wider shrink-0 hidden sm:inline">AKTIF</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button id="btn-copy-endpoint-url" onclick="copyCurrentEndpointUrl()" 
                            class="flex items-center gap-1 px-3 py-1.5 rounded bg-surface-container-high hover:bg-surface-variant text-on-surface text-body-sm font-body-sm transition-colors border border-[#222d45] hover:border-outline">
                            <span class="material-symbols-outlined text-[15px]">content_copy</span>
                            <span id="btn-copy-url-label">Salin URL</span>
                        </button>
                    </div>
                </div>

                {{-- Content Specs Body --}}
                <div class="p-4 sm:p-6 lg:p-8 space-y-6 max-w-4xl mx-auto w-full">
                    
                    {{-- ─── VIEW 1: ENDPOINT SPECIFICATION VIEW ─── --}}
                    <div id="endpoint-spec-view" class="space-y-6">

                        {{-- Title, Breadcrumbs & Overview --}}
                        <div>
                            <div class="flex items-center gap-2 text-outline font-label-badge text-[11px] uppercase tracking-wider mb-1" id="spec-breadcrumb">
                                <span id="spec-section-label">Modul API</span> &bull; <span>SIAKAD Kedokteran Gateway</span>
                            </div>
                            <h1 class="font-headline-xl text-2xl sm:text-3xl text-on-surface font-bold tracking-tight" id="spec-title">
                                Pilih Endpoint
                            </h1>
                            <p class="mt-2 text-body-md text-on-surface-variant leading-relaxed" id="spec-desc">
                                Pilih salah satu endpoint dari daftar di kolom kiri untuk melihat rincian spesifikasi, parameter, dan skema respons.
                            </p>

                            {{-- Security Notice Callout --}}
                            <div class="mt-4 p-3.5 rounded-lg bg-surface-container-low border border-[#222d45] flex items-start gap-3">
                                <span class="material-symbols-outlined text-secondary text-[20px] mt-0.5">security</span>
                                <div class="text-body-sm text-on-surface-variant">
                                    <span class="font-semibold text-secondary">Autentikasi &amp; Proteksi Sanctum:</span> 
                                    Endpoint terlindungi memerlukan session cookie (<code>laravel_session</code>) dan token CSRF (<code>X-XSRF-TOKEN</code>). Pada pengujian via API Tester, token dan session akan otomatis disertakan.
                                </div>
                            </div>


                            {{-- Prerequisite Flow Card (Exclusive Fork-and-Join DAG with SVG Bézier Connectors) --}}
                            <div id="spec-prerequisite-card" class="mt-4 p-4 rounded-xl bg-surface-container-low border border-[#222d45] space-y-3 hidden">
                                <div class="flex items-center justify-between flex-wrap gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-secondary text-[18px]">account_tree</span>
                                        <span class="font-title-md text-[13px] font-bold text-on-surface" id="spec-prereq-title">Alur Prerequisite Endpoint</span>
                                        <span id="spec-prereq-branch-badge" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-primary-container/30 text-primary border border-primary/30">
                                            Bercabang lalu Menyatu
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[11px] text-outline font-code-sm" id="spec-prereq-subtitle">Klik node langkah mana pun untuk menguji langsung di Live Tester</span>
                                    </div>
                                </div>

                                {{-- Exclusive Branching Diagram (Bercabang lalu Menyatu dengan SVG Bézier Connectors) --}}
                                <div id="spec-prereq-branching-wrap" class="overflow-x-auto pb-2 pt-1">
                                    {{-- Injected dynamically --}}
                                </div>
                            </div>

                            {{-- Active Prerequisite Indicator Banner --}}
                            <div id="spec-prereq-active-banner" class="hidden mt-3 p-3 rounded-xl bg-primary-container/20 border border-primary/40 flex items-center justify-between gap-3 text-body-sm shadow-sm transition-all">
                                <div class="flex items-center gap-2.5 truncate min-w-0">
                                    <span class="material-symbols-outlined text-primary text-[20px] shrink-0 animate-pulse">play_circle</span>
                                    <div class="truncate text-[12px]">
                                        <span class="text-on-surface">Menguji Prasyarat:</span>
                                        <strong id="spec-prereq-active-name" class="text-primary font-code-sm ml-1">GET /api/...</strong>
                                        <span class="text-outline text-[11px] hidden md:inline ml-1.5">&bull; Request Headers, Body, Response &amp; Live Tester disesuaikan</span>
                                    </div>
                                </div>
                                <button type="button" onclick="restoreMainEndpoint()" 
                                    class="px-3 py-1 rounded-lg bg-surface-container hover:bg-surface-container-high text-secondary hover:text-on-surface text-[11px] font-semibold flex items-center gap-1 border border-[#222d45] transition-colors shrink-0 shadow-sm"
                                    title="Kembali ke spesifikasi dan tester endpoint utama">
                                    <span>Kembali ke Target</span>
                                    <span class="material-symbols-outlined text-[13px]">arrow_forward</span>
                                </button>
                            </div>
                        </div>

                        {{-- Request Headers Table --}}
                        <section class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h2 class="font-title-md text-[16px] text-on-surface font-bold flex items-center gap-2">
                                    <span class="w-1.5 h-4 rounded-full bg-primary"></span>
                                    Request Headers
                                </h2>
                                <span class="font-code-sm text-[11px] text-outline">HTTP 1.1 / HTTP 2</span>
                            </div>
                            <div class="overflow-x-auto rounded-lg border border-[#222d45] bg-surface-container-lowest">
                                <table class="w-full text-left font-body-sm text-[13px]">
                                    <thead class="bg-surface-container-low border-b border-[#222d45] text-outline font-label-badge text-[11px] uppercase">
                                        <tr>
                                            <th class="py-2.5 px-3">Header Key</th>
                                            <th class="py-2.5 px-3">Tipe</th>
                                            <th class="py-2.5 px-3">Status</th>
                                            <th class="py-2.5 px-3">Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#222d45] font-code-sm text-[12px]" id="spec-headers-tbody">
                                        {{-- Injected dynamically --}}
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        {{-- Request Body / Params Schema Table --}}
                        <section class="space-y-3" id="spec-body-section">
                            <div class="flex items-center justify-between">
                                <h2 class="font-title-md text-[16px] text-on-surface font-bold flex items-center gap-2">
                                    <span class="w-1.5 h-4 rounded-full bg-primary"></span>
                                    <span id="spec-body-heading">Request Body (JSON Schema)</span>
                                </h2>
                                <span class="font-code-sm text-[11px] text-outline" id="spec-body-type-badge">application/json</span>
                            </div>

                            {{-- Structured Schema Table (if fields detected) --}}
                            <div class="overflow-x-auto rounded-lg border border-[#222d45] bg-surface-container-lowest hidden" id="spec-body-table-wrap">
                                <table class="w-full text-left font-body-sm text-[13px]">
                                    <thead class="bg-surface-container-low border-b border-[#222d45] text-outline font-label-badge text-[11px] uppercase">
                                        <tr>
                                            <th class="py-2.5 px-3">Field</th>
                                            <th class="py-2.5 px-3">Tipe</th>
                                            <th class="py-2.5 px-3">Validasi</th>
                                            <th class="py-2.5 px-3">Contoh / Deskripsi</th>
                                            <th class="py-2.5 px-3 text-secondary font-bold">Asal Nilai (Source)</th>
                                        </tr>
                                    </thead>
                                <tbody class="divide-y divide-[#222d45] font-code-sm text-[12px]" id="spec-body-tbody">
                                    {{-- Injected dynamically --}}
                                </tbody>
                            </table>
                        </div>

                        {{-- Raw Code View --}}
                        <div class="rounded-lg bg-surface-container-lowest border border-[#222d45] overflow-hidden font-code-sm text-[12px]">
                            <div class="px-4 py-1.5 bg-surface-container-low border-b border-[#222d45] flex items-center justify-between text-outline text-[11px]">
                                <span id="spec-body-raw-label">Payload Schema</span>
                                <button onclick="copySnippetText('spec-body-raw')" class="hover:text-on-surface flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[13px]">content_copy</span> Salin
                                </button>
                            </div>
                            <pre class="p-4 text-secondary leading-5 overflow-x-auto" id="spec-body-raw"><code>-</code></pre>
                        </div>
                    </section>

                    {{-- Response Specifications --}}
                    <section class="space-y-3">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <h2 class="font-title-md text-[16px] text-on-surface font-bold flex items-center gap-2">
                                <span class="w-1.5 h-4 rounded-full bg-primary"></span>
                                Contoh Response Spesifikasi
                            </h2>
                            <div class="flex items-center gap-1 bg-surface-container-low p-1 rounded border border-[#222d45]">
                                <button class="px-2 py-0.5 rounded font-label-badge text-[11px] bg-tertiary/20 text-tertiary font-bold">200 OK</button>
                                <button class="px-2 py-0.5 rounded font-label-badge text-[11px] text-outline hover:text-on-surface">401 Unauthorized</button>
                                <button class="px-2 py-0.5 rounded font-label-badge text-[11px] text-outline hover:text-on-surface">422 Unprocessable</button>
                            </div>
                        </div>
                        <div class="rounded-lg bg-surface-container-lowest border border-[#222d45] overflow-hidden font-code-sm text-[12px]">
                            <div class="px-4 py-2 bg-surface-container-low border-b border-[#222d45] flex items-center justify-between text-outline">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-tertiary"></span>
                                    <span>Response Body (JSON)</span>
                                </div>
                                <button onclick="copySnippetText('spec-response-code')" class="hover:text-on-surface flex items-center gap-1 text-[11px]">
                                    <span class="material-symbols-outlined text-[13px]">content_copy</span> Salin Respons
                                </button>
                            </div>
                            <pre class="p-4 text-tertiary leading-5 overflow-x-auto max-h-[360px]" id="spec-response-code"><code>{
  "status": true,
  "message": "Data berhasil dimuat."
}</code></pre>
                        </div>
                    </section>

                    {{-- Code Implementation Snippets --}}
                    <section class="space-y-3 pb-8">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <h2 class="font-title-md text-[16px] text-on-surface font-bold flex items-center gap-2">
                                <span class="w-1.5 h-4 rounded-full bg-primary"></span>
                                Code Implementation Snippets
                            </h2>
                            <div class="flex items-center gap-1 bg-surface-container-low p-1 rounded border border-[#222d45]">
                                <button id="snip-tab-curl" onclick="switchSnippetLang('curl')" class="snip-tab px-2.5 py-0.5 rounded font-label-badge text-[11px] bg-primary text-on-primary font-bold">cURL</button>
                                <button id="snip-tab-js" onclick="switchSnippetLang('js')" class="snip-tab px-2.5 py-0.5 rounded font-label-badge text-[11px] text-outline hover:text-on-surface">JavaScript (Fetch)</button>
                                <button id="snip-tab-php" onclick="switchSnippetLang('php')" class="snip-tab px-2.5 py-0.5 rounded font-label-badge text-[11px] text-outline hover:text-on-surface">PHP (Laravel Http)</button>
                            </div>
                        </div>
                        <div class="rounded-lg bg-surface-container-lowest border border-[#222d45] overflow-hidden font-code-sm text-[12px] relative group">
                            <button onclick="copySnippetText('code-snippet-pre')" 
                                class="absolute top-2.5 right-2.5 px-2.5 py-1 rounded bg-surface-container hover:bg-surface-container-high text-outline hover:text-on-surface text-[11px] flex items-center gap-1 transition-colors border border-[#222d45]">
                                <span class="material-symbols-outlined text-[13px]">content_copy</span>
                                <span>Salin Snippet</span>
                            </button>
                            <pre class="p-4 text-on-surface leading-5 overflow-x-auto" id="code-snippet-pre"><code>curl ...</code></pre>
                        </div>
                    </div>{{-- Akhir #endpoint-spec-view --}}

                    {{-- ─── VIEW 2: ALUR BISNIS / WORKFLOW VIEW (F5) ─── --}}
                    <div id="workflow-spec-view" class="space-y-6 hidden">
                        <div>
                            <div class="flex items-center gap-2 text-outline font-label-badge text-[11px] uppercase tracking-wider mb-1">
                                <span id="wf-badge" class="px-2 py-0.5 rounded-full bg-secondary/10 text-secondary font-bold">Alur Bisnis</span>
                                <span>&bull;</span>
                                <span>SIAKAD Kedokteran Gateway</span>
                            </div>
                            <h1 class="font-headline-xl text-2xl sm:text-3xl text-on-surface font-bold tracking-tight flex items-center gap-2.5 mt-2" id="wf-title">
                                <span class="material-symbols-outlined text-primary text-[30px]" id="wf-icon">alt_route</span>
                                <span id="wf-title-text">Nama Alur Bisnis</span>
                            </h1>
                            <p class="mt-2 text-body-md text-on-surface-variant leading-relaxed" id="wf-summary">
                                Deskripsi alur bisnis.
                            </p>

                            {{-- Roles Callout --}}
                            <div class="mt-4 p-3.5 rounded-lg bg-surface-container-low border border-[#222d45] flex items-center gap-3 text-body-sm">
                                <span class="material-symbols-outlined text-secondary text-[20px] shrink-0">groups</span>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-semibold text-on-surface">Role yang Terlibat:</span>
                                    <div class="flex items-center gap-1.5 flex-wrap" id="wf-roles-list">
                                        {{-- Injected dynamically --}}
                                    </div>
                                </div>
                            </div>

                            {{-- Dedicated Assessment Banner inside Workflow View (active when penilaian-bertingkat is selected) --}}
                            <div id="wf-assessment-banner" class="hidden mt-4 p-4 rounded-xl bg-gradient-to-r from-primary-container/20 via-surface-container-low to-secondary-container/10 border border-primary/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-lg">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-primary-container/40 border border-primary/50 flex items-center justify-center text-primary shrink-0 mt-0.5 sm:mt-0">
                                        <span class="material-symbols-outlined text-[22px]">schema</span>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="font-title-md text-[14px] font-bold text-white">Visual Peta Alur &amp; Spesifikasi Lengkap Penilaian Blok</h3>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold bg-tertiary/20 text-tertiary border border-tertiary/30">DEDICATED PAGE</span>
                                        </div>
                                        <p class="text-xs text-on-surface-variant mt-1 leading-relaxed">
                                            Tersedia diagram sequence interaktif (Mermaid), pohon hierarki komponen 100%, matrix wewenang Staff vs Dosen, dan panduan validasi bulk sekelas.
                                        </p>
                                    </div>
                                </div>
                                <a href="{{ url('/admin/docs/penilaian') }}" class="px-4 py-2 rounded-lg bg-primary hover:bg-primary/90 text-on-primary text-xs font-bold flex items-center gap-1.5 shrink-0 shadow transition-all hover:scale-[1.02]">
                                    <span>Buka Halaman Spesifik</span>
                                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                                </a>
                            </div>
                        </div>

                        {{-- Business Rules Section --}}
                        <section class="space-y-3">
                            <h2 class="font-title-md text-[16px] text-on-surface font-bold flex items-center gap-2">
                                <span class="w-1.5 h-4 rounded-full bg-secondary"></span>
                                Aturan Bisnis &amp; Validasi
                            </h2>
                            <div class="space-y-2 font-body-sm" id="wf-rules-list">
                                {{-- Injected dynamically --}}
                            </div>
                        </section>

                        {{-- Phases & Stepper Section --}}
                        <section class="space-y-4 pb-8">
                            <h2 class="font-title-md text-[16px] text-on-surface font-bold flex items-center gap-2">
                                <span class="w-1.5 h-4 rounded-full bg-primary"></span>
                                Tahapan Proses &amp; Endpoint Terkait
                            </h2>
                            <div class="space-y-3" id="wf-phases-list">
                                {{-- Injected dynamically --}}
                            </div>
                        </section>
                    </div>

                </div>
            </main>

            {{-- ─── Gutter 2: Pemisah Kolom Tengah dan Kanan (PRD F-01) ─── --}}
            <div id="gutter-right"
                role="separator"
                tabindex="0"
                aria-orientation="vertical"
                aria-label="Pemisah panel live API tester"
                aria-valuenow="485"
                aria-valuemin="320"
                aria-valuemax="900"
                class="panel-gutter hidden lg:flex items-center justify-center w-1.5 shrink-0 bg-surface-container-low hover:bg-primary-container active:bg-primary cursor-col-resize z-20 transition-colors border-x border-[#222d45] focus:outline-none focus:ring-1 focus:ring-primary group"
                title="Tarik untuk mengubah lebar panel live tester (Klik 2x untuk reset)">
                <div class="gutter-handle w-0.5 h-8 rounded-full bg-outline/40 transition-colors"></div>
            </div>

            {{-- ==================== KOLOM 3: LIVE API TESTER & CONSOLE (485px default) ==================== --}}
            <aside id="col-tester" style="width: 485px;" class="shrink-0 flex flex-col bg-surface-container-lowest overflow-hidden select-text border-l border-[#222d45] hidden lg:flex">
                
                {{-- Console Ribbon Header --}}
                <div class="px-4 py-3 border-b border-[#222d45] bg-surface-container-low/70 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-tertiary animate-pulse"></span>
                        <span class="font-label-badge text-[11px] text-on-surface uppercase tracking-wider font-bold">Live API Tester</span>
                    </div>

                    <span class="px-2 py-0.5 rounded bg-surface-container font-code-sm text-[10px] text-tertiary border border-[#222d45]">HTTP Client</span>
                </div>

                {{-- URL Input Strip --}}
                <div class="p-3 border-b border-[#222d45] bg-surface-container-low/30 space-y-2">
                    <div class="flex items-center rounded-lg border border-[#222d45] bg-surface-container-lowest overflow-hidden shadow-sm">
                        <select id="tester-method" onchange="onTesterMethodChange()" 
                            class="px-2.5 py-1.5 bg-surface-container font-code-sm font-bold text-[12px] border-r border-[#222d45] text-tertiary focus:outline-none cursor-pointer">
                            <option value="GET">GET</option>
                            <option value="POST">POST</option>
                            <option value="PUT">PUT</option>
                            <option value="PATCH">PATCH</option>
                            <option value="DELETE">DELETE</option>
                        </select>
                        <input id="tester-url" 
                            class="flex-1 px-3 py-1.5 bg-transparent font-code-sm text-[12px] text-on-surface focus:outline-none font-medium" 
                            type="text" value="{{ url('/api') }}">
                    </div>

                    {{-- Request Configuration Tabs --}}
                    <div class="flex items-center justify-between pt-1">
                        <div class="flex items-center gap-1 font-body-sm">
                            <button id="req-tab-params" onclick="switchReqTab('params')" class="px-2.5 py-1 rounded bg-surface-container-high text-on-surface font-semibold text-[11px] border-b-2 border-primary">Params</button>
                            <button id="req-tab-body" onclick="switchReqTab('body')" class="px-2.5 py-1 rounded text-outline hover:text-on-surface text-[11px]">Body</button>
                            <button id="req-tab-headers" onclick="switchReqTab('headers')" class="px-2.5 py-1 rounded text-outline hover:text-on-surface text-[11px]">Headers</button>
                            <button id="req-tab-cookies" onclick="switchReqTab('cookies')" class="px-2.5 py-1 rounded text-outline hover:text-on-surface text-[11px]">Cookies Debug</button>
                        </div>
                        <span class="font-code-sm text-[10px] text-outline" id="req-type-indicator">URL • Query & Path Params</span>
                    </div>
                    {{-- Active Auto-Chained Parameters Indicator Bar --}}
                    <div id="tester-prereq-chain-bar" class="hidden mt-2 p-2 rounded-lg bg-secondary/10 border border-secondary/20 text-[11px] text-secondary flex items-center justify-between shadow-sm">
                        {{-- Injected dynamically --}}
                    </div>
                </div>

                {{-- Interactive Editable Params, Body & Headers Pane --}}
                <div class="h-44 border-b border-[#222d45] bg-[#070e1e] flex flex-col relative font-code-sm text-[12px]">
                    {{-- Params Editor Pane (Untuk GET: Path & Query params) --}}
                    <div id="pane-req-params" class="flex flex-1 overflow-hidden p-2">
                        <textarea id="tester-params-input" 
                            class="w-full h-full bg-transparent resize-none font-code-sm text-[12px] text-on-surface leading-5 focus:outline-none placeholder:text-outline-variant" 
                            placeholder='{"nim": "25080110006"}'></textarea>
                    </div>

                    {{-- Body Editor Pane (Untuk selain GET: JSON Payload) --}}
                    <div id="pane-req-body" class="flex flex-1 overflow-hidden p-2 hidden">
                        <textarea id="tester-body-input" 
                            class="w-full h-full bg-transparent resize-none font-code-sm text-[12px] text-on-surface leading-5 focus:outline-none placeholder:text-outline-variant" 
                            placeholder='{"key": "value"}'></textarea>
                    </div>

                    {{-- Headers Editor Pane --}}
                    <div id="pane-req-headers" class="flex flex-1 overflow-hidden p-2 hidden">
                        <textarea id="tester-headers-input" 
                            class="w-full h-full bg-transparent resize-none font-code-sm text-[12px] text-on-surface leading-5 focus:outline-none placeholder:text-outline-variant">{
  "Accept": "application/json"
}</textarea>
                    </div>

                    {{-- Cookies Debug Pane --}}
                    <div id="pane-req-cookies" class="flex-1 overflow-y-auto p-2.5 space-y-1.5 hidden text-[11px]">
                        <div class="flex items-center justify-between text-outline">
                            <span>Status XSRF Token:</span>
                            <span id="dbg-xsrf-badge" class="px-1.5 py-0.5 rounded bg-surface-container text-tertiary">Checking...</span>
                        </div>
                        <div class="flex items-center justify-between text-outline">
                            <span>Session Cookie:</span>
                            <span id="dbg-session-badge" class="px-1.5 py-0.5 rounded bg-surface-container text-secondary">laravel_session</span>
                        </div>
                        <div class="pt-1">
                            <span class="text-outline block mb-1">Active Cookies:</span>
                            <div id="dbg-cookie-chips" class="flex flex-wrap gap-1"></div>
                        </div>
                    </div>

                    {{-- Editor Status Footer --}}
                    <div class="px-3 py-1 bg-surface-container-lowest/90 border-t border-[#1a233a] flex items-center justify-between text-[11px] text-outline">
                        <span id="tester-editor-hint">Params URL / Query String</span>
                        <span id="tester-char-count">Chars: 0</span>
                    </div>
                </div>

                {{-- Execute Action Bar --}}
                <div class="p-3 border-b border-[#222d45] bg-surface-container-low/50 flex items-center justify-between gap-2">
                    <button id="btn-run-request" onclick="sendTesterRequest()" 
                        class="flex-1 flex items-center justify-center gap-2 py-2 px-4 rounded-lg bg-primary-container hover:bg-primary-container/90 text-on-primary-container font-body-sm font-semibold transition-all shadow-md active:scale-[0.99]">
                        <span class="material-symbols-outlined text-[18px]" id="run-icon">play_arrow</span>
                        <span id="run-label">Kirim Request (Ctrl+Enter)</span>
                    </button>
                    <button onclick="resetTesterBody()" class="p-2 rounded-lg bg-surface-container hover:bg-surface-container-high text-outline hover:text-on-surface transition-colors" title="Reset Body">
                        <span class="material-symbols-outlined text-[18px]">restart_alt</span>
                    </button>
                </div>

                {{-- Live Response Inspector --}}
                <div class="flex-1 flex flex-col min-h-0 bg-surface-container-lowest">
                    {{-- Metabar Status --}}
                    <div class="px-4 py-2 border-b border-[#222d45] bg-surface-container-low flex items-center justify-between text-body-sm">
                        <div class="flex items-center gap-2">
                            <span id="res-status-badge" class="px-2 py-0.5 rounded font-label-badge text-[11px] font-bold bg-surface-container text-outline">
                                Siap Kirim
                            </span>
                            <span id="res-time-badge" class="font-code-sm text-[11px] text-on-surface-variant flex items-center gap-1 hidden">
                                <span class="material-symbols-outlined text-[13px] text-tertiary">timer</span> <span id="res-time-val">0 ms</span>
                            </span>
                            <span id="res-size-badge" class="font-code-sm text-[11px] text-on-surface-variant hidden" id="res-size-val"></span>
                        </div>
                        <button onclick="copyResponseText()" id="btn-copy-res" class="hover:text-on-surface text-outline flex items-center gap-1 text-[11px] hidden">
                            <span class="material-symbols-outlined text-[13px]">content_copy</span> Copy
                        </button>
                    </div>

                    {{-- Response Tabs --}}
                    <div class="px-4 pt-1.5 border-b border-[#222d45] flex items-center gap-3 font-body-sm text-[12px]">
                        <button id="res-tab-body" onclick="switchResTab('body')" class="pb-1 text-on-surface font-semibold border-b-2 border-primary">Body (JSON)</button>
                        <button id="res-tab-headers" onclick="switchResTab('headers')" class="pb-1 text-outline hover:text-on-surface">Headers</button>
                    </div>

                    {{-- Response Code Content --}}
                    <div class="flex-1 p-3 overflow-y-auto font-code-sm text-[11px] leading-5 text-on-surface bg-[#080f21]">
                        <pre id="res-body-code" class="text-tertiary"><code>Klik "Kirim Request" untuk mengeksekusi endpoint ini.</code></pre>
                        <pre id="res-headers-code" class="text-outline hidden"><code></code></pre>
                    </div>

                    {{-- Token callout --}}
                    <div class="p-2 px-3 bg-surface-container-low border-t border-[#222d45] flex items-center gap-2">
                        <span class="material-symbols-outlined text-tertiary text-[15px]">check_circle</span>
                        <span class="font-body-sm text-[11px] text-on-surface-variant truncate">
                            Credentials &amp; Session otomatis disertakan dalam pengujian API.
                        </span>
                    </div>
                </div>

            </aside>

        </div>
    </main>

    {{-- ─── CLIENT DATA & ENGINE SCRIPT ─── --}}
    <script>
        // Global Datasets injected from Laravel
        const SECTIONS_DATA = @json($sections);
        const WORKFLOWS_DATA = @json($workflows ?? []);
        const BASE_API_URL = '{{ url("/api") }}';
        const APP_ROOT_URL = '{{ url("/") }}';
        
        // Lookup Map for fast O(1) endpoint access
        const ENDPOINTS_MAP = {};
        let ALL_ENDPOINTS_LIST = [];
        let ACTIVE_ENDPOINT_ID = null;
        let CURRENT_MAIN_ENDPOINT = null;
        let ACTIVE_PREREQ_STEP_DATA = null;
        let ACTIVE_WORKFLOW_ID = null;
        let CURRENT_SNIPPET_LANG = 'curl';
        let CURRENT_METHOD_FILTER = 'ALL';

        // Initialize Data Mapping
        SECTIONS_DATA.forEach(section => {
            if (section.endpoints) {
                section.endpoints.forEach(ep => {
                    ep.section_title = section.title;
                    ENDPOINTS_MAP[ep.id] = ep;
                    ALL_ENDPOINTS_LIST.push(ep);
                });
            }
        });

        // ─── SELECT WORKFLOW ACTION (F3 & F5) ───
        function selectWorkflow(id) {
            const wf = (WORKFLOWS_DATA || []).find(w => w.id === id);
            if (!wf) return;
            ACTIVE_WORKFLOW_ID = id;
            ACTIVE_PREREQ_STEP_DATA = null;
            document.getElementById('spec-prereq-active-banner')?.classList.add('hidden');

            // Switch view
            document.getElementById('endpoint-spec-view')?.classList.add('hidden');
            document.getElementById('workflow-spec-view')?.classList.remove('hidden');

            // Update sidebar active states
            document.querySelectorAll('.ep-nav-item').forEach(el => {
                el.classList.remove('bg-surface-container-high', 'text-on-surface', 'border-l-2', 'border-tertiary');
                el.querySelector('.active-indicator')?.classList.add('hidden');
            });
            document.querySelectorAll('.wf-nav-item').forEach(el => {
                el.classList.remove('bg-surface-container-high', 'text-on-surface', 'border-l-2', 'border-primary');
            });
            document.getElementById(`nav-wf-${id}`)?.classList.add('bg-surface-container-high', 'text-on-surface', 'border-l-2', 'border-primary');

            // Populate Workflow Details
            document.getElementById('wf-badge').textContent = wf.badge;
            document.getElementById('wf-title-text').textContent = wf.title;
            document.getElementById('wf-icon').textContent = wf.icon;
            document.getElementById('wf-summary').textContent = wf.summary;

            // Toggle Dedicated Assessment Banner
            const wfAssessBanner = document.getElementById('wf-assessment-banner');
            if (wfAssessBanner) {
                if (id === 'penilaian-bertingkat') {
                    wfAssessBanner.classList.remove('hidden');
                } else {
                    wfAssessBanner.classList.add('hidden');
                }
            }

            // Roles
            const rolesContainer = document.getElementById('wf-roles-list');
            rolesContainer.innerHTML = (wf.roles || []).map(r => `
                <span class="px-2 py-0.5 rounded-full bg-secondary/10 text-secondary font-label-badge text-[11px] font-semibold border border-secondary/30">
                    ${escapeHtml(r)}
                </span>
            `).join('');

            // Rules
            const rulesContainer = document.getElementById('wf-rules-list');
            rulesContainer.innerHTML = Object.entries(wf.rules || {}).map(([rKey, rDesc]) => `
                <div class="p-3 rounded-lg bg-surface-container-low border border-[#222d45] flex items-start gap-2.5">
                    <span class="material-symbols-outlined text-amber-400 text-[18px] mt-0.5 shrink-0">verified</span>
                    <div>
                        <div class="font-semibold text-amber-300 font-label-badge text-[12px]">${escapeHtml(rKey)}</div>
                        <div class="text-on-surface-variant text-[12px] mt-0.5 leading-relaxed font-body-sm">${escapeHtml(rDesc)}</div>
                    </div>
                </div>
            `).join('');

            // Phases
            const phasesContainer = document.getElementById('wf-phases-list');
            phasesContainer.innerHTML = (wf.phases || []).map((ph, idx) => {
                const endpointsHtml = (ph.endpoints || []).map(epItem => {
                    const matchedEp = ALL_ENDPOINTS_LIST.find(e => e.method === epItem.method && e.url === epItem.url);
                    const actionBtn = matchedEp 
                        ? `<button onclick="selectEndpoint(${matchedEp.id})" class="px-2 py-1 rounded bg-primary-container/20 hover:bg-primary-container/40 text-primary text-[11px] font-semibold border border-primary/30 transition-colors flex items-center gap-1 shrink-0">
                               <span>Lihat Spec</span>
                               <span class="material-symbols-outlined text-[13px]">arrow_forward</span>
                           </button>`
                        : `<span class="text-[10px] text-outline font-code-sm shrink-0">Spec Ready</span>`;

                    return `
                        <div class="flex items-center justify-between p-2.5 rounded-lg bg-surface-container-lowest border border-[#222d45] gap-2">
                            <div class="flex items-center gap-2 truncate min-w-0">
                                <span class="px-1.5 py-0.5 rounded font-label-badge text-[10px] font-bold method-badge-${epItem.method}">${epItem.method}</span>
                                <span class="font-code-sm text-[12px] text-on-surface font-semibold truncate">${escapeHtml(epItem.url)}</span>
                                <span class="text-outline text-[11px] hidden sm:inline truncate">&bull; ${escapeHtml(epItem.desc)}</span>
                            </div>
                            ${actionBtn}
                        </div>
                    `;
                }).join('');

                return `
                    <div class="p-4 rounded-xl bg-surface-container-low border border-[#222d45] space-y-3">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-primary/20 text-primary flex items-center justify-center font-bold text-[12px]">${idx + 1}</span>
                                <h3 class="font-title-md text-[14px] font-bold text-on-surface">${escapeHtml(ph.phase)}</h3>
                            </div>
                            <span class="px-2 py-0.5 rounded bg-surface-container text-secondary text-[11px] font-semibold font-label-badge">
                                Aktor: ${escapeHtml(ph.actor)}
                            </span>
                        </div>
                        <p class="text-body-sm text-on-surface-variant">${escapeHtml(ph.description)}</p>
                        <div class="space-y-1.5 pt-1">
                            ${endpointsHtml}
                        </div>
                    </div>
                `;
            }).join('');

            // Sync Ribbon Title
            document.getElementById('spec-ribbon-method').textContent = 'FLOW';
            document.getElementById('spec-ribbon-method').className = 'px-2.5 py-1 rounded font-code-md text-[12px] font-bold bg-secondary/15 text-secondary border border-secondary/30';
            document.getElementById('spec-ribbon-url').textContent = `/workflow/${wf.id}`;

            if (window.innerWidth < 1024) {
                toggleMobileSidebar(false);
            }
        }

        // ─── PREREQUISITE TREE RESOLUTION (EXCLUSIVE FORK & JOIN DAG ENGINE) ───
        let CURRENT_PREREQ_VIEW_MODE = 'branching';

        function togglePrereqViewMode(mode) {
            // Branching DAG is now the exclusive prerequisite view
            requestAnimationFrame(() => renderDagConnectors());
        }

        function getPrerequisiteTree(ep) {
            const authRole = ep.auth_role || (
                ep.url.startsWith('/api/mhs') ? 'Mahasiswa' :
                ep.url.startsWith('/api/dosen') ? 'Dosen' : 'Staff'
            );

            let loginEp = null;
            if (authRole === 'Mahasiswa') {
                loginEp = ALL_ENDPOINTS_LIST.find(e => e.url === '/api/auth/mhs/login');
            } else if (authRole === 'Dosen') {
                loginEp = ALL_ENDPOINTS_LIST.find(e => e.url === '/api/auth/dosen/login');
            } else {
                loginEp = ALL_ENDPOINTS_LIST.find(e => e.url === '/api/auth/staff/login');
            }

            // 1. Kumpulkan semua dependensi langsung dari parameter endpoint
            const directMap = new Map();
            if (ep.parameters && ep.parameters.length > 0) {
                ep.parameters.forEach(p => {
                    if (p.source_type === 'endpoint' && p.source_endpoint) {
                        const srcId = p.source_endpoint.id;
                        const fieldName = p.source_field || p.name;
                        if (directMap.has(srcId)) {
                            const b = directMap.get(srcId);
                            if (!b.fields.includes(fieldName)) b.fields.push(fieldName);
                        } else {
                            const fullSrcEp = ENDPOINTS_MAP[srcId] || p.source_endpoint;
                            directMap.set(srcId, {
                                id: srcId,
                                endpoint: fullSrcEp,
                                fields: [fieldName],
                                note: p.source_note
                            });
                        }
                    }
                });
            }

            if (directMap.size === 0) {
                return {
                    authRole,
                    loginEp,
                    branches: [],
                    isBranching: false,
                    linearList: [],
                    targetStepNumber: 1,
                    targetEp: ep
                };
            }

            // 2. Helper untuk mendapatkan semua upstream IDs dari sebuah node secara rekursif
            function getUpstreamIds(nodeId, visited = new Set()) {
                const node = ENDPOINTS_MAP[nodeId];
                if (!node || !node.parameters) return visited;
                node.parameters.forEach(p => {
                    if (p.source_type === 'endpoint' && p.source_endpoint) {
                        const upId = p.source_endpoint.id;
                        if (!visited.has(upId)) {
                            visited.add(upId);
                            getUpstreamIds(upId, visited);
                        }
                    }
                });
                return visited;
            }

            // 3. Transitive Reduction:
            // Jika sebuah dependensi A ada di directMap, TETAPI A juga merupakan upstream dari B yang ada di directMap,
            // maka A BUKAN cabang independen yang setara, melainkan A adalah hulu dari B!
            const directBranchIds = [];
            directMap.forEach((dataA, idA) => {
                let isSubsumed = false;
                directMap.forEach((dataB, idB) => {
                    if (idA !== idB) {
                        const upstreamsB = getUpstreamIds(idB);
                        if (upstreamsB.has(idA)) {
                            isSubsumed = true;
                        }
                    }
                });
                if (!isSubsumed) {
                    directBranchIds.push(idA);
                }
            });

            // 4. Susun cabang dan alirkan upstream secara terurut (tanpa duplikasi node)
            const branches = [];
            const visitedGlobal = new Set([ep.id]);

            directBranchIds.forEach(bId => {
                const branchDirect = directMap.get(bId);
                const laneNodes = [];

                function collectLane(curId) {
                    const curNode = ENDPOINTS_MAP[curId];
                    if (!curNode || !curNode.parameters) return;
                    curNode.parameters.forEach(p => {
                        if (p.source_type === 'endpoint' && p.source_endpoint) {
                            const uId = p.source_endpoint.id;
                            if (!visitedGlobal.has(uId)) {
                                visitedGlobal.add(uId);
                                collectLane(uId);
                                laneNodes.push({
                                    id: uId,
                                    endpoint: ENDPOINTS_MAP[uId] || p.source_endpoint,
                                    fields: [p.source_field || p.name],
                                    note: p.source_note
                                });
                            }
                        }
                    });
                }

                collectLane(bId);
                visitedGlobal.add(bId);

                branches.push({
                    id: bId,
                    endpoint: branchDirect.endpoint,
                    fields: branchDirect.fields,
                    note: branchDirect.note,
                    upstream: laneNodes
                });
            });

            // 5. Penomoran Langkah Sekuensial (Step 1 Login, Step 2..N Prereq, Step Target)
            let stepCounter = 2;
            const linearList = [];

            branches.forEach(b => {
                b.upstream.forEach(up => {
                    up.stepNumber = stepCounter++;
                    linearList.push(up);
                });
                b.stepNumber = stepCounter++;
                linearList.push(b);
            });

            const targetStepNumber = stepCounter;

            return {
                authRole,
                loginEp,
                branches,
                isBranching: branches.length > 1,
                linearList,
                targetStepNumber,
                targetEp: ep
            };
        }

        // Backward compatibility helper
        function getPrerequisiteChain(ep) {
            const tree = getPrerequisiteTree(ep);
            return {
                authRole: tree.authRole,
                loginEp: tree.loginEp,
                prerequisites: tree.linearList,
                targetEp: tree.targetEp
            };
        }

        // ─── RENDER SVG BÉZIER CONNECTORS (SMOOTH FORK & JOIN CURVES - ANTI-PUTUS) ───
        function renderDagConnectors() {
            const container = document.getElementById('spec-prereq-dag-container');
            const svgCanvas = document.getElementById('dag-svg-canvas');
            const rootNode = document.getElementById('dag-node-root');
            const targetNode = document.getElementById('dag-node-target');
            const badgeFork = document.getElementById('dag-badge-fork');
            const badgeJoin = document.getElementById('dag-badge-join');

            if (!container || !svgCanvas || !rootNode || !targetNode) return;

            const containerRect = container.getBoundingClientRect();
            if (containerRect.width === 0 && containerRect.height === 0) return;

            const rootRect = rootNode.getBoundingClientRect();
            const targetRect = targetNode.getBoundingClientRect();

            // Titik keluar sisi kanan Root (Login)
            const xRoot = rootRect.right - containerRect.left;
            const yRoot = rootRect.top + rootRect.height / 2 - containerRect.top;

            // Titik masuk sisi kiri Target
            const xTarget = targetRect.left - containerRect.left;
            const yTarget = targetRect.top + targetRect.height / 2 - containerRect.top;
            const xTargetPoint = xTarget - 7; // Ujung panah marker berlabuh 7px sebelum border kartu Target

            const branchRows = container.querySelectorAll('.dag-branch-row');
            let forkPaths = '';
            let joinPaths = '';
            let minInX = 999999;
            let maxOutX = 0;

            branchRows.forEach(row => {
                const inCard = row.querySelector('.dag-branch-in-card') || row;
                const outCard = row.querySelector('.dag-branch-out-card') || row;

                const inRect = inCard.getBoundingClientRect();
                const outRect = outCard.getBoundingClientRect();

                // Titik masuk kartu cabang (InCard)
                const xIn = inRect.left - containerRect.left;
                const yIn = inRect.top + inRect.height / 2 - containerRect.top;
                const xInPoint = xIn - 7; // Ujung panah marker berlabuh 7px sebelum border kartu cabang

                if (xIn < minInX) minInX = xIn;

                // --- 1. KURVA FORK: Root -> InCard ---
                const dxFork = Math.max(xInPoint - xRoot, 20);
                const cpFork1 = xRoot + dxFork * 0.52;
                const cpFork2 = xRoot + dxFork * 0.48;
                const dFork = `M ${xRoot},${yRoot} C ${cpFork1},${yRoot} ${cpFork2},${yIn} ${xInPoint},${yIn}`;

                forkPaths += `
                    <path d="${dFork}" 
                        fill="none" 
                        stroke="#38bdf8" 
                        stroke-width="2.2" 
                        stroke-linecap="round"
                        filter="url(#glow-fork)"
                        marker-end="url(#arrow-fork)"
                        class="transition-all duration-300 opacity-90 hover:opacity-100" />
                `;

                // Titik keluar kartu cabang (OutCard)
                const xOut = outRect.right - containerRect.left;
                const yOut = outRect.top + outRect.height / 2 - containerRect.top;

                if (xOut > maxOutX) maxOutX = xOut;

                // --- 2. KURVA JOIN: OutCard -> Target (ANTI-PUTUS) ---
                const dxJoin = xTargetPoint - xOut;

                let dJoin = '';
                if (dxJoin > 85) {
                    // Jika outCard masih jauh dari Target (seperti baris yang lebih pendek dari baris lainnya):
                    // Tarik garis horizontal lurus dari tepi kanan kartu hingga ke zona belokan,
                    // lalu belokkan kurva Bézier yang simetris dan seimbang ke kartu Target!
                    const xBridgeEnd = xTargetPoint - 55;
                    const cpJoin1 = xBridgeEnd + 55 * 0.52;
                    const cpJoin2 = xBridgeEnd + 55 * 0.48;
                    dJoin = `M ${xOut},${yOut} L ${xBridgeEnd},${yOut} C ${cpJoin1},${yOut} ${cpJoin2},${yTarget} ${xTargetPoint},${yTarget}`;
                } else {
                    // Jika outCard sudah dekat dengan Target (jarak normal ~50-80px):
                    const cpJoin1 = xOut + dxJoin * 0.52;
                    const cpJoin2 = xOut + dxJoin * 0.48;
                    dJoin = `M ${xOut},${yOut} C ${cpJoin1},${yOut} ${cpJoin2},${yTarget} ${xTargetPoint},${yTarget}`;
                }

                joinPaths += `
                    <path d="${dJoin}" 
                        fill="none" 
                        stroke="#34d399" 
                        stroke-width="2.2" 
                        stroke-linecap="round"
                        filter="url(#glow-join)"
                        marker-end="url(#arrow-join)"
                        class="transition-all duration-300 opacity-90 hover:opacity-100" />
                `;
            });

            // Posisikan badge FORK dan JOIN dengan presisi
            if (badgeFork && minInX < 999999) {
                const forkCenterX = (xRoot + minInX) / 2;
                badgeFork.style.left = `${forkCenterX}px`;
                badgeFork.style.top = `10px`;
                badgeFork.classList.remove('hidden');
            }

            if (badgeJoin && maxOutX > 0) {
                const joinCenterX = (maxOutX + xTarget) / 2;
                badgeJoin.style.left = `${joinCenterX}px`;
                badgeJoin.style.top = `10px`;
                badgeJoin.classList.remove('hidden');
            }

            const defs = `
                <defs>
                    <marker id="arrow-fork" viewBox="0 0 10 10" refX="7" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                        <path d="M 0 1.5 L 8 5 L 0 8.5 z" fill="#38bdf8" />
                    </marker>
                    <marker id="arrow-join" viewBox="0 0 10 10" refX="7" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                        <path d="M 0 1.5 L 8 5 L 0 8.5 z" fill="#34d399" />
                    </marker>
                    <filter id="glow-fork" x="-20%" y="-20%" width="140%" height="140%">
                        <feDropShadow dx="0" dy="0" stdDeviation="1.2" flood-color="#38bdf8" flood-opacity="0.45"/>
                    </filter>
                    <filter id="glow-join" x="-20%" y="-20%" width="140%" height="140%">
                        <feDropShadow dx="0" dy="0" stdDeviation="1.2" flood-color="#34d399" flood-opacity="0.45"/>
                    </filter>
                </defs>
            `;

            svgCanvas.innerHTML = defs + forkPaths + joinPaths;
        }

        // ─── BUILD FORK & JOIN BRANCHING DIAGRAM HTML ───
        function buildBranchingHtml(tree) {
            const loginRole = tree.authRole || 'Staff';
            const loginEp = tree.loginEp;
            const targetEp = tree.targetEp;

            // 1. Pangkal: Root Node (Auth Login)
            const rootHtml = loginEp ? `
                <button type="button" id="prereq-branch-btn-1" data-step="1" data-endpoint-id="${loginEp.id}"
                    onclick="selectPrerequisiteStep(${loginEp.id}, 1, false, 'Login ${loginRole}')"
                    class="prereq-step-btn flex flex-col justify-center p-3 rounded-xl bg-surface-container hover:bg-surface-container-high border border-[#222d45] hover:border-primary shrink-0 transition-all text-left group cursor-pointer shadow-md w-[170px]"
                    title="Klik untuk menguji login ${loginRole} di Live Tester">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="w-5 h-5 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center font-bold text-[10px] shrink-0">1</span>
                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-primary/20 text-primary uppercase font-label-badge border border-primary/30">AUTH</span>
                    </div>
                    <div class="font-bold text-on-surface text-[12px] truncate group-hover:text-primary transition-colors">Login ${loginRole}</div>
                    <div class="text-outline text-[10px] mt-0.5 truncate flex items-center justify-between">
                        <span>Sanctum &amp; CSRF</span>
                        <span class="material-symbols-outlined text-[13px] opacity-0 group-hover:opacity-100 text-primary transition-opacity">play_circle</span>
                    </div>
                </button>
            ` : `
                <div class="flex flex-col justify-center p-3 rounded-xl bg-surface-container border border-[#222d45] shrink-0 text-left w-[170px]">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="w-5 h-5 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center font-bold text-[10px] shrink-0">1</span>
                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-primary/20 text-primary uppercase font-label-badge">AUTH</span>
                    </div>
                    <div class="font-bold text-on-surface text-[12px] truncate">Login ${loginRole}</div>
                    <div class="text-outline text-[10px] mt-0.5 truncate">Sanctum &amp; CSRF</div>
                </div>
            `;

            // 2. Cabang-Cabang Paralel (Middle Rows)
            const branchesRowsHtml = tree.branches.map((b, bIdx) => {
                const fieldsStr = b.fields.join(', ');
                
                let laneItemsHtml = '';
                if (b.upstream && b.upstream.length > 0) {
                    b.upstream.forEach((up, upIdx) => {
                        const upFields = up.fields.join(', ');
                        const isFirst = (upIdx === 0);
                        laneItemsHtml += `
                            <button type="button" id="prereq-branch-btn-${up.stepNumber}" data-step="${up.stepNumber}" data-endpoint-id="${up.endpoint.id}"
                                onclick="selectPrerequisiteStep(${up.endpoint.id}, ${up.stepNumber}, false, 'Hasilkan ${escapeHtml(upFields)}')"
                                class="prereq-step-btn ${isFirst ? 'dag-branch-in-card' : ''} flex items-center gap-2 p-2.5 rounded-xl bg-surface-container hover:bg-surface-container-high border border-[#222d45] hover:border-secondary shrink-0 transition-all text-left group cursor-pointer w-[215px] shadow-sm">
                                <span class="w-5 h-5 rounded-full bg-secondary/20 text-secondary flex items-center justify-center font-bold text-[10px] shrink-0">${up.stepNumber}</span>
                                <div class="text-[11px] min-w-0 flex-1 truncate">
                                    <div class="font-semibold text-on-surface flex items-center gap-1">
                                        <span class="px-1.5 py-0.2 rounded font-label-badge text-[9px] method-badge-${up.endpoint.method} font-bold">${up.endpoint.method}</span>
                                        <span class="truncate font-code-sm group-hover:text-secondary transition-colors">${escapeHtml(up.endpoint.url)}</span>
                                    </div>
                                    <div class="text-tertiary text-[10px] mt-0.5 truncate">Hasilkan <code>${escapeHtml(upFields)}</code></div>
                                </div>
                            </button>
                            <!-- In-lane connector arrow -->
                            <div class="flex items-center px-2 shrink-0">
                                <svg class="w-5 h-3 text-outline/70" viewBox="0 0 20 12" fill="none">
                                    <path d="M 1 6 L 15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M 11 2 L 16 6 L 11 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                        `;
                    });
                }

                const isLastInCard = (!b.upstream || b.upstream.length === 0);
                laneItemsHtml += `
                    <button type="button" id="prereq-branch-btn-${b.stepNumber}" data-step="${b.stepNumber}" data-endpoint-id="${b.endpoint.id}"
                        onclick="selectPrerequisiteStep(${b.endpoint.id}, ${b.stepNumber}, false, 'Hasilkan ${escapeHtml(fieldsStr)}')"
                        class="prereq-step-btn ${isLastInCard ? 'dag-branch-in-card' : ''} dag-branch-out-card flex items-center gap-2.5 p-2.5 rounded-xl bg-surface-container hover:bg-surface-container-high border border-[#222d45] hover:border-secondary shrink-0 transition-all text-left group cursor-pointer w-[240px] shadow-sm">
                        <span class="w-5 h-5 rounded-full bg-secondary/20 text-secondary flex items-center justify-center font-bold text-[10px] shrink-0">${b.stepNumber}</span>
                        <div class="text-[11px] min-w-0 flex-1 truncate">
                            <div class="font-semibold text-on-surface flex items-center justify-between gap-1">
                                <div class="flex items-center gap-1 min-w-0 truncate">
                                    <span class="px-1.5 py-0.2 rounded font-label-badge text-[9px] method-badge-${b.endpoint.method} font-bold">${b.endpoint.method}</span>
                                    <span class="truncate font-code-sm group-hover:text-secondary transition-colors">${escapeHtml(b.endpoint.url)}</span>
                                </div>
                                <span class="material-symbols-outlined text-[14px] text-outline group-hover:text-secondary transition-colors shrink-0">play_circle</span>
                            </div>
                            <div class="text-tertiary text-[10px] mt-0.5 truncate">Hasilkan <code class="bg-tertiary/10 px-1 py-0.2 rounded">${escapeHtml(fieldsStr)}</code></div>
                        </div>
                    </button>
                `;

                return `
                    <div class="dag-branch-row flex items-center shrink-0" data-branch-idx="${bIdx}">
                        ${laneItemsHtml}
                    </div>
                `;
            }).join('');

            // 3. Ujung: Target Node (Menyatu)
            const targetHtml = `
                <button type="button" id="prereq-branch-btn-${tree.targetStepNumber}" data-step="${tree.targetStepNumber}" data-endpoint-id="${targetEp.id}"
                    onclick="restoreMainEndpoint()"
                    class="prereq-step-btn flex flex-col justify-center p-3 rounded-xl bg-tertiary/10 hover:bg-tertiary/20 border border-tertiary/40 shrink-0 transition-all text-left group ring-2 ring-tertiary/40 cursor-pointer shadow-md w-[190px]"
                    title="Klik untuk kembali ke endpoint target utama">
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="w-5 h-5 rounded-full bg-tertiary/20 text-tertiary flex items-center justify-center font-bold text-[10px] shrink-0">${tree.targetStepNumber}</span>
                        <span class="px-1.5 py-0.5 rounded font-label-badge text-[8px] bg-tertiary/20 text-tertiary font-bold tracking-wider border border-tertiary/30">TARGET</span>
                    </div>
                    <div class="font-bold text-tertiary text-[12px] truncate">${escapeHtml(targetEp.title)}</div>
                    <div class="text-tertiary/80 text-[10px] mt-0.5 truncate flex items-center justify-between">
                        <div class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-[13px]">call_merge</span>
                            <span>Menyatu &amp; Selesai</span>
                        </div>
                        <span class="material-symbols-outlined text-[13px] opacity-0 group-hover:opacity-100 text-tertiary transition-opacity">settings_backup_restore</span>
                    </div>
                </button>
            `;

            return `
                <div id="spec-prereq-dag-container" class="relative flex items-center min-w-max py-5 px-6 rounded-xl bg-surface-container-lowest/80 border border-[#222d45]">
                    <!-- Global SVG Canvas Overlay (Covers entire container) -->
                    <svg id="dag-svg-canvas" class="absolute inset-0 w-full h-full pointer-events-none overflow-visible" style="z-index: 1;">
                        <!-- Bézier paths and markers injected dynamically -->
                    </svg>

                    <!-- Floating Pill Badges -->
                    <span id="dag-badge-fork" class="absolute -translate-x-1/2 px-2 py-0.5 rounded-full text-[8.5px] font-bold bg-primary-container/70 text-primary border border-primary/40 uppercase tracking-wider shadow-sm z-10 pointer-events-none hidden">Fork</span>
                    <span id="dag-badge-join" class="absolute -translate-x-1/2 px-2 py-0.5 rounded-full text-[8.5px] font-bold bg-emerald-950/80 text-emerald-400 border border-emerald-500/40 uppercase tracking-wider shadow-sm z-10 pointer-events-none hidden">Join</span>

                    <!-- 1. Pangkal: Login (Root) -->
                    <div id="dag-node-root" class="shrink-0 flex items-center mr-16 relative" style="z-index: 2;">
                        ${rootHtml}
                    </div>

                    <!-- 2. Cabang-Cabang Paralel (Middle Rows) -->
                    <div id="dag-branches-col" class="flex flex-col gap-4 shrink-0 py-1 mr-16 relative" style="z-index: 2;">
                        ${branchesRowsHtml}
                    </div>

                    <!-- 3. Ujung: Target Endpoint (Target) -->
                    <div id="dag-node-target" class="shrink-0 flex items-center relative" style="z-index: 2;">
                        ${targetHtml}
                    </div>
                </div>
            `;
        }

        // Backward compatibility helper
        function buildLinearHtml(tree) {
            return '';
        }

        // ─── SELECT ENDPOINT ACTION ───
        function selectEndpoint(id) {
            const ep = ENDPOINTS_MAP[id];
            if (!ep) return;
            ACTIVE_ENDPOINT_ID = id;
            CURRENT_MAIN_ENDPOINT = ep;
            ACTIVE_PREREQ_STEP_DATA = null;

            // Switch to Endpoint view
            document.getElementById('endpoint-spec-view')?.classList.remove('hidden');
            document.getElementById('workflow-spec-view')?.classList.add('hidden');

            // 1. Update Sidebar Active State (TETAP pada endpoint ini)
            document.querySelectorAll('.wf-nav-item').forEach(el => {
                el.classList.remove('bg-surface-container-high', 'text-on-surface', 'border-l-2', 'border-primary');
            });
            document.querySelectorAll('.ep-nav-item').forEach(el => {
                el.classList.remove('bg-surface-container-high', 'text-on-surface', 'border-l-2', 'border-tertiary');
                el.querySelector('.active-indicator')?.classList.add('hidden');
            });
            const navEl = document.getElementById(`nav-ep-${id}`);
            if (navEl) {
                navEl.classList.add('bg-surface-container-high', 'text-on-surface', 'border-l-2', 'border-tertiary');
                navEl.querySelector('.active-indicator')?.classList.remove('hidden');
            }

            // Hide prerequisite active banner when switching main endpoint
            document.getElementById('spec-prereq-active-banner')?.classList.add('hidden');

            // 2. Render Kolom 2: Specs & Exclusive Branching DAG Diagram
            renderSpecs(ep);

            // 3. Render Kolom 2: Sub-specs (Headers, Body/Params, Response, Snippets)
            renderDynamicSubSpec(ep);

            // 4. Render Kolom 3: Tester
            syncTester(ep);

            // Close mobile sidebar if open
            if (window.innerWidth < 1024) {
                toggleMobileSidebar(false);
            }
        }

        // ─── RENDER SPECS (KOLOM 2 - TOP CONTEXT & EXCLUSIVE BRANCHING DAG) ───
        function renderSpecs(ep) {
            // Ribbon
            const ribbonMethod = document.getElementById('spec-ribbon-method');
            ribbonMethod.textContent = ep.method;
            ribbonMethod.className = `px-2.5 py-1 rounded font-code-md text-[12px] font-bold method-badge-${ep.method} border`;
            document.getElementById('spec-ribbon-url').textContent = ep.url;

            // Breadcrumbs & Title
            document.getElementById('spec-section-label').textContent = ep.section_title || 'Modul API';
            document.getElementById('spec-title').textContent = ep.title;
            document.getElementById('spec-desc').textContent = ep.description || 'Tidak ada deskripsi rinci untuk endpoint ini.';

            // Prerequisite Branching Diagram (Exclusive DAG with SVG Curves)
            const prereqCard = document.getElementById('spec-prerequisite-card');
            const prereqBranchWrap = document.getElementById('spec-prereq-branching-wrap');
            const branchBadge = document.getElementById('spec-prereq-branch-badge');

            const tree = getPrerequisiteTree(ep);

            if (tree.branches.length > 0) {
                prereqCard.classList.remove('hidden');
                document.getElementById('spec-prereq-title').textContent = `Alur Prerequisite Endpoint (${tree.targetStepNumber} Langkah)`;

                if (tree.isBranching) {
                    branchBadge.classList.remove('hidden');
                    branchBadge.textContent = `Bercabang (${tree.branches.length} Cabang lalu Menyatu)`;
                } else {
                    branchBadge.classList.remove('hidden');
                    branchBadge.textContent = `Alur Prasyarat (1 Dependensi)`;
                }

                // Render diagram DAG bercabang eksklusif
                prereqBranchWrap.innerHTML = buildBranchingHtml(tree);
                prereqBranchWrap.scrollLeft = 0;

                // Hitung koordinat dan render garis kurva Bézier SVG
                requestAnimationFrame(() => {
                    renderDagConnectors();
                    setTimeout(renderDagConnectors, 60);
                    setTimeout(renderDagConnectors, 250);
                });
            } else {
                prereqCard.classList.add('hidden');
                document.getElementById('spec-prereq-active-banner')?.classList.add('hidden');
            }
        }

        // ─── RENDER DYNAMIC SUB-SPEC (HEADERS, BODY, RESPONSE, SNIPPET) ───
        // Berubah dinamis in-place tanpa mengubah route aktif di sidebar atau judul utama
        function renderDynamicSubSpec(ep) {
            // 1. Headers Table
            const hTbody = document.getElementById('spec-headers-tbody');
            let hRows = `
                <tr class="hover:bg-surface-container/50">
                    <td class="py-2 px-3 font-semibold text-secondary">Accept</td>
                    <td class="py-2 px-3 text-outline">string</td>
                    <td class="py-2 px-3"><span class="px-1.5 py-0.5 rounded bg-error/10 text-error text-[10px] font-bold">Wajib</span></td>
                    <td class="py-2 px-3 text-on-surface-variant font-body-sm">Harus bernilai <code>application/json</code></td>
                </tr>
            `;
            if (['POST', 'PUT', 'PATCH'].includes(ep.method)) {
                hRows += `
                    <tr class="hover:bg-surface-container/50">
                        <td class="py-2 px-3 font-semibold text-secondary">Content-Type</td>
                        <td class="py-2 px-3 text-outline">string</td>
                        <td class="py-2 px-3"><span class="px-1.5 py-0.5 rounded bg-error/10 text-error text-[10px] font-bold">Wajib</span></td>
                        <td class="py-2 px-3 text-on-surface-variant font-body-sm">Format payload <code>application/json</code></td>
                    </tr>
                `;
            }
            if (ep.headers && ep.headers !== '-') {
                ep.headers.split('\n').forEach(line => {
                    const trimmed = line.trim();
                    if (trimmed && !trimmed.startsWith('//')) {
                        const parts = trimmed.split(':');
                        const key = parts[0]?.trim();
                        const val = parts.slice(1).join(':')?.trim();
                        if (key && key !== 'Content-Type' && key !== 'Accept') {
                            hRows += `
                                <tr class="hover:bg-surface-container/50">
                                    <td class="py-2 px-3 font-semibold text-secondary">${escapeHtml(key)}</td>
                                    <td class="py-2 px-3 text-outline">string</td>
                                    <td class="py-2 px-3"><span class="px-1.5 py-0.5 rounded bg-surface-variant text-outline text-[10px]">Header</span></td>
                                    <td class="py-2 px-3 text-on-surface-variant font-body-sm">${escapeHtml(val || '-')}</td>
                                </tr>
                            `;
                        }
                    }
                });
            }
            hTbody.innerHTML = hRows;

            // 2. Body / Params Schema & Table Heading
            const bodyHeading = document.getElementById('spec-body-heading');
            const bodyTypeBadge = document.getElementById('spec-body-type-badge');
            const bodyRawLabel = document.getElementById('spec-body-raw-label');
            const bodyRawEl = document.getElementById('spec-body-raw');
            const bodyTableWrap = document.getElementById('spec-body-table-wrap');
            const bodyTbody = document.getElementById('spec-body-tbody');

            if (ep.method === 'GET' || ep.method === 'DELETE') {
                if (bodyHeading) bodyHeading.textContent = 'Request Parameters (Query & Path)';
                if (bodyTypeBadge) bodyTypeBadge.textContent = 'URL Parameters';
                if (bodyRawLabel) bodyRawLabel.textContent = 'Parameters Schema';
            } else {
                if (bodyHeading) bodyHeading.textContent = 'Request Body (JSON Schema)';
                if (bodyTypeBadge) bodyTypeBadge.textContent = 'application/json';
                if (bodyRawLabel) bodyRawLabel.textContent = 'Payload Schema';
            }

            // Render Parameters (F1 - dengan Asal Nilai)
            if (ep.parameters && ep.parameters.length > 0) {
                bodyRawEl.textContent = ep.body || '// Skema parameter dari database';
                bodyTableWrap.classList.remove('hidden');
                bodyTbody.innerHTML = ep.parameters.map(p => {
                    let sourceCell = '';
                    if (p.source_type === 'endpoint' && p.source_endpoint) {
                        sourceCell = `
                            <div class="space-y-0.5">
                                <a href="javascript:void(0)" onclick="selectPrerequisiteStep(${p.source_endpoint.id}, null, false, 'Hasilkan ${escapeHtml(p.source_field || p.name)}')" 
                                    class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-primary-container/20 hover:bg-primary-container/40 text-primary text-[10px] font-semibold border border-primary/30 transition-colors cursor-pointer"
                                    title="Uji ${p.source_endpoint.url} di Live Tester">
                                    <span class="font-bold text-[9px] px-1 rounded bg-primary/20">${p.source_endpoint.method}</span>
                                    <span class="truncate max-w-[130px] font-code-sm">${p.source_endpoint.url}</span>
                                    <span class="material-symbols-outlined text-[11px]">play_circle</span>
                                </a>
                                <div class="text-[10px] text-outline">
                                    ${p.source_field ? 'Field: <code class="text-secondary">' + escapeHtml(p.source_field) + '</code>' : ''}
                                </div>
                            </div>
                        `;
                    } else if (p.source_type === 'session') {
                        sourceCell = `
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-purple-500/10 text-purple-300 text-[10px] font-medium border border-purple-500/30">
                                <span class="material-symbols-outlined text-[12px]">vpn_key</span>
                                <span>Sesi Sanctum</span>
                            </span>
                        `;
                    } else {
                        sourceCell = `
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-surface-container-high text-outline text-[10px]">
                                <span class="material-symbols-outlined text-[12px]">edit</span>
                                <span>Input Manual</span>
                            </span>
                        `;
                    }

                    const validationBadge = p.is_required 
                        ? `<span class="px-1.5 py-0.5 rounded bg-error/10 text-error text-[10px] font-bold">Wajib</span>`
                        : `<span class="px-1.5 py-0.5 rounded bg-surface-variant text-outline text-[10px]">Opsional</span>`;

                    return `
                        <tr class="hover:bg-surface-container/50">
                            <td class="py-2 px-3 font-semibold text-tertiary">
                                ${escapeHtml(p.name)}
                                <span class="text-[9px] text-outline block">(${p.location})</span>
                            </td>
                            <td class="py-2 px-3 text-outline">${escapeHtml(p.type)}</td>
                            <td class="py-2 px-3">${validationBadge}</td>
                            <td class="py-2 px-3 text-on-surface-variant font-body-sm">${escapeHtml(p.example || p.source_note || '-')}</td>
                            <td class="py-2 px-3">${sourceCell}</td>
                        </tr>
                    `;
                }).join('');
            } else if (ep.body && ep.body !== '-') {
                bodyRawEl.textContent = ep.body;
                const parsedRows = parseBodyValidationSchema(ep.body);
                if (parsedRows.length > 0) {
                    bodyTableWrap.classList.remove('hidden');
                    bodyTbody.innerHTML = parsedRows.map(r => `
                        <tr class="hover:bg-surface-container/50">
                            <td class="py-2 px-3 font-semibold text-tertiary">${escapeHtml(r.field)}</td>
                            <td class="py-2 px-3 text-outline">${escapeHtml(r.type)}</td>
                            <td class="py-2 px-3">${r.validation}</td>
                            <td class="py-2 px-3 text-on-surface-variant font-body-sm">${escapeHtml(r.desc)}</td>
                            <td class="py-2 px-3"><span class="text-outline text-[10px]">Input Manual</span></td>
                        </tr>
                    `).join('');
                } else {
                    bodyTableWrap.classList.add('hidden');
                }
            } else {
                bodyRawEl.textContent = (ep.method === 'GET' || ep.method === 'DELETE')
                    ? '// Endpoint ini tidak memerlukan parameter URL.' 
                    : '// Endpoint ini tidak memerlukan request body.';
                bodyTableWrap.classList.add('hidden');
            }

            // 3. Response Example
            const respCodeEl = document.getElementById('spec-response-code');
            if (ep.response_example && ep.response_example !== '-') {
                try {
                    const parsed = JSON.parse(ep.response_example);
                    respCodeEl.textContent = JSON.stringify(parsed, null, 2);
                } catch {
                    respCodeEl.textContent = ep.response_example;
                }
            } else {
                respCodeEl.textContent = JSON.stringify({ status: true, message: "OK" }, null, 2);
            }

            // 4. Code Snippets
            renderCodeSnippet(ep);
        }

        // ─── SELECT PREREQUISITE STEP (IN-PLACE TESTER & SUB-SPEC SWITCHER) ───
        function selectPrerequisiteStep(targetEpId, stepNumber, isMainTarget = false, stepLabel = '') {
            const targetEp = ENDPOINTS_MAP[targetEpId];
            if (!targetEp) return;

            const banner = document.getElementById('spec-prereq-active-banner');
            const bannerName = document.getElementById('spec-prereq-active-name');

            // Reset active styles on buttons across both views
            document.querySelectorAll('.prereq-step-btn').forEach(btn => {
                btn.classList.remove('ring-2', 'ring-primary', 'bg-primary-container/20', 'border-primary', 'font-bold');
            });

            if (isMainTarget || (CURRENT_MAIN_ENDPOINT && targetEpId === CURRENT_MAIN_ENDPOINT.id)) {
                // User returns to Main Target Endpoint
                ACTIVE_PREREQ_STEP_DATA = null;
                banner?.classList.add('hidden');

                // Highlight the final target button in both views
                document.querySelectorAll('.prereq-step-btn').forEach(btn => {
                    if (btn.dataset.endpointId == CURRENT_MAIN_ENDPOINT.id) {
                        btn.classList.add('ring-2', 'ring-tertiary', 'bg-tertiary/20');
                    }
                });

                renderDynamicSubSpec(CURRENT_MAIN_ENDPOINT);
                syncTester(CURRENT_MAIN_ENDPOINT);
                return;
            }

            // User clicked a prerequisite step
            ACTIVE_PREREQ_STEP_DATA = { ep: targetEp, step: stepNumber, label: stepLabel };

            // Highlight the clicked step button across both views (branching & linear)
            document.querySelectorAll('.prereq-step-btn').forEach(btn => {
                const matchStep = stepNumber && btn.dataset.step == stepNumber;
                const matchEp = btn.dataset.endpointId == targetEpId;
                if (matchStep || (!stepNumber && matchEp)) {
                    btn.classList.add('ring-2', 'ring-primary', 'bg-primary-container/20', 'border-primary');
                }
            });

            // Display informative active banner
            if (banner && bannerName) {
                banner.classList.remove('hidden');
                const stepNumText = stepNumber ? `(Langkah ${stepNumber})` : '';
                const labelText = stepLabel ? `&bull; ${escapeHtml(stepLabel)}` : '';
                bannerName.innerHTML = `
                    <span class="px-1.5 py-0.5 rounded font-label-badge text-[10px] font-bold method-badge-${targetEp.method}">${targetEp.method}</span>
                    <span class="text-primary font-code-sm font-semibold">${escapeHtml(targetEp.url)}</span>
                    <span class="text-on-surface-variant font-normal text-[11px] ml-1">${stepNumText} ${labelText}</span>
                `;
            }

            // Dynamic in-place switch for sub-specs & live tester
            renderDynamicSubSpec(targetEp);
            syncTester(targetEp);
        }

        // ─── RESTORE MAIN ENDPOINT ───
        function restoreMainEndpoint() {
            if (CURRENT_MAIN_ENDPOINT) {
                selectPrerequisiteStep(CURRENT_MAIN_ENDPOINT.id, null, true);
            }
        }

        // ─── GLOBAL AUTO-CAPTURED PREREQUISITE STORE (SMART DATA CHAINING) ───
        const AUTO_CAPTURED_PARAMS = {};

        function setDeepValue(obj, path, value) {
            const parts = path.split('.');
            let current = obj;
            for (let i = 0; i < parts.length; i++) {
                const p = parts[i];
                const isLast = (i === parts.length - 1);
                if (p === '*') {
                    if (!Array.isArray(current)) {
                        // fallback jika bukan array
                    }
                    if (current.length === 0) current.push({});
                    if (isLast) {
                        current[0] = value;
                    } else {
                        current = current[0];
                    }
                } else {
                    if (isLast) {
                        current[p] = value;
                    } else {
                        const nextPart = parts[i + 1];
                        if (!current[p] || typeof current[p] !== 'object') {
                            current[p] = (nextPart === '*') ? [] : {};
                        }
                        current = current[p];
                    }
                }
            }
        }

        function synthesizeSampleParams(ep) {
            const result = { query: {}, body: {}, capturedList: [] };

            if (ep.parameters && ep.parameters.length > 0) {
                ep.parameters.forEach(p => {
                    let val = null;
                    const paramKey = p.name;
                    const cleanKey = paramKey.replace(/^mahasiswa\.\*\./, '').replace(/\.\*/g, '');

                    // Prioritas 1: Ambil dari hasil live test sebelumnya (Auto-Chained)
                    if (AUTO_CAPTURED_PARAMS[paramKey] !== undefined) {
                        val = AUTO_CAPTURED_PARAMS[paramKey];
                        if (!result.capturedList.includes(paramKey)) result.capturedList.push(paramKey);
                    } else if (AUTO_CAPTURED_PARAMS[cleanKey] !== undefined) {
                        val = AUTO_CAPTURED_PARAMS[cleanKey];
                        if (!result.capturedList.includes(cleanKey)) result.capturedList.push(cleanKey);
                    } else if (paramKey === 'code' && ep.url.includes('program-studi') && AUTO_CAPTURED_PARAMS['code_prodi'] !== undefined) {
                        val = AUTO_CAPTURED_PARAMS['code_prodi'];
                        if (!result.capturedList.includes('code_prodi')) result.capturedList.push('code_prodi');
                    } else if (p.example && p.example !== '-') {
                        // Prioritas 2: Nilai example dari database
                        val = p.example.split('|')[0].replace(/[{}]/g, '').trim();
                        if (p.type === 'number' || p.type === 'integer') {
                            const num = Number(val);
                            if (!isNaN(num)) val = num;
                        }
                    } else if (p.source_type === 'endpoint' && p.source_endpoint) {
                        // Prioritas 3: Petunjuk terarah jika belum dieksekusi
                        val = `<Ambil dari ${p.source_endpoint.method} ${p.source_endpoint.url}>`;
                    } else {
                        if (p.type === 'number' || p.type === 'integer') val = 1;
                        else if (p.type === 'boolean') val = true;
                        else if (p.type === 'array') val = [];
                        else val = `contoh_${cleanKey}`;
                    }

                    if (p.location === 'query') {
                        result.query[paramKey] = val;
                    } else {
                        // Body: periksa apakah dot notation
                        if (paramKey.includes('.')) {
                            setDeepValue(result.body, paramKey, val);
                        } else {
                            if (p.type === 'array' && !Array.isArray(val)) {
                                result.body[paramKey] = [];
                            } else {
                                result.body[paramKey] = val;
                            }
                        }
                    }
                });

                return result;
            }

            // Fallback ke ep.body jika tabel parameters belum terisi
            if (ep.body && ep.body !== '-') {
                try {
                    const parsed = JSON.parse(ep.body);
                    const cleanObj = {};
                    for (const [k, v] of Object.entries(parsed)) {
                        let cleanV = v;
                        if (typeof v === 'string' && v.includes('|')) {
                            cleanV = v.split('|')[0].replace(/[{}]/g, '');
                        }
                        if (k.includes('.')) {
                            setDeepValue(cleanObj, k, cleanV);
                        } else {
                            cleanObj[k] = cleanV;
                        }
                    }
                    if (ep.method === 'GET') {
                        result.query = cleanObj;
                    } else {
                        result.body = cleanObj;
                    }
                } catch {
                    if (ep.method === 'GET') result.query = {};
                    else result.body = ep.body;
                }
            }

            return result;
        }

        function autoCaptureResponseParams(data, targetUrl = '') {
            if (!data || typeof data !== 'object') return [];
            const captured = [];

            function traverse(obj, depth = 0) {
                if (depth > 6 || !obj) return;
                if (Array.isArray(obj)) {
                    if (obj.length > 0 && typeof obj[0] === 'object') {
                        traverse(obj[0], depth + 1);
                    }
                    return;
                }
                for (const [k, v] of Object.entries(obj)) {
                    if (typeof v === 'string' || typeof v === 'number') {
                        // Deteksi pola property kunci: diawali code_, id_, key, dsb.
                        if (
                            k.startsWith('code_') || 
                            k === 'assessment_dosen_id' || 
                            k === 'node_key' || 
                            k === 'id' || 
                            k === 'code'
                        ) {
                            if (!AUTO_CAPTURED_PARAMS[k] || AUTO_CAPTURED_PARAMS[k] !== v) {
                                AUTO_CAPTURED_PARAMS[k] = v;
                                if (!captured.includes(k)) captured.push(k);
                            }
                            // Contextual Aliases berdasarkan URL
                            if (k === 'code') {
                                if (targetUrl.includes('dosen')) {
                                    AUTO_CAPTURED_PARAMS['code_dosen'] = v;
                                    if (!captured.includes('code_dosen')) captured.push('code_dosen');
                                } else if (targetUrl.includes('program-studi')) {
                                    AUTO_CAPTURED_PARAMS['code_prodi'] = v;
                                    AUTO_CAPTURED_PARAMS['code_program_studi'] = v;
                                    AUTO_CAPTURED_PARAMS['program_studi_kode'] = v;
                                    AUTO_CAPTURED_PARAMS['kode_program_studi'] = v;
                                    if (!captured.includes('code_prodi')) captured.push('code_prodi');
                                } else if (targetUrl.includes('mahasiswa')) {
                                    AUTO_CAPTURED_PARAMS['code_mahasiswa'] = v;
                                    if (!captured.includes('code_mahasiswa')) captured.push('code_mahasiswa');
                                }
                            }
                        }
                    } else if (typeof v === 'object' && v !== null) {
                        traverse(v, depth + 1);
                    }
                }
            }

            traverse(data);
            return captured;
        }

        // ─── SYNC TESTER (KOLOM 3) ───
        function syncTester(ep) {
            const methodSel = document.getElementById('tester-method');
            const urlInput = document.getElementById('tester-url');
            const paramsInput = document.getElementById('tester-params-input');
            const bodyInput = document.getElementById('tester-body-input');
            const prereqChainBar = document.getElementById('tester-prereq-chain-bar');

            methodSel.value = ep.method;

            // Construct full URL
            let fullUrl = ep.url;
            if (!fullUrl.startsWith('http')) {
                fullUrl = fullUrl.startsWith('/') ? `${APP_ROOT_URL}${fullUrl}` : `${APP_ROOT_URL}/${fullUrl}`;
            }
            urlInput.value = fullUrl;

            // Generate Smart Synthesized Data
            const synthesized = synthesizeSampleParams(ep);

            const queryStr = Object.keys(synthesized.query).length > 0 
                ? JSON.stringify(synthesized.query, null, 2) 
                : '';
            
            const bodyStr = Object.keys(synthesized.body).length > 0
                ? (typeof synthesized.body === 'string' ? synthesized.body : JSON.stringify(synthesized.body, null, 2))
                : '';

            if (ep.method === 'GET') {
                if (paramsInput) paramsInput.value = queryStr;
                if (bodyInput) bodyInput.value = '';
                switchReqTab('params');
            } else {
                if (bodyInput) bodyInput.value = bodyStr;
                if (paramsInput) paramsInput.value = queryStr;
                switchReqTab('body');
            }

            // UI Feedback: Apakah ada parameter yang terisi otomatis dari respon langkah sebelumnya
            if (prereqChainBar) {
                if (synthesized.capturedList.length > 0) {
                    prereqChainBar.classList.remove('hidden');
                    prereqChainBar.innerHTML = `
                        <div class="flex items-center gap-1.5 truncate">
                            <span class="material-symbols-outlined text-secondary text-[14px]">link</span>
                            <span>Auto-Chained: <strong class="text-on-surface">${synthesized.capturedList.join(', ')}</strong></span>
                        </div>
                        <span class="text-[9px] px-1.5 py-0.2 rounded bg-secondary/20 text-secondary font-bold">Terisi Otomatis</span>
                    `;
                } else {
                    prereqChainBar.classList.add('hidden');
                }
            }

            updateCharCount();
        }

        // ─── CODE SNIPPET GENERATOR ───
        function renderCodeSnippet(ep) {
            const preEl = document.getElementById('code-snippet-pre');
            let fullUrl = ep.url.startsWith('http') ? ep.url : `${APP_ROOT_URL}${ep.url.startsWith('/') ? '' : '/'}${ep.url}`;
            const synthesized = synthesizeSampleParams(ep);

            let payloadStr = '';
            if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(ep.method)) {
                if (Object.keys(synthesized.body).length > 0) {
                    payloadStr = typeof synthesized.body === 'string' ? synthesized.body : JSON.stringify(synthesized.body, null, 2);
                }
            }

            // Jika GET dan memiliki query params, tambahkan ke URL snippet
            if (ep.method === 'GET' && Object.keys(synthesized.query).length > 0) {
                const qs = new URLSearchParams(synthesized.query).toString();
                if (qs) fullUrl += (fullUrl.includes('?') ? '&' : '?') + qs;
            }

            if (CURRENT_SNIPPET_LANG === 'curl') {
                let cmd = `curl -X ${ep.method} "${fullUrl}" \\\n  -H "Accept: application/json"`;
                if (['POST', 'PUT', 'PATCH'].includes(ep.method)) {
                    cmd += ` \\\n  -H "Content-Type: application/json"`;
                }
                if (payloadStr) {
                    cmd += ` \\\n  -d '${payloadStr.replace(/\n/g, '\n  ')}'`;
                }
                preEl.textContent = cmd;
            } else if (CURRENT_SNIPPET_LANG === 'js') {
                let code = `const response = await fetch("${fullUrl}", {\n  method: "${ep.method}",\n  headers: {\n    "Accept": "application/json"`;
                if (['POST', 'PUT', 'PATCH'].includes(ep.method)) {
                    code += `,\n    "Content-Type": "application/json"`;
                }
                code += `\n  },\n  credentials: "include"`;
                if (payloadStr && ['POST', 'PUT', 'PATCH'].includes(ep.method)) {
                    code += `,\n  body: JSON.stringify(${payloadStr.replace(/\n/g, '\n  ')})`;
                }
                code += `\n});\n\nconst data = await response.json();\nconsole.log(data);`;
                preEl.textContent = code;
            } else if (CURRENT_SNIPPET_LANG === 'php') {
                let code = `use Illuminate\\Support\\Facades\\Http;\n\n$response = Http::withHeaders([\n    'Accept' => 'application/json',\n])`;
                if (payloadStr && ['POST', 'PUT', 'PATCH'].includes(ep.method)) {
                    code += `->${ep.method.toLowerCase()}('${fullUrl}', ${payloadStr.replace(/\n/g, '\n    ')});`;
                } else {
                    code += `->${ep.method.toLowerCase()}('${fullUrl}');`;
                }
                code += `\n\n$data = $response->json();`;
                preEl.textContent = code;
            }
        }

        function switchSnippetLang(lang) {
            CURRENT_SNIPPET_LANG = lang;
            document.querySelectorAll('.snip-tab').forEach(b => {
                b.classList.remove('bg-primary', 'text-on-primary');
                b.classList.add('text-outline');
            });
            const activeBtn = document.getElementById(`snip-tab-${lang}`);
            if (activeBtn) {
                activeBtn.classList.add('bg-primary', 'text-on-primary');
                activeBtn.classList.remove('text-outline');
            }
            const currentActive = ACTIVE_PREREQ_STEP_DATA?.ep || CURRENT_MAIN_ENDPOINT || (ACTIVE_ENDPOINT_ID ? ENDPOINTS_MAP[ACTIVE_ENDPOINT_ID] : null);
            if (currentActive) {
                renderCodeSnippet(currentActive);
            }
        }

        // ─── SEARCH & FILTER ENGINE ───
        document.getElementById('route-search-input')?.addEventListener('input', function() {
            filterSidebar();
        });

        function setMethodFilter(method) {
            CURRENT_METHOD_FILTER = method;
            document.querySelectorAll('.method-chip').forEach(c => {
                c.classList.remove('active', 'bg-primary-container', 'text-on-primary-container');
                if (c.dataset.method === method) {
                    c.classList.add('active', 'bg-primary-container', 'text-on-primary-container');
                }
            });
            filterSidebar();
        }

        function filterSidebar() {
            const query = (document.getElementById('route-search-input')?.value || '').toLowerCase().trim();
            let visibleCount = 0;

            document.querySelectorAll('.section-group').forEach(secGroup => {
                let sectionHasMatch = false;
                const items = secGroup.querySelectorAll('.ep-nav-item');

                items.forEach(item => {
                    const epMethod = item.dataset.method;
                    const epPath = item.dataset.path;
                    const epTitle = item.dataset.title;

                    const matchMethod = (CURRENT_METHOD_FILTER === 'ALL' || epMethod === CURRENT_METHOD_FILTER);
                    const matchQuery = (!query || epPath.includes(query) || epTitle.includes(query));

                    if (matchMethod && matchQuery) {
                        item.classList.remove('hidden');
                        sectionHasMatch = true;
                        visibleCount++;
                    } else {
                        item.classList.add('hidden');
                    }
                });

                if (sectionHasMatch) {
                    secGroup.classList.remove('hidden');
                    // auto open accordion if searching
                    if (query) {
                        const secId = secGroup.dataset.sectionId;
                        document.querySelector(`.accordion-content-${secId}`)?.classList.remove('hidden');
                    }
                } else {
                    secGroup.classList.add('hidden');
                }
            });

            const badge = document.getElementById('total-routes-badge');
            if (badge) badge.textContent = `${visibleCount} Routes`;
        }

        function toggleSectionAccordion(secId) {
            const content = document.querySelector(`.accordion-content-${secId}`);
            const icon = document.querySelector(`.accordion-icon-${secId}`);
            if (content) {
                content.classList.toggle('hidden');
                if (icon) {
                    icon.textContent = content.classList.contains('hidden') ? 'folder' : 'folder_open';
                }
            }
        }

        // ─── LIVE API TESTER ENGINE ───
        async function sendTesterRequest() {
            const method = document.getElementById('tester-method').value;
            const url = document.getElementById('tester-url').value.trim();
            const rawParams = document.getElementById('tester-params-input')?.value.trim() || '';
            const rawBody = document.getElementById('tester-body-input')?.value.trim() || '';
            const rawHeaders = document.getElementById('tester-headers-input')?.value.trim() || '';

            if (!url) {
                alert('URL endpoint tidak boleh kosong.');
                return;
            }

            // Headers parsing
            let headers = {};
            try {
                if (rawHeaders) headers = JSON.parse(rawHeaders);
            } catch {
                alert('Format JSON Headers tidak valid.');
                return;
            }

            headers['Accept'] = headers['Accept'] || 'application/json';
            if (['POST', 'PUT', 'PATCH'].includes(method)) {
                headers['Content-Type'] = headers['Content-Type'] || 'application/json';
            }

            // Inject XSRF token from cookie
            const xsrfToken = getCookie('XSRF-TOKEN');
            if (xsrfToken) {
                headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfToken);
            }

            let finalUrl = url;
            let body = undefined;

            // 1. Process Params (Path substitution + Query String)
            const paramSource = rawParams || (method === 'GET' ? rawBody : '');
            if (paramSource) {
                try {
                    const parsedParams = JSON.parse(paramSource);
                    const remainingParams = {};
                    for (const [k, v] of Object.entries(parsedParams)) {
                        const regex = new RegExp(`(\\{${k}\\}|:${k}|<${k}>|<encrypted_${k}>)`, 'gi');
                        if (regex.test(finalUrl)) {
                            finalUrl = finalUrl.replace(regex, encodeURIComponent(v));
                        } else {
                            remainingParams[k] = v;
                        }
                    }

                    if (method === 'GET') {
                        const qs = new URLSearchParams(remainingParams).toString();
                        if (qs) finalUrl += (finalUrl.includes('?') ? '&' : '?') + qs;
                    }
                } catch {
                    // Non-JSON params fallback
                }
            }

            // 2. Process Body (Payload for non-GET)
            if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method) && rawBody) {
                try {
                    const parsedBody = JSON.parse(rawBody);
                    // Also substitute any remaining path placeholders in URL if matched
                    const cleanBody = {};
                    for (const [k, v] of Object.entries(parsedBody)) {
                        const regex = new RegExp(`(\\{${k}\\}|:${k}|<${k}>|<encrypted_${k}>)`, 'gi');
                        if (regex.test(finalUrl)) {
                            finalUrl = finalUrl.replace(regex, encodeURIComponent(v));
                        } else {
                            cleanBody[k] = v;
                        }
                    }
                    body = Object.keys(cleanBody).length > 0 ? JSON.stringify(cleanBody, null, 2) : rawBody;
                } catch {
                    body = rawBody;
                }
            }

            // UI: Loading state
            const btnRun = document.getElementById('btn-run-request');
            const runIcon = document.getElementById('run-icon');
            const runLabel = document.getElementById('run-label');
            btnRun.disabled = true;
            btnRun.classList.add('opacity-75');
            runIcon.textContent = 'hourglass_empty';
            runLabel.textContent = 'Mengirim Request...';

            const statusBadge = document.getElementById('res-status-badge');
            const timeBadge = document.getElementById('res-time-badge');
            const timeVal = document.getElementById('res-time-val');
            const resBodyCode = document.getElementById('res-body-code');
            const resHeadersCode = document.getElementById('res-headers-code');
            const copyResBtn = document.getElementById('btn-copy-res');

            statusBadge.textContent = 'Sending...';
            statusBadge.className = 'px-2 py-0.5 rounded font-label-badge text-[11px] font-bold bg-surface-container text-outline';

            const t0 = performance.now();
            let res, resText, elapsed = 0;

            try {
                res = await fetch(finalUrl, {
                    method,
                    headers,
                    body,
                    credentials: 'include'
                });
                resText = await res.text();
                elapsed = Math.round(performance.now() - t0);
            } catch (err) {
                statusBadge.textContent = `ERR: ${err.message}`;
                statusBadge.className = 'px-2 py-0.5 rounded font-label-badge text-[11px] font-bold bg-error/20 text-error';
                resBodyCode.textContent = `Network / CORS Error: ${err.message}\n\nPastikan aplikasi backend aktif dan domain diizinkan.`;
                btnRun.disabled = false;
                btnRun.classList.remove('opacity-75');
                runIcon.textContent = 'play_arrow';
                runLabel.textContent = 'Kirim Request (Ctrl+Enter)';
                return;
            }

            // Status Badge Formatting
            const status = res.status;
            let statusClass = 'bg-tertiary/20 text-tertiary border border-tertiary/40';
            if (status >= 400 && status < 500) statusClass = 'bg-amber-400/20 text-amber-400 border border-amber-400/40';
            if (status >= 500) statusClass = 'bg-error/20 text-error border border-error/40';

            statusBadge.textContent = `${status} ${res.statusText || 'OK'}`;
            statusBadge.className = `px-2 py-0.5 rounded font-label-badge text-[11px] font-bold ${statusClass}`;

            // Time & Size
            timeVal.textContent = `${elapsed} ms`;
            timeBadge.classList.remove('hidden');

            const bytes = new Blob([resText]).size;
            const sizeStr = bytes > 1024 ? `${(bytes / 1024).toFixed(2)} KB` : `${bytes} B`;
            const sizeBadge = document.getElementById('res-size-badge');
            sizeBadge.textContent = sizeStr;
            sizeBadge.classList.remove('hidden');
            copyResBtn.classList.remove('hidden');

            // Format Body JSON & Auto-Capture Prerequisite Values
            try {
                const jsonObj = JSON.parse(resText);
                resBodyCode.textContent = JSON.stringify(jsonObj, null, 2);

                if (res.ok) {
                    const newlyCaptured = autoCaptureResponseParams(jsonObj, finalUrl);
                    if (newlyCaptured.length > 0) {
                        const prereqChainBar = document.getElementById('tester-prereq-chain-bar');
                        if (prereqChainBar) {
                            prereqChainBar.classList.remove('hidden');
                            prereqChainBar.innerHTML = `
                                <div class="flex items-center gap-1.5 truncate">
                                    <span class="material-symbols-outlined text-secondary text-[14px]">task_alt</span>
                                    <span>Tersimpan untuk langkah berikutnya: <strong class="text-on-surface">${newlyCaptured.join(', ')}</strong></span>
                                </div>
                                <span class="text-[9px] px-1.5 py-0.2 rounded bg-secondary/20 text-secondary font-bold">Auto-Captured</span>
                            `;
                        }
                    }
                }
            } catch {
                resBodyCode.textContent = resText;
            }

            // Headers
            let hdrsStr = '';
            res.headers.forEach((val, key) => { hdrsStr += `${key}: ${val}\n`; });
            resHeadersCode.textContent = hdrsStr || '(No headers returned)';

            // Finish UI
            btnRun.disabled = false;
            btnRun.classList.remove('opacity-75');
            runIcon.textContent = 'play_arrow';
            runLabel.textContent = 'Kirim Request (Ctrl+Enter)';

            refreshDebugCookies();
        }

        function onTesterMethodChange() {
            const m = document.getElementById('tester-method').value;
            if (m === 'GET') {
                switchReqTab('params');
            } else {
                switchReqTab('body');
            }
        }

        function resetTesterBody() {
            const epToSync = ACTIVE_PREREQ_STEP_DATA?.ep || CURRENT_MAIN_ENDPOINT || (ACTIVE_ENDPOINT_ID ? ENDPOINTS_MAP[ACTIVE_ENDPOINT_ID] : null);
            if (epToSync) {
                syncTester(epToSync);
            } else {
                const p = document.getElementById('tester-params-input');
                const b = document.getElementById('tester-body-input');
                if (p) p.value = '';
                if (b) b.value = '';
            }
            updateCharCount();
        }

        function updateCharCount() {
            const isParamsVisible = !document.getElementById('pane-req-params')?.classList.contains('hidden');
            const inputEl = isParamsVisible 
                ? document.getElementById('tester-params-input')
                : document.getElementById('tester-body-input');
            const len = inputEl?.value.length || 0;
            const el = document.getElementById('tester-char-count');
            if (el) el.textContent = `Chars: ${len}`;
        }
        document.getElementById('tester-params-input')?.addEventListener('input', updateCharCount);
        document.getElementById('tester-body-input')?.addEventListener('input', updateCharCount);

        // ─── TABS & PANES SWITCHING ───
        function switchReqTab(tab) {
            ['params', 'body', 'headers', 'cookies'].forEach(t => {
                document.getElementById(`pane-req-${t}`)?.classList.add('hidden');
                document.getElementById(`req-tab-${t}`)?.classList.remove('bg-surface-container-high', 'text-on-surface', 'border-b-2', 'border-primary');
                document.getElementById(`req-tab-${t}`)?.classList.add('text-outline');
            });
            document.getElementById(`pane-req-${tab}`)?.classList.remove('hidden');
            const btn = document.getElementById(`req-tab-${tab}`);
            if (btn) {
                btn.classList.add('bg-surface-container-high', 'text-on-surface', 'border-b-2', 'border-primary');
                btn.classList.remove('text-outline');
            }
            if (tab === 'cookies') refreshDebugCookies();

            const m = document.getElementById('tester-method')?.value || 'GET';
            const hint = document.getElementById('tester-editor-hint');
            const typeIndicator = document.getElementById('req-type-indicator');

            if (tab === 'params') {
                if (hint) hint.textContent = 'Params URL / Query String';
                if (typeIndicator) typeIndicator.textContent = 'URL • Query & Path Params';
            } else if (tab === 'body') {
                if (hint) hint.textContent = m === 'GET' ? 'Info: Method GET tidak menggunakan body payload' : 'JSON Payload Body';
                if (typeIndicator) typeIndicator.textContent = 'RAW • application/json';
            } else if (tab === 'headers') {
                if (hint) hint.textContent = 'JSON Request Headers';
                if (typeIndicator) typeIndicator.textContent = 'HTTP Headers';
            } else if (tab === 'cookies') {
                if (hint) hint.textContent = 'Sanctum & XSRF Cookies';
                if (typeIndicator) typeIndicator.textContent = 'Cookie Inspector';
            }
            updateCharCount();
        }

        function switchResTab(tab) {
            ['body', 'headers'].forEach(t => {
                document.getElementById(`res-${t}-code`)?.classList.add('hidden');
                document.getElementById(`res-tab-${t}`)?.classList.remove('text-on-surface', 'border-b-2', 'border-primary');
                document.getElementById(`res-tab-${t}`)?.classList.add('text-outline');
            });
            document.getElementById(`res-${tab}-code`)?.classList.remove('hidden');
            const btn = document.getElementById(`res-tab-${tab}`);
            if (btn) {
                btn.classList.add('text-on-surface', 'border-b-2', 'border-primary');
                btn.classList.remove('text-outline');
            }
        }

        // ─── MOBILE VIEW SWITCHER ───
        function switchMobilePane(pane) {
            const colSpecs = document.getElementById('col-specs');
            const colTester = document.getElementById('col-tester');
            const btnDocs = document.getElementById('tab-btn-docs');
            const btnTester = document.getElementById('tab-btn-tester');

            if (pane === 'docs') {
                colSpecs?.classList.remove('hidden');
                colTester?.classList.add('hidden');
                colTester?.classList.remove('flex', 'w-full');
                btnDocs.className = 'px-3 py-1 rounded font-body-sm text-[12px] font-semibold bg-primary-container text-on-primary-container';
                btnTester.className = 'px-3 py-1 rounded font-body-sm text-[12px] font-semibold text-outline hover:text-on-surface';
            } else {
                colSpecs?.classList.add('hidden');
                colTester?.classList.remove('hidden');
                colTester?.classList.add('flex', 'w-full');
                btnTester.className = 'px-3 py-1 rounded font-body-sm text-[12px] font-semibold bg-primary-container text-on-primary-container';
                btnDocs.className = 'px-3 py-1 rounded font-body-sm text-[12px] font-semibold text-outline hover:text-on-surface';
            }
        }

        function toggleMobileSidebar(forceState) {
            const sidebar = document.getElementById('col-sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            const isClosed = sidebar.classList.contains('-translate-x-full');
            const nextState = forceState !== undefined ? forceState : isClosed;

            if (nextState) {
                sidebar.classList.remove('-translate-x-full');
                backdrop?.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                backdrop?.classList.add('hidden');
            }
        }
        document.getElementById('btn-toggle-sidebar')?.addEventListener('click', () => toggleMobileSidebar());

        // ─── COOKIE HELPERS ───
        function getCookie(name) {
            const v = `; ${document.cookie}`;
            const parts = v.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        }

        function refreshDebugCookies() {
            const xsrf = getCookie('XSRF-TOKEN');
            const xsrfEl = document.getElementById('dbg-xsrf-badge');
            if (xsrfEl) {
                xsrfEl.textContent = xsrf ? 'Aktif ✓' : 'Belum Ada';
                xsrfEl.className = xsrf ? 'px-1.5 py-0.5 rounded bg-tertiary/20 text-tertiary' : 'px-1.5 py-0.5 rounded bg-amber-400/20 text-amber-400';
            }

            const chipsEl = document.getElementById('dbg-cookie-chips');
            if (chipsEl) {
                if (document.cookie) {
                    const cookies = document.cookie.split(';').map(c => c.trim().split('=')[0]);
                    chipsEl.innerHTML = cookies.map(c => `
                        <span class="px-1.5 py-0.5 rounded bg-surface-container font-code-sm text-[10px] text-secondary border border-[#222d45]">${escapeHtml(c)}</span>
                    `).join('');
                } else {
                    chipsEl.innerHTML = '<span class="text-outline text-[10px]">Tidak ada cookie terdeteksi</span>';
                }
            }
        }

        // ─── UTILITY HELPERS ───
        function parseBodyValidationSchema(bodyStr) {
            if (!bodyStr || bodyStr === '-' || bodyStr.trim() === '{}') return [];
            try {
                const obj = JSON.parse(bodyStr);
                const rows = [];
                for (const [key, val] of Object.entries(obj)) {
                    let type = 'string';
                    let validation = '<span class="px-1.5 py-0.5 rounded bg-surface-variant text-outline text-[10px]">Opsional</span>';
                    let desc = '';

                    if (typeof val === 'string') {
                        const parts = val.split('|');
                        if (parts.length > 1) {
                            desc = parts[0].replace(/[{}]/g, '');
                            const rules = parts.slice(1);
                            const isReq = rules.includes('required');
                            validation = isReq 
                                ? `<span class="px-1.5 py-0.5 rounded bg-error/10 text-error text-[10px] font-bold">Wajib</span>` 
                                : `<span class="px-1.5 py-0.5 rounded bg-surface-variant text-outline text-[10px]">Opsional</span>`;
                            validation += ` <span class="text-[10px] text-outline">(${rules.join(', ')})</span>`;
                            if (rules.includes('integer') || rules.includes('numeric')) type = 'number';
                        } else {
                            desc = val;
                        }
                    } else if (typeof val === 'number') {
                        type = 'number';
                        desc = String(val);
                    } else if (typeof val === 'boolean') {
                        type = 'boolean';
                        desc = String(val);
                    } else if (Array.isArray(val)) {
                        type = 'array';
                        desc = JSON.stringify(val);
                    } else if (typeof val === 'object') {
                        type = 'object';
                        desc = JSON.stringify(val);
                    }
                    rows.push({ field: key, type, validation, desc });
                }
                return rows;
            } catch {
                return [];
            }
        }

        function copyCurrentEndpointUrl() {
            const url = document.getElementById('spec-ribbon-url')?.textContent || '';
            const fullUrl = url.startsWith('http') ? url : `${APP_ROOT_URL}${url.startsWith('/') ? '' : '/'}${url}`;
            navigator.clipboard?.writeText(fullUrl).then(() => {
                const label = document.getElementById('btn-copy-url-label');
                if (label) {
                    const orig = label.textContent;
                    label.textContent = 'Tersalin!';
                    setTimeout(() => label.textContent = orig, 1500);
                }
            });
        }

        function copySnippetText(elemId) {
            const text = document.getElementById(elemId)?.textContent || '';
            navigator.clipboard?.writeText(text).then(() => {
                alert('Tersalin ke clipboard!');
            });
        }

        function copyResponseText() {
            const text = document.getElementById('res-body-code')?.textContent || '';
            navigator.clipboard?.writeText(text).then(() => {
                const btn = document.getElementById('btn-copy-res');
                if (btn) {
                    btn.innerHTML = '<span class="material-symbols-outlined text-[13px]">done</span> Copied';
                    setTimeout(() => {
                        btn.innerHTML = '<span class="material-symbols-outlined text-[13px]">content_copy</span> Copy';
                    }, 1500);
                }
            });
        }

        function escapeHtml(str) {
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        // ─── 3-PANEL RESIZABLE LAYOUT MANAGER (PRD F-01 s/d F-12) ───
        const PANEL_LAYOUT = {
            STORAGE_KEY: 'siska_docs_panel_layout_v1',
            SIDEBAR: {
                DEFAULT: 285,
                MIN: 200,
                MAX: 480
            },
            SPECS: {
                MIN: 420
            },
            TESTER: {
                DEFAULT: 485,
                MIN: 320,
                MAX: 900
            },
            GUTTER_WIDTH: 6,
            STEP_NORMAL: 10,
            STEP_LARGE: 50
        };

        let currentSidebarWidth = PANEL_LAYOUT.SIDEBAR.DEFAULT;
        let currentTesterWidth = PANEL_LAYOUT.TESTER.DEFAULT;
        let isTesterCollapsed = false;

        function loadSavedLayout() {
            try {
                const raw = localStorage.getItem(PANEL_LAYOUT.STORAGE_KEY);
                if (!raw) return null;
                const data = JSON.parse(raw);
                if (data && data.version === 1) {
                    return data;
                }
            } catch (err) {
                console.warn('Gagal membaca layout dari localStorage:', err);
            }
            return null;
        }

        function saveLayout() {
            try {
                const data = {
                    version: 1,
                    sidebarWidth: Math.round(currentSidebarWidth),
                    testerWidth: Math.round(currentTesterWidth),
                    isTesterCollapsed: Boolean(isTesterCollapsed)
                };
                localStorage.setItem(PANEL_LAYOUT.STORAGE_KEY, JSON.stringify(data));
            } catch (err) {
                console.warn('Gagal menyimpan layout ke localStorage:', err);
            }
        }

        function getContainerWidth() {
            const container = document.getElementById('workspace-container');
            return container ? container.clientWidth : window.innerWidth;
        }

        function calculateMaxSidebarWidth() {
            const containerWidth = getContainerWidth();
            const activeTesterWidth = isTesterCollapsed ? 0 : currentTesterWidth;
            const gutters = isTesterCollapsed ? PANEL_LAYOUT.GUTTER_WIDTH : (PANEL_LAYOUT.GUTTER_WIDTH * 2);
            const available = containerWidth - activeTesterWidth - PANEL_LAYOUT.SPECS.MIN - gutters;
            return Math.max(PANEL_LAYOUT.SIDEBAR.MIN, Math.min(PANEL_LAYOUT.SIDEBAR.MAX, available));
        }

        function calculateMaxTesterWidth() {
            const containerWidth = getContainerWidth();
            const gutters = PANEL_LAYOUT.GUTTER_WIDTH * 2;
            const available = containerWidth - currentSidebarWidth - PANEL_LAYOUT.SPECS.MIN - gutters;
            return Math.max(PANEL_LAYOUT.TESTER.MIN, Math.min(PANEL_LAYOUT.TESTER.MAX, available));
        }

        function applySidebarWidth(targetWidth, persist = false) {
            const colSidebar = document.getElementById('col-sidebar');
            const gutterLeft = document.getElementById('gutter-left');
            if (!colSidebar || window.innerWidth < 1024) return;

            const maxAllowed = calculateMaxSidebarWidth();
            const clamped = Math.max(PANEL_LAYOUT.SIDEBAR.MIN, Math.min(targetWidth, maxAllowed));
            currentSidebarWidth = clamped;

            colSidebar.style.width = `${clamped}px`;
            if (gutterLeft) {
                gutterLeft.setAttribute('aria-valuenow', String(Math.round(clamped)));
                gutterLeft.setAttribute('aria-valuemax', String(Math.round(maxAllowed)));
            }

            if (persist) saveLayout();
        }

        function applyTesterWidth(targetWidth, persist = false) {
            const colTester = document.getElementById('col-tester');
            const gutterRight = document.getElementById('gutter-right');
            if (!colTester || isTesterCollapsed || window.innerWidth < 1024) return;

            const maxAllowed = calculateMaxTesterWidth();
            const clamped = Math.max(PANEL_LAYOUT.TESTER.MIN, Math.min(targetWidth, maxAllowed));
            currentTesterWidth = clamped;

            colTester.style.width = `${clamped}px`;
            if (gutterRight) {
                gutterRight.setAttribute('aria-valuenow', String(Math.round(clamped)));
                gutterRight.setAttribute('aria-valuemax', String(Math.round(maxAllowed)));
            }

            if (persist) saveLayout();
        }

        function toggleTesterPanel(open) {
            if (window.innerWidth < 1024) {
                const colTester = document.getElementById('col-tester');
                const isCurrentlyVisible = colTester && !colTester.classList.contains('hidden');
                const targetOpen = (open !== undefined) ? open : !isCurrentlyVisible;
                switchMobilePane(targetOpen ? 'tester' : 'docs');
                return;
            }

            const colTester = document.getElementById('col-tester');
            const gutterRight = document.getElementById('gutter-right');
            const toggleIcon = document.getElementById('btn-toggle-tester-icon');
            const toggleBtn = document.getElementById('btn-toggle-tester');

            if (open === undefined) {
                isTesterCollapsed = !isTesterCollapsed;
            } else {
                isTesterCollapsed = !open;
            }

            if (isTesterCollapsed) {
                colTester?.classList.add('hidden');
                colTester?.classList.remove('flex');
                gutterRight?.classList.add('hidden');
                gutterRight?.classList.remove('flex');
                if (toggleIcon) toggleIcon.textContent = 'vertical_split';
                if (toggleBtn) toggleBtn.setAttribute('title', 'Buka panel Live API Tester (Alt + T)');
            } else {
                colTester?.classList.remove('hidden');
                colTester?.classList.add('flex');
                gutterRight?.classList.remove('hidden');
                gutterRight?.classList.add('flex');
                if (toggleIcon) toggleIcon.textContent = 'right_panel_close';
                if (toggleBtn) toggleBtn.setAttribute('title', 'Tutup panel Live API Tester (Alt + T)');
                applyTesterWidth(currentTesterWidth);
            }

            saveLayout();
        }

        function resetPanelLayout() {
            currentSidebarWidth = PANEL_LAYOUT.SIDEBAR.DEFAULT;
            currentTesterWidth = PANEL_LAYOUT.TESTER.DEFAULT;
            isTesterCollapsed = false;

            const colTester = document.getElementById('col-tester');
            const gutterRight = document.getElementById('gutter-right');
            const toggleIcon = document.getElementById('btn-toggle-tester-icon');
            const toggleBtn = document.getElementById('btn-toggle-tester');

            colTester?.classList.remove('hidden');
            colTester?.classList.add('flex');
            gutterRight?.classList.remove('hidden');
            gutterRight?.classList.add('flex');
            if (toggleIcon) toggleIcon.textContent = 'right_panel_close';
            if (toggleBtn) toggleBtn.setAttribute('title', 'Tutup panel Live API Tester (Alt + T)');

            applySidebarWidth(PANEL_LAYOUT.SIDEBAR.DEFAULT);
            applyTesterWidth(PANEL_LAYOUT.TESTER.DEFAULT);

            saveLayout();
        }

        function initPanelLayout() {
            const saved = loadSavedLayout();
            if (saved) {
                currentSidebarWidth = saved.sidebarWidth || PANEL_LAYOUT.SIDEBAR.DEFAULT;
                currentTesterWidth = saved.testerWidth || PANEL_LAYOUT.TESTER.DEFAULT;
                isTesterCollapsed = Boolean(saved.isTesterCollapsed);
            }

            if (window.innerWidth >= 1024) {
                applySidebarWidth(currentSidebarWidth);
                if (isTesterCollapsed) {
                    toggleTesterPanel(false);
                } else {
                    applyTesterWidth(currentTesterWidth);
                }
            }

            const gutterLeft = document.getElementById('gutter-left');
            const gutterRight = document.getElementById('gutter-right');
            const container = document.getElementById('workspace-container');

            function setupGutter(gutter, type) {
                if (!gutter || !container) return;

                let dragging = false;

                gutter.addEventListener('pointerdown', function(e) {
                    if (window.innerWidth < 1024) return;
                    if (e.pointerType === 'mouse' && e.button !== 0) return;

                    dragging = true;
                    gutter.setPointerCapture(e.pointerId);
                    document.body.classList.add('is-resizing');
                    gutter.classList.add('is-dragging');
                    e.preventDefault();
                });

                gutter.addEventListener('pointermove', function(e) {
                    if (!dragging || window.innerWidth < 1024) return;
                    const containerRect = container.getBoundingClientRect();

                    if (type === 'left') {
                        const target = e.clientX - containerRect.left;
                        applySidebarWidth(target);
                    } else if (type === 'right') {
                        const target = containerRect.right - e.clientX;
                        applyTesterWidth(target);
                    }
                });

                function endDrag(e) {
                    if (!dragging) return;
                    dragging = false;
                    try {
                        gutter.releasePointerCapture(e.pointerId);
                    } catch (_) {}
                    document.body.classList.remove('is-resizing');
                    gutter.classList.remove('is-dragging');
                    saveLayout();
                    requestAnimationFrame(() => renderDagConnectors());
                }

                gutter.addEventListener('pointerup', endDrag);
                gutter.addEventListener('pointercancel', endDrag);

                // Double click to reset per panel (PRD F-06)
                gutter.addEventListener('dblclick', function() {
                    if (type === 'left') {
                        applySidebarWidth(PANEL_LAYOUT.SIDEBAR.DEFAULT, true);
                    } else {
                        applyTesterWidth(PANEL_LAYOUT.TESTER.DEFAULT, true);
                    }
                });

                // Keyboard operation (PRD F-08)
                gutter.addEventListener('keydown', function(e) {
                    const step = e.shiftKey ? PANEL_LAYOUT.STEP_LARGE : PANEL_LAYOUT.STEP_NORMAL;
                    if (type === 'left') {
                        if (e.key === 'ArrowLeft') {
                            e.preventDefault();
                            applySidebarWidth(currentSidebarWidth - step, true);
                        } else if (e.key === 'ArrowRight') {
                            e.preventDefault();
                            applySidebarWidth(currentSidebarWidth + step, true);
                        } else if (e.key === 'Home') {
                            e.preventDefault();
                            applySidebarWidth(PANEL_LAYOUT.SIDEBAR.MIN, true);
                        } else if (e.key === 'End') {
                            e.preventDefault();
                            applySidebarWidth(PANEL_LAYOUT.SIDEBAR.MAX, true);
                        } else if (e.key === 'Enter') {
                            e.preventDefault();
                            applySidebarWidth(PANEL_LAYOUT.SIDEBAR.DEFAULT, true);
                        }
                    } else if (type === 'right') {
                        if (e.key === 'ArrowLeft') {
                            e.preventDefault();
                            applyTesterWidth(currentTesterWidth + step, true);
                        } else if (e.key === 'ArrowRight') {
                            e.preventDefault();
                            applyTesterWidth(currentTesterWidth - step, true);
                        } else if (e.key === 'Home') {
                            e.preventDefault();
                            applyTesterWidth(PANEL_LAYOUT.TESTER.MAX, true);
                        } else if (e.key === 'End') {
                            e.preventDefault();
                            applyTesterWidth(PANEL_LAYOUT.TESTER.MIN, true);
                        } else if (e.key === 'Enter') {
                            e.preventDefault();
                            applyTesterWidth(PANEL_LAYOUT.TESTER.DEFAULT, true);
                        }
                    }
                });
            }

            setupGutter(gutterLeft, 'left');
            setupGutter(gutterRight, 'right');

            // Responsive window resize listener (PRD F-10)
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 1024) {
                    const colSpecs = document.getElementById('col-specs');
                    const colTester = document.getElementById('col-tester');
                    colSpecs?.classList.remove('hidden');
                    colTester?.classList.remove('w-full');
                    applySidebarWidth(currentSidebarWidth);
                    if (isTesterCollapsed) {
                        colTester?.classList.add('hidden');
                    } else {
                        colTester?.classList.remove('hidden');
                        colTester?.classList.add('flex');
                        applyTesterWidth(currentTesterWidth);
                    }
                }
            });
        }

        // ─── KEYBOARD SHORTCUTS ───
        window.addEventListener('keydown', function(e) {
            // Shortcut "/" to search
            if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
                e.preventDefault();
                document.getElementById('route-search-input')?.focus();
            }
            // Shortcut Ctrl + Enter to send tester request
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                sendTesterRequest();
            }
            // Shortcut Alt + T to toggle tester panel
            if (e.altKey && (e.key === 't' || e.key === 'T')) {
                e.preventDefault();
                toggleTesterPanel();
            }
        });

        window.addEventListener('resize', function() {
            requestAnimationFrame(() => renderDagConnectors());
        });

        // ─── INITIALIZATION ON LOAD ───
        document.addEventListener('DOMContentLoaded', function() {
            initPanelLayout();

            // Select first endpoint by default
            if (ALL_ENDPOINTS_LIST.length > 0) {
                // If URL has query params ?method=...&url=...
                const urlParams = new URLSearchParams(window.location.search);
                const reqUrl = urlParams.get('url');
                const reqMethod = urlParams.get('method');

                let target = null;
                if (reqUrl) {
                    target = ALL_ENDPOINTS_LIST.find(e => e.url === reqUrl && (!reqMethod || e.method === reqMethod));
                }
                if (!target) {
                    target = ALL_ENDPOINTS_LIST[0];
                }
                selectEndpoint(target.id);
            }
            refreshDebugCookies();
        });
    </script>
</body>

</html>