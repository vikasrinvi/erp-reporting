@extends('layouts.app')

@section('content')

@php
    $definition = $definition ?? [];

    $columns = $definition['columns'] ?? [];
    $joins = $definition['joins'] ?? [];
    $filters = $definition['filters'] ?? [];
    $groups = $definition['groups'] ?? [];
    $sorts = $definition['sorts'] ?? [];
    $pivot = $definition['pivot'] ?? [];
@endphp

<style>
    .builder-page {
        background: #f5f7fb;
        min-height: calc(100vh - 70px);
        padding: 24px;
    }

    .builder-header {
        background: linear-gradient(135deg, #172033, #273b63);
        color: white;
        border-radius: 18px;
        padding: 24px 28px;
        box-shadow: 0 10px 30px rgba(23, 32, 51, .18);
        margin-bottom: 20px;
    }

    .builder-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e6eaf0;
        box-shadow: 0 6px 20px rgba(25, 42, 70, .06);
        margin-bottom: 18px;
    }

    .builder-card-header {
        padding: 16px 20px;
        border-bottom: 1px solid #edf0f4;
        font-weight: 700;
        color: #172033;
    }

    .builder-card-body {
        padding: 20px;
    }

    .builder-label {
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 6px;
    }

    .builder-input,
    .builder-select {
        border-radius: 9px;
        border: 1px solid #dce2ea;
        min-height: 42px;
    }

    .builder-input:focus,
    .builder-select:focus {
        border-color: #526d9d;
        box-shadow: 0 0 0 3px rgba(82, 109, 157, .12);
    }

    .builder-row {
        background: #f8fafc;
        border: 1px solid #e7ebf0;
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 10px;
    }

    .section-number {
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #172033;
        color: white;
        font-size: 12px;
        font-weight: 700;
        margin-right: 8px;
    }

    .sql-editor {
        width: 100%;
        min-height: 600px;
        resize: vertical;
        background: #111827;
        color: #e5e7eb;
        border: 0;
        border-radius: 12px;
        padding: 18px;
        font-family: Consolas, Monaco, monospace;
        font-size: 13px;
        line-height: 1.6;
    }

    .result-wrapper {
        max-height: 600px;
        overflow: auto;
    }

    .result-table {
        font-size: 13px;
        white-space: nowrap;
    }

    .result-table thead th {
        position: sticky;
        top: 0;
        background: #172033;
        color: white;
        z-index: 2;
    }

    .sticky-actions {
        position: sticky;
        bottom: 15px;
        z-index: 10;
    }

    .builder-help {
        font-size: 12px;
        color: #64748b;
    }

    .pivot-box {
        border: 2px dashed #d9e0eb;
        border-radius: 12px;
        padding: 18px;
        background: #fafcff;
    }
</style>

