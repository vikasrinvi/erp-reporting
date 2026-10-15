@extends('layouts.app')

@section('content')

    <style>

        body {
            background: #f4f6f9;
            color: #212529;
            font-family:
                Inter,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
        }

        .page-header {
            background: linear-gradient(
                135deg,
                #172033,
                #273b63
            );

            color: white;

            padding: 28px 0;

            margin-bottom: 24px;
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .page-subtitle {
            color: rgba(255,255,255,.7);
            font-size: 14px;
        }

        .report-card {
            border: 0;
            border-radius: 14px;

            box-shadow:
                0 3px 15px rgba(0,0,0,.06);
        }

        .filter-card {
            margin-top: -45px;
            position: relative;
            z-index: 2;
        }

        .form-label {
            font-size: 12px;
            font-weight: 700;
            color: #495057;

            text-transform: uppercase;

            letter-spacing: .3px;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            min-height: 42px;
            border-color: #dee2e6;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 .2rem rgba(102,126,234,.12);
        }

        .division-box {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px 12px;
            background: #fff;
        }

        .division-check {
            margin-right: 16px;
            margin-bottom: 5px;
        }

        .division-check label {
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-main {
            min-height: 42px;
            border-radius: 8px;
            font-weight: 600;
        }

        .summary-card {
            border-radius: 12px;
            border: 0;

            box-shadow:
                0 2px 10px rgba(0,0,0,.04);
        }

        .summary-number {
            font-size: 22px;
            font-weight: 700;
        }

        .summary-label {
            color: #6c757d;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .table-container {
            overflow-x: auto;
            max-height: 68vh;

            border-radius: 10px;

            border: 1px solid #e9ecef;
        }

        .report-table {
            margin-bottom: 0;
            white-space: nowrap;
            font-size: 13px;
        }

        .report-table thead {
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .report-table thead th {
            background: #202a3c;
            color: white;

            text-align: center;

            padding: 10px 12px;

            border-color: #39455c;

            font-size: 12px;
        }

        .report-table thead th.item-column {
            text-align: left;
        }

        .report-table tbody td {
            padding: 9px 12px;
            vertical-align: middle;
            border-color: #edf0f2;
        }

        .report-table tbody tr:hover {
            background: #f8f9ff;
        }

        .item-code {
            font-weight: 700;
            color: #273b63;
        }

        .item-name {
            min-width: 230px;
        }

        .number-cell {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .month-header {
            background: #2d3d5c !important;
            font-weight: 700 !important;
        }

        .total-row {
            position: sticky;
            bottom: 0;

            background: #eef2f7 !important;

            font-weight: 700;

            z-index: 4;
        }

        .total-row td {
            border-top: 2px solid #adb5bd !important;
        }

        .badge-asv {
            background: #e8f0ff;
            color: #3159a5;

            padding: 5px 9px;

            border-radius: 20px;

            font-size: 11px;
            font-weight: 700;
        }

        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 42px;
            margin-bottom: 12px;
        }

        .report-footer {
            font-size: 12px;
            color: #6c757d;
        }

        .loading-overlay {
            display: none;

            position: fixed;

            inset: 0;

            background: rgba(255,255,255,.7);

            z-index: 9999;

            align-items: center;
            justify-content: center;
        }

        .loading-box {
            background: white;

            padding: 25px 35px;

            border-radius: 12px;

            box-shadow: 0 5px 30px rgba(0,0,0,.15);

            text-align: center;
        }

        @media(max-width: 768px) {

            .page-title {
                font-size: 20px;
            }

            .filter-card {
                margin-top: -30px;
            }

            .table-container {
                max-height: 60vh;
            }
        }

        @media print {

            body {
                background: white;
            }

            .page-header {
                background: white !important;
                color: black !important;

                padding: 10px 0;
            }

            .page-subtitle {
                color: #555 !important;
            }

            .filter-card,
            .summary-section,
            .no-print {
                display: none !important;
            }

            .report-card {
                box-shadow: none;
            }

            .table-container {
                overflow: visible;
                max-height: none;
                border: 0;
            }

            .report-table {
                font-size: 9px;
            }

            .report-table thead {
                position: static;
            }

            .report-table thead th {
                background: #eee !important;
                color: #000 !important;
            }

        }

    </style>

<!-- </head> -->

<!-- <body> -->

<!-- Loading -->
<div
    class="loading-overlay"
    id="loadingOverlay"
>
    <div class="loading-box">

        <div
            class="spinner-border text-primary mb-3"
            role="status"
        ></div>

        <div class="fw-semibold">
            Generating report...
        </div>

    </div>
</div>


<!-- HEADER -->


<div class="page-header">

    <div class="container-fluid px-4">

        <div class="d-flex justify-content-between align-items-center">

            <div>

                <div class="page-title">

                    <i class="bi bi-bar-chart-line me-2"></i>

                    ASV Item Issue Report 

                </div>

                <div class="page-subtitle">

                    Monthly division-wise item issue report

                </div>

            </div>

            <div class="d-none d-md-block">

                <span class="badge bg-light text-dark px-3 py-2">

                    Marketing Category: ASV

                </span>

            </div>

        </div>

    </div>

</div>


<div class="container-fluid px-4">

    <!-- FILTER CARD -->

    <div class="card report-card filter-card mb-4">

        <div class="card-body p-4">

            <form
                method="GET"
                action="{{ route('reports.asv') }}"
                id="reportForm"
            >

                <div class="row g-3 align-items-end">

                    <!-- FROM -->

                    <div class="col-md-2">

                        <label class="form-label">
                            From Month
                        </label>

                        <input
                            type="month"
                            name="from"
                            value="{{ $from }}"
                            class="form-control"
                            required
                        >

                    </div>


                    <!-- TO -->

                    <div class="col-md-2">

                        <label class="form-label">
                            To Month
                        </label>

                        <input
                            type="month"
                            name="to"
                            value="{{ $to }}"
                            class="form-control"
                            required
                        >

                    </div>


                    <!-- SEARCH -->

                    <div class="col-md-4">

                        <label class="form-label">
                            Search Item
                        </label>

                        <div class="input-group">

                            <span class="input-group-text bg-white">
                                <i class="bi bi-search"></i>
                            </span>

                            <input
                                type="text"
                                name="search"
                                value="{{ $search }}"
                                class="form-control"
                                placeholder="Item code or item name..."
                            >

                        </div>

                    </div>


                    <!-- BUTTON -->

                    <div class="col-md-2">

                        <button
                            type="submit"
                            class="btn btn-primary btn-main w-100"
                        >

                            <i class="bi bi-funnel me-1"></i>

                            Generate

                        </button>

                    </div>


                    <!-- RESET -->

                    <div class="col-md-2">

                        <a
                            href="{{ route('reports.asv') }}"
                            class="btn btn-outline-secondary btn-main w-100"
                        >

                            <i class="bi bi-arrow-counterclockwise me-1"></i>

                            Reset

                        </a>

                    </div>

                </div>


                <!-- DIVISIONS -->

                <div class="mt-4">

                    <label class="form-label">
                        Divisions
                    </label>

                    <div class="division-box">

                        <div class="d-flex flex-wrap">

                            @foreach($divisions ?? ['AG','AH','AM','DL','GZ','HL','ZP','JH'] as $division)

                                <div class="form-check division-check">

                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="divisions[]"
                                        value="{{ $division }}"
                                        id="division_{{ $division }}"
                                        {{ in_array($division, $selectedDivisions) ? 'checked' : '' }}
                                    >

                                    <label
                                        class="form-check-label"
                                        for="division_{{ $division }}"
                                    >
                                        {{ $division }}
                                    </label>

                                </div>

                            @endforeach

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <!-- SUMMARY -->

    <div class="row g-3 mb-4 summary-section">

        <div class="col-md-3">

            <div class="card summary-card">

                <div class="card-body">

                    <div class="summary-label">
                        Items
                    </div>

                    <div class="summary-number">
                        {{ number_format($rows->count()) }}
                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="card summary-card">

                <div class="card-body">

                    <div class="summary-label">
                        Period
                    </div>

                    <div class="summary-number fs-6 mt-2">

                        {{ strtoupper(\Carbon\Carbon::createFromFormat('Y-m', $from)->format('M Y')) }}

                        -

                        {{ strtoupper(\Carbon\Carbon::createFromFormat('Y-m', $to)->format('M Y')) }}

                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="card summary-card">

                <div class="card-body">

                    <div class="summary-label">
                        Divisions
                    </div>

                    <div class="summary-number fs-6 mt-2">

                        {{ implode(', ', $selectedDivisions) }}

                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-3">

            <div class="card summary-card">

                <div class="card-body">

                    <div class="summary-label">
                        Category
                    </div>

                    <div class="summary-number">

                        <span class="badge-asv">
                            ASV
                        </span>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- REPORT -->

    <div class="card report-card mb-4">

        <div class="card-body p-0">


            <!-- REPORT TOOLBAR -->

            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">

                <div>

                    <h6 class="mb-1 fw-bold">
                        Item Issue Details
                    </h6>

                    <div class="report-footer">

                        Showing
                        <strong>{{ number_format($rows->count()) }}</strong>
                        items

                    </div>

                </div>


                <div class="d-flex gap-2 no-print">

                    <!-- CSV -->

                    <a
                        href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
                        class="btn btn-outline-success btn-sm"
                    >

                        <i class="bi bi-file-earmark-spreadsheet me-1"></i>

                        CSV

                    </a>


                    <!-- PRINT -->

                    <button
                        type="button"
                        onclick="window.print()"
                        class="btn btn-outline-dark btn-sm"
                    >

                        <i class="bi bi-printer me-1"></i>

                        Print

                    </button>

                </div>

            </div>


            <!-- TABLE -->

            <div class="table-container">

                @if($rows->count() > 0)

                    <table class="table table-hover report-table">

                        <thead>

                            <!-- MONTH GROUP -->

                            <tr>

                                <th
                                    rowspan="2"
                                    class="item-column"
                                >
                                    Item Code
                                </th>

                                <th
                                    rowspan="2"
                                    class="item-column"
                                >
                                    Item Name
                                </th>

                                <th
                                    rowspan="2"
                                >
                                    Category
                                </th>

                                @foreach($months as $month)

                                    <th
                                        colspan="{{ count($selectedDivisions) }}"
                                        class="month-header"
                                    >

                                        {{ $month['label'] }}

                                    </th>

                                @endforeach

                            </tr>


                            <!-- DIVISION -->

                            <tr>

                                @foreach($months as $month)

                                    @foreach($selectedDivisions as $division)

                                        <th>
                                            {{ $division }}
                                        </th>

                                    @endforeach

                                @endforeach

                            </tr>

                        </thead>


                        <tbody>

                            @foreach($rows as $row)

                                <tr>

                                    <td class="item-code">

                                        {{ $row->ITEM_CODE }}

                                    </td>


                                    <td class="item-name">

                                        {{ $row->ITEM_NAME }}

                                    </td>


                                    <td class="text-center">

                                        <span class="badge-asv">

                                            {{ $row->MKTG_CATG }}

                                        </span>

                                    </td>


                                    @foreach($months as $month)

                                        @foreach($selectedDivisions as $division)

                                            @php

                                                $alias = strtolower(
                                                    $month['short'] . '_' . $division
                                                );

                                                $value = (float) ($row->$alias ?? 0);

                                            @endphp

                                            <td class="number-cell">

                                                {{ number_format($value, 0) }}

                                            </td>

                                        @endforeach

                                    @endforeach

                                </tr>

                            @endforeach


                            <!-- TOTAL -->

                            <tr class="total-row">

                                <td colspan="3">

                                    TOTAL

                                </td>


                                @foreach($months as $month)

                                    @foreach($selectedDivisions as $division)

                                        @php

                                            $alias = strtolower(
                                                $month['short'] . '_' . $division
                                            );

                                        @endphp

                                        <td class="number-cell">

                                            {{ number_format($totals[$alias] ?? 0, 0) }}

                                        </td>

                                    @endforeach

                                @endforeach

                            </tr>

                        </tbody>

                    </table>

                @else

                    <div class="empty-state">

                        <i class="bi bi-inbox"></i>

                        <h5>
                            No data found
                        </h5>

                        <p class="mb-0">

                            Try changing the date range,
                            search term or divisions.

                        </p>

                    </div>

                @endif

            </div>


            <!-- FOOTER -->

            <div class="p-3 border-top report-footer">

                <i class="bi bi-info-circle me-1"></i>

                Report generated for

                <strong>ASV</strong>

                marketing category.

                Period:

                <strong>
                    {{ $from }} to {{ $to }}
                </strong>

            </div>

        </div>

    </div>

</div>


<script>

    document
        .getElementById('reportForm')
        .addEventListener('submit', function () {

            document
                .getElementById('loadingOverlay')
                .style.display = 'flex';

        });

</script>

@endsection