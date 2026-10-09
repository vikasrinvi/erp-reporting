@extends('layouts.app')

@section('title', 'Query Builder - ' . $report->report_name)

@section('content')

<style>

    .builder-page {
        background: #f5f7fb;
        min-height: calc(100vh - 68px);
        padding: 24px;
    }

    .builder-header {
        background: linear-gradient(135deg, #172033, #273b63);
        color: white;
        border-radius: 18px;
        padding: 24px 28px;
        margin-bottom: 20px;
    }

    .builder-card {
        background: white;
        border-radius: 16px;
        border: 0;
        padding: 22px;
        margin-bottom: 20px;
        box-shadow: 0 5px 20px rgba(0,0,0,.05);
    }

    .advanced-card {
        border: 1px solid #dbe3f0;
        background: linear-gradient(180deg, #ffffff 0%, #f9fbff 100%);
    }

    .section-title {
        font-size: 16px;
        font-weight: 700;
        color: #172033;
        border-bottom: 1px solid #eaecf0;
        padding-bottom: 12px;
        margin-bottom: 18px;
    }

    .section-title i {
        margin-right: 7px;
    }

    .sub-section-title {
        font-size: 14px;
        font-weight: 700;
        color: #344054;
        margin-bottom: 12px;
    }

    .form-label {
        font-size: 12px;
        font-weight: 600;
        color: #344054;
    }

    .form-control,
    .form-select {
        font-size: 13px;
    }

    .builder-row {
        background: #f8fafc;
        border: 1px solid #eaecf0;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 10px;
    }

    .advanced-row {
        background: #ffffff;
        border: 1px solid #dfe6f0;
    }

    .help-text {
        font-size: 11px;
        color: #98a2b3;
    }

    .builder-help {
        font-size: 11px;
        color: #98a2b3;
    }

    .sql-editor {
        background: #101828;
        color: #f8fafc;
        border: 0;
        border-radius: 12px;
        padding: 18px;
        font-family: Consolas, Monaco, monospace;
        font-size: 13px;
        line-height: 1.6;
        min-height: 380px;
        resize: vertical;
    }

    .result-wrapper {
        max-height: 600px;
        overflow: auto;
        border: 1px solid #eaecf0;
        border-radius: 10px;
    }

    .result-wrapper table {
        margin-bottom: 0;
        white-space: nowrap;
    }

    .result-wrapper thead th {
        position: sticky;
        top: 0;
        background: #172033;
        color: white;
        z-index: 2;
    }

    .empty-result {
        padding: 50px;
        text-align: center;
        color: #98a2b3;
    }

    .loading {
        display: none;
    }

    .advanced-toggle {
        border: 1px solid #d0d5dd;
        background: white;
        border-radius: 10px;
        padding: 14px 16px;
    }

    .advanced-toggle:hover {
        background: #f9fafb;
    }

    .advanced-badge {
        font-size: 10px;
        background: #eef4ff;
        color: #344054;
        border-radius: 20px;
        padding: 4px 9px;
        font-weight: 600;
    }

    .pivot-box {
        border: 1px solid #dbe3f0;
        border-radius: 12px;
        padding: 16px;
        background: #f9fbff;
    }

    .dimension-box {
        border: 1px dashed #cbd5e1;
        background: white;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 10px;
    }

    .value-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #eef2f7;
        border: 1px solid #d9e0ea;
        border-radius: 20px;
        padding: 4px 9px;
        font-size: 11px;
        margin: 3px;
    }

    .value-chip button {
        border: 0;
        background: transparent;
        padding: 0;
        line-height: 1;
        color: #667085;
    }

    .sql-status {
        font-size: 11px;
        color: #667085;
    }

    .sticky-actions {
        position: sticky;
        bottom: 16px;
        z-index: 10;
    }

    .action-bar {
        background: rgba(255,255,255,.96);
        backdrop-filter: blur(8px);
        border: 1px solid #d0d5dd;
        border-radius: 14px;
        padding: 12px;
        box-shadow: 0 10px 30px rgba(16,24,40,.12);
    }

    .switch-label {
        cursor: pointer;
        user-select: none;
    }

    .code-hint {
        font-family: Consolas, Monaco, monospace;
        font-size: 11px;
        background: #f2f4f7;
        border-radius: 6px;
        padding: 2px 5px;
    }

    @media (max-width: 768px) {

        .builder-page {
            padding: 12px;
        }

        .builder-header {
            padding: 18px;
        }

    }

</style>


<div class="builder-page">

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="builder-header">

        <div class="d-flex justify-content-between align-items-center gap-3">

            <div>

                <div class="small opacity-75 mb-1">
                    {{ $report->report_code }}
                </div>

                <h3 class="mb-1">
                    <i class="bi bi-diagram-3 me-2"></i>
                    {{ $report->report_name }}
                </h3>

                <div class="opacity-75">
                    Visual SQL Query Builder
                </div>

            </div>

            <div class="d-flex gap-2">

                <a href="{{ route('reports.library.show', $report) }}"
                   class="btn btn-outline-light">

                    <i class="bi bi-eye me-1"></i>
                    Report

                </a>

                <a href="{{ route('reports.library') }}"
                   class="btn btn-light">

                    <i class="bi bi-arrow-left me-1"></i>
                    Library

                </a>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- QUERY NAME --}}
    {{-- ========================================================= --}}

    <div class="builder-card">

        <div class="row align-items-end g-3">

            <div class="col-md-6">

                <label class="form-label">
                    Query Name
                </label>

                <input
                    type="text"
                    id="queryName"
                    class="form-control"
                    value="{{ $query->query_name ?? 'Default Query' }}"
                    placeholder="Example: ASV Monthly Issue"
                >

            </div>

            <div class="col-md-6">

                <div class="builder-help">
                    Build the query visually, generate SQL, test it, and save the configuration.
                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- 1. TABLES --}}
    {{-- ========================================================= --}}

    <div class="builder-card">

        <div class="section-title">
            <i class="bi bi-table"></i>
            1. Tables
        </div>

        <div id="tablesContainer"></div>

        <button
            type="button"
            class="btn btn-outline-dark btn-sm"
            onclick="addTableRow()">

            <i class="bi bi-plus-lg me-1"></i>
            Add Table

        </button>

    </div>


    {{-- ========================================================= --}}
    {{-- 2. JOINS --}}
    {{-- ========================================================= --}}

    <div class="builder-card">

        <div class="section-title">
            <i class="bi bi-bezier2"></i>
            2. Joins
        </div>

        <div id="joinsContainer"></div>

        <button
            type="button"
            class="btn btn-outline-dark btn-sm"
            onclick="addJoinRow()">

            <i class="bi bi-plus-lg me-1"></i>
            Add Join

        </button>

    </div>


    {{-- ========================================================= --}}
    {{-- 3. COLUMNS --}}
    {{-- ========================================================= --}}

    <div class="builder-card">

        <div class="section-title">
            <i class="bi bi-layout-three-columns"></i>
            3. Columns
        </div>

        <div id="columnsContainer"></div>

        <button
            type="button"
            class="btn btn-outline-dark btn-sm"
            onclick="addColumnRow()">

            <i class="bi bi-plus-lg me-1"></i>
            Add Column

        </button>

    </div>


    {{-- ========================================================= --}}
    {{-- 4. FILTERS --}}
    {{-- ========================================================= --}}

    <div class="builder-card">

        <div class="section-title">
            <i class="bi bi-funnel"></i>
            4. Filters
        </div>

        <div id="filtersContainer"></div>

        <button
            type="button"
            class="btn btn-outline-dark btn-sm"
            onclick="addFilterRow()">

            <i class="bi bi-plus-lg me-1"></i>
            Add Filter

        </button>

    </div>


    {{-- REPORT PAGE FILTER DEFINITIONS --}}

    <div class="builder-card">

        <div class="section-title">
            <i class="bi bi-funnel"></i>
            Report Page Filters
        </div>

        <p class="text-muted small">
            Choose which fields users can filter on when viewing the saved report.
        </p>

        <div id="reportFiltersContainer"></div>

        <button
            type="button"
            class="btn btn-outline-primary btn-sm"
            onclick="addReportFilter()"
        >
            <i class="bi bi-plus-lg me-1"></i>
            Add Report Filter
        </button>

    </div>


    {{-- ========================================================= --}}
    {{-- 5. GROUP BY --}}
    {{-- ========================================================= --}}

    <div class="builder-card">

        <div class="section-title">
            <i class="bi bi-collection"></i>
            5. Group By
        </div>

        <div id="groupContainer"></div>

        <button
            type="button"
            class="btn btn-outline-dark btn-sm"
            onclick="addGroupRow()">

            <i class="bi bi-plus-lg me-1"></i>
            Add Grouping

        </button>

    </div>


    {{-- ========================================================= --}}
    {{-- 6. ORDER BY --}}
    {{-- ========================================================= --}}

    <div class="builder-card">

        <div class="section-title">
            <i class="bi bi-sort-down"></i>
            6. Order By
        </div>

        <div id="orderContainer"></div>

        <button
            type="button"
            class="btn btn-outline-dark btn-sm"
            onclick="addOrderRow()">

            <i class="bi bi-plus-lg me-1"></i>
            Add Sorting

        </button>

    </div>


    {{-- ========================================================= --}}
    {{-- 7. ADVANCED SQL --}}
    {{-- ========================================================= --}}

    <div class="builder-card advanced-card">

        <div class="section-title d-flex justify-content-between align-items-center">

            <div>
                <i class="bi bi-stars"></i>
                7. Advanced SQL
            </div>

            <span class="advanced-badge">
                CTE + Calculations + Pivot + HAVING
            </span>

        </div>


        {{-- ----------------------------------------------------- --}}
        {{-- DISTINCT + LIMIT --}}
        {{-- ----------------------------------------------------- --}}

        <div class="row g-3 mb-4">

            <div class="col-md-4">

                <div class="form-check form-switch">

                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="distinctFlag">

                    <label
                        class="form-check-label switch-label"
                        for="distinctFlag">

                        SELECT DISTINCT

                    </label>

                </div>

                <div class="help-text mt-1">
                    Removes duplicate rows in normal queries.
                </div>

            </div>


            <div class="col-md-4">

                <label class="form-label">
                    Result Limit
                </label>

                <input
                    type="number"
                    id="limitValue"
                    class="form-control"
                    min="1"
                    max="5000"
                    value="500">

                <div class="help-text mt-1">
                    Maximum rows when executing the generated query.
                </div>

            </div>

        </div>


        {{-- ----------------------------------------------------- --}}
        {{-- CTE --}}
        {{-- ----------------------------------------------------- --}}

        <div class="pivot-box mb-4">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <div>

                    <div class="sub-section-title mb-1">
                        <i class="bi bi-layers me-1"></i>
                        Common Table Expression (CTE)
                    </div>

                    <div class="help-text">
                        Wrap the current visual query inside a
                        <span class="code-hint">WITH</span>
                        clause.
                    </div>

                </div>

                <div class="form-check form-switch">

                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="cteEnabled">

                    <label
                        class="form-check-label"
                        for="cteEnabled">

                        Enable CTE

                    </label>

                </div>

            </div>


            <div id="cteSettings"
                 style="display:none;">

                <div class="row g-3">

                    <div class="col-md-4">

                        <label class="form-label">
                            CTE Name
                        </label>

                        <input
                            type="text"
                            id="cteName"
                            class="form-control"
                            value="base"
                            placeholder="base">

                    </div>

                    <div class="col-md-8">

                        <label class="form-label">
                            Purpose
                        </label>

                        <input
                            type="text"
                            id="cteDescription"
                            class="form-control"
                            placeholder="Prepare aggregated base data before pivoting">

                    </div>

                </div>

                <div class="alert alert-light border mt-3 mb-0 small">

                    <i class="bi bi-info-circle me-1"></i>

                    When Pivot is enabled, this visual query becomes the CTE source.
                    For example:

                    <code>
                        WITH base AS (...)
                    </code>

                </div>

            </div>

        </div>


        {{-- ----------------------------------------------------- --}}
        {{-- CALCULATED COLUMNS --}}
        {{-- ----------------------------------------------------- --}}

        <div class="pivot-box mb-4">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <div>

                    <div class="sub-section-title mb-1">

                        <i class="bi bi-calculator me-1"></i>

                        Calculated Columns

                    </div>

                    <div class="help-text">

                        Add SQL expressions such as
                        <span class="code-hint">DATE_FORMAT(ib.VRDATE, '%Y-%m')</span>
                        or
                        <span class="code-hint">SUM(ib.QTYISSUED)</span>.

                    </div>

                </div>

                <button
                    type="button"
                    class="btn btn-outline-dark btn-sm"
                    onclick="addCalculatedColumnRow()">

                    <i class="bi bi-plus-lg me-1"></i>
                    Add Calculation

                </button>

            </div>

            <div id="calculatedColumnsContainer"></div>

        </div>


        {{-- ----------------------------------------------------- --}}
        {{-- HAVING --}}
        {{-- ----------------------------------------------------- --}}

        <div class="pivot-box mb-4">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <div>

                    <div class="sub-section-title mb-1">

                        <i class="bi bi-filter-circle me-1"></i>

                        HAVING

                    </div>

                    <div class="help-text">
                        Filter aggregated results after GROUP BY.
                    </div>

                </div>

                <button
                    type="button"
                    class="btn btn-outline-dark btn-sm"
                    onclick="addHavingRow()">

                    <i class="bi bi-plus-lg me-1"></i>
                    Add HAVING

                </button>

            </div>

            <div id="havingContainer"></div>

        </div>


        {{-- ----------------------------------------------------- --}}
        {{-- PIVOT --}}
        {{-- ----------------------------------------------------- --}}

        <div class="pivot-box">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <div>

                    <div class="sub-section-title mb-1">

                        <i class="bi bi-grid-3x3-gap me-1"></i>

                        Pivot / Cross Tab

                    </div>

                    <div class="help-text">

                        Convert rows such as
                        <span class="code-hint">YM</span>
                        and
                        <span class="code-hint">DIV_CODE</span>
                        into dynamic columns.

                    </div>

                </div>

                <div class="form-check form-switch">

                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="pivotEnabled">

                    <label
                        class="form-check-label"
                        for="pivotEnabled">

                        Enable Pivot

                    </label>

                </div>

            </div>


            <div id="pivotSettings"
                 style="display:none;">

                {{-- Rows --}}

                <div class="row g-3 mb-3">

                    <div class="col-md-6">

                        <label class="form-label">
                            Pivot Row Columns
                        </label>

                        <input
                            type="text"
                            id="pivotRows"
                            class="form-control"
                            placeholder="ITEM_CODE, ITEM_NAME, MKTG_CATG">

                        <div class="help-text mt-1">
                            Comma-separated columns from the CTE.
                        </div>

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">
                            Pivot Column
                        </label>

                        <input
                            type="text"
                            id="pivotColumn"
                            class="form-control"
                            placeholder="YM">

                        <div class="help-text mt-1">
                            Example:
                            <span class="code-hint">YM</span>
                        </div>

                    </div>

                </div>


                {{-- Value + Aggregate --}}

                <div class="row g-3 mb-4">

                    <div class="col-md-6">

                        <label class="form-label">
                            Value Column
                        </label>

                        <input
                            type="text"
                            id="pivotValue"
                            class="form-control"
                            placeholder="QTY">

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">
                            Aggregate Function
                        </label>

                        <select
                            id="pivotAggregate"
                            class="form-select">

                            <option value="SUM">
                                SUM
                            </option>

                            <option value="COUNT">
                                COUNT
                            </option>

                            <option value="AVG">
                                AVG
                            </option>

                            <option value="MIN">
                                MIN
                            </option>

                            <option value="MAX">
                                MAX
                            </option>

                        </select>

                    </div>

                </div>


                {{-- Pivot Values --}}

                <div class="mb-4">

                    <div class="d-flex justify-content-between align-items-center mb-2">

                        <div>

                            <label class="form-label mb-0">
                                Pivot Column Values
                            </label>

                            <div class="help-text">
                                Example:
                                2026-04, 2026-05, 2026-06...
                            </div>

                        </div>

                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            onclick="generateMonthValues()">

                            <i class="bi bi-calendar3 me-1"></i>
                            Generate Months

                        </button>

                    </div>


                    <div class="row g-2">

                        <div class="col-md-4">

                            <input
                                type="text"
                                id="pivotValueInput"
                                class="form-control"
                                placeholder="2026-04">

                        </div>

                        <div class="col-md-2">

                            <button
                                type="button"
                                class="btn btn-dark w-100"
                                onclick="addPivotValue()">

                                <i class="bi bi-plus-lg"></i>
                                Add

                            </button>

                        </div>

                    </div>


                    <div id="pivotValuesContainer"
                         class="mt-2"></div>

                </div>


                {{-- Pivot Dimensions --}}

                <div>

                    <div class="d-flex justify-content-between align-items-center mb-2">

                        <div>

                            <label class="form-label mb-0">
                                Pivot Dimensions
                            </label>

                            <div class="help-text">
                                Each dimension creates combinations with the pivot values.
                            </div>

                        </div>

                        <button
                            type="button"
                            class="btn btn-outline-dark btn-sm"
                            onclick="addPivotDimension()">

                            <i class="bi bi-plus-lg me-1"></i>
                            Add Dimension

                        </button>

                    </div>


                    <div id="pivotDimensionsContainer"></div>

                </div>


                {{-- Preview --}}

                <div class="alert alert-primary border mt-4 mb-0">

                    <div class="fw-semibold mb-1">

                        <i class="bi bi-eye me-1"></i>

                        Pivot Preview

                    </div>

                    <div id="pivotPreview"
                         class="small">

                        Enable pivot and add values/dimensions to see the generated columns.

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- GENERATE --}}
    {{-- ========================================================= --}}

    <div class="builder-card">

        <div class="d-flex justify-content-between align-items-center gap-3">

            <div>

                <strong>
                    Generate SQL
                </strong>

                <div class="builder-help">
                    Convert the complete visual configuration into SQL.
                </div>

            </div>

            <button
                type="button"
                class="btn btn-dark px-4"
                onclick="generateSQL()">

                <i class="bi bi-magic me-1"></i>
                Generate SQL

            </button>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- SQL EDITOR --}}
    {{-- ========================================================= --}}

    <div class="builder-card">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <div>

                <div class="section-title mb-1 border-0">

                    <i class="bi bi-code-square"></i>

                    SQL Query

                </div>

                <div class="sql-status"
                     id="sqlStatus">

                    Generated SQL will appear here.

                </div>

            </div>

            <div class="d-flex gap-2">

                <button
                    type="button"
                    class="btn btn-outline-secondary btn-sm"
                    onclick="copySQL()">

                    <i class="bi bi-copy me-1"></i>
                    Copy

                </button>

                <button
                    type="button"
                    class="btn btn-success btn-sm"
                    onclick="runSQL()">

                    <i class="bi bi-play-fill me-1"></i>
                    Run Query

                </button>

            </div>

        </div>


        <textarea
            id="sqlEditor"
            class="form-control sql-editor"
            spellcheck="false">{{ $query->custom_sql ?? $query->generated_sql ?? '' }}</textarea>


        <div class="d-flex justify-content-between align-items-center mt-3">

            <div class="builder-help">

                You can manually edit the SQL before executing it.
                Only SELECT / WITH queries are permitted.

            </div>

            <button
                type="button"
                class="btn btn-dark btn-sm"
                onclick="saveQuery()">

                <i class="bi bi-save me-1"></i>
                Save Query

            </button>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- RESULTS --}}
    {{-- ========================================================= --}}

    <div class="builder-card">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <div>

                <div class="section-title mb-1 border-0">

                    <i class="bi bi-grid-3x3-gap"></i>

                    Query Result

                </div>

                <div id="resultCount"
                     class="builder-help">

                    Run the query to see results.

                </div>

            </div>

            <div class="loading"
                 id="loading">

                <div class="spinner-border spinner-border-sm"></div>

                Running...

            </div>

        </div>


        <div id="resultContainer">

            <div class="empty-result">

                <i class="bi bi-database fs-2 d-block mb-2"></i>

                No query executed yet.

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- STICKY ACTION BAR --}}
    {{-- ========================================================= --}}

    <div class="sticky-actions">

        <div class="action-bar d-flex justify-content-between align-items-center gap-2">

            <div class="small text-muted">

                <i class="bi bi-lightning-charge me-1"></i>

                Advanced builder enabled

            </div>

            <div class="d-flex gap-2">

                <button
                    type="button"
                    class="btn btn-outline-secondary btn-sm"
                    onclick="generateSQL()">

                    <i class="bi bi-magic me-1"></i>
                    Generate

                </button>

                <button
                    type="button"
                    class="btn btn-success btn-sm"
                    onclick="runSQL()">

                    <i class="bi bi-play-fill me-1"></i>
                    Run

                </button>

                <button
                    type="button"
                    class="btn btn-dark btn-sm"
                    onclick="saveQuery()">

                    <i class="bi bi-save me-1"></i>
                    Save

                </button>

            </div>

        </div>

    </div>