<div class="builder-page">

    {{-- HEADER --}}
    <div class="builder-header">

        <div class="d-flex justify-content-between align-items-start gap-3">

            <div>
                <div class="small opacity-75 mb-1">
                    REPORT DESIGNER
                </div>

                <h3 class="mb-1">
                    {{ $report->report_name }}
                </h3>

                <div class="opacity-75">
                    {{ $report->report_code }}
                    @if($report->module)
                        · {{ $report->module }}
                    @endif
                </div>
            </div>

            <div class="d-flex gap-2">

                <a href="{{ route('reports.library.show', $report) }}"
                   class="btn btn-light">
                    <i class="bi bi-arrow-left"></i>
                    Back
                </a>

            </div>

        </div>

    </div>


    @if(session('success'))
        <div class="alert alert-success">
            <i class="bi bi-check-circle me-1"></i>
            {{ session('success') }}
        </div>
    @endif


    @if($builderError ?? false)
        <div class="alert alert-danger">
            <strong>Query Error:</strong>
            {{ $builderError }}
        </div>
    @endif


    <div class="row g-4">

        {{-- LEFT SIDE --}}
        <div class="col-xl-7">

            {{-- DATA SOURCE --}}
            <div class="builder-card">

                <div class="builder-card-header">
                    <span class="section-number">1</span>
                    Data Source
                </div>

                <div class="builder-card-body">

                    <div class="row g-3">

                        <div class="col-md-7">

                            <label class="builder-label">
                                Base Table
                            </label>

                            <select
                                id="base_table"
                                class="form-select builder-select"
                            >
                                <option value="">
                                    Select table
                                </option>

                                @foreach($tables as $table)
                                    <option
                                        value="{{ $table }}"
                                        @selected(
                                            ($definition['base_table'] ?? '') === $table
                                        )
                                    >
                                        {{ $table }}
                                    </option>
                                @endforeach
                            </select>

                        </div>

                        <div class="col-md-5">

                            <label class="builder-label">
                                Alias
                            </label>

                            <input
                                id="base_alias"
                                class="form-control builder-input"
                                value="{{ $definition['base_alias'] ?? 't1' }}"
                                placeholder="ib"
                            >

                        </div>

                        <div class="col-md-7">

                            <label class="builder-label">
                                CTE Name
                            </label>

                            <input
                                id="cte_name"
                                class="form-control builder-input"
                                value="{{ $definition['cte_name'] ?? 'base' }}"
                            >

                        </div>

                    </div>

                    <div class="builder-help mt-3">
                        The CTE becomes the first stage of the generated report.
                    </div>

                </div>

            </div>


            {{-- JOINS --}}
            <div class="builder-card">

                <div class="builder-card-header d-flex justify-content-between align-items-center">

                    <div>
                        <span class="section-number">2</span>
                        Joins
                    </div>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary"
                        onclick="addJoin()"
                    >
                        <i class="bi bi-plus"></i>
                        Add Join
                    </button>

                </div>

                <div class="builder-card-body">

                    <div id="joinsContainer"></div>

                </div>

            </div>


            {{-- COLUMNS --}}
            <div class="builder-card">

                <div class="builder-card-header d-flex justify-content-between align-items-center">

                    <div>
                        <span class="section-number">3</span>
                        Columns & Calculations
                    </div>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary"
                        onclick="addColumn()"
                    >
                        <i class="bi bi-plus"></i>
                        Add Column
                    </button>

                </div>

                <div class="builder-card-body">

                    <div class="builder-help mb-3">
                        Expression can contain normal columns, DATE_FORMAT,
                        CASE expressions, etc.
                    </div>

                    <div id="columnsContainer"></div>

                </div>

            </div>


            {{-- FILTERS --}}
            <div class="builder-card">

                <div class="builder-card-header d-flex justify-content-between align-items-center">

                    <div>
                        <span class="section-number">4</span>
                        Filters
                    </div>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary"
                        onclick="addFilter()"
                    >
                        <i class="bi bi-plus"></i>
                        Add Filter
                    </button>

                </div>

                <div class="builder-card-body">

                    <div id="filtersContainer"></div>

                </div>

            </div>


            {{-- GROUP --}}
            <div class="builder-card">

                <div class="builder-card-header d-flex justify-content-between align-items-center">

                    <div>
                        <span class="section-number">5</span>
                        Group By
                    </div>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary"
                        onclick="addGroup()"
                    >
                        <i class="bi bi-plus"></i>
                        Add Group
                    </button>

                </div>

                <div class="builder-card-body">

                    <div id="groupsContainer"></div>

                </div>

            </div>


            {{-- PIVOT --}}
            <div class="builder-card">

                <div class="builder-card-header">
                    <span class="section-number">6</span>
                    Pivot / Cross Tab
                </div>

                <div class="builder-card-body">

                    <div class="form-check form-switch mb-4">

                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="pivot_enabled"
                            @checked($pivot['enabled'] ?? false)
                            onchange="togglePivot()"
                        >

                        <label
                            class="form-check-label fw-semibold"
                            for="pivot_enabled"
                        >
                            Enable Pivot / Conditional Aggregation
                        </label>

                    </div>


                    <div
                        id="pivotContainer"
                        class="pivot-box"
                        style="display:none;"
                    >

                        <div class="row g-3">

                            <div class="col-md-6">

                                <label class="builder-label">
                                    Pivot Column Expression
                                </label>

                                <input
                                    id="pivot_column_expression"
                                    class="form-control builder-input"
                                    value="{{ $pivot['column_expression'] ?? '' }}"
                                    placeholder="YM"
                                >

                            </div>


                            <div class="col-md-6">

                                <label class="builder-label">
                                    Value Expression
                                </label>

                                <input
                                    id="pivot_value_expression"
                                    class="form-control builder-input"
                                    value="{{ $pivot['value_expression'] ?? '' }}"
                                    placeholder="QTY"
                                >

                            </div>


                            <div class="col-md-6">

                                <label class="builder-label">
                                    Aggregate
                                </label>

                                <select
                                    id="pivot_aggregate"
                                    class="form-select builder-select"
                                >

                                    @foreach(['SUM','COUNT','AVG','MIN','MAX'] as $aggregate)

                                        <option
                                            value="{{ $aggregate }}"
                                            @selected(
                                                ($pivot['aggregate'] ?? 'SUM') === $aggregate
                                            )
                                        >
                                            {{ $aggregate }}
                                        </option>

                                    @endforeach

                                </select>

                            </div>


                            <div class="col-md-6">

                                <label class="builder-label">
                                    Row Columns
                                </label>

                                <input
                                    id="pivot_row_columns"
                                    class="form-control builder-input"
                                    value="{{ implode(',', $pivot['row_columns'] ?? []) }}"
                                    placeholder="ITEM_CODE,ITEM_NAME,MKTG_CATG"
                                >

                            </div>


                            <div class="col-12">

                                <label class="builder-label">
                                    Pivot Values
                                </label>

                                <textarea
                                    id="pivot_values"
                                    class="form-control builder-input"
                                    rows="5"
                                    placeholder="2026-04|APR&#10;2026-05|MAY&#10;2026-06|JUN"
                                >{{ collect($pivot['multi_column_values'] ?? [])->map(fn($x) => ($x['value'] ?? '') . '|' . ($x['alias'] ?? ''))->implode("\n") }}</textarea>

                                <div class="builder-help mt-1">
                                    One value per line:
                                    <code>value|alias</code>
                                </div>

                            </div>


                            <div class="col-12">

                                <label class="builder-label">
                                    Pivot Dimensions
                                </label>

                                <textarea
                                    id="pivot_dimensions"
                                    class="form-control builder-input"
                                    rows="5"
                                    placeholder="DIV_CODE|AG&#10;DIV_CODE|AH&#10;DIV_CODE|AM"
                                >{{ collect($pivot['multi_column_expression'] ?? [])->map(fn($x) => ($x['expression'] ?? '') . '|' . ($x['value'] ?? '') . '|' . ($x['alias_prefix'] ?? ''))->implode("\n") }}</textarea>

                                <div class="builder-help mt-1">
                                    One per line:
                                    <code>expression|value|alias</code>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- SORT --}}
            <div class="builder-card">

                <div class="builder-card-header d-flex justify-content-between align-items-center">

                    <div>
                        <span class="section-number">7</span>
                        Sorting
                    </div>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary"
                        onclick="addSort()"
                    >
                        <i class="bi bi-plus"></i>
                        Add Sort
                    </button>

                </div>

                <div class="builder-card-body">

                    <div id="sortsContainer"></div>

                </div>

            </div>


            {{-- LIMIT --}}
            <div class="builder-card">

                <div class="builder-card-header">
                    <span class="section-number">8</span>
                    Result Limit
                </div>

                <div class="builder-card-body">

                    <input
                        type="number"
                        id="query_limit"
                        class="form-control builder-input"
                        min="1"
                        max="5000"
                        value="{{ $definition['limit'] ?? 500 }}"
                    >

                </div>

            </div>

        </div>


        {{-- RIGHT SIDE --}}
        <div class="col-xl-5">

            <div class="builder-card">

                <div class="builder-card-header d-flex justify-content-between align-items-center">

                    <div>
                        <i class="bi bi-code-slash me-1"></i>
                        Generated SQL
                    </div>

                    <button
                        type="button"
                        class="btn btn-sm btn-light"
                        onclick="copySql()"
                    >
                        <i class="bi bi-copy"></i>
                        Copy
                    </button>

                </div>

                <div class="builder-card-body">

                    <textarea
                        id="sqlEditor"
                        class="sql-editor"
                    >{{ $executedSql ?? ($savedQuery->custom_sql ?? $savedQuery->generated_sql ?? '') }}</textarea>

                </div>

            </div>


            {{-- ACTIONS --}}
            <div class="builder-card sticky-actions">

                <div class="builder-card-body">

                    <div class="d-grid gap-2">

                        <button
                            type="button"
                            class="btn btn-primary btn-lg"
                            onclick="generateSql()"
                        >
                            <i class="bi bi-magic me-1"></i>
                            Generate SQL
                        </button>


                        <button
                            type="button"
                            class="btn btn-success btn-lg"
                            onclick="runSql()"
                        >
                            <i class="bi bi-play-fill me-1"></i>
                            Run SQL
                        </button>


                        <button
                            type="button"
                            class="btn btn-outline-dark"
                            onclick="saveQuery()"
                        >
                            <i class="bi bi-save me-1"></i>
                            Save Report Query
                        </button>

                    </div>

                </div>

            </div>


            @if(isset($resultRows))

                <div class="builder-card">

                    <div class="builder-card-header d-flex justify-content-between">

                        <span>
                            <i class="bi bi-table me-1"></i>
                            Result
                        </span>

                        <span class="badge bg-secondary">
                            {{ count($resultRows) }} rows
                        </span>

                    </div>

                    <div class="builder-card-body p-0">

                        <div class="result-wrapper">

                            @if(!empty($resultRows))

                                <table class="table table-bordered table-hover result-table mb-0">

                                    <thead>

                                        <tr>

                                            @foreach($resultColumns as $column)
                                                <th>
                                                    {{ $column }}
                                                </th>
                                            @endforeach

                                        </tr>

                                    </thead>

                                    <tbody>

                                        @foreach($resultRows as $row)

                                            <tr>

                                                @foreach($resultColumns as $column)

                                                    <td>
                                                        {{ data_get($row, $column) }}
                                                    </td>

                                                @endforeach

                                            </tr>

                                        @endforeach

                                    </tbody>

                                </table>

                            @else

                                <div class="text-center p-5 text-muted">
                                    Query executed successfully but returned no rows.
                                </div>

                            @endif

                        </div>

                    </div>

                </div>

            @endif

        </div>

    </div>

