@extends('admin.layout')
@section('title', 'Endpoints')
@section('page-title', 'Endpoints')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<style>
    /* Hide default DT search box */
    div.dataTables_wrapper div.dataTables_filter {
        display: none;
    }

    table.dataTable thead th {
        white-space: nowrap;
    }

    /* Info text */
    div.dataTables_wrapper div.dataTables_info {
        font-size: .82rem;
        color: #6c757d;
        padding: 0;
        margin: 0;
        white-space: nowrap;
    }

    /* Bottom bar layout */
    .dt-bottom-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .75rem;
        padding: .85rem 1.2rem;
        border-top: 1px solid #e9ecef;
        background: #f8f9fb;
        border-radius: 0 0 12px 12px;
    }

    .dt-bottom-left {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
    }

    /* ── Pagination: Bootstrap 5 native ── */
    div.dataTables_wrapper div.dataTables_paginate {
        margin: 0;
        padding: 0;
    }

    div.dataTables_wrapper .dataTables_paginate .pagination {
        margin: 0;
        font-size: .82rem;
    }

    /* Active page – brand color */
    div.dataTables_wrapper .dataTables_paginate .page-item.active .page-link {
        background-color: #0d3b6e;
        border-color: #0d3b6e;
        color: #fff;
    }

    /* Hover – non-active, non-disabled */
    div.dataTables_wrapper .dataTables_paginate .page-item:not(.active):not(.disabled) .page-link:hover {
        background-color: #e7eef7;
        border-color: #bdd0ea;
        color: #0d3b6e;
    }

    /* Focus ring */
    div.dataTables_wrapper .dataTables_paginate .page-link:focus {
        box-shadow: 0 0 0 .2rem rgba(13, 59, 110, .2);
        outline: none;
    }

    /* Disabled */
    div.dataTables_wrapper .dataTables_paginate .page-item.disabled .page-link {
        color: #adb5bd;
        background-color: #fff;
    }
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-header-custom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-code-slash me-2"></i>Daftar Endpoint</span>
        <a href="{{ route('admin.endpoints.create') }}" class="btn btn-sm btn-light">
            <i class="bi bi-plus-lg me-1"></i>Tambah Endpoint
        </a>
    </div>

    {{-- Filter bar --}}
    <div class="px-3 py-2 border-bottom bg-light d-flex flex-wrap gap-2 align-items-center">
        <select id="filterSection" class="form-select form-select-sm w-auto">
            <option value="">Semua Section</option>
            @foreach($sections as $sec)
            <option value="{{ $sec->title }}">{{ $sec->title }}</option>
            @endforeach
        </select>
        <select id="filterMethod" class="form-select form-select-sm w-auto">
            <option value="">Semua Method</option>
            @foreach(['GET','POST','PUT','PATCH','DELETE'] as $m)
            <option value="{{ $m }}">{{ $m }}</option>
            @endforeach
        </select>
        <div class="input-group input-group-sm flex-grow-1" style="min-width:180px">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="searchInput" class="form-control" placeholder="Cari endpoint, URL...">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle" id="endpointsTable">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Endpoint</th>
                    <th class="d-none d-md-table-cell">Section</th>
                    <th>Method</th>
                    <th class="d-none d-lg-table-cell">URL</th>
                    <th class="d-none d-md-table-cell">Urutan</th>
                    <th style="width:110px" data-orderable="false">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($endpoints as $ep)
                <tr>
                    <td class="ps-3">
                        <div class="fw-semibold">{{ $ep->title }}</div>
                        @if($ep->description)
                        <div class="text-muted small text-truncate" style="max-width:220px">{{ $ep->description }}</div>
                        @endif
                        <div class="d-lg-none text-muted small font-monospace text-truncate mt-1"
                            style="max-width:220px">{{ $ep->url }}</div>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <span class="badge bg-light text-dark border">{{ $ep->section->title ?? '-' }}</span>
                    </td>
                    <td><span class="method-badge m-{{ $ep->method }}">{{ $ep->method }}</span></td>
                    <td class="d-none d-lg-table-cell text-muted small font-monospace" style="max-width:260px">
                        <span class="d-block text-truncate">{{ $ep->url }}</span>
                    </td>
                    <td class="d-none d-md-table-cell">{{ $ep->sort_order }}</td>
                    <td>
                        <a href="{{ route('admin.endpoints.edit', $ep) }}" class="btn btn-sm btn-outline-primary me-1">
                            <i class="bi bi-pencil-fill"></i>
                        </a>
                        <form action="{{ route('admin.endpoints.destroy', $ep) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('Hapus endpoint ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">Belum ada endpoint.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
    const table = $('#endpointsTable').DataTable({
        dom: 'rt<"dt-bottom-bar"<"dt-bottom-left"i> p>',
        language: {
            search:       '',
            lengthMenu:   'Tampilkan _MENU_ baris',
            info:         'Menampilkan _START_\u2013_END_ dari _TOTAL_ endpoint',
            infoFiltered: ' &mdash; filter dari _MAX_ total',
            infoEmpty:    'Tidak ada endpoint tersedia',
            zeroRecords:  '<div class="text-center py-3 text-muted"><i class="bi bi-search me-2"></i>Tidak ada endpoint yang cocok</div>',
            paginate: {
                first:    '&laquo;',
                last:     '&raquo;',
                next:     '&rsaquo;',
                previous: '&lsaquo;',
            }
        },
        pageLength: 15,
        lengthMenu: [10, 15, 25, 50, 100],
        order: [[4, 'asc']],
        columnDefs: [
            { orderable: false, targets: 5 },
        ],
        drawCallback: function () {
            // Tambah kelas Bootstrap sm ke pagination
            $(this.api().table().container())
                .find('.pagination')
                .addClass('pagination-sm');
        },
    });
    // Search input → DataTables global search
    document.getElementById('searchInput').addEventListener('keyup', function () {
        table.search(this.value).draw();
    });
    // Filter by Section (column 1)
    document.getElementById('filterSection').addEventListener('change', function () {
        table.column(1).search(this.value, false, false).draw();
    });
    // Filter by Method (column 2) — exact match
    document.getElementById('filterMethod').addEventListener('change', function () {
        table.column(2).search(this.value ? '^' + this.value + '$' : '', true, false).draw();
    });
});
</script>
@endpush