</div>


<script>

/*
|--------------------------------------------------------------------------
| Global Data
|--------------------------------------------------------------------------
*/

const tables = @json($tables);

@php
    $savedQueryData = $query ? [
        'tables' => $query->tables_json ?? [],
        'joins' => $query->joins_json ?? [],
        'columns' => $query->columns_json ?? [],
        'filters' => $query->filters_json ?? [],
        'group_by' => $query->group_by_json ?? [],
        'order_by' => $query->order_by_json ?? [],
        'cte' => $query->cte_json ?? null,
        'calculated_columns' => $query->calculated_columns_json ?? [],
        'pivot' => $query->pivot_json ?? null,
        'having' => $query->having_json ?? [],
        'distinct' => $query->distinct_flag ?? false,
        'limit' => $query->limit_value ?? 500,
    ] : null;
@endphp

const savedQuery = @json($savedQueryData);


let pivotValues = [];
let pivotDimensions = [];

let reportFilters = [];


/*
|--------------------------------------------------------------------------
| Utility
|--------------------------------------------------------------------------
*/

function escapeHtml(value) {

    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

}


function tableOptions(selected = '') {

    let html = `
        <option value="">
            Select Table
        </option>
    `;

    tables.forEach(table => {

        html += `
            <option
                value="${escapeHtml(table)}"
                ${selected === table ? 'selected' : ''}>
                ${escapeHtml(table)}
            </option>
        `;

    });

    return html;

}