</div>


<form
    id="runForm"
    method="POST"
    action="{{ route('reports.library.builder.run', $report) }}"
    style="display:none;"
>
    @csrf

    <input
        type="hidden"
        name="sql"
        id="runSqlInput"
    >

    <input
        type="hidden"
        name="definition"
        id="runDefinitionInput"
    >

</form>


<form
    id="saveForm"
    method="POST"
    action="{{ route('reports.library.builder.save', $report) }}"
    style="display:none;"
>
    @csrf

    <input
        type="hidden"
        name="query_name"
        id="saveQueryName"
    >

    <input
        type="hidden"
        name="definition"
        id="saveDefinitionInput"
    >

    <input
        type="hidden"
        name="generated_sql"
        id="saveGeneratedSql"
    >

    <input
        type="hidden"
        name="custom_sql"
        id="saveCustomSql"
    >

    <input
        type="hidden"
        name="use_custom_sql"
        id="saveUseCustomSql"
    >

</form>


<script>

const tables = @json($tables);

let definition = @json($definition);

const generateUrl =
    @json(route('reports.library.builder.generate', $report));

const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    || '{{ csrf_token() }}';


/*
|--------------------------------------------------------------------------
| Utilities
|--------------------------------------------------------------------------
*/

function esc(value) {

    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}


