<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>{{ $report->report_name }} - Report Details</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <style>

        body {
            background: #f4f6f9;

            font-family:
                Inter,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
        }

        .page-header {
            background:
                linear-gradient(
                    135deg,
                    #172033,
                    #273b63
                );

            color: white;

            padding: 30px 0;

            margin-bottom: 25px;
        }

        .report-card {
            border: 0;
            border-radius: 14px;

            box-shadow:
                0 3px 15px rgba(0,0,0,.06);
        }

        .report-code {
            color: rgba(255,255,255,.65);

            font-size: 12px;

            font-weight: 700;

            letter-spacing: .5px;
        }

        .page-title {
            font-size: 25px;
            font-weight: 700;
        }

        .page-subtitle {
            color: rgba(255,255,255,.7);
            font-size: 14px;
        }

        .section-title {
            font-size: 14px;

            font-weight: 700;

            color: #273b63;

            text-transform: uppercase;

            letter-spacing: .4px;

            margin-bottom: 12px;
        }

        .info-row {
            padding: 12px 0;

            border-bottom: 1px solid #edf0f2;
        }

        .info-row:last-child {
            border-bottom: 0;
        }

        .info-label {
            font-size: 12px;

            font-weight: 700;

            color: #6c757d;

            text-transform: uppercase;
        }

        .info-value {
            font-size: 14px;

            font-weight: 600;

            color: #212529;
        }

        .detail-text {
            color: #495057;

            font-size: 14px;

            line-height: 1.7;

            white-space: pre-line;
        }

        .source-box {
            background: #f8f9fb;

            border: 1px solid #e9ecef;

            border-radius: 10px;

            padding: 14px;
        }

        .source-item {
            font-family: monospace;

            font-size: 13px;

            padding: 5px 0;

            color: #273b63;
        }

        .badge-custom {
            display: inline-block;

            padding: 6px 10px;

            border-radius: 20px;

            font-size: 11px;

            font-weight: 700;

            background: #eef2f7;

            color: #495057;
        }

        .status-active {
            background: #e7f7ee;
            color: #16834b;
        }

    </style>

</head>

<body>


<!-- HEADER -->

<div class="page-header">

    <div class="container-fluid px-4">

        <div class="d-flex justify-content-between align-items-center">

            <div>

                <div class="report-code">

                    {{ $report->report_code }}

                </div>

                <div class="page-title">

                    <i class="bi bi-bar-chart-line me-2"></i>

                    {{ $report->report_name }}

                </div>

                <div class="page-subtitle">

                    {{ $report->module }}

                    @if($report->sub_module)
                        / {{ $report->sub_module }}
                    @endif

                </div>

            </div>


            <div class="d-flex gap-2">

                <a
                    href="{{ route('reports.library') }}"
                    class="btn btn-light"
                >

                    <i class="bi bi-arrow-left me-1"></i>

                    Report Library

                </a>


                @if($report->route)

                    <a
                        href="{{ url($report->route) }}"
                        class="btn btn-success"
                    >

                        <i class="bi bi-box-arrow-up-right me-1"></i>

                        Open Report

                    </a>

                @endif

            </div>

        </div>

    </div>

</div>