function removeRow(button) {

    const row = button.closest('.builder-row');

    if (row) {
        row.remove();
    }

}


/*
|--------------------------------------------------------------------------
| Tables
|--------------------------------------------------------------------------
*/

function addTableRow(data = {}) {

    document.getElementById('tablesContainer').insertAdjacentHTML(
        'beforeend',
        `
        <div class="builder-row table-row">

            <div class="row g-2 align-items-end">

                <div class="col-md-7">

                    <label class="form-label">
                        Table
                    </label>

                    <select
                        class="form-select table-name"
                        onchange="refreshColumnOptions()">

                        ${tableOptions(data.table ?? '')}

                    </select>

                </div>

                <div class="col-md-4">

                    <label class="form-label">
                        Alias
                    </label>

                    <input
                        type="text"
                        class="form-control table-alias"
                        value="${escapeHtml(data.alias ?? '')}"
                        placeholder="ib">

                </div>

                <div class="col-md-1">

                    <button
                        type="button"
                        class="btn btn-outline-danger w-100"
                        onclick="removeRow(this)">

                        <i class="bi bi-trash"></i>

                    </button>

                </div>

            </div>

        </div>
        `
    );

}


/*
|--------------------------------------------------------------------------
| Joins
|--------------------------------------------------------------------------
*/

function addJoinRow(data = {}) {

    document.getElementById('joinsContainer').insertAdjacentHTML(
        'beforeend',
        `
        <div class="builder-row">

            <div class="row g-2 align-items-end">

                <div class="col-md-2">

                    <label class="form-label">
                        Join Type
                    </label>

                    <select class="form-select join-type">

                        <option value="INNER"
                            ${data.type === 'INNER' ? 'selected' : ''}>
                            INNER
                        </option>

                        <option value="LEFT"
                            ${data.type === 'LEFT' ? 'selected' : ''}>
                            LEFT
                        </option>

                        <option value="RIGHT"
                            ${data.type === 'RIGHT' ? 'selected' : ''}>
                            RIGHT
                        </option>

                    </select>

                </div>

                <div class="col-md-3">

                    <label class="form-label">
                        Join Table
                    </label>

                    <select class="form-select join-table">

                        ${tableOptions(data.table ?? '')}

                    </select>

                </div>

                <div class="col-md-2">

                    <label class="form-label">
                        Alias
                    </label>

                    <input
                        type="text"
                        class="form-control join-alias"
                        value="${escapeHtml(data.alias ?? '')}"
                        placeholder="im">

                </div>

                <div class="col-md-2">

                    <label class="form-label">
                        Left Column
                    </label>

                    <input
                        type="text"
                        class="form-control join-left"
                        value="${escapeHtml(data.left ?? '')}"
                        placeholder="ib.ITEM_CODE">

                </div>

                <div class="col-md-1">

                    <label class="form-label">
                        Op
                    </label>

                    <select class="form-select join-operator">

                        ${['=', '>', '<', '>=', '<=', '<>'].map(op => `
                            <option
                                value="${escapeHtml(op)}"
                                ${data.operator === op ? 'selected' : ''}>
                                ${escapeHtml(op)}
                            </option>
                        `).join('')}

                    </select>

                </div>

                <div class="col-md-2">

                    <label class="form-label">
                        Right Column
                    </label>

                    <input
                        type="text"
                        class="form-control join-right"
                        value="${escapeHtml(data.right ?? '')}"
                        placeholder="im.ITEM_CODE">

                </div>

                <div class="col-md-1">

                    <button
                        type="button"
                        class="btn btn-outline-danger w-100"
                        onclick="removeRow(this)">

                        <i class="bi bi-trash"></i>

                    </button>

                </div>

            </div>

        </div>
        `
    );

}