function removeRow(button) {

    button.closest('.builder-row').remove();

}


/*
|--------------------------------------------------------------------------
| Columns
|--------------------------------------------------------------------------
*/

function addColumn(data = {}) {

    const container =
        document.getElementById('columnsContainer');

    const row =
        document.createElement('div');

    row.className = 'builder-row';

    row.innerHTML = `

        <div class="row g-2 align-items-end">

            <div class="col-md-6">

                <label class="builder-label">
                    Expression
                </label>

                <input
                    class="form-control builder-input column-expression"
                    value="${esc(data.expression)}"
                    placeholder="ib.ITEM_CODE"
                >

            </div>

            <div class="col-md-2">

                <label class="builder-label">
                    Aggregate
                </label>

                <select class="form-select builder-select column-aggregate">

                    <option value="">None</option>
                    <option value="SUM">SUM</option>
                    <option value="COUNT">COUNT</option>
                    <option value="AVG">AVG</option>
                    <option value="MIN">MIN</option>
                    <option value="MAX">MAX</option>

                </select>

            </div>

            <div class="col-md-3">

                <label class="builder-label">
                    Alias
                </label>

                <input
                    class="form-control builder-input column-alias"
                    value="${esc(data.alias)}"
                    placeholder="QTY"
                >

            </div>

            <div class="col-md-1">

                <button
                    type="button"
                    class="btn btn-outline-danger w-100"
                    onclick="removeRow(this)"
                >
                    <i class="bi bi-trash"></i>
                </button>

            </div>

        </div>
    `;

    container.appendChild(row);

    row.querySelector('.column-aggregate').value =
        data.aggregate || '';

}


