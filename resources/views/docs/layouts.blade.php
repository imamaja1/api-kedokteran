<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'API SISKA') — SIAKAD Kedokteran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('docs/layouts.css') }}">
    <style>
        body {
            background: @yield('body-bg', '#f4f6fb');
        }
    </style>
    @stack('styles')
</head>

<body>

    {{-- ─── Top Bar ─── --}}
    <nav class="docs-navbar">
        @hasSection('sidebar')
        <button class="nav-hamburger" id="sidebarToggle" aria-label="Buka sidebar" onclick="toggleSidebar()">
            <i class="bi bi-list"></i>
        </button>
        @endif

        <a href="/" class="nav-brand">
            <i class="bi bi-heart-pulse-fill text-danger"></i>
            <span>API SISKA</span>
        </a>

        <div class="nav-tabs-row">
            <a href="{{ route('admin.docs') }}"
                class="nav-tab {{ request()->routeIs('docs','admin.docs') ? 'active' : '' }}">
                <i class="bi bi-file-earmark-code"></i> API Docs
            </a>
            <a href="{{ route('admin.tester') }}"
                class="nav-tab {{ request()->routeIs('tester','admin.tester') ? 'active' : '' }}">
                <i class="bi bi-send"></i> API Tester
            </a>
            @yield('extra-tabs')
        </div>

        <div class="nav-right">
            @auth
            <span class="nav-right-user">
                <i class="bi bi-person-circle"></i>
                <span>{{ auth()->user()->name }}</span>
            </span>
            @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.dashboard') }}" class="nav-right-link">
                <i class="bi bi-grid-3x3-gap-fill"></i>
                <span class="d-none d-sm-inline">Admin</span>
            </a>
            @endif
            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="nav-right-link btn-logout">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="d-none d-sm-inline">Logout</span>
                </button>
            </form>
            @else
            <a href="{{ route('login') }}" class="nav-right-link">
                <i class="bi bi-shield-lock"></i>
                <span class="d-none d-sm-inline">Login</span>
            </a>
            @endauth
        </div>
    </nav>

    {{-- ─── Sidebar overlay (mobile) ─── --}}
    @hasSection('sidebar')
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
    @endif

    {{-- ─── Sidebar & Content Layout ─── --}}
    @hasSection('sidebar')
    <div class="page-wrapper has-sidebar">
        <aside class="docs-sidebar" id="docsSidebar">
            <button class="sidebar-close-btn" onclick="closeSidebar()">
                <i class="bi bi-x-lg"></i> Tutup
            </button>
            @yield('sidebar')
        </aside>
        <div class="page-main">
            <div class="page-main-content">
                @yield('content')
            </div>
            <footer class="docs-footer">
                <span>API SISKA <strong>v1.0</strong> &bull; {{ date('Y') }} &bull; SIAKAD Kedokteran</span>
                <span>{{ url('/api') }}</span>
            </footer>
        </div>
    </div>
    @else
    <div class="page-wrapper">
        <div class="page-main">
            <div class="page-main-content">
                @yield('content')
            </div>
            <footer class="docs-footer">
                <span>API SISKA <strong>v1.0</strong> &bull; {{ date('Y') }} &bull; SIAKAD Kedokteran</span>
                <span>{{ url('/api') }}</span>
            </footer>
        </div>
    </div>
    @endif

    {{-- ─── Mobile bottom navigation ─── --}}
    <nav class="mobile-bottom-nav">
        <a href="{{ route('admin.docs') }}" class="{{ request()->routeIs('docs','admin.docs') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-code"></i>
            <span>Docs</span>
        </a>
        <a href="{{ route('admin.tester') }}" class="{{ request()->routeIs('tester','admin.tester') ? 'active' : '' }}">
            <i class="bi bi-send"></i>
            <span>Tester</span>
        </a>
        @auth
        <a href="{{ route('admin.dashboard') }}" class="">
            <i class="bi bi-grid-3x3-gap-fill"></i>
            <span>Admin</span>
        </a>
        @else
        <a href="{{ route('login') }}" class="">
            <i class="bi bi-shield-lock"></i>
            <span>Login</span>
        </a>
        @endauth
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('docs/layouts.js') }}"></script>
    @stack('scripts')
</body>

</html>