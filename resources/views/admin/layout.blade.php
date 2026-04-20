<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') — Admin SIAKAD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar: 240px;
            --primary: #0d3b6e;
        }

        body {
            min-height: 100vh;
            background: #f4f6fb;
            font-size: .93rem;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar);
            background: var(--primary);
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 300;
            display: flex;
            flex-direction: column;
            transition: width .25s ease, transform .25s ease;
        }

        /* Collapsed: icon-only on desktop */
        body.sidebar-collapsed .sidebar {
            width: 62px;
        }

        body.sidebar-collapsed .sidebar .sidebar-brand-text,
        body.sidebar-collapsed .sidebar .nav-section-label,
        body.sidebar-collapsed .sidebar .nav-link-text {
            opacity: 0;
            width: 0;
            overflow: hidden;
            white-space: nowrap;
            display: inline-block;
            transition: opacity .2s ease, width .2s ease;
        }

        body.sidebar-collapsed .sidebar .sidebar-brand {
            padding: 1.2rem .9rem 1rem;
        }

        body.sidebar-collapsed .sidebar-nav a {
            justify-content: center;
            padding: .6rem 0;
        }

        body.sidebar-collapsed .sidebar-nav a i {
            font-size: 1.15rem;
        }

        /* Mobile: off-canvas */
        @media (max-width: 767.98px) {
            .sidebar {
                transform: translateX(0);
                width: var(--sidebar) !important;
            }

            body.sidebar-collapsed .sidebar {
                transform: translateX(calc(-1 * var(--sidebar)));
                width: var(--sidebar) !important;
            }

            body.sidebar-collapsed .sidebar .sidebar-brand-text,
            body.sidebar-collapsed .sidebar .nav-section-label,
            body.sidebar-collapsed .sidebar .nav-link-text {
                opacity: 1;
                width: auto;
            }

            body.sidebar-collapsed .sidebar-nav a {
                justify-content: flex-start;
                padding: .55rem 1.4rem;
            }
        }

        /* Overlay for mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .45);
            z-index: 299;
        }

        body:not(.sidebar-collapsed) .sidebar-overlay {
            display: none;
        }

        @media (max-width: 767.98px) {
            body:not(.sidebar-collapsed) .sidebar-overlay {
                display: block;
            }
        }

        .sidebar-brand {
            padding: 1.2rem 1.4rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, .1);
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
        }

        .sidebar-nav {
            flex: 1;
            padding-bottom: 1rem;
        }

        .nav-section-label {
            padding: .85rem 1.4rem .25rem;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, .38);
            text-transform: uppercase;
            transition: opacity .2s ease;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .55rem 1.4rem;
            color: rgba(255, 255, 255, .72);
            text-decoration: none;
            font-size: .88rem;
            border-left: 3px solid transparent;
            transition: all .15s;
            white-space: nowrap;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            color: #fff;
            background: rgba(255, 255, 255, .08);
            border-left-color: #4da6ff;
        }

        /* Main */
        .main-wrap {
            margin-left: var(--sidebar);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin-left .25s ease;
        }

        body.sidebar-collapsed .main-wrap {
            margin-left: 62px;
        }

        @media (max-width: 767.98px) {
            .main-wrap {
                margin-left: 0 !important;
            }
        }

        .topbar {
            background: #fff;
            border-bottom: 1px solid #e5e8ef;
            padding: .7rem 1.2rem .7rem 1.6rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        #sidebarToggle {
            border: none;
            background: transparent;
            color: #555;
            font-size: 1.25rem;
            padding: .2rem .4rem;
            border-radius: 6px;
            cursor: pointer;
            transition: background .15s;
        }

        #sidebarToggle:hover {
            background: #f0f2f5;
        }

        .content {
            padding: 1.6rem;
            flex: 1;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 8px rgba(13, 59, 110, .07);
        }

        .card-header-custom {
            background: var(--primary);
            color: #fff;
            padding: .75rem 1.2rem;
            border-radius: 12px 12px 0 0;
            font-weight: 600;
        }

        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background: #1a73e8;
            border-color: #1a73e8;
        }

        .badge-admin {
            background: #dc3545;
        }

        .badge-staff {
            background: #0d6efd;
        }

        .method-badge {
            font-size: .7rem;
            font-weight: 700;
            border-radius: 4px;
            padding: .2em .6em;
            min-width: 56px;
            text-align: center;
        }

        .m-GET {
            background: #d1fae5;
            color: #065f46;
        }

        .m-POST {
            background: #dbeafe;
            color: #1e40af;
        }

        .m-PUT {
            background: #fef9c3;
            color: #854d0e;
        }

        .m-PATCH {
            background: #ede9fe;
            color: #5b21b6;
        }

        .m-DELETE {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
    @stack('styles')
</head>

<body>

    {{-- Sidebar overlay (mobile) --}}
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    {{-- Sidebar --}}
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="text-white fw-bold fs-6 text-truncate">
                <i class="bi bi-heart-pulse-fill text-danger me-2"></i><span class="sidebar-brand-text">SIAKAD</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-label">Main</div>
            <a href="{{ route('admin.dashboard') }}"
                class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> <span class="nav-link-text">Dashboard</span>
            </a>

            <div class="nav-section-label">Dokumentasi</div>
            <a href="{{ route('admin.sections.index') }}"
                class="{{ request()->routeIs('admin.sections.*') ? 'active' : '' }}">
                <i class="bi bi-collection-fill"></i> <span class="nav-link-text">Sections</span>
            </a>
            <a href="{{ route('admin.endpoints.index') }}"
                class="{{ request()->routeIs('admin.endpoints.*') ? 'active' : '' }}">
                <i class="bi bi-code-slash"></i> <span class="nav-link-text">Endpoints</span>
            </a>

            @if(auth()->user()->isAdmin())
            <div class="nav-section-label">Manajemen</div>
            <a href="{{ route('admin.users.index') }}"
                class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i> <span class="nav-link-text">Users</span>
            </a>
            <a href="{{ route('admin.connections.index') }}"
                class="{{ request()->routeIs('admin.connections.*') ? 'active' : '' }}">
                <i class="bi bi-plug-fill"></i> <span class="nav-link-text">Api Connections</span>
            </a>
            <a href="{{ route('admin.tahun-akademik.index') }}"
                class="{{ request()->routeIs('admin.tahun-akademik.*') ? 'active' : '' }}">
                <i class="bi bi-calendar-fill"></i> <span class="nav-link-text">Tahun Akademik</span>
            </a>
            <a href="{{ route('admin.mahasiswa.index') }}"
                class="{{ request()->routeIs('admin.mahasiswa.*') ? 'active' : '' }}">
                <i class="bi bi-mortarboard-fill"></i> <span class="nav-link-text">Mahasiswa</span>
            </a>
            <a href="{{ route('admin.matakuliah.index') }}"
                class="{{ request()->routeIs('admin.matakuliah.*') ? 'active' : '' }}">
                <i class="bi bi-journal-bookmark-fill"></i> <span class="nav-link-text">Matakuliah</span>
            </a>
            <a href="{{ route('admin.krs-khs.index') }}"
                class="{{ request()->routeIs('admin.krs-khs.*') ? 'active' : '' }}">
                <i class="bi bi-journal-check"></i> <span class="nav-link-text">KRS KHS Mahasiswa</span>
            </a>
            <a href="{{ route('admin.dosen.index') }}"
                class="{{ request()->routeIs('admin.dosen.*') ? 'active' : '' }}">
                <i class="bi bi-person-workspace"></i> <span class="nav-link-text">Dosen</span>
            </a>
            <a href="{{ route('admin.kelas.index') }}"
                class="{{ request()->routeIs('admin.kelas.*') ? 'active' : '' }}">
                <i class="bi bi-diagram-3-fill"></i> <span class="nav-link-text">Kelas</span>
            </a>
            <a href="{{ route('admin.kurikulum.index') }}"
                class="{{ request()->routeIs('admin.kurikulum.*') ? 'active' : '' }}">
                <i class="bi bi-journal-richtext"></i> <span class="nav-link-text">Kurikulum</span>
            </a>
            @endif

            <div class="nav-section-label">Lainnya</div>
            <a href="{{ route('admin.docs') }}" target="_blank">
                <i class="bi bi-eye-fill"></i> <span class="nav-link-text">Lihat Docs</span>
            </a>
            <a href="{{ route('admin.tester') }}" target="_blank">
                <i class="bi bi-terminal-fill"></i> <span class="nav-link-text">API Tester</span>
            </a>
        </nav>
    </div>

    {{-- Main --}}
    <div class="main-wrap">
        <div class="topbar">
            <div class="d-flex gap-2">
                <button id="sidebarToggle" title="Toggle Sidebar">
                    <i class="bi bi-list"></i>
                </button>
                <div class="d-none d-sm-block mt-2">
                    <span class="fw-semibold text-dark ms-1">@yield('page-title', 'Dashboard')</span>
                    <span class="text-muted ms-1 small d-none d-sm-inline">/ SIAKAD Admin</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small d-none d-md-inline">
                    <i class="bi bi-person-circle me-1"></i>{{ auth()->user()->name }}
                </span>
                <span class="badge {{ auth()->user()->isAdmin() ? 'badge-admin' : 'badge-staff' }} text-white">
                    {{ ucfirst(auth()->user()->role) }}
                </span>
                <form action="{{ route('logout') }}" method="POST" class="d-inline ms-1">
                    @csrf
                    <button class="btn btn-sm btn-outline-secondary" title="Logout">
                        <i class="bi bi-box-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="content">
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show py-2">
                <i class="bi bi-check-circle-fill me-1"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show py-2">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const body    = document.body;
            const toggle  = document.getElementById('sidebarToggle');
            const overlay = document.getElementById('sidebarOverlay');
            const isMobile = () => window.innerWidth < 768;

            // Restore saved state on desktop (default expanded)
            if (!isMobile() && localStorage.getItem('sidebarCollapsed') === '1') {
                body.classList.add('sidebar-collapsed');
            }

            // On mobile: collapsed = hidden, start hidden
            if (isMobile()) {
                body.classList.add('sidebar-collapsed');
            }

            toggle.addEventListener('click', function () {
                body.classList.toggle('sidebar-collapsed');
                if (!isMobile()) {
                    localStorage.setItem('sidebarCollapsed',
                        body.classList.contains('sidebar-collapsed') ? '1' : '0');
                }
            });

            // Close sidebar on mobile when overlay is clicked
            overlay.addEventListener('click', function () {
                body.classList.add('sidebar-collapsed');
            });

            // On resize: apply correct default
            window.addEventListener('resize', function () {
                if (!isMobile()) {
                    overlay.style.display = '';
                    const saved = localStorage.getItem('sidebarCollapsed');
                    if (saved === '1') {
                        body.classList.add('sidebar-collapsed');
                    } else {
                        body.classList.remove('sidebar-collapsed');
                    }
                } else {
                    body.classList.add('sidebar-collapsed');
                }
            });
        })();
    </script>
    @stack('scripts')
</body>

</html>