/*
|--------------------------------------------------------------------------
| Joins
|--------------------------------------------------------------------------
*/

function addJoin(data = {}) {

    const container =
        document.getElementById('joinsContainer');

    const row =
        document.createElement('div');

    row.className = 'builder-row';

    row.innerHTML = `

        <div class="row g-2 align-items-end">

            <div class="col-md-2">

                <label class="builder-label">
                    Type
                </label>

                <select class="form-select builder-select join-type">

                    <option value="INNER">INNER</option>
                    <option value="LEFT">LEFT</option>
                    <option value="RIGHT">RIGHT</option>

                </select>

            </div>

            <div class="col-md-4">

                <label class="builder-label">
                    Table
                </label>

                <select class="form-select builder-select join-table">

                    <option value="">Select</option>

                    ${tables.map(table => `
                        <option value="${esc(table)}">
                            ${esc(table)}
                        </option>
                    `).join('')}

                </select>

            </div>

            <div class="col-md-2">

                <label class="builder-label">
                    Alias
                </label>

                <input
                    class="form-control builder-input join-alias"
                    value="${esc(data.alias)}"
                    placeholder="im"
                >

            </div>

            <div class="col-md-4">

                <label class="builder-label">
                    Join Condition
                </label>

                <input
                    class="form-control builder-input join-left"
                    value="${esc(data.left)}"
                    placeholder="im.ITEM_CODE = ib.ITEM_CODE"
                >

            </div>

            <div class="col-md-1">

                <button
                    type="button"
                    class="btn btn-outline-danger w-100"
                    onclick="removeRow(this)"
                >
                    <i class="bi bi-trash"></i>
                </button>

            </div>

            <div class="col-md-12">

                <label class="builder-label">
                    Right Side
                </label>

                <div class="input-group">

                    <input
                        class="form-control builder-input join-right"
                        value="${esc(data.right)}"
                        placeholder="ib.ITEM_CODE"
                    >

                    <select class="form-select builder-select join-operator"
                            style="max-width:100px;">

                        <option value="=">=</option>
                        <option value="!=">!=</option>
                        <option value="<>"><></option>
                        <option value=">">></option>
                        <option value="<"><</option>
                        <option value=">=">>=</option>
                        <option value="<="><=</option>

                    </select>

                </div>

            </div>

        </div>
    `;

    container.appendChild(row);

    row.querySelector('.join-table').value =
        data.table || '';

    row.querySelector('.join-type').value =
        data.type || 'INNER';

    row.querySelector('.join-operator').value =
        data.operator || '=';

}


