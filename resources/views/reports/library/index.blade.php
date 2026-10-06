@extends('layouts.app')

@section('content')

<style>

    .library-page {
        background: #f5f7fb;
        min-height: calc(100vh - 70px);
        padding: 24px;
    }

    .library-header {
        background: linear-gradient(135deg, #172033, #273b63);
        color: white;
        border-radius: 18px;
        padding: 28px 30px;
        margin-bottom: 24px;
        box-shadow: 0 10px 30px rgba(23, 32, 51, .15);
    }

    .library-header h2 {
        font-weight: 700;
        margin-bottom: 5px;
    }

    .library-header p {
        margin: 0;
        opacity: .75;
    }

    .summary-card {
        border: 0;
        border-radius: 15px;
        background: white;
        padding: 20px;
        height: 100%;
        box-shadow: 0 5px 20px rgba(0,0,0,.05);
    }

    .summary-icon {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #eef2f8;
        font-size: 20px;
        margin-bottom: 12px;
    }

    .summary-number {
        font-size: 26px;
        font-weight: 700;
        color: #172033;
    }

    .summary-label {
        color: #6c757d;
        font-size: 14px;
    }

    .filter-card {
        background: white;
        border: 0;
        border-radius: 15px;
        padding: 18px;
        margin-bottom: 24px;
        box-shadow: 0 5px 20px rgba(0,0,0,.05);
    }

    .report-card {
        background: white;
        border: 0;
        border-radius: 17px;
        height: 100%;
        padding: 22px;
        box-shadow: 0 5px 20px rgba(0,0,0,.05);
        transition: .2s ease;
    }

    .report-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 30px rgba(0,0,0,.09);
    }

    .report-code {
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .5px;
        color: #667085;
    }

    .report-title {
        font-size: 18px;
        font-weight: 700;
        color: #172033;
        margin-top: 7px;
    }

    .report-description {
        color: #667085;
        font-size: 14px;
        line-height: 1.5;
        min-height: 63px;
    }

    .meta-label {
        font-size: 11px;
        color: #98a2b3;
        text-transform: uppercase;
        font-weight: 600;
    }

    .meta-value {
        font-size: 13px;
        color: #344054;
        font-weight: 600;
    }

    .status-active {
        background: #e8f7ee;
        color: #18794e;
    }

    .status-inactive {
        background: #fceaea;
        color: #b42318;
    }

    .status-badge {
        padding: 5px 10px;
        border-radius: 30px;
        font-size: 11px;
        font-weight: 700;
    }

    .module-badge {
        background: #eef2ff;
        color: #344054;
        border-radius: 30px;
        padding: 5px 10px;
        font-size: 11px;
        font-weight: 600;
    }

    .empty-state {
        background: white;
        border-radius: 17px;
        padding: 70px 20px;
        text-align: center;
    }

</style>


