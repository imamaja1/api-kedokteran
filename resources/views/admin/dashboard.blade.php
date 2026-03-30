@extends('admin.layout')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card p-3 d-flex flex-row align-items-center gap-3">
            <div
                style="width:48px;height:48px;border-radius:12px;background:#e8f0fe;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-collection-fill text-primary fs-5"></i>
            </div>
            <div>
                <div style="font-size:1.8rem;font-weight:800;color:#0d3b6e;line-height:1">{{ $stats['sections'] }}</div>
                <div class="text-muted small">Sections</div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card p-3 d-flex flex-row align-items-center gap-3">
            <div
                style="width:48px;height:48px;border-radius:12px;background:#e6f9f0;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-code-slash text-success fs-5"></i>
            </div>
            <div>
                <div style="font-size:1.8rem;font-weight:800;color:#0d3b6e;line-height:1">{{ $stats['endpoints'] }}
                </div>
                <div class="text-muted small">Endpoints</div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card p-3 d-flex flex-row align-items-center gap-3">
            <div
                style="width:48px;height:48px;border-radius:12px;background:#fff3e0;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-people-fill text-warning fs-5"></i>
            </div>
            <div>
                <div style="font-size:1.8rem;font-weight:800;color:#0d3b6e;line-height:1">{{ $stats['users'] }}</div>
                <div class="text-muted small">Users</div>
            </div>
        </div>
    </div>
</div>
@endsection