/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

function addFilter(data = {}) {

    const container =
        document.getElementById('filtersContainer');

    const row =
        document.createElement('div');

    row.className = 'builder-row';

    row.innerHTML = `

        <div class="row g-2 align-items-end">

            <div class="col-md-5">

                <label class="builder-label">
                    Expression
                </label>

                <input
                    class="form-control builder-input filter-expression"
                    value="${esc(data.expression)}"
                    placeholder="pm.MKTG_CATG"
                >

            </div>

            <div class="col-md-3">

                <label class="builder-label">
                    Operator
                </label>

                <select class="form-select builder-select filter-operator">

                    <option value="=">=</option>
                    <option value="!=">!=</option>
                    <option value=">">></option>
                    <option value="<"><</option>
                    <option value=">=">>=</option>
                    <option value="<="><=</option>
                    <option value="LIKE">LIKE</option>
                    <option value="NOT LIKE">NOT LIKE</option>
                    <option value="IN">IN</option>
                    <option value="NOT IN">NOT IN</option>
                    <option value="BETWEEN">BETWEEN</option>
                    <option value="IS NULL">IS NULL</option>
                    <option value="IS NOT NULL">IS NOT NULL</option>

                </select>

            </div>

            <div class="col-md-3">

                <label class="builder-label">
                    Value
                </label>

                <input
                    class="form-control builder-input filter-value"
                    value="${esc(data.value)}"
                    placeholder="ASV / A,B,C / start|end"
                >

            </div>

            <div class="col-md-1">

                <button
                    type="button"
                    class="btn btn-outline-danger w-100"
                    onclick="removeRow(this)"
                >
                    <i class="bi bi-trash"></i>
                </button>

            </div>

        </div>
    `;

    container.appendChild(row);

    row.querySelector('.filter-operator').value =
        data.operator || '=';

}


/*
|--------------------------------------------------------------------------
| Group
|--------------------------------------------------------------------------
*/

function addGroup(data = {}) {

    const container =
        document.getElementById('groupsContainer');

    const row =
        document.createElement('div');

    row.className = 'builder-row';

    row.innerHTML = `

        <div class="input-group">

            <input
                class="form-control builder-input group-expression"
                value="${esc(data.expression)}"
                placeholder="ITEM_CODE"
            >

            <button
                type="button"
                class="btn btn-outline-danger"
                onclick="removeRow(this)"
            >
                <i class="bi bi-trash"></i>
            </button>

        </div>
    `;

    container.appendChild(row);

}


/*
|--------------------------------------------------------------------------
| Sort
|--------------------------------------------------------------------------
*/

function addSort(data = {}) {

    const container =
        document.getElementById('sortsContainer');

    const row =
        document.createElement('div');

    row.className = 'builder-row';

    row.innerHTML = `

        <div class="row g-2">

            <div class="col-md-9">

                <input
                    class="form-control builder-input sort-expression"
                    value="${esc(data.expression)}"
                    placeholder="ITEM_CODE"
                >

            </div>

            <div class="col-md-2">

                <select class="form-select builder-select sort-direction">

                    <option value="ASC">ASC</option>
                    <option value="DESC">DESC</option>

                </select>

            </div>

            <div class="col-md-1">

                <button
                    type="button"
                    class="btn btn-outline-danger w-100"
                    onclick="removeRow(this)"
                >
                    <i class="bi bi-trash"></i>
                </button>

            </div>

        </div>
    `;

    container.appendChild(row);

    row.querySelector('.sort-direction').value =
        data.direction || 'ASC';

}