<div class="library-page">

    {{-- Header --}}
    <div class="library-header">

        <div class="d-flex justify-content-between align-items-center gap-3">

            <div>
                <h2>
                    <i class="bi bi-journal-text me-2"></i>
                    Report Library
                </h2>

                <p>
                    Central documentation and catalogue of ERP reports
                </p>
            </div>

            <a href="{{ route('reports.library.create') }}"
               class="btn btn-light fw-semibold">

                <i class="bi bi-plus-lg me-1"></i>
                Add Report

            </a>

        </div>

    </div>


    {{-- Summary --}}
    <div class="row g-3 mb-4">

        <div class="col-md-3">

            <div class="summary-card">

                <div class="summary-icon">
                    <i class="bi bi-file-earmark-bar-graph"></i>
                </div>

                <div class="summary-number">
                    {{ $totalReports }}
                </div>

                <div class="summary-label">
                    Total Reports
                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="summary-card">

                <div class="summary-icon">
                    <i class="bi bi-check-circle"></i>
                </div>

                <div class="summary-number">
                    {{ $activeReports }}
                </div>

                <div class="summary-label">
                    Active Reports
                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="summary-card">

                <div class="summary-icon">
                    <i class="bi bi-pause-circle"></i>
                </div>

                <div class="summary-number">
                    {{ $inactiveReports }}
                </div>

                <div class="summary-label">
                    Inactive Reports
                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="summary-card">

                <div class="summary-icon">
                    <i class="bi bi-grid"></i>
                </div>

                <div class="summary-number">
                    {{ $moduleCount }}
                </div>

                <div class="summary-label">
                    Modules
                </div>

            </div>

        </div>

    </div>


    {{-- Filters --}}
    <div class="filter-card">

        <form method="GET"
              action="{{ route('reports.library') }}">

            <div class="row g-3 align-items-end">

                <div class="col-md-5">

                    <label class="form-label fw-semibold">
                        Search
                    </label>

                    <div class="input-group">

                        <span class="input-group-text bg-white">
                            <i class="bi bi-search"></i>
                        </span>

                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               class="form-control"
                               placeholder="Report name, code, module...">

                    </div>

                </div>


                <div class="col-md-3">

                    <label class="form-label fw-semibold">
                        Module
                    </label>

                    <select name="module"
                            class="form-select">

                        <option value="">
                            All Modules
                        </option>

                        @foreach($modules as $module)

                            <option value="{{ $module }}"
                                @selected(request('module') == $module)>

                                {{ $module }}

                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="col-md-2">

                    <label class="form-label fw-semibold">
                        Status
                    </label>

                    <select name="status"
                            class="form-select">

                        <option value="">
                            All
                        </option>

                        <option value="active"
                            @selected(request('status') == 'active')>
                            Active
                        </option>

                        <option value="inactive"
                            @selected(request('status') == 'inactive')>
                            Inactive
                        </option>

                    </select>

                </div>


                <div class="col-md-2">

                    <button class="btn btn-dark w-100">

                        <i class="bi bi-funnel me-1"></i>
                        Filter

                    </button>

                </div>

            </div>

        </form>

    </div>


    {{-- Success --}}
    @if(session('success'))

        <div class="alert alert-success border-0 shadow-sm">
            <i class="bi bi-check-circle me-2"></i>
            {{ session('success') }}
        </div>

    @endif


    {{-- Reports --}}
    <div class="row g-4">

        @forelse($reports as $report)

            <div class="col-xl-4 col-lg-6">

                <div class="report-card">

                    <div class="d-flex justify-content-between align-items-start">

                        <div class="report-code">
                            {{ $report->report_code }}
                        </div>

                        <span class="status-badge
                            {{ $report->status === 'active'
                                ? 'status-active'
                                : 'status-inactive' }}">

                            {{ ucfirst($report->status) }}

                        </span>

                    </div>


                    <div class="report-title">
                        {{ $report->report_name }}
                    </div>


                    <div class="mt-2 mb-3">

                        @if($report->module)

                            <span class="module-badge">
                                {{ $report->module }}
                            </span>

                        @endif

                        @if($report->sub_module)

                            <span class="module-badge">
                                {{ $report->sub_module }}
                            </span>

                        @endif

                    </div>


                    <div class="report-description">

                        {{ \Illuminate\Support\Str::limit(
                            $report->description,
                            150
                        ) }}

                    </div>


                    <hr>


                    <div class="row g-3">

                        <div class="col-6">

                            <div class="meta-label">
                                Report Type
                            </div>

                            <div class="meta-value">
                                {{ $report->report_type ?: '—' }}
                            </div>

                        </div>


                        <div class="col-6">

                            <div class="meta-label">
                                Main Table
                            </div>

                            <div class="meta-value text-truncate">
                                {{ $report->main_table ?: '—' }}
                            </div>

                        </div>


                        <div class="col-12">

                            <div class="meta-label">
                                Output
                            </div>

                            <div class="meta-value">
                                {{ $report->output_types ?: '—' }}
                            </div>

                        </div>

                    </div>


                    <div class="d-flex gap-2 mt-4">

                        <a href="{{ route(
                            'reports.library.show',
                            $report
                        ) }}"
                           class="btn btn-outline-dark btn-sm flex-fill">

                            <i class="bi bi-eye me-1"></i>
                            Details

                        </a>


                        @if($report->route)

                            <a href="{{ url($report->route) }}"
                               class="btn btn-dark btn-sm flex-fill">

                                <i class="bi bi-box-arrow-up-right me-1"></i>
                                Open Report

                            </a>

                        @endif

                    </div>

                </div>

            </div>

        @empty

            <div class="col-12">

                <div class="empty-state">

                    <i class="bi bi-file-earmark-x fs-1 text-muted"></i>

                    <h4 class="mt-3">
                        No Reports Found
                    </h4>

                    <p class="text-muted">
                        Try changing your search or filters.
                    </p>

                    <a href="{{ route('reports.library.create') }}"
                       class="btn btn-dark">

                        <i class="bi bi-plus-lg me-1"></i>
                        Add First Report

                    </a>

                </div>

            </div>

        @endforelse

    </div>


    @if($reports->hasPages())

        <div class="mt-4">
            {{ $reports->links() }}
        </div>

    @endif

</div>

@endsection