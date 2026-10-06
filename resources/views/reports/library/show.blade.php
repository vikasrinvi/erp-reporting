@extends('layouts.app')

@section('content')

<style>

    .library-page {
        background: #f5f7fb;
        min-height: calc(100vh - 70px);
        padding: 24px;
    }

    .detail-header {
        background: linear-gradient(135deg, #172033, #273b63);
        color: white;
        border-radius: 18px;
        padding: 28px;
        margin-bottom: 22px;
    }

    .detail-card {
        background: white;
        border: 0;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
        box-shadow: 0 5px 20px rgba(0,0,0,.05);
    }

    .section-title {
        font-size: 17px;
        font-weight: 700;
        color: #172033;
        border-bottom: 1px solid #eaecf0;
        padding-bottom: 12px;
        margin-bottom: 18px;
    }

    .field-label {
        font-size: 11px;
        text-transform: uppercase;
        font-weight: 700;
        color: #98a2b3;
        margin-bottom: 4px;
    }

    .field-value {
        color: #344054;
        font-size: 14px;
        white-space: pre-line;
    }

    .code-box {
        background: #f8f9fb;
        border-radius: 10px;
        padding: 13px;
        font-family: monospace;
        font-size: 13px;
        word-break: break-word;
    }

    .status-badge {
        padding: 6px 12px;
        border-radius: 30px;
        font-size: 11px;
        font-weight: 700;
    }

    .active {
        background: #e8f7ee;
        color: #18794e;
    }

    .inactive {
        background: #fceaea;
        color: #b42318;
    }

</style>


<div class="library-page">

    {{-- Header --}}
    <div class="detail-header">

        <div class="d-flex justify-content-between align-items-start gap-3">

            <div>

                <div class="small opacity-75 mb-1">
                    {{ $report->report_code }}
                </div>

                <h2 class="mb-2">
                    {{ $report->report_name }}
                </h2>

                <div class="d-flex gap-2 flex-wrap">

                    @if($report->module)
                        <span class="badge bg-light text-dark">
                            {{ $report->module }}
                        </span>
                    @endif

                    @if($report->sub_module)
                        <span class="badge bg-light text-dark">
                            {{ $report->sub_module }}
                        </span>
                    @endif

                    @if($report->report_type)
                        <span class="badge bg-light text-dark">
                            {{ $report->report_type }}
                        </span>
                    @endif

                    <span class="status-badge
                        {{ $report->status === 'active'
                            ? 'active'
                            : 'inactive' }}">

                        {{ ucfirst($report->status) }}

                    </span>

                </div>

            </div>


            <div class="d-flex gap-2">

                <a href="{{ route(
                    'reports.library.edit',
                    $report
                ) }}"
                   class="btn btn-light">

                    <i class="bi bi-pencil me-1"></i>
                    Edit

                </a>


                @if($report->route)

                    <a href="{{ url($report->route) }}"
                       class="btn btn-light">

                        <i class="bi bi-box-arrow-up-right me-1"></i>
                        Open Report

                    </a>

                @endif

            </div>

        </div>

    </div>


    {{-- Description --}}
    <div class="detail-card">

        <div class="section-title">
            <i class="bi bi-info-circle me-2"></i>
            Report Overview
        </div>

        <div class="row g-4">

            <div class="col-md-6">

                <div class="field-label">
                    Description
                </div>

                <div class="field-value">
                    {{ $report->description ?: 'Not documented.' }}
                </div>

            </div>


            <div class="col-md-6">

                <div class="field-label">
                    Purpose
                </div>

                <div class="field-value">
                    {{ $report->purpose ?: 'Not documented.' }}
                </div>

            </div>


            <div class="col-md-12">

                <div class="field-label">
                    Business Logic
                </div>

                <div class="field-value">
                    {{ $report->business_logic ?: 'Not documented.' }}
                </div>

            </div>

        </div>

    </div>


    {{-- Database --}}
    <div class="detail-card">

        <div class="section-title">
            <i class="bi bi-database me-2"></i>
            Database & Data Source
        </div>

        <div class="row g-4">

            <div class="col-md-4">

                <div class="field-label">
                    Main Table
                </div>

                <div class="code-box">
                    {{ $report->main_table ?: '—' }}
                </div>

            </div>


            <div class="col-md-8">

                <div class="field-label">
                    Data Source
                </div>

                <div class="field-value">
                    {{ $report->data_source ?: '—' }}
                </div>

            </div>


            <div class="col-12">

                <div class="field-label">
                    Table Relationships
                </div>

                <div class="field-value">
                    {{ $report->table_relationships ?: '—' }}
                </div>

            </div>

        </div>

    </div>


    {{-- Logic --}}
    <div class="detail-card">

        <div class="section-title">
            <i class="bi bi-diagram-3 me-2"></i>
            Report Logic
        </div>

        <div class="row g-4">

            <div class="col-md-6">

                <div class="field-label">
                    Filters
                </div>

                <div class="field-value">
                    {{ $report->filters ?: '—' }}
                </div>

            </div>


            <div class="col-md-6">

                <div class="field-label">
                    Default Filters
                </div>

                <div class="field-value">
                    {{ $report->default_filters ?: '—' }}
                </div>

            </div>


            <div class="col-md-6">

                <div class="field-label">
                    Grouping
                </div>

                <div class="field-value">
                    {{ $report->grouping_details ?: '—' }}
                </div>

            </div>


            <div class="col-md-6">

                <div class="field-label">
                    Calculations
                </div>

                <div class="field-value">
                    {{ $report->calculation_details ?: '—' }}
                </div>

            </div>


            <div class="col-md-6">

                <div class="field-label">
                    Columns Displayed
                </div>

                <div class="field-value">
                    {{ $report->columns_displayed ?: '—' }}
                </div>

            </div>


            <div class="col-md-6">

                <div class="field-label">
                    Sorting
                </div>

                <div class="field-value">
                    {{ $report->sorting ?: '—' }}
                </div>

            </div>

        </div>

    </div>


    {{-- Technical --}}
    <div class="detail-card">

        <div class="section-title">
            <i class="bi bi-code-slash me-2"></i>
            Technical Implementation
        </div>

        <div class="row g-4">

            <div class="col-md-6">

                <div class="field-label">
                    Route
                </div>

                <div class="code-box">
                    {{ $report->route ?: '—' }}
                </div>

            </div>


            <div class="col-md-6">

                <div class="field-label">
                    Controller
                </div>

                <div class="code-box">
                    {{ $report->controller ?: '—' }}
                </div>

            </div>


            <div class="col-md-6">

                <div class="field-label">
                    View
                </div>

                <div class="code-box">
                    {{ $report->view ?: '—' }}
                </div>

            </div>


            <div class="col-md-6">

                <div class="field-label">
                    Procedure / Function
                </div>

                <div class="code-box">
                    {{ $report->procedure_function ?: 'None' }}
                </div>

            </div>


            <div class="col-md-6">

                <div class="field-label">
                    Output Types
                </div>

                <div class="field-value">
                    {{ $report->output_types ?: '—' }}
                </div>

            </div>


            <div class="col-md-6">

                <div class="field-label">
                    Version
                </div>

                <div class="field-value">
                    {{ $report->version ?: '—' }}
                </div>

            </div>

        </div>

    </div>


    {{-- Access --}}
    <div class="detail-card">

        <div class="section-title">
            <i class="bi bi-shield-lock me-2"></i>
            Access & Notes
        </div>

        <div class="row g-4">

            <div class="col-md-6">

                <div class="field-label">
                    Access Role
                </div>

                <div class="field-value">
                    {{ $report->access_role ?: '—' }}
                </div>

            </div>


            <div class="col-md-6">

                <div class="field-label">
                    Notes
                </div>

                <div class="field-value">
                    {{ $report->notes ?: '—' }}
                </div>

            </div>

        </div>

    </div>


    {{-- Audit --}}
    <div class="detail-card">

        <div class="section-title">
            <i class="bi bi-clock-history me-2"></i>
            Documentation History
        </div>

        <div class="row g-4">

            <div class="col-md-6">

                <div class="field-label">
                    Created
                </div>

                <div class="field-value">

                    {{ $report->created_at
                        ? $report->created_at->format('d M Y, h:i A')
                        : '—' }}

                </div>

            </div>


            <div class="col-md-6">

                <div class="field-label">
                    Last Updated
                </div>

                <div class="field-value">

                    {{ $report->updated_at
                        ? $report->updated_at->format('d M Y, h:i A')
                        : '—' }}

                </div>

            </div>

        </div>

    </div>


    <div class="mb-5">

        <a href="{{ route('reports.library') }}"
           class="btn btn-outline-secondary">

            <i class="bi bi-arrow-left me-1"></i>
            Back to Report Library

        </a>

    </div>

</div>

@endsection