/*
|--------------------------------------------------------------------------
| Collect definition
|--------------------------------------------------------------------------
*/

function collectDefinition() {

    const columns = [];

    document
        .querySelectorAll('#columnsContainer .builder-row')
        .forEach(row => {

            const expression =
                row.querySelector('.column-expression').value.trim();

            if (!expression) {
                return;
            }

            columns.push({
                expression,
                aggregate:
                    row.querySelector('.column-aggregate').value,
                alias:
                    row.querySelector('.column-alias').value.trim(),
            });

        });


    const joins = [];

    document
        .querySelectorAll('#joinsContainer .builder-row')
        .forEach(row => {

            const table =
                row.querySelector('.join-table').value;

            if (!table) {
                return;
            }

            joins.push({
                type:
                    row.querySelector('.join-type').value,

                table,

                alias:
                    row.querySelector('.join-alias').value.trim(),

                left:
                    row.querySelector('.join-left').value.trim(),

                right:
                    row.querySelector('.join-right').value.trim(),

                operator:
                    row.querySelector('.join-operator').value,
            });

        });


    const filters = [];

    document
        .querySelectorAll('#filtersContainer .builder-row')
        .forEach(row => {

            const expression =
                row.querySelector('.filter-expression').value.trim();

            if (!expression) {
                return;
            }

            filters.push({
                expression,

                operator:
                    row.querySelector('.filter-operator').value,

                value:
                    row.querySelector('.filter-value').value,
            });

        });


    const groups = [];

    document
        .querySelectorAll('#groupsContainer .builder-row')
        .forEach(row => {

            const expression =
                row.querySelector('.group-expression').value.trim();

            if (expression) {
                groups.push({
                    expression
                });
            }

        });


    const sorts = [];

    document
        .querySelectorAll('#sortsContainer .builder-row')
        .forEach(row => {

            const expression =
                row.querySelector('.sort-expression').value.trim();

            if (expression) {

                sorts.push({
                    expression,

                    direction:
                        row.querySelector('.sort-direction').value,
                });

            }

        });


    /*
     * Pivot values
     */

    const pivotValues = [];

    document
        .getElementById('pivot_values')
        .value
        .split('\n')
        .map(x => x.trim())
        .filter(Boolean)
        .forEach(line => {

            const parts =
                line.split('|');

            pivotValues.push({
                value: parts[0]?.trim() || '',
                alias: parts[1]?.trim() || parts[0]?.trim() || '',
            });

        });


    /*
     * Pivot dimensions
     */

    const pivotDimensions = [];

    document
        .getElementById('pivot_dimensions')
        .value
        .split('\n')
        .map(x => x.trim())
        .filter(Boolean)
        .forEach(line => {

            const parts =
                line.split('|');

            pivotDimensions.push({
                expression: parts[0]?.trim() || '',
                value: parts[1]?.trim() || '',
                alias_prefix:
                    parts[2]?.trim() ||
                    parts[1]?.trim() ||
                    '',
            });

        });


    return {

        base_table:
            document.getElementById('base_table').value,

        base_alias:
            document.getElementById('base_alias').value.trim(),

        cte_name:
            document.getElementById('cte_name').value.trim(),

        columns,

        joins,

        filters,

        groups,

        sorts,

        pivot: {

            enabled:
                document.getElementById('pivot_enabled').checked,

            row_columns:
                document
                    .getElementById('pivot_row_columns')
                    .value
                    .split(',')
                    .map(x => x.trim())
                    .filter(Boolean),

            column_expression:
                document
                    .getElementById('pivot_column_expression')
                    .value
                    .trim(),

            value_expression:
                document
                    .getElementById('pivot_value_expression')
                    .value
                    .trim(),

            aggregate:
                document
                    .getElementById('pivot_aggregate')
                    .value,

            multi_column_values:
                pivotValues,

            multi_column_expression:
                pivotDimensions,
        },

        limit:
            parseInt(
                document.getElementById('query_limit').value
            ) || 500,
    };
}


/*
|--------------------------------------------------------------------------
| Generate SQL
|--------------------------------------------------------------------------
*/

async function generateSql() {

    const definition =
        collectDefinition();

    const editor =
        document.getElementById('sqlEditor');

    editor.value =
        'Generating SQL...';

    try {

        const response =
            await fetch(
                generateUrl,
                {
                    method: 'POST',

                    headers: {
                        'Content-Type':
                            'application/json',

                        'Accept':
                            'application/json',

                        'X-CSRF-TOKEN':
                            csrfToken,
                    },

                    body: JSON.stringify({
                        definition
                    })
                }
            );

        const data =
            await response.json();

        if (!response.ok) {
            throw new Error(
                data.message ||
                'Unable to generate SQL.'
            );
        }

        editor.value =
            data.sql;

    } catch(error) {

        editor.value =
            'ERROR:\n\n' +
            error.message;

    }

}


/*
|--------------------------------------------------------------------------
| Run SQL
|--------------------------------------------------------------------------
*/

async function runSql() {

    const definition =
        collectDefinition();

    const sql =
        document.getElementById('sqlEditor')
            .value
            .trim();

    if (!sql) {

        alert(
            'Generate or enter SQL first.'
        );

        return;
    }

    document.getElementById(
        'runSqlInput'
    ).value = sql;

    document.getElementById(
        'runDefinitionInput'
    ).value =
        JSON.stringify(definition);

    document.getElementById(
        'runForm'
    ).submit();

}


/*
|--------------------------------------------------------------------------
| Save
|--------------------------------------------------------------------------
*/

function saveQuery() {

    const definition =
        collectDefinition();

    const sql =
        document.getElementById('sqlEditor')
            .value
            .trim();

    if (!sql) {

        alert(
            'Generate SQL before saving.'
        );

        return;
    }

    const name =
        prompt(
            'Enter query/report name:',
            '{{ addslashes($report->report_name) }}'
        );

    if (!name) {
        return;
    }

    document.getElementById(
        'saveQueryName'
    ).value = name;

    document.getElementById(
        'saveDefinitionInput'
    ).value =
        JSON.stringify(definition);

    document.getElementById(
        'saveGeneratedSql'
    ).value = sql;

    document.getElementById(
        'saveCustomSql'
    ).value = sql;

    document.getElementById(
        'saveUseCustomSql'
    ).value = '1';

    document.getElementById(
        'saveForm'
    ).submit();

}


/*
|--------------------------------------------------------------------------
| Copy
|--------------------------------------------------------------------------
*/

async function copySql() {

    const sql =
        document.getElementById('sqlEditor')
            .value;

    await navigator.clipboard.writeText(sql);

    alert(
        'SQL copied to clipboard.'
    );

}


/*
|--------------------------------------------------------------------------
| Pivot
|--------------------------------------------------------------------------
*/

function togglePivot() {

    const enabled =
        document.getElementById(
            'pivot_enabled'
        ).checked;

    document.getElementById(
        'pivotContainer'
    ).style.display =
        enabled ? 'block' : 'none';

}


/*
|--------------------------------------------------------------------------
| Load saved builder state
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function () {

        (definition.columns || [])
            .forEach(addColumn);

        (definition.joins || [])
            .forEach(addJoin);

        (definition.filters || [])
            .forEach(addFilter);

        (definition.groups || [])
            .forEach(addGroup);

        (definition.sorts || [])
            .forEach(addSort);

        togglePivot();

        /*
         * If this is a new builder, give useful empty rows.
         */

        if (
            !definition.columns ||
            definition.columns.length === 0
        ) {

            addColumn({
                expression: '',
                aggregate: '',
                alias: ''
            });

        }

    }
);

</script>

@endsection