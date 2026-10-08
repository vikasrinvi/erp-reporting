@extends('layouts.app')

@section('title', 'Saved Report Queries')

@section('content')

<style>

    .query-page {
        background: #f5f7fb;
        min-height: calc(100vh - 60px);
        padding: 28px;
    }

    .page-header {
        background: linear-gradient(
            135deg,
            #172033 0%,
            #273b63 100%
        );
        border-radius: 16px;
        padding: 24px 26px;
        color: #fff;
        box-shadow: 0 8px 25px rgba(23, 32, 51, .15);
    }

    .page-header h2 {
        margin: 0;
        font-weight: 700;
    }

    .page-header p {
        margin: 5px 0 0;
        opacity: .75;
    }

    .stat-card {
        background: #fff;
        border-radius: 14px;
        padding: 18px;
        border: 1px solid #e8ecf3;
        height: 100%;
        box-shadow: 0 4px 14px rgba(30, 45, 70, .05);
    }

    .stat-label {
        color: #6b7280;
        font-size: 13px;
        font-weight: 600;
    }

    .stat-value {
        font-size: 26px;
        font-weight: 700;
        color: #172033;
        margin-top: 3px;
    }

    .filter-card,
    .table-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #e8ecf3;
        box-shadow: 0 4px 14px rgba(30, 45, 70, .05);
    }

    .filter-card {
        padding: 18px;
    }

    .table-card {
        overflow: hidden;
    }

    .table-card .table {
        margin: 0;
    }

    .table thead th {
        background: #f8fafc;
        color: #596579;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .04em;
        font-weight: 700;
        padding: 14px 16px;
        border-bottom: 1px solid #e7ebf2;
        white-space: nowrap;
    }

    .table tbody td {
        padding: 15px 16px;
        vertical-align: middle;
        border-color: #edf0f5;
    }

    .query-name {
        font-weight: 700;
        color: #172033;
    }

    .report-name {
        color: #566176;
        font-size: 13px;
        margin-top: 3px;
    }

    .mode-badge,
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border-radius: 20px;
        padding: 5px 10px;
        font-size: 11px;
        font-weight: 700;
    }

    .mode-builder {
        background: #eef4ff;
        color: #315da8;
    }

    .mode-custom {
        background: #f3efff;
        color: #6941c6;
    }

    .status-active {
        background: #eaf8ef;
        color: #198754;
    }

    .status-inactive {
        background: #fbecec;
        color: #c0392b;
    }

    .action-btn {
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }

    .empty-state {
        padding: 65px 20px;
        text-align: center;
        color: #687386;
    }

    .empty-state i {
        font-size: 44px;
        opacity: .35;
    }

    .pagination-wrapper {
        padding: 16px;
        border-top: 1px solid #edf0f5;
    }

    .form-label {
        font-size: 12px;
        font-weight: 700;
        color: #566176;
    }

    @media (max-width: 768px) {

        .query-page {
            padding: 15px;
        }

        .page-header {
            padding: 20px;
        }

        .table-card {
            overflow-x: auto;
        }

    }

</style>