/*
|--------------------------------------------------------------------------
| Columns
|--------------------------------------------------------------------------
*/

function addColumnRow(data = {}) {

    document.getElementById('columnsContainer').insertAdjacentHTML(
        'beforeend',
        `
        <div class="builder-row">

            <div class="row g-2 align-items-end">

                <div class="col-md-5">

                    <label class="form-label">
                        Column / Expression
                    </label>

                    <input
                        type="text"
                        class="form-control column-expression"
                        value="${escapeHtml(data.expression ?? '')}"
                        placeholder="ib.ITEM_CODE">

                </div>

                <div class="col-md-2">

                    <label class="form-label">
                        Function
                    </label>

                    <select class="form-select column-function">

                        <option value="NONE"
                            ${!data.function || data.function === 'NONE' ? 'selected' : ''}>
                            None
                        </option>

                        <option value="SUM"
                            ${data.function === 'SUM' ? 'selected' : ''}>
                            SUM
                        </option>

                        <option value="COUNT"
                            ${data.function === 'COUNT' ? 'selected' : ''}>
                            COUNT
                        </option>

                        <option value="AVG"
                            ${data.function === 'AVG' ? 'selected' : ''}>
                            AVG
                        </option>

                        <option value="MIN"
                            ${data.function === 'MIN' ? 'selected' : ''}>
                            MIN
                        </option>

                        <option value="MAX"
                            ${data.function === 'MAX' ? 'selected' : ''}>
                            MAX
                        </option>

                    </select>

                </div>

                <div class="col-md-4">

                    <label class="form-label">
                        Alias
                    </label>

                    <input
                        type="text"
                        class="form-control column-alias"
                        value="${escapeHtml(data.alias ?? '')}"
                        placeholder="TOTAL_QTY">

                </div>

                <div class="col-md-1">

                    <button
                        type="button"
                        class="btn btn-outline-danger w-100"
                        onclick="removeRow(this)">

                        <i class="bi bi-trash"></i>

                    </button>

                </div>

            </div>

        </div>
        `
    );

}


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

function addFilterRow(data = {}) {

    document.getElementById('filtersContainer').insertAdjacentHTML(
        'beforeend',
        `
        <div class="builder-row">

            <div class="row g-2 align-items-end">

                <div class="col-md-3">

                    <label class="form-label">
                        Column
                    </label>

                    <input
                        type="text"
                        class="form-control filter-column"
                        value="${escapeHtml(data.column ?? '')}"
                        placeholder="pm.MKTG_CATG">

                </div>

                <div class="col-md-2">

                    <label class="form-label">
                        Operator
                    </label>

                    <select class="form-select filter-operator">

                        ${[
                            '=',
                            '!=',
                            '>',
                            '<',
                            '>=',
                            '<=',
                            'LIKE',
                            'NOT LIKE',
                            'IS NULL',
                            'IS NOT NULL',
                            'IN',
                            'NOT IN'
                        ].map(op => `
                            <option
                                value="${escapeHtml(op)}"
                                ${data.operator === op ? 'selected' : ''}>
                                ${escapeHtml(op)}
                            </option>
                        `).join('')}

                    </select>

                </div>

                <div class="col-md-6">

                    <label class="form-label">
                        Value
                    </label>

                    <input
                        type="text"
                        class="form-control filter-value"
                        value="${escapeHtml(data.value ?? '')}"
                        placeholder="ASV">

                </div>

                <div class="col-md-1">

                    <button
                        type="button"
                        class="btn btn-outline-danger w-100"
                        onclick="removeRow(this)">

                        <i class="bi bi-trash"></i>

                    </button>

                </div>

            </div>

        </div>
        `
    );

}


/*
|--------------------------------------------------------------------------
| Group By
|--------------------------------------------------------------------------
*/

function addGroupRow(data = '') {

    document.getElementById('groupContainer').insertAdjacentHTML(
        'beforeend',
        `
        <div class="builder-row">

            <div class="row g-2 align-items-end">

                <div class="col-md-11">

                    <label class="form-label">
                        Column / Expression
                    </label>

                    <input
                        type="text"
                        class="form-control group-column"
                        value="${escapeHtml(data)}"
                        placeholder="ib.ITEM_CODE">

                </div>

                <div class="col-md-1">

                    <button
                        type="button"
                        class="btn btn-outline-danger w-100"
                        onclick="removeRow(this)">

                        <i class="bi bi-trash"></i>

                    </button>

                </div>

            </div>

        </div>
        `
    );

}


/*
|--------------------------------------------------------------------------
| Order By
|--------------------------------------------------------------------------
*/

function addOrderRow(data = {}) {

    document.getElementById('orderContainer').insertAdjacentHTML(
        'beforeend',
        `
        <div class="builder-row">

            <div class="row g-2 align-items-end">

                <div class="col-md-7">

                    <label class="form-label">
                        Column / Expression
                    </label>

                    <input
                        type="text"
                        class="form-control order-column"
                        value="${escapeHtml(data.column ?? '')}"
                        placeholder="ib.ITEM_CODE">

                </div>

                <div class="col-md-4">

                    <label class="form-label">
                        Direction
                    </label>

                    <select class="form-select order-direction">

                        <option value="ASC"
                            ${data.direction !== 'DESC' ? 'selected' : ''}>
                            ASC
                        </option>

                        <option value="DESC"
                            ${data.direction === 'DESC' ? 'selected' : ''}>
                            DESC
                        </option>

                    </select>

                </div>

                <div class="col-md-1">

                    <button
                        type="button"
                        class="btn btn-outline-danger w-100"
                        onclick="removeRow(this)">

                        <i class="bi bi-trash"></i>

                    </button>

                </div>

            </div>

        </div>
        `
    );

}


/*
|--------------------------------------------------------------------------
| Calculated Columns
|--------------------------------------------------------------------------
*/

function addCalculatedColumnRow(data = {}) {

    document.getElementById('calculatedColumnsContainer')
        .insertAdjacentHTML(
            'beforeend',
            `
            <div class="builder-row advanced-row">

                <div class="row g-2 align-items-end">

                    <div class="col-md-7">

                        <label class="form-label">
                            SQL Expression
                        </label>

                        <input
                            type="text"
                            class="form-control calculated-expression"
                            value="${escapeHtml(data.expression ?? '')}"
                            placeholder="DATE_FORMAT(ib.VRDATE, '%Y-%m')">

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">
                            Alias
                        </label>

                        <input
                            type="text"
                            class="form-control calculated-alias"
                            value="${escapeHtml(data.alias ?? '')}"
                            placeholder="YM">

                    </div>

                    <div class="col-md-1">

                        <button
                            type="button"
                            class="btn btn-outline-danger w-100"
                            onclick="removeRow(this)">

                            <i class="bi bi-trash"></i>

                        </button>

                    </div>

                </div>

            </div>
            `
        );

}


/*
|--------------------------------------------------------------------------
| HAVING
|--------------------------------------------------------------------------
*/

function addHavingRow(data = {}) {

    document.getElementById('havingContainer')
        .insertAdjacentHTML(
            'beforeend',
            `
            <div class="builder-row advanced-row">

                <div class="row g-2 align-items-end">

                    <div class="col-md-4">

                        <label class="form-label">
                            Expression
                        </label>

                        <input
                            type="text"
                            class="form-control having-column"
                            value="${escapeHtml(data.column ?? '')}"
                            placeholder="SUM(ib.QTYISSUED)">

                    </div>

                    <div class="col-md-2">

                        <label class="form-label">
                            Operator
                        </label>

                        <select class="form-select having-operator">

                            ${['=', '!=', '>', '<', '>=', '<=', 'LIKE'].map(op => `
                                <option
                                    value="${escapeHtml(op)}"
                                    ${data.operator === op ? 'selected' : ''}>
                                    ${escapeHtml(op)}
                                </option>
                            `).join('')}

                        </select>

                    </div>

                    <div class="col-md-5">

                        <label class="form-label">
                            Value
                        </label>

                        <input
                            type="text"
                            class="form-control having-value"
                            value="${escapeHtml(data.value ?? '')}"
                            placeholder="100">

                    </div>

                    <div class="col-md-1">

                        <button
                            type="button"
                            class="btn btn-outline-danger w-100"
                            onclick="removeRow(this)">

                            <i class="bi bi-trash"></i>

                        </button>

                    </div>

                </div>

            </div>
            `
        );

}


