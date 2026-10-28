@extends('layouts.app')

@section('content')

    <style>
        body {
            background: #f4f6f9;
            color: #212529;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            overflow-x: hidden;
        }

        .container-fluid {
            max-width: 100%;
            overflow-x: hidden;
        }

        .page-header {
            background: linear-gradient(135deg, #172033, #273b63);
            color: white;
            padding: 24px 0;
            margin-bottom: 20px;
        }

        .page-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .page-subtitle {
            color: rgba(255, 255, 255, .7);
            font-size: 13px;
        }

        .report-card {
            border: 0;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, .05);
        }

        .filter-card {
            margin-top: -35px;
            position: relative;
            z-index: 2;
        }

        .form-label {
            font-size: 11px;
            font-weight: 700;
            color: #495057;
            text-transform: uppercase;
            letter-spacing: .3px;
            margin-bottom: 4px;
        }

        .form-control,
        .form-select {
            border-radius: 6px;
            min-height: 38px;
            font-size: 13px;
            border-color: #dee2e6;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 .2rem rgba(102, 126, 234, .12);
        }

        .btn-main {
            min-height: 38px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
        }

        .summary-card {
            border-radius: 10px;
            border: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
        }

        .summary-number {
            font-size: 20px;
            font-weight: 700;
        }

        .summary-label {
            color: #6c757d;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .table-container {
            width: 100%;
            overflow-x: auto;
            max-height: 72vh;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .report-table {
            width: 100%;
            margin-bottom: 0;
            font-size: 12px;
            white-space: nowrap;
        }

        .report-table thead {
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .report-table thead th {
            background: #202a3c;
            color: white;
            padding: 9px 8px;
            font-size: 11px;
            border-color: #39455c;
            text-align: right;
        }

        .report-table thead th:first-child,
        .report-table thead th:nth-child(2) {
            text-align: left;
        }

        .report-table tbody td {
            padding: 7px 8px;
            vertical-align: middle;
            border-color: #edf0f2;
            text-align: right;
        }

        .report-table tbody td:first-child,
        .report-table tbody td:nth-child(2) {
            text-align: left;
        }

        .report-table tbody tr:hover {
            background: #f8f9ff;
        }

        .stock-form {
            display: flex;
            align-items: center;
            gap: 4px;
            justify-content: flex-end;
        }

        .stock-input {
            width: 85px;
            height: 32px;
            padding: 4px 6px;
            font-size: 12px;
            text-align: right;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }

        .save-btn {
            background: #155eef;
            color: white;
            border: none;
            padding: 4px 8px;
            font-size: 11px;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
        }

        .save-btn:hover {
            background: #0f4fc7;
        }

        .total-row {
            background: #eef2f7 !important;
            font-weight: 700;
        }

        .total-row td {
            border-top: 2px solid #adb5bd !important;
        }

        .report-footer {
            font-size: 11px;
            color: #6c757d;
        }

        .loading-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, .7);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .loading-box {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, .15);
            text-align: center;
        }

        @media print {
            body { background: white; }
            .page-header { background: white !important; color: black !important; padding: 10px 0; }
            .filter-card, .summary-section, .save-btn, .no-print { display: none !important; }
            .report-card { box-shadow: none; }
            .table-container { border: 0; max-height: none; overflow: visible; }
            .report-table { font-size: 9px; }
            .report-table thead th { background: #eee !important; color: #000 !important; }
        }
    </style>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-box">
            <div class="spinner-border text-primary mb-2" role="status" style="width: 1.5rem; height: 1.5rem;"></div>
            <div class="fw-semibold small">Generating report...</div>
        </div>
    </div>

    <!-- HEADER -->
    <div class="page-header">
        <div class="container-fluid px-3 px-md-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="page-title">
                        <i class="bi bi-bar-chart-line me-2"></i> Dispatch Requirement Report
                    </div>
                    <div class="page-subtitle">
                        Item-wise dispatch analysis, stock levels, and requirements.
                    </div>
                </div>
                <div class="d-none d-md-block">
                    <span class="badge bg-light text-dark px-3 py-2 small">
                        Overview
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid px-3 px-md-4">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
                <strong>Please correct the following:</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- FILTER CARD -->
        <div class="card report-card filter-card mb-3">
            <div class="card-body p-3 p-md-4">
                <form method="GET" action="{{ route('dispatch-requirement.index') }}" id="reportForm" onsubmit="document.getElementById('loadingOverlay').style.display='flex';">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label" for="from_date">From Date</label>
                            <input type="date" id="from_date" name="from_date" value="{{ $from }}" class="form-control" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label" for="to_date">To Date</label>
                            <input type="date" id="to_date" name="to_date" value="{{ $to }}" class="form-control" required>
                        </div>

                        <div class="col-md-6 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-main flex-grow-1">
                                Generate Report
                            </button>
                            <a href="{{ route('dispatch-requirement.export', ['from_date' => $from, 'to_date' => $to]) }}" class="btn btn-success btn-main">
                                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Excel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- SUMMARY CARDS -->
        <div class="row g-2 mb-3 summary-section">
            <div class="col-md-6">
                <div class="card summary-card h-100">
                    <div class="card-body py-3">
                        <div class="summary-label">Selected Period</div>
                        <div class="summary-number text-primary mt-1 fs-6">
                            {{ $from }} to {{ $to }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card summary-card h-100">
                    <div class="card-body py-3">
                        <div class="summary-label">Total Items</div>
                        <div class="summary-number text-dark mt-1">
                            {{ number_format($rows->count()) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- REPORT TABLE CARD -->
        <div class="card report-card mb-4">
            <div class="card-body p-0">

                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white rounded-top">
                    <div>
                        <h6 class="mb-0 fw-bold small">Requirement Details</h6>
                        <div class="report-footer">
                            Showing items and multi-month breakdowns
                        </div>
                    </div>

                    <div class="d-flex gap-2 no-print">
                        <button type="button" onclick="window.print()" class="btn btn-outline-dark btn-sm py-1 px-2 small">
                            <i class="bi bi-printer me-1"></i> Print
                        </button>
                    </div>
                </div>

                <div class="table-container">
                    <table class="table table-hover report-table">
                        <thead>
                            <tr>
                                <th>Item Code</th>
                                <th>Item Name</th>

                                @foreach($months as $month)
                                    <th>Dispatch {{ $month['label'] }} JH</th>
                                    <!-- <th>Dispatch {{ $month['label'] }} BPJH</th> -->
                                @endforeach

                                <th>TOTAL_JH</th>
                                <th>Stock JH</th>
                                <th>REQUIRED_JH</th>
                                @foreach($months as $month)
                                    <!-- <th>Dispatch {{ $month['label'] }} JH</th> -->
                                    <th>Dispatch {{ $month['label'] }} BPJH</th>
                                @endforeach

                                <th>TOTAL_BPJH</th>
                                <th>Stock BPJH</th>
                                <th>REQUIRED_BPJH</th>

                                <th>TOTAL_DIS</th>
                                <th>TOTAL_REQUIRED</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($rows as $row)
                                <tr>
                                    <td class="fw-bold text-primary">{{ $row['item_code'] }}</td>
                                    <td>{{ $row['item_name'] }}</td>

                                    @foreach($months as $month)
                                        @php
                                            $key = str_replace('-', '_', $month['key']);
                                        @endphp
                                        <td>{{ number_format($row['dispatch_' . $key . '_jh'] ?? 0, 3) }}</td>
                                        <!-- <td>{{ number_format($row['dispatch_' . $key . '_bp'] ?? 0, 3) }}</td> -->
                                    @endforeach

                                    <td>{{ number_format($row['total_jh'], 3) }}</td>

                                    <td>
                                        <form class="stock-form" method="POST" action="{{ route('dispatch-requirement.stock.save') }}">
                                            @csrf
                                            <input type="hidden" name="item_code" value="{{ $row['item_code'] }}">
                                            <input type="hidden" name="from_date" value="{{ $from }}">
                                            <input type="hidden" name="to_date" value="{{ $to }}">

                                            <input class="stock-input" type="number" step="0.001" min="0" name="stock_jh"
                                                   value="{{ old('item_code') == $row['item_code'] ? old('stock_jh') : number_format($row['stock_jh'], 3, '.', '') }}" required>
                                            <button class="save-btn" type="submit">Save</button>
                                        </form>
                                    </td>

                                    <td>{{ number_format($row['required_jh'], 3) }}</td>
                                    @foreach($months as $month)
                                        @php
                                            $key = str_replace('-', '_', $month['key']);
                                        @endphp
                                        <!-- <td>{{ number_format($row['dispatch_' . $key . '_jh'] ?? 0, 3) }}</td> -->
                                        <td>{{ number_format($row['dispatch_' . $key . '_bp'] ?? 0, 3) }}</td>
                                    @endforeach
                                    <td>{{ number_format($row['total_bp'], 3) }}</td>

                                    <td>
                                        <form class="stock-form" method="POST" action="{{ route('dispatch-requirement.stock.save') }}">
                                            @csrf
                                            <input type="hidden" name="item_code" value="{{ $row['item_code'] }}">
                                            <input type="hidden" name="from_date" value="{{ $from }}">
                                            <input type="hidden" name="to_date" value="{{ $to }}">

                                            <input class="stock-input" type="number" step="0.001" min="0" name="stock_bp"
                                                   value="{{ old('item_code') == $row['item_code'] ? old('stock_bp') : number_format($row['stock_bp'], 3, '.', '') }}" required>
                                            <input type="hidden" name="stock_jh" value="{{ number_format($row['stock_jh'], 3, '.', '') }}">
                                            <button class="save-btn" type="submit">Save</button>
                                        </form>
                                    </td>

                                    <td>{{ number_format($row['required_bp'], 3) }}</td>
                                    <td>{{ number_format($row['total_dis'], 3) }}</td>
                                    <td class="fw-bold text-success">{{ number_format($row['total_required'], 3) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 10 + (count($months) * 2) }}" class="text-center py-4 text-muted small">
                                        <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                                        No items found for the selected criteria.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>
@endsection