<div class="query-page">

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="page-header mb-4">

        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">

            <div>

                <h2>
                    <i class="bi bi-database-gear me-2"></i>
                    Saved Report Queries
                </h2>

                <p>
                    Manage, edit and execute your saved report queries.
                </p>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- FLASH MESSAGES --}}
    {{-- ========================================================= --}}

    @if(session('success'))

        <div class="alert alert-success alert-dismissible fade show">

            <i class="bi bi-check-circle me-2"></i>

            {{ session('success') }}

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    @endif


    @if(session('error'))

        <div class="alert alert-danger alert-dismissible fade show">

            <i class="bi bi-exclamation-triangle me-2"></i>

            {{ session('error') }}

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- STATISTICS --}}
    {{-- ========================================================= --}}

    <div class="row g-3 mb-4">

        <div class="col-md-4">

            <div class="stat-card">

                <div class="stat-label">
                    Total Queries
                </div>

                <div class="stat-value">
                    {{ $queries->total() }}
                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="stat-card">

                <div class="stat-label">
                    Current Page
                </div>

                <div class="stat-value">
                    {{ $queries->count() }}
                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="stat-card">

                <div class="stat-label">
                    Showing
                </div>

                <div class="stat-value">

                    @if($queries->total() > 0)

                        {{ $queries->firstItem() }}
                        -
                        {{ $queries->lastItem() }}

                    @else

                        0

                    @endif

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- FILTERS --}}
    {{-- ========================================================= --}}

    <div class="filter-card mb-4">

        <form
            method="GET"
            action="{{ route('report-queries.index') }}"
        >

            <div class="row g-3 align-items-end">

                {{-- Search --}}

                <div class="col-lg-4 col-md-6">

                    <label class="form-label">
                        Search
                    </label>

                    <div class="input-group">

                        <span class="input-group-text bg-white">
                            <i class="bi bi-search"></i>
                        </span>

                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            class="form-control"
                            placeholder="Query name or report name..."
                        >

                    </div>

                </div>


                {{-- Report --}}

                <div class="col-lg-3 col-md-6">

                    <label class="form-label">
                        Report
                    </label>

                    <select
                        name="report_id"
                        class="form-select"
                    >

                        <option value="">
                            All Reports
                        </option>

                        @foreach($reports as $report)

                            <option
                                value="{{ $report->id }}"
                                @selected(
                                    (string) request('report_id')
                                    ===
                                    (string) $report->id
                                )
                            >
                                {{ $report->report_name }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- Mode --}}

                <div class="col-lg-2 col-md-6">

                    <label class="form-label">
                        Query Mode
                    </label>

                    <select
                        name="query_mode"
                        class="form-select"
                    >

                        <option value="">
                            All Modes
                        </option>

                        <option
                            value="builder"
                            @selected(
                                request('query_mode') === 'builder'
                            )
                        >
                            Visual Builder
                        </option>

                        <option
                            value="custom"
                            @selected(
                                request('query_mode') === 'custom'
                            )
                        >
                            Custom SQL
                        </option>

                    </select>

                </div>


                {{-- Status --}}

                <div class="col-lg-2 col-md-6">

                    <label class="form-label">
                        Status
                    </label>

                    <select
                        name="status"
                        class="form-select"
                    >

                        <option value="">
                            All Status
                        </option>

                        <option
                            value="active"
                            @selected(
                                request('status') === 'active'
                            )
                        >
                            Active
                        </option>

                        <option
                            value="inactive"
                            @selected(
                                request('status') === 'inactive'
                            )
                        >
                            Inactive
                        </option>

                    </select>

                </div>


                {{-- Buttons --}}

                <div class="col-lg-1 col-md-6 d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary w-100"
                        title="Apply filters"
                    >
                        <i class="bi bi-funnel"></i>
                    </button>

                    <a
                        href="{{ route('report-queries.index') }}"
                        class="btn btn-light border"
                        title="Clear filters"
                    >
                        <i class="bi bi-x-lg"></i>
                    </a>

                </div>

            </div>

        </form>

    </div>


    {{-- ========================================================= --}}
    {{-- TABLE --}}
    {{-- ========================================================= --}}

    <div class="table-card">

        @if($queries->count())

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>

                            <th style="width: 30px;">
                                #
                            </th>

                            <th>
                                Query
                            </th>

                            <th>
                                Report
                            </th>

                            <th>
                                Mode
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Last Updated
                            </th>

                            <th class="text-end">
                                Actions
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @foreach($queries as $savedQuery)

                            <tr>

                                <td class="text-muted">
                                    {{ $queries->firstItem() + $loop->index }}
                                </td>


                                {{-- Query --}}

                                <td>

                                    <div class="query-name">

                                        {{ $savedQuery->query_name }}

                                    </div>

                                    <div class="text-muted small">

                                        Query #{{ $savedQuery->id }}

                                    </div>

                                </td>


                                {{-- Report --}}

                                <td>

                                    @if($savedQuery->report)

                                        <div class="report-name">

                                            <i class="bi bi-file-earmark-bar-graph me-1"></i>

                                            {{ $savedQuery->report->name }}

                                        </div>

                                    @else

                                        <span class="text-muted">
                                            Report unavailable
                                        </span>

                                    @endif

                                </td>


                                {{-- Mode --}}

                                <td>

                                    @if($savedQuery->query_mode === 'custom')

                                        <span class="mode-badge mode-custom">

                                            <i class="bi bi-code-slash"></i>

                                            Custom SQL

                                        </span>

                                    @else

                                        <span class="mode-badge mode-builder">

                                            <i class="bi bi-diagram-3"></i>

                                            Builder

                                        </span>

                                    @endif

                                </td>


                                {{-- Status --}}

                                <td>

                                    @if($savedQuery->is_active)

                                        <span class="status-badge status-active">

                                            <i class="bi bi-check-circle"></i>

                                            Active

                                        </span>

                                    @else

                                        <span class="status-badge status-inactive">

                                            <i class="bi bi-x-circle"></i>

                                            Inactive

                                        </span>

                                    @endif

                                </td>


                                {{-- Updated --}}

                                <td>

                                    <div class="small fw-semibold">

                                        {{ $savedQuery->updated_at?->format('d M Y') }}

                                    </div>

                                    <div class="text-muted small">

                                        {{ $savedQuery->updated_at?->format('h:i A') }}

                                    </div>

                                </td>


                                {{-- Actions --}}

                                <td class="text-end">

                                    <div class="d-flex justify-content-end gap-2">

                                        {{-- Query Builder --}}

                                        @if($savedQuery->report)

                                            <a
                                                href="{{ route(
                                                    'reports.library.query-builder',
                                                    $savedQuery->report
                                                ) }}"
                                                class="btn btn-sm btn-outline-primary action-btn"
                                                title="Open query builder"
                                            >

                                                <i class="bi bi-pencil-square me-1"></i>

                                                Query Builder

                                            </a>

                                        @endif


                                        {{-- View Report --}}

                                        <a
                                            href="{{ route(
                                                'report-queries.report',
                                                $savedQuery
                                            ) }}"
                                            class="btn btn-sm btn-primary action-btn"
                                            title="Execute query and view report"
                                        >

                                            <i class="bi bi-bar-chart-line me-1"></i>

                                            View Report

                                        </a>

                                    </div>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>


            {{-- Pagination --}}

            <div class="pagination-wrapper">

                {{ $queries->links() }}

            </div>

        @else

            <div class="empty-state">

                <i class="bi bi-database-x"></i>

                <h5 class="mt-3">
                    No saved queries found
                </h5>

                <p class="mb-0">
                    Try changing your filters or create a new report query.
                </p>

            </div>

        @endif

    </div>

</div>

@endsection