/*
|--------------------------------------------------------------------------
| Pivot Values
|--------------------------------------------------------------------------
*/

function addPivotValue(value = null) {

    value = value ?? document.getElementById('pivotValueInput').value.trim();

    if (!value) {
        return;
    }

    if (pivotValues.includes(value)) {

        document.getElementById('pivotValueInput').value = '';

        return;
    }

    pivotValues.push(value);

    document.getElementById('pivotValueInput').value = '';

    renderPivotValues();

    updatePivotPreview();

}


function removePivotValue(index) {

    pivotValues.splice(index, 1);

    renderPivotValues();

    updatePivotPreview();

}


function renderPivotValues() {

    const container =
        document.getElementById('pivotValuesContainer');

    if (!pivotValues.length) {

        container.innerHTML = `
            <div class="help-text">
                No pivot values added yet.
            </div>
        `;

        return;
    }

    container.innerHTML =
        pivotValues.map((value, index) => `
            <span class="value-chip">

                ${escapeHtml(value)}

                <button
                    type="button"
                    onclick="removePivotValue(${index})">

                    <i class="bi bi-x"></i>

                </button>

            </span>
        `).join('');

}


/*
|--------------------------------------------------------------------------
| Generate Month Values
|--------------------------------------------------------------------------
*/

function generateMonthValues() {

    const from =
        prompt(
            'Enter starting month (YYYY-MM):',
            pivotValues[0] ?? '2026-04'
        );

    if (!from) {
        return;
    }

    const to =
        prompt(
            'Enter ending month (YYYY-MM):',
            pivotValues[pivotValues.length - 1] ?? '2026-09'
        );

    if (!to) {
        return;
    }

    if (!/^\d{4}-\d{2}$/.test(from) ||
        !/^\d{4}-\d{2}$/.test(to)) {

        alert('Please use YYYY-MM format.');

        return;
    }

    const [startYear, startMonth] =
        from.split('-').map(Number);

    const [endYear, endMonth] =
        to.split('-').map(Number);

    let year = startYear;
    let month = startMonth;

    const generated = [];

    while (
        year < endYear ||
        (year === endYear && month <= endMonth)
    ) {

        generated.push(
            `${year}-${String(month).padStart(2, '0')}`
        );

        month++;

        if (month > 12) {

            month = 1;
            year++;

        }

        if (generated.length > 120) {
            break;
        }

    }

    pivotValues = generated;

    renderPivotValues();

    updatePivotPreview();

}


/*
|--------------------------------------------------------------------------
| Pivot Dimensions
|--------------------------------------------------------------------------
*/

function addPivotDimension(data = {}) {

    const index =
        pivotDimensions.length;

    pivotDimensions.push({
        column: data.column ?? '',
        values: data.values ?? []
    });

    document.getElementById('pivotDimensionsContainer')
        .insertAdjacentHTML(
            'beforeend',
            `
            <div class="dimension-box"
                 data-dimension-index="${index}">

                <div class="row g-2 align-items-end">

                    <div class="col-md-4">

                        <label class="form-label">
                            Dimension Column
                        </label>

                        <input
                            type="text"
                            class="form-control dimension-column"
                            value="${escapeHtml(data.column ?? '')}"
                            placeholder="DIV_CODE"
                            oninput="syncPivotDimensions()">

                    </div>

                    <div class="col-md-7">

                        <label class="form-label">
                            Dimension Values <span class="help-text mt-1"> (Separate values with commas.)</span>
                        </label>

                        <input
                            type="text"
                            class="form-control dimension-values"
                            value="${escapeHtml((data.values ?? []).join(', '))}"
                            placeholder="AG, AH, AM, DL, GZ, HL, ZP, JH"
                            oninput="syncPivotDimensions()">

                       

                    </div>

                    <div class="col-md-1">

                        <button
                            type="button"
                            class="btn btn-outline-danger w-100"
                            onclick="removePivotDimension(this)">

                            <i class="bi bi-trash"></i>

                        </button>

                    </div>

                </div>

            </div>
            `
        );

    syncPivotDimensions();

    updatePivotPreview();

}


function removePivotDimension(button) {

    button.closest('.dimension-box').remove();

    syncPivotDimensions();

    updatePivotPreview();

}


function syncPivotDimensions() {

    pivotDimensions = [];

    document.querySelectorAll(
        '#pivotDimensionsContainer .dimension-box'
    ).forEach(box => {

        const column =
            box.querySelector('.dimension-column').value.trim();

        const values =
            box.querySelector('.dimension-values').value
                .split(',')
                .map(value => value.trim())
                .filter(Boolean);

        pivotDimensions.push({
            column,
            values
        });

    });

}


/*
|--------------------------------------------------------------------------
| Pivot Preview
|--------------------------------------------------------------------------
*/

function updatePivotPreview() {

    const preview =
        document.getElementById('pivotPreview');

    if (!document.getElementById('pivotEnabled').checked) {

        preview.innerHTML =
            'Pivot is currently disabled.';

        return;
    }

    syncPivotDimensions();

    if (!pivotValues.length) {

        preview.innerHTML =
            'Add pivot values first.';

        return;
    }

    const dimensions =
        pivotDimensions.filter(
            dimension =>
                dimension.column &&
                dimension.values.length
        );

    if (!dimensions.length) {

        preview.innerHTML = `
            <span class="text-muted">
                No dimensions configured. The pivot will create one column per pivot value.
            </span>
        `;

        return;
    }

    let combinations = [[]];

    dimensions.forEach(dimension => {

        const next = [];

        combinations.forEach(existing => {

            dimension.values.forEach(value => {

                next.push([
                    ...existing,
                    {
                        column: dimension.column,
                        value
                    }
                ]);

            });

        });

        combinations = next;

    });

    const aliases = [];

    pivotValues.forEach(pivotValue => {

        combinations.forEach(combination => {

            let alias =
                formatPivotAlias(
                    pivotValue,
                    combination
                );

            aliases.push(alias);

        });

    });

    const maxPreview = 50;

    let html =
        `<div class="mb-2">
            <strong>${aliases.length}</strong>
            generated pivot column(s)
        </div>`;

    html += aliases
        .slice(0, maxPreview)
        .map(alias =>
            `<span class="value-chip">${escapeHtml(alias)}</span>`
        )
        .join('');

    if (aliases.length > maxPreview) {

        html += `
            <div class="mt-2 text-muted">
                Showing first ${maxPreview} columns.
            </div>
        `;

    }

    preview.innerHTML = html;

}


function formatPivotAlias(pivotValue, combination) {

    let prefix = pivotValue;

    if (/^\d{4}-\d{2}$/.test(pivotValue)) {

        const [year, month] =
            pivotValue.split('-');

        const date =
            new Date(
                Number(year),
                Number(month) - 1,
                1
            );

        prefix =
            date.toLocaleString(
                'en-US',
                { month: 'short' }
            ).toUpperCase();

    }

    const suffix =
        combination
            .map(item => item.value)
            .join('_');

    const alias =
        suffix
            ? `${prefix}_${suffix}`
            : prefix;

    return alias
        .replace(/[^A-Za-z0-9_]/g, '_')
        .replace(/_+/g, '_')
        .replace(/^_+|_+$/g, '')
        .toUpperCase();

}


/*
|--------------------------------------------------------------------------
| Collect Basic Builder
|--------------------------------------------------------------------------
*/