<div class="container-fluid px-4">


    <!-- BASIC INFORMATION -->

    <div class="card report-card mb-4">

        <div class="card-body p-4">

            <div class="section-title">

                <i class="bi bi-info-circle me-1"></i>

                Report Information

            </div>


            <div class="row">

                <div class="col-md-3">

                    <div class="info-row">

                        <div class="info-label">
                            Report Code
                        </div>

                        <div class="info-value">
                            {{ $report->report_code }}
                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="info-row">

                        <div class="info-label">
                            Module
                        </div>

                        <div class="info-value">
                            {{ $report->module ?: '-' }}
                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="info-row">

                        <div class="info-label">
                            Sub Module
                        </div>

                        <div class="info-value">
                            {{ $report->sub_module ?: '-' }}
                        </div>

                    </div>

                </div>


                <div class="col-md-3">

                    <div class="info-row">

                        <div class="info-label">
                            Status
                        </div>

                        <div class="info-value">

                            <span class="badge-custom status-active">

                                {{ strtoupper($report->status) }}

                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <div class="row g-4">


        <!-- LEFT -->

        <div class="col-lg-8">


            <!-- DESCRIPTION -->

            <div class="card report-card mb-4">

                <div class="card-body p-4">

                    <div class="section-title">

                        <i class="bi bi-file-text me-1"></i>

                        Description

                    </div>

                    <div class="detail-text">

                        {{ $report->description ?: 'No description available.' }}

                    </div>

                </div>

            </div>


            <!-- PURPOSE -->

            <div class="card report-card mb-4">

                <div class="card-body p-4">

                    <div class="section-title">

                        <i class="bi bi-bullseye me-1"></i>

                        Purpose

                    </div>

                    <div class="detail-text">

                        {{ $report->purpose ?: 'No purpose documented.' }}

                    </div>

                </div>

            </div>


            <!-- DATA SOURCE -->

            <div class="card report-card mb-4">

                <div class="card-body p-4">

                    <div class="section-title">

                        <i class="bi bi-database me-1"></i>

                        Data Sources

                    </div>

                    <div class="source-box">

                        <div class="source-item">

                            {{ $report->data_source ?: 'Not documented' }}

                        </div>

                    </div>

                </div>

            </div>


            <!-- FILTERS -->

            <div class="card report-card mb-4">

                <div class="card-body p-4">

                    <div class="section-title">

                        <i class="bi bi-funnel me-1"></i>

                        Filters

                    </div>

                    <div class="detail-text">

                        {{ $report->filters ?: 'No filters documented.' }}

                    </div>

                </div>

            </div>


            <!-- GROUPING -->

            <div class="card report-card mb-4">

                <div class="card-body p-4">

                    <div class="section-title">

                        <i class="bi bi-diagram-3 me-1"></i>

                        Grouping & Calculation

                    </div>

                    <div class="detail-text">

                        {{ $report->grouping_details ?: 'Not documented.' }}

                    </div>

                </div>

            </div>


            <!-- NOTES -->

            @if($report->notes)

                <div class="card report-card mb-4">

                    <div class="card-body p-4">

                        <div class="section-title">

                            <i class="bi bi-sticky me-1"></i>

                            Notes

                        </div>

                        <div class="detail-text">

                            {{ $report->notes }}

                        </div>

                    </div>

                </div>

            @endif

        </div>


        <!-- RIGHT -->

        <div class="col-lg-4">


            <!-- TECHNICAL -->

            <div class="card report-card mb-4">

                <div class="card-body p-4">

                    <div class="section-title">

                        <i class="bi bi-code-slash me-1"></i>

                        Technical Details

                    </div>


                    <div class="info-row">

                        <div class="info-label">
                            Main Table
                        </div>

                        <div class="info-value">
                            {{ $report->main_table ?: '-' }}
                        </div>

                    </div>


                    <div class="info-row">

                        <div class="info-label">
                            Route
                        </div>

                        <div class="info-value text-break">
                            {{ $report->route ?: '-' }}
                        </div>

                    </div>


                    <div class="info-row">

                        <div class="info-label">
                            Access
                        </div>

                        <div class="info-value">
                            {{ $report->access_role ?: 'All Authorized Users' }}
                        </div>

                    </div>


                    <div class="info-row">

                        <div class="info-label">
                            Output
                        </div>

                        <div class="info-value">
                            {{ $report->output_types ?: '-' }}
                        </div>

                    </div>

                </div>

            </div>


            <!-- ACTION -->

            <div class="card report-card">

                <div class="card-body p-4">

                    <div class="section-title">

                        <i class="bi bi-lightning me-1"></i>

                        Actions

                    </div>


                    @if($report->route)

                        <a
                            href="{{ url($report->route) }}"
                            class="btn btn-primary w-100 mb-2"
                        >

                            <i class="bi bi-bar-chart-line me-1"></i>

                            Open Report

                        </a>

                    @endif


                    <a
                        href="{{ route('reports.library') }}"
                        class="btn btn-outline-secondary w-100"
                    >

                        <i class="bi bi-arrow-left me-1"></i>

                        Back to Library

                    </a>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>