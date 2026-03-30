@extends('admin.layout')
@section('title', 'API Connections')
@section('page-title', 'API Connections')

@section('content')
<div class="card">
    <div class="card-header-custom d-flex justify-content-between align-items-center">
        <span><i class="bi bi-plug-fill me-2"></i>Daftar API Connections</span>
        <a href="{{ route('admin.connections.create') }}" class="btn btn-sm btn-light">
            <i class="bi bi-plus-lg me-1"></i>Tambah Koneksi
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th class="ps-3 d-none d-sm-table-cell" style="width:50px">#</th>
                    <th>Nama Koneksi</th>
                    <th class="d-none d-lg-table-cell">Base URL</th>
                    <th class="d-none d-md-table-cell">Username</th>
                    <th>Status</th>
                    <th class="d-none d-md-table-cell">Cookie</th>
                    <th style="width:110px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($connections as $connection)
                <tr>
                    <td class="ps-3 text-muted d-none d-sm-table-cell">{{ $connection->id }}</td>
                    <td>
                        <div class="fw-semibold">{{ $connection->name }}</div>
                        @if($connection->description)
                        <div class="text-muted small">{{ Str::limit($connection->description, 60) }}</div>
                        @endif
                        <div class="d-md-none text-muted small">{{ $connection->username ?? '—' }}</div>
                        <div class="d-md-none mt-1">
                            @if($connection->isCookieValid())
                            <span class="badge bg-info text-dark"><i class="bi bi-cookie me-1"></i>Cookie Valid</span>
                            @endif
                        </div>
                    </td>
                    <td class="d-none d-lg-table-cell text-muted small font-monospace" style="max-width:240px">
                        <span class="d-block text-truncate">{{ $connection->base_url }}</span>
                    </td>
                    <td class="d-none d-md-table-cell text-muted">{{ $connection->username ?? '—' }}</td>
                    <td>
                        @if($connection->is_active)
                        <span class="badge bg-success">Aktif</span>
                        @else
                        <span class="badge bg-secondary">Nonaktif</span>
                        @endif
                    </td>
                    <td class="d-none d-md-table-cell">
                        @if($connection->isCookieValid())
                        <span class="badge bg-info text-dark"><i class="bi bi-cookie me-1"></i>Valid</span>
                        @else
                        <span class="badge bg-warning text-dark"><i
                                class="bi bi-exclamation-circle me-1"></i>Kosong</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.connections.edit', $connection) }}"
                            class="btn btn-sm btn-outline-primary me-1">
                            <i class="bi bi-pencil-fill"></i>
                        </a>
                        <form action="{{ route('admin.connections.destroy', $connection) }}" method="POST"
                            class="d-inline" onsubmit="return confirm('Hapus koneksi {{ $connection->name }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-plug me-2"></i>Belum ada koneksi API yang ditambahkan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection