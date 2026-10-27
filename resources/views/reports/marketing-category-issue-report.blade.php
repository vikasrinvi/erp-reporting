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

        /* Enforce fit-to-view table layout */
        .table-container {
            width: 100%;
            overflow-x: hidden;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .report-table {
            width: 100%;
            table-layout: fixed;
            margin-bottom: 0;
            font-size: 12px;
        }

        .report-table thead {
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .report-table thead th {
            background: #202a3c;
            color: white;
            padding: 8px 6px;
            font-size: 11px;
            border-color: #39455c;
            text-overflow: ellipsis;
            overflow: hidden;
            white-space: nowrap;
        }

        .report-table tbody td {
            padding: 8px 6px;
            vertical-align: middle;
            border-color: #edf0f2;
            word-break: break-word;
        }

        .report-table tbody tr:hover {
            background: #f8f9ff;
        }

        /* Specific Column Width Distribution to prevent overflow */
        .col-sno { width: 5%; text-align: center; }
        .col-mktg { width: 11%; }
        .col-prodcode { width: 9%; }
        .col-prodname { width: 14%; }
        .col-itemcode { width: 9%; }
        .col-itemname { width: 14%; }
        .col-qty { width: 8%; text-align: right; }
        .col-val { width: 9%; text-align: right; }
        .col-remarks { width: 21%; }

        .number-cell {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .category-badge {
            background: #e8f0ff;
            color: #3159a5;
            padding: 3px 6px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .report-table textarea.form-control {
            min-height: 32px;
            height: 34px;
            font-size: 11px;
            padding: 4px 6px;
            resize: none;
        }

        .report-table textarea.form-control:focus {
            height: 65px;
            resize: vertical;
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
            .filter-card, .summary-section, .save-button, .no-print { display: none !important; }
            .report-card { box-shadow: none; }
            .table-container { border: 0; }
            .report-table { font-size: 9px; table-layout: auto; }
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
                        <i class="bi bi-bar-chart-line me-2"></i> Marketing Category Issue Report
                    </div>
                    <div class="page-subtitle">
                        Item-wise issue quantities and values for the selected period.
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

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
                <strong>Please correct the following:</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- FILTER CARD -->
        <div class="card report-card filter-card mb-3">
            <div class="card-body p-3 p-md-4">
                <form method="GET" action="{{ route('marketing-issue-report.index') }}" id="reportForm" onsubmit="document.getElementById('loadingOverlay').style.display='flex';">
                    
                    <div class="row g-2 align-items-end">
                        <!-- FROM DATE -->
                        <div class="col-md-3">
                            <label class="form-label" for="from">From Date</label>
                            <input type="date" id="from" name="from" value="{{ $from }}" class="form-control" required>
                        </div>

                        <!-- TO DATE -->
                        <div class="col-md-3">
                            <label class="form-label" for="to">To Date</label>
                            <input type="date" id="to" name="to" value="{{ $to }}" class="form-control" required>
                        </div>

                        <!-- SEARCH -->
                        <div class="col-md-4">
                            <label class="form-label" for="search">Search</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white py-1"><i class="bi bi-search small"></i></span>
                                <input type="text" id="search" name="search" value="{{ $search }}" class="form-control" placeholder="Item, product or category">
                            </div>
                        </div>

                        <!-- BUTTONS -->
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-main w-100">
                                Generate
                            </button>
                            <button type="submit" name="export" value="csv" class="btn btn-outline-success btn-main px-2" title="Export CSV">
                                <i class="bi bi-file-earmark-spreadsheet"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- SUMMARY CARDS -->
        <div class="row g-2 mb-3 summary-section">
            <div class="col-md-4">
                <div class="card summary-card h-100">
                    <div class="card-body py-3">
                        <div class="summary-label">Total Qty Issued in PCS</div>
                        <div class="summary-number text-primary mt-1">
                            {{ number_format((float) $totals['pcs'], 2) }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card summary-card h-100">
                    <div class="card-body py-3">
                        <div class="summary-label">Total Qty Issued in BOX</div>
                        <div class="summary-number text-success mt-1">
                            {{ number_format((float) $totals['box'], 2) }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card summary-card h-100">
                    <div class="card-body py-3">
                        <div class="summary-label">Total Value Issued</div>
                        <div class="summary-number text-dark mt-1">
                            {{ number_format((float) $totals['value'], 2) }}
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
                        <h6 class="mb-0 fw-bold small">Issue Details</h6>
                        <div class="report-footer">
                            Period: {{ $from }} to {{ $to }} &nbsp;|&nbsp; Rows: <strong>{{ $rows->count() }}</strong>
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
                                <th class="col-sno">S.No</th>
                                <th class="col-mktg">Category</th>
                                <th class="col-prodcode">P. Code</th>
                                <th class="col-prodname">Product Name</th>
                                <th class="col-itemcode">I. Code</th>
                                <th class="col-itemname">Item Name</th>
                                <th class="col-qty number-cell">PCS</th>
                                <th class="col-qty number-cell">BOX</th>
                                <th class="col-val number-cell">Value</th>
                                <th class="col-remarks">Remarks</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="category-badge" title="{{ $row->MKTG_CATG ?: 'Uncategorised' }}">
                                            {{ $row->MKTG_CATG ?: 'Uncategorised' }}
                                        </span>
                                    </td>
                                    <td class="text-truncate" title="{{ $row->PROD_CODE }}">{{ $row->PROD_CODE }}</td>
                                    <td class="text-truncate" title="{{ $row->PROD_NAME }}">{{ $row->PROD_NAME }}</td>
                                    <td class="fw-bold text-primary text-truncate" title="{{ $row->ITEM_CODE }}">{{ $row->ITEM_CODE }}</td>
                                    <td class="text-truncate" title="{{ $row->ITEM_NAME }}">{{ $row->ITEM_NAME }}</td>

                                    <td class="number-cell">
                                        {{ number_format((float) $row->qty_issued_pcs, 2) }}
                                    </td>

                                    <td class="number-cell">
                                        {{ number_format((float) $row->qty_issued_box, 2) }}
                                    </td>

                                    <td class="number-cell">
                                        {{ number_format((float) $row->val_issued, 2) }}
                                    </td>

                                    <td>
                                        <form method="POST" action="{{ route('marketing-issue-report.remarks.save') }}" class="d-flex flex-column gap-1">
                                            @csrf
                                            <input type="hidden" name="marketing_category" value="{{ $row->MKTG_CATG }}">
                                            <input type="hidden" name="prod_code" value="{{ $row->PROD_CODE }}">
                                            <input type="hidden" name="item_code" value="{{ $row->ITEM_CODE }}">
                                            <input type="hidden" name="from" value="{{ $from }}">
                                            <input type="hidden" name="to" value="{{ $to }}">
                                            <input type="hidden" name="search" value="{{ $search }}">

                                            <textarea name="remarks" maxlength="10000" class="form-control" placeholder="Remarks...">{{ $row->saved_remark }}</textarea>

                                            <button type="submit" class="btn btn-success btn-sm py-0 px-1 save-button" style="font-size: 10px; align-self: flex-end;">
                                                Save
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4 text-muted small">
                                        <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                                        No issue records found for the selected date range.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                        @if ($rows->isNotEmpty())
                            <tfoot>
                                <tr class="total-row">
                                    <td colspan="6" class="text-end fw-bold">Grand Total:</td>
                                    <td class="number-cell fw-bold">{{ number_format((float) $totals['pcs'], 2) }}</td>
                                    <td class="number-cell fw-bold">{{ number_format((float) $totals['box'], 2) }}</td>
                                    <td class="number-cell fw-bold">{{ number_format((float) $totals['value'], 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

            </div>
        </div>

    </div>
@endsection