function collectBuilderData() {

    const tables = [];

    document.querySelectorAll('.table-row')
        .forEach(row => {

            const table =
                row.querySelector('.table-name').value.trim();

            const alias =
                row.querySelector('.table-alias').value.trim();

            if (table) {

                tables.push({
                    table,
                    alias
                });

            }

        });


    const joins = [];

    document.querySelectorAll('#joinsContainer .builder-row')
        .forEach(row => {

            joins.push({

                type:
                    row.querySelector('.join-type').value,

                table:
                    row.querySelector('.join-table').value,

                alias:
                    row.querySelector('.join-alias').value,

                left:
                    row.querySelector('.join-left').value,

                operator:
                    row.querySelector('.join-operator').value,

                right:
                    row.querySelector('.join-right').value

            });

        });


    const columns = [];

    document.querySelectorAll('#columnsContainer .builder-row')
        .forEach(row => {

            columns.push({

                expression:
                    row.querySelector('.column-expression').value,

                function:
                    row.querySelector('.column-function').value,

                alias:
                    row.querySelector('.column-alias').value

            });

        });


    const filters = [];

    document.querySelectorAll('#filtersContainer .builder-row')
        .forEach(row => {

            filters.push({

                column:
                    row.querySelector('.filter-column').value,

                operator:
                    row.querySelector('.filter-operator').value,

                value:
                    row.querySelector('.filter-value').value

            });

        });


    const groupBy = [];

    document.querySelectorAll('.group-column')
        .forEach(input => {

            if (input.value.trim()) {

                groupBy.push(
                    input.value.trim()
                );

            }

        });


    const orderBy = [];

    document.querySelectorAll('#orderContainer .builder-row')
        .forEach(row => {

            const column =
                row.querySelector('.order-column').value.trim();

            if (!column) {
                return;
            }

            orderBy.push({

                column,

                direction:
                    row.querySelector('.order-direction').value

            });

        });


        return {

            tables,
            joins,
            columns,

            filters: [
                ...filters,

                ...reportFilters
                    .filter(filter => filter.column.trim() !== '')
                    .map(filter => ({
                        column: filter.column.trim(),
                        label: filter.label.trim(),
                        type: filter.type,
                        options: filter.options,
                        runtime: true
                    }))
            ],

            group_by: groupBy,
            order_by: orderBy

        };

}


/*
|--------------------------------------------------------------------------
| Collect Calculated Columns
|--------------------------------------------------------------------------
*/

function collectCalculatedColumns() {

    const calculations = [];

    document.querySelectorAll(
        '#calculatedColumnsContainer .builder-row'
    ).forEach(row => {

        const expression =
            row.querySelector('.calculated-expression')
                .value.trim();

        const alias =
            row.querySelector('.calculated-alias')
                .value.trim();

        if (!expression) {
            return;
        }

        calculations.push({
            expression,
            alias
        });

    });

    return calculations;

}


/*
|--------------------------------------------------------------------------
| Collect HAVING
|--------------------------------------------------------------------------
*/

function collectHaving() {

    const having = [];

    document.querySelectorAll(
        '#havingContainer .builder-row'
    ).forEach(row => {

        const column =
            row.querySelector('.having-column')
                .value.trim();

        if (!column) {
            return;
        }

        having.push({

            column,

            operator:
                row.querySelector('.having-operator').value,

            value:
                row.querySelector('.having-value').value

        });

    });

    return having;

}


/*
|--------------------------------------------------------------------------
| Collect Pivot
|--------------------------------------------------------------------------
*/

function collectPivot() {

    syncPivotDimensions();

    return {

        enabled:
            document.getElementById('pivotEnabled').checked,

        rows:
            document.getElementById('pivotRows')
                .value
                .split(',')
                .map(value => value.trim())
                .filter(Boolean),

        column:
            document.getElementById('pivotColumn')
                .value
                .trim(),

        value:
            document.getElementById('pivotValue')
                .value
                .trim(),

        aggregate:
            document.getElementById('pivotAggregate')
                .value,

        values:
            [...pivotValues],

        dimensions:
            pivotDimensions

    };

}


/*
|--------------------------------------------------------------------------
| Collect Advanced Data
|--------------------------------------------------------------------------
*/

function collectAdvancedData() {

    const pivot =
        collectPivot();

    return {

        cte: {

            enabled:
                document.getElementById('cteEnabled').checked,

            name:
                document.getElementById('cteName')
                    .value
                    .trim(),

            description:
                document.getElementById('cteDescription')
                    .value
                    .trim()

        },

        calculated_columns:
            collectCalculatedColumns(),

        having:
            collectHaving(),

        pivot,

        distinct:
            document.getElementById('distinctFlag').checked,

        limit:
            Number(
                document.getElementById('limitValue').value || 500
            )

    };

}


/*
|--------------------------------------------------------------------------
| Generate SQL
|--------------------------------------------------------------------------
*/

async function generateSQL() {

    const builder =
        collectBuilderData();

    const advanced =
        collectAdvancedData();


    if (!builder.tables.length) {

        alert(
            'Please add at least one table.'
        );

        return;
    }


    if (!builder.columns.length) {

        alert(
            'Please add at least one normal column.'
        );

        return;
    }


    if (
        advanced.pivot.enabled &&
        !advanced.cte.enabled
    ) {

        alert(
            'Pivot requires CTE to be enabled.'
        );

        return;
    }


    if (
        advanced.pivot.enabled &&
        !advanced.pivot.column
    ) {

        alert(
            'Please enter the Pivot Column.'
        );

        return;
    }


    if (
        advanced.pivot.enabled &&
        !advanced.pivot.value
    ) {

        alert(
            'Please enter the Pivot Value Column.'
        );

        return;
    }


    const payload = {

        ...builder,

        cte:
            advanced.cte,

        calculated_columns:
            advanced.calculated_columns,

        having:
            advanced.having,

        pivot:
            advanced.pivot,

        distinct:
            advanced.distinct,

        limit:
            advanced.limit

    };


    setSqlStatus(
        'Generating SQL...'
    );


    try {

        const response = await fetch(
            "{{ route('reports.library.query-builder.generate', $report) }}",
            {

                method: 'POST',

                headers: {

                    'Content-Type':
                        'application/json',

                    'X-CSRF-TOKEN':
                        "{{ csrf_token() }}",

                    'Accept':
                        'application/json'

                },

                body:
                    JSON.stringify(payload)

            }
        );


        const result =
            await response.json();


        if (!response.ok) {

            alert(
                result.message ??
                'Unable to generate SQL.'
            );

            setSqlStatus(
                'SQL generation failed.'
            );

            return;
        }


        document.getElementById(
            'sqlEditor'
        ).value =
            result.sql;


        setSqlStatus(
            'SQL generated successfully.'
        );


    } catch (error) {

        console.error(error);

        alert(
            'Something went wrong while generating SQL.'
        );

        setSqlStatus(
            'SQL generation failed.'
        );

    }

}


/*
|--------------------------------------------------------------------------
| SQL Status
|--------------------------------------------------------------------------
*/

function setSqlStatus(message) {

    document.getElementById(
        'sqlStatus'
    ).textContent =
        message;

}


/*
|--------------------------------------------------------------------------
| Copy SQL
|--------------------------------------------------------------------------
*/

async function copySQL() {

    const sql =
        document.getElementById('sqlEditor')
            .value;

    if (!sql.trim()) {

        alert(
            'There is no SQL to copy.'
        );

        return;
    }


    try {

        await navigator.clipboard.writeText(
            sql
        );

        alert(
            'SQL copied to clipboard.'
        );

    } catch (error) {

        alert(
            'Unable to copy SQL.'
        );

    }

}


/*
|--------------------------------------------------------------------------
| Run SQL
|--------------------------------------------------------------------------
*/

async function runSQL() {

    const sql =
        document.getElementById('sqlEditor')
            .value
            .trim();


    if (!sql) {

        alert(
            'Please generate or enter SQL first.'
        );

        return;
    }


    document.getElementById(
        'loading'
    ).style.display =
        'block';


    try {

        const response =
            await fetch(
                "{{ route('reports.library.query-builder.execute', $report) }}",
                {

                    method: 'POST',

                    headers: {

                        'Content-Type':
                            'application/json',

                        'X-CSRF-TOKEN':
                            "{{ csrf_token() }}",

                        'Accept':
                            'application/json'

                    },

                    body:
                        JSON.stringify({
                            sql
                        })

                }
            );


        const result =
            await response.json();


        if (
            !response.ok ||
            !result.success
        ) {

            alert(
                result.message ??
                'Query execution failed.'
            );

            return;
        }


        renderResults(result);


    } catch (error) {

        console.error(error);

        alert(
            'Something went wrong while executing the query.'
        );

    } finally {

        document.getElementById(
            'loading'
        ).style.display =
            'none';

    }

}


/*
|--------------------------------------------------------------------------
| Render Results
|--------------------------------------------------------------------------
*/

