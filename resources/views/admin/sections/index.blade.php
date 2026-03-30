@extends('admin.layout')
@section('title', 'Sections')
@section('page-title', 'Sections')

@section('content')
<div class="card">
    <div class="card-header-custom d-flex justify-content-between align-items-center">
        <span><i class="bi bi-collection-fill me-2"></i>Daftar Section</span>
        <a href="{{ route('admin.sections.create') }}" class="btn btn-sm btn-light">
            <i class="bi bi-plus-lg me-1"></i>Tambah Section
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th class="ps-3 d-none d-sm-table-cell" style="width:60px">#</th>
                    <th>Judul Section</th>
                    <th class="d-none d-sm-table-cell">Endpoints</th>
                    <th class="d-none d-md-table-cell">Urutan</th>
                    <th style="width:110px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sections as $section)
                <tr>
                    <td class="ps-3 text-muted d-none d-sm-table-cell">{{ $section->id }}</td>
                    <td class="fw-semibold">{{ $section->title }}</td>
                    <td class="d-none d-sm-table-cell">
                        <span class="badge bg-secondary">{{ $section->endpoints_count }}</span>
                    </td>
                    <td class="d-none d-md-table-cell">{{ $section->sort_order }}</td>
                    <td>
                        <a href="{{ route('admin.sections.edit', $section) }}"
                            class="btn btn-sm btn-outline-primary me-1">
                            <i class="bi bi-pencil-fill"></i>
                        </a>
                        <form action="{{ route('admin.sections.destroy', $section) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('Hapus section ini? Semua endpoint di dalamnya juga akan terhapus.')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">Belum ada section.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection