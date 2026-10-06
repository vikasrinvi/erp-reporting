@extends('layouts.app')

@section('title', 'Edit Report')

@section('content')

<style>

    .library-page {
        background: #f5f7fb;
        min-height: calc(100vh - 70px);
        padding: 24px;
    }

    .form-header {
        background: linear-gradient(135deg, #172033, #273b63);
        color: white;
        border-radius: 18px;
        padding: 25px 28px;
        margin-bottom: 22px;
    }

    .form-card {
        background: white;
        border: 0;
        border-radius: 16px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 5px 20px rgba(0,0,0,.05);
    }

    .section-title {
        font-size: 17px;
        font-weight: 700;
        color: #172033;
        padding-bottom: 12px;
        margin-bottom: 20px;
        border-bottom: 1px solid #eaecf0;
    }

    .section-title i {
        margin-right: 8px;
    }

    .form-label {
        font-size: 13px;
        font-weight: 600;
        color: #344054;
    }

    textarea {
        min-height: 100px;
        resize: vertical;
    }

    .help-text {
        font-size: 11px;
        color: #98a2b3;
        margin-top: 5px;
    }

    .report-code {
        font-family: monospace;
        font-weight: 600;
        text-transform: uppercase;
    }

    .audit-info {
        font-size: 12px;
        color: #667085;
    }

</style>


<div class="library-page">

    {{-- ===================================================== --}}
    {{-- HEADER --}}
    {{-- ===================================================== --}}

    <div class="form-header">

        <div class="d-flex justify-content-between align-items-center">

            <div>

                <h3 class="mb-1">
                    <i class="bi bi-pencil-square me-2"></i>
                    Edit Report
                </h3>

                <div class="opacity-75">
                    Update report documentation and technical details
                </div>

            </div>

            <div class="d-flex gap-2">

                <a href="{{ route('reports.library.show', $report) }}"
                   class="btn btn-outline-light">

                    <i class="bi bi-eye me-1"></i>
                    View

                </a>

                <a href="{{ route('reports.library') }}"
                   class="btn btn-light">

                    <i class="bi bi-arrow-left me-1"></i>
                    Back

                </a>

            </div>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- VALIDATION ERRORS --}}
    {{-- ===================================================== --}}

    @if($errors->any())

        <div class="alert alert-danger">

            <strong>
                <i class="bi bi-exclamation-triangle me-1"></i>
                Please fix the following:
            </strong>

            <ul class="mb-0 mt-2">

                @foreach($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- ===================================================== --}}
    {{-- FORM --}}
    {{-- ===================================================== --}}

    <form method="POST"
          action="{{ route('reports.library.update', $report) }}">

        @csrf
        @method('PUT')


        {{-- ===================================================== --}}
        {{-- BASIC INFORMATION --}}
        {{-- ===================================================== --}}

        <div class="form-card">

            <div class="section-title">
                <i class="bi bi-info-circle"></i>
                Basic Information
            </div>

            <div class="row g-3">

                <div class="col-md-3">

                    <label class="form-label">
                        Report Code *
                    </label>

                    <input type="text"
                           name="report_code"
                           value="{{ old('report_code', $report->report_code) }}"
                           class="form-control report-code"
                           placeholder="RPT-ASV-001"
                           required>

                </div>


                <div class="col-md-5">

                    <label class="form-label">
                        Report Name *
                    </label>

                    <input type="text"
                           name="report_name"
                           value="{{ old('report_name', $report->report_name) }}"
                           class="form-control"
                           placeholder="ASV Item Issue Report"
                           required>

                </div>


                <div class="col-md-2">

                    <label class="form-label">
                        Module
                    </label>

                    <input type="text"
                           name="module"
                           value="{{ old('module', $report->module) }}"
                           class="form-control"
                           placeholder="Inventory">

                </div>


                <div class="col-md-2">

                    <label class="form-label">
                        Sub Module
                    </label>

                    <input type="text"
                           name="sub_module"
                           value="{{ old('sub_module', $report->sub_module) }}"
                           class="form-control"
                           placeholder="Item Issue">

                </div>


                <div class="col-md-4">

                    <label class="form-label">
                        Report Type
                    </label>

                    <select name="report_type"
                            class="form-select">

                        <option value="">
                            Select Type
                        </option>

                        @foreach([
                            'Transaction',
                            'Summary',
                            'MIS',
                            'Analytical',
                            'Master',
                            'Operational'
                        ] as $type)

                            <option value="{{ $type }}"
                                @selected(old('report_type', $report->report_type) == $type)>

                                {{ $type }}

                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="col-md-8">

                    <label class="form-label">
                        Description
                    </label>

                    <textarea name="description"
                              class="form-control"
                              placeholder="What does this report show?">{{ old('description', $report->description) }}</textarea>

                </div>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- BUSINESS DEFINITION --}}
        {{-- ===================================================== --}}

        <div class="form-card">

            <div class="section-title">
                <i class="bi bi-briefcase"></i>
                Business Definition
            </div>

            <div class="row g-3">

                <div class="col-md-6">

                    <label class="form-label">
                        Purpose
                    </label>

                    <textarea name="purpose"
                              class="form-control"
                              placeholder="Why is this report required?">{{ old('purpose', $report->purpose) }}</textarea>

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Business Logic
                    </label>

                    <textarea name="business_logic"
                              class="form-control"
                              placeholder="Explain the business rules used by the report">{{ old('business_logic', $report->business_logic) }}</textarea>

                </div>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- DATABASE --}}
        {{-- ===================================================== --}}

        <div class="form-card">

            <div class="section-title">
                <i class="bi bi-database"></i>
                Database & Data Source
            </div>

            <div class="row g-3">

                <div class="col-md-6">

                    <label class="form-label">
                        Main Table
                    </label>

                    <input type="text"
                           name="main_table"
                           value="{{ old('main_table', $report->main_table) }}"
                           class="form-control"
                           placeholder="itemtran_body">

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Data Source
                    </label>

                    <textarea name="data_source"
                              class="form-control"
                              placeholder="itemtran_body, item_mast, prod_mast">{{ old('data_source', $report->data_source) }}</textarea>

                </div>


                <div class="col-12">

                    <label class="form-label">
                        Table Relationships
                    </label>

                    <textarea name="table_relationships"
                              class="form-control"
                              placeholder="itemtran_body.ITEM_CODE → item_mast.ITEM_CODE&#10;item_mast.PROD_CODE → prod_mast.PROD_CODE">{{ old('table_relationships', $report->table_relationships) }}</textarea>

                    <div class="help-text">
                        Mention joins and key relationships between tables.
                    </div>

                </div>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- REPORT LOGIC --}}
        {{-- ===================================================== --}}

        <div class="form-card">

            <div class="section-title">
                <i class="bi bi-diagram-3"></i>
                Report Logic
            </div>

            <div class="row g-3">

                <div class="col-md-6">

                    <label class="form-label">
                        Filters
                    </label>

                    <textarea name="filters"
                              class="form-control"
                              placeholder="From Month&#10;To Month&#10;Division&#10;Item Code&#10;Item Name">{{ old('filters', $report->filters) }}</textarea>

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Default Filters
                    </label>

                    <textarea name="default_filters"
                              class="form-control"
                              placeholder="MKTG_CATG = ASV&#10;All Divisions">{{ old('default_filters', $report->default_filters) }}</textarea>

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Grouping
                    </label>

                    <textarea name="grouping_details"
                              class="form-control"
                              placeholder="ITEM_CODE&#10;ITEM_NAME&#10;ITEM_CATG">{{ old('grouping_details', $report->grouping_details) }}</textarea>

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Calculations
                    </label>

                    <textarea name="calculation_details"
                              class="form-control"
                              placeholder="SUM(QTYISSUED)&#10;Monthly division-wise aggregation">{{ old('calculation_details', $report->calculation_details) }}</textarea>

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Columns Displayed
                    </label>

                    <textarea name="columns_displayed"
                              class="form-control"
                              placeholder="Item Code&#10;Item Name&#10;Category&#10;Apr AG&#10;Apr AH...">{{ old('columns_displayed', $report->columns_displayed) }}</textarea>

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Sorting
                    </label>

                    <textarea name="sorting"
                              class="form-control"
                              placeholder="ITEM_CATG ASC&#10;ITEM_CODE ASC">{{ old('sorting', $report->sorting) }}</textarea>

                </div>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- TECHNICAL --}}
        {{-- ===================================================== --}}

        <div class="form-card">

            <div class="section-title">
                <i class="bi bi-code-slash"></i>
                Technical Implementation
            </div>

            <div class="row g-3">

                <div class="col-md-6">

                    <label class="form-label">
                        Route
                    </label>

                    <input type="text"
                           name="route"
                           value="{{ old('route', $report->route) }}"
                           class="form-control"
                           placeholder="/reports/asv">

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Controller
                    </label>

                    <input type="text"
                           name="controller"
                           value="{{ old('controller', $report->controller) }}"
                           class="form-control"
                           placeholder="AsvReportController">

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Blade View
                    </label>

                    <input type="text"
                           name="view"
                           value="{{ old('view', $report->view) }}"
                           class="form-control"
                           placeholder="reports.asv-report">

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Procedure / Function
                    </label>

                    <input type="text"
                           name="procedure_function"
                           value="{{ old('procedure_function', $report->procedure_function) }}"
                           class="form-control"
                           placeholder="None / procedure name">

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Output Types
                    </label>

                    <input type="text"
                           name="output_types"
                           value="{{ old('output_types', $report->output_types) }}"
                           class="form-control"
                           placeholder="Web, CSV, Print">

                </div>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- SECURITY --}}
        {{-- ===================================================== --}}

        <div class="form-card">

            <div class="section-title">
                <i class="bi bi-shield-lock"></i>
                Access & Management
            </div>

            <div class="row g-3">

                <div class="col-md-4">

                    <label class="form-label">
                        Access Role
                    </label>

                    <input type="text"
                           name="access_role"
                           value="{{ old('access_role', $report->access_role) }}"
                           class="form-control"
                           placeholder="Inventory, Management">

                </div>


                <div class="col-md-3">

                    <label class="form-label">
                        Status *
                    </label>

                    <select name="status"
                            class="form-select">

                        <option value="active"
                            @selected(old('status', $report->status) == 'active')>

                            Active

                        </option>

                        <option value="inactive"
                            @selected(old('status', $report->status) == 'inactive')>

                            Inactive

                        </option>

                    </select>

                </div>


                <div class="col-md-2">

                    <label class="form-label">
                        Version
                    </label>

                    <input type="text"
                           name="version"
                           value="{{ old('version', $report->version ?? '1.0') }}"
                           class="form-control">

                </div>


                <div class="col-md-12">

                    <label class="form-label">
                        Notes
                    </label>

                    <textarea name="notes"
                              class="form-control"
                              placeholder="Additional technical or business notes">{{ old('notes', $report->notes) }}</textarea>

                </div>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- AUDIT INFORMATION --}}
        {{-- ===================================================== --}}

        <div class="form-card">

            <div class="section-title">
                <i class="bi bi-clock-history"></i>
                Audit Information
            </div>

            <div class="row">

                <div class="col-md-6 audit-info">

                    <strong>Created:</strong>

                    @if($report->created_at)
                        {{ $report->created_at->format('d M Y, h:i A') }}
                    @else
                        —
                    @endif

                </div>


                <div class="col-md-6 audit-info">

                    <strong>Last Updated:</strong>

                    @if($report->updated_at)
                        {{ $report->updated_at->format('d M Y, h:i A') }}
                    @else
                        —
                    @endif

                </div>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- BUTTONS --}}
        {{-- ===================================================== --}}

        <div class="d-flex justify-content-between align-items-center gap-2 mb-5">

            <a href="{{ route('reports.library.show', $report) }}"
               class="btn btn-outline-secondary">

                <i class="bi bi-x-lg me-1"></i>
                Cancel

            </a>


            <button type="submit"
                    class="btn btn-dark px-4">

                <i class="bi bi-check-lg me-1"></i>
                Update Report

            </button>

        </div>

    </form>

</div>

@endsection