function renderResults(result) {

    document.getElementById(
        'resultCount'
    ).innerHTML =
        `<strong>${result.count}</strong> row(s) returned. Maximum 500 rows shown.`;


    if (!result.rows.length) {

        document.getElementById(
            'resultContainer'
        ).innerHTML = `

            <div class="empty-result">

                <i class="bi bi-inbox fs-2 d-block mb-2"></i>

                Query executed successfully but returned no records.

            </div>

        `;

        return;
    }


    let html = `

        <div class="result-wrapper">

            <table class="table table-sm table-hover">

                <thead>

                    <tr>

    `;


    result.columns.forEach(column => {

        html += `
            <th>
                ${escapeHtml(column)}
            </th>
        `;

    });


    html += `

                    </tr>

                </thead>

                <tbody>

    `;


    result.rows.forEach(row => {

        html += `<tr>`;


        result.columns.forEach(column => {

            let value =
                row[column];

            if (value === null) {

                value = '';

            }

            html += `

                <td>
                    ${escapeHtml(value)}
                </td>

            `;

        });


        html += `</tr>`;

    });


    html += `

                </tbody>

            </table>

        </div>

    `;


    document.getElementById(
        'resultContainer'
    ).innerHTML =
        html;

}


/*
|--------------------------------------------------------------------------
| Save Query
|--------------------------------------------------------------------------
*/

async function saveQuery() {

    const builder =
        collectBuilderData();

    const advanced =
        collectAdvancedData();

    const queryName =
        document.getElementById(
            'queryName'
        ).value.trim();

    const sql =
        document.getElementById(
            'sqlEditor'
        ).value.trim();


    if (!queryName) {

        alert(
            'Please enter a query name.'
        );

        return;
    }


    if (!sql) {

        alert(
            'Please generate or enter SQL first.'
        );

        return;
    }


    const payload = {

        query_name:
            queryName,

        query_mode:
            advanced.pivot.enabled ||
            advanced.cte.enabled ||
            advanced.calculated_columns.length ||
            advanced.having.length
                ? 'builder'
                : 'custom',

        tables_json:
            JSON.stringify(
                builder.tables
            ),

        joins_json:
            JSON.stringify(
                builder.joins
            ),

        columns_json:
            JSON.stringify(
                builder.columns
            ),

        filters_json:
            JSON.stringify(
                builder.filters
            ),

        group_by_json:
            JSON.stringify(
                builder.group_by
            ),

        order_by_json:
            JSON.stringify(
                builder.order_by
            ),

        cte_json:
            JSON.stringify(
                advanced.cte
            ),

        calculated_columns_json:
            JSON.stringify(
                advanced.calculated_columns
            ),

        pivot_json:
            JSON.stringify(
                advanced.pivot
            ),

        having_json:
            JSON.stringify(
                advanced.having
            ),

        distinct_flag:
            advanced.distinct
                ? 1
                : 0,

        limit_value:
            advanced.limit,

        generated_sql:
            sql,

        custom_sql:
            sql

    };


    try {

        const response =
            await fetch(
                "{{ route('reports.library.query-builder.save', $report) }}",
                {

                    method: 'POST',

                    headers: {

                        'Content-Type':
                            'application/json',

                        'X-CSRF-TOKEN':
                            "{{ csrf_token() }}",

                        'Accept':
                            'application/json'

                    },

                    body:
                        JSON.stringify(payload)

                }
            );


        const result =
            await response.json();


        if (!response.ok) {

            alert(
                result.message ??
                'Unable to save query.'
            );

            return;
        }


        alert(
            result.message ??
            'Query saved successfully.'
        );


    } catch (error) {

        console.error(error);

        alert(
            'Unable to save query.'
        );

    }

}


/*
|--------------------------------------------------------------------------
| Load Saved Configuration
|--------------------------------------------------------------------------
*/

function loadSavedQuery() {

    if (!savedQuery) {
        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | Basic Builder
    |--------------------------------------------------------------------------
    */

    (savedQuery.tables ?? [])
        .forEach(row =>
            addTableRow(row)
        );


    (savedQuery.joins ?? [])
        .forEach(row =>
            addJoinRow(row)
        );


    (savedQuery.columns ?? [])
        .forEach(row =>
            addColumnRow(row)
        );


    (savedQuery.filters ?? [])
        .forEach(row =>
            addFilterRow(row)
        );


    (savedQuery.group_by ?? [])
        .forEach(row =>
            addGroupRow(row)
        );


    (savedQuery.order_by ?? [])
        .forEach(row =>
            addOrderRow(row)
        );


    /*
    |--------------------------------------------------------------------------
    | CTE
    |--------------------------------------------------------------------------
    */

    const cte =
        savedQuery.cte;


    if (cte) {

        document.getElementById(
            'cteEnabled'
        ).checked =
            Boolean(
                cte.enabled
            );

        document.getElementById(
            'cteName'
        ).value =
            cte.name ??
            'base';

        document.getElementById(
            'cteDescription'
        ).value =
            cte.description ??
            '';

        toggleCteSettings();

    }


    /*
    |--------------------------------------------------------------------------
    | Calculated Columns
    |--------------------------------------------------------------------------
    */

    (savedQuery.calculated_columns ?? [])
        .forEach(row =>
            addCalculatedColumnRow(row)
        );


    /*
    |--------------------------------------------------------------------------
    | HAVING
    |--------------------------------------------------------------------------
    */

    (savedQuery.having ?? [])
        .forEach(row =>
            addHavingRow(row)
        );


    /*
    |--------------------------------------------------------------------------
    | DISTINCT + LIMIT
    |--------------------------------------------------------------------------
    */

    document.getElementById(
        'distinctFlag'
    ).checked =
        Boolean(
            savedQuery.distinct
        );


    document.getElementById(
        'limitValue'
    ).value =
        savedQuery.limit ??
        500;


    /*
    |--------------------------------------------------------------------------
    | Pivot
    |--------------------------------------------------------------------------
    */

    const pivot =
        savedQuery.pivot;


    if (pivot) {

        document.getElementById(
            'pivotEnabled'
        ).checked =
            Boolean(
                pivot.enabled
            );

        document.getElementById(
            'pivotRows'
        ).value =
            (pivot.rows ?? [])
                .join(', ');

        document.getElementById(
            'pivotColumn'
        ).value =
            pivot.column ??
            '';

        document.getElementById(
            'pivotValue'
        ).value =
            pivot.value ??
            '';

        document.getElementById(
            'pivotAggregate'
        ).value =
            pivot.aggregate ??
            'SUM';


        pivotValues =
            pivot.values ??
            [];


        renderPivotValues();


        (pivot.dimensions ?? [])
            .forEach(dimension =>
                addPivotDimension(
                    dimension
                )
            );


        togglePivotSettings();

        updatePivotPreview();

    }


    return true;

}


/*
|--------------------------------------------------------------------------
| Toggle CTE
|--------------------------------------------------------------------------
*/

function toggleCteSettings() {

    const enabled =
        document.getElementById(
            'cteEnabled'
        ).checked;

    document.getElementById(
        'cteSettings'
    ).style.display =
        enabled
            ? 'block'
            : 'none';

}


/*
|--------------------------------------------------------------------------
| Toggle Pivot
|--------------------------------------------------------------------------
*/

function togglePivotSettings() {

    const enabled =
        document.getElementById(
            'pivotEnabled'
        ).checked;

    document.getElementById(
        'pivotSettings'
    ).style.display =
        enabled
            ? 'block'
            : 'none';

    updatePivotPreview();

}


/*
|--------------------------------------------------------------------------
| Refresh Column Options
|--------------------------------------------------------------------------
|
| Reserved for future metadata-driven column dropdowns.
|
*/

function refreshColumnOptions() {

    // Intentionally kept for compatibility
    // with the existing builder.

}


/*
|--------------------------------------------------------------------------
| Event Listeners
|--------------------------------------------------------------------------
*/

document.getElementById(
    'cteEnabled'
).addEventListener(
    'change',
    toggleCteSettings
);


document.getElementById(
    'pivotEnabled'
).addEventListener(
    'change',
    togglePivotSettings
);


document.getElementById(
    'pivotRows'
).addEventListener(
    'input',
    updatePivotPreview
);


document.getElementById(
    'pivotColumn'
).addEventListener(
    'input',
    updatePivotPreview
);


document.getElementById(
    'pivotValue'
).addEventListener(
    'input',
    updatePivotPreview
);


/*
|--------------------------------------------------------------------------
| Initial Setup
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const loaded =
            loadSavedQuery();


        /*
        |--------------------------------------------------------------------------
        | First-time example setup
        |--------------------------------------------------------------------------
        */

        if (!loaded) {

            /*
            |--------------------------------------------------------------------------
            | Tables
            |--------------------------------------------------------------------------
            */

            addTableRow({
                table: 'itemtran_body',
                alias: 'ib'
            });


            addTableRow({
                table: 'item_mast',
                alias: 'im'
            });


            addTableRow({
                table: 'prod_mast',
                alias: 'pm'
            });


            /*
            |--------------------------------------------------------------------------
            | Joins
            |--------------------------------------------------------------------------
            */

            addJoinRow({

                type: 'INNER',

                table: 'item_mast',

                alias: 'im',

                left: 'ib.ITEM_CODE',

                operator: '=',

                right: 'im.ITEM_CODE'

            });


            addJoinRow({

                type: 'INNER',

                table: 'prod_mast',

                alias: 'pm',

                left: 'im.PROD_CODE',

                operator: '=',

                right: 'pm.PROD_CODE'

            });


            /*
            |--------------------------------------------------------------------------
            | Base Columns
            |--------------------------------------------------------------------------
            */

            addColumnRow({
                expression: 'ib.ITEM_CODE'
            });


            addColumnRow({
                expression: 'im.ITEM_NAME'
            });


            addColumnRow({
                expression: 'im.ITEM_CATG'
            });


            addColumnRow({
                expression: 'pm.MKTG_CATG'
            });


            addColumnRow({
                expression: 'ib.DIV_CODE'
            });


            /*
            |--------------------------------------------------------------------------
            | Filters
            |--------------------------------------------------------------------------
            */

            addFilterRow({

                column: 'ib.VRDATE',

                operator: '>=',

                value: '2026-04-01'

            });


            addFilterRow({

                column: 'ib.VRDATE',

                operator: '<',

                value: '2026-10-01'

            });


            addFilterRow({

                column: 'ib.QTYISSUED',

                operator: 'IS NOT NULL',

                value: ''

            });


            addFilterRow({

                column: 'pm.MKTG_CATG',

                operator: '=',

                value: 'ASV'

            });


            addFilterRow({

                column: 'ib.DIV_CODE',

                operator: 'IN',

                value: 'AG,AH,AM,DL,GZ,HL,ZP,JH'

            });


            /*
            |--------------------------------------------------------------------------
            | Group By
            |--------------------------------------------------------------------------
            */

            addGroupRow(
                'ib.ITEM_CODE'
            );

            addGroupRow(
                'im.ITEM_NAME'
            );

            addGroupRow(
                'im.ITEM_CATG'
            );

            addGroupRow(
                'pm.MKTG_CATG'
            );

            addGroupRow(
                'ib.DIV_CODE'
            );


            /*
            |--------------------------------------------------------------------------
            | Calculated Columns
            |--------------------------------------------------------------------------
            */

            addCalculatedColumnRow({

                expression:
                    "DATE_FORMAT(ib.VRDATE, '%Y-%m')",

                alias:
                    'YM'

            });


            addCalculatedColumnRow({

                expression:
                    'SUM(ib.QTYISSUED)',

                alias:
                    'QTY'

            });


            /*
            |--------------------------------------------------------------------------
            | CTE
            |--------------------------------------------------------------------------
            */

            document.getElementById(
                'cteEnabled'
            ).checked =
                true;


            document.getElementById(
                'cteName'
            ).value =
                'base';


            toggleCteSettings();


            /*
            |--------------------------------------------------------------------------
            | Pivot
            |--------------------------------------------------------------------------
            */

            document.getElementById(
                'pivotEnabled'
            ).checked =
                true;


            document.getElementById(
                'pivotRows'
            ).value =
                'ITEM_CODE, ITEM_NAME, MKTG_CATG';


            document.getElementById(
                'pivotColumn'
            ).value =
                'YM';


            document.getElementById(
                'pivotValue'
            ).value =
                'QTY';


            document.getElementById(
                'pivotAggregate'
            ).value =
                'SUM';


            pivotValues = [

                '2026-04',
                '2026-05',
                '2026-06',
                '2026-07',
                '2026-08',
                '2026-09'

            ];


            renderPivotValues();


            addPivotDimension({

                column:
                    'DIV_CODE',

                values: [

                    'AG',
                    'AH',
                    'AM',
                    'DL',
                    'GZ',
                    'HL',
                    'ZP',
                    'JH'

                ]

            });


            togglePivotSettings();


            /*
            |--------------------------------------------------------------------------
            | Order By
            |--------------------------------------------------------------------------
            */

            addOrderRow({

                column:
                    'ITEM_CATG',

                direction:
                    'ASC'

            });


            addOrderRow({

                column:
                    'ITEM_CODE',

                direction:
                    'ASC'

            });


            /*
            |--------------------------------------------------------------------------
            | Limit
            |--------------------------------------------------------------------------
            */

            document.getElementById(
                'limitValue'
            ).value =
                500;

        }

        updatePivotPreview();

    }
);

/*
|--------------------------------------------------------------------------
| Report Page Filters
|--------------------------------------------------------------------------
*/

function addReportFilter(data = {}) {

const id = Number.isInteger(data._id)
    ? data._id
    : Date.now() + Math.floor(Math.random() * 100000);

const filter = {
    _id: id,
    column: data.column || '',
    label: data.label || '',
    type: data.type || 'search',
    options: Array.isArray(data.options)
        ? data.options
        : [],
    runtime: true
};

reportFilters.push(filter);

renderReportFilters();
}


function renderReportFilters() {

const container =
    document.getElementById('reportFiltersContainer');

if (!container) {
    return;
}

container.innerHTML = '';

reportFilters.forEach((filter, index) => {

    const row = document.createElement('div');

    row.className = 'builder-row border rounded p-3 mb-3';

    row.innerHTML = `
        <div class="row g-3 align-items-end">

            <div class="col-md-3">
                <label class="form-label">Column</label>

                <input
                    type="text"
                    class="form-control report-filter-column"
                    data-index="${index}"
                    value="${escapeHtml(filter.column)}"
                    placeholder="ib.DIV_CODE"
                >
            </div>

            <div class="col-md-2">
                <label class="form-label">Label</label>

                <input
                    type="text"
                    class="form-control report-filter-label"
                    data-index="${index}"
                    value="${escapeHtml(filter.label)}"
                    placeholder="Division"
                >
            </div>

            <div class="col-md-3">
                <label class="form-label">Filter Type</label>

                <select
                    class="form-select report-filter-type"
                    data-index="${index}"
                >
                    <option value="search"
                        ${filter.type === 'search' ? 'selected' : ''}>
                        Search Text
                    </option>

                    <option value="select"
                        ${filter.type === 'select' ? 'selected' : ''}>
                        Dropdown
                    </option>

                    <option value="multi_select"
                        ${filter.type === 'multi_select' ? 'selected' : ''}>
                        Multi Select
                    </option>

                    <option value="date_range"
                        ${filter.type === 'date_range' ? 'selected' : ''}>
                        From / To Date
                    </option>

                    <option value="number_range"
                        ${filter.type === 'number_range' ? 'selected' : ''}>
                        Min / Max Number
                    </option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Dropdown Options</label>

                <input
                    type="text"
                    class="form-control report-filter-options"
                    data-index="${index}"
                    value="${escapeHtml(filter.options.join(', '))}"
                    placeholder="AG, AH, AM"
                >

                <div class="form-text">
                    For dropdowns: comma-separated values, or leave empty to load from the database.
                </div>
            </div>

            <div class="col-md-1">
                <button
                    type="button"
                    class="btn btn-outline-danger"
                    onclick="removeReportFilter(${index})"
                    title="Remove filter"
                >
                    <i class="bi bi-trash"></i>
                </button>
            </div>

        </div>
    `;

    container.appendChild(row);
});

attachReportFilterEvents();
}


function attachReportFilterEvents() {

document.querySelectorAll('.report-filter-column')
    .forEach(element => {

        element.addEventListener('input', function () {

            reportFilters[Number(this.dataset.index)].column =
                this.value.trim();
        });
    });


document.querySelectorAll('.report-filter-label')
    .forEach(element => {

        element.addEventListener('input', function () {

            reportFilters[Number(this.dataset.index)].label =
                this.value.trim();
        });
    });


document.querySelectorAll('.report-filter-type')
    .forEach(element => {

        element.addEventListener('change', function () {

            reportFilters[Number(this.dataset.index)].type =
                this.value;
        });
    });


document.querySelectorAll('.report-filter-options')
    .forEach(element => {

        element.addEventListener('input', function () {

            reportFilters[Number(this.dataset.index)].options =
                this.value
                    .split(',')
                    .map(value => value.trim())
                    .filter(Boolean);
        });
    });
}


function removeReportFilter(index) {

reportFilters.splice(index, 1);

renderReportFilters();
}

</script>

@endsection