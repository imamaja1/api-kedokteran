@extends('admin.layout')
@section('title', 'Users')
@section('page-title', 'Manajemen Users')

@section('content')
<div class="card">
    <div class="card-header-custom d-flex justify-content-between align-items-center">
        <span><i class="bi bi-people-fill me-2"></i>Daftar User</span>
        <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-light">
            <i class="bi bi-plus-lg me-1"></i>Tambah User
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th class="ps-3 d-none d-sm-table-cell" style="width:50px">#</th>
                    <th>Nama</th>
                    <th class="d-none d-md-table-cell">Email</th>
                    <th>Role</th>
                    <th class="d-none d-md-table-cell">Dibuat</th>
                    <th style="width:110px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td class="ps-3 text-muted d-none d-sm-table-cell">{{ $user->id }}</td>
                    <td>
                        <div class="fw-semibold">
                            {{ $user->name }}
                            @if($user->id === auth()->id())
                            <span class="badge bg-success ms-1">Anda</span>
                            @endif
                        </div>
                        <div class="d-md-none text-muted small">{{ $user->email }}</div>
                    </td>
                    <td class="d-none d-md-table-cell text-muted">{{ $user->email }}</td>
                    <td>
                        <span class="badge {{ $user->role === 'admin' ? 'bg-danger' : 'bg-primary' }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td class="d-none d-md-table-cell text-muted small">{{ $user->created_at->format('d M Y') }}</td>
                    <td>
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary me-1">
                            <i class="bi bi-pencil-fill"></i>
                        </a>
                        @if($user->id !== auth()->id())
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('Hapus user {{ $user->name }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection