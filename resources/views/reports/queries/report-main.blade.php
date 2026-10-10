@extends('layouts.app')

@section('title', $query->query_name . ' - Report')

@section('content')

<style>

    .report-page {
        background: #f5f7fb;
        min-height: calc(100vh - 60px);
        padding: 28px;
    }

    .report-header {
        background: linear-gradient(
            135deg,
            #172033 0%,
            #273b63 100%
        );
        color: white;
        border-radius: 16px;
        padding: 22px 26px;
        box-shadow: 0 8px 25px rgba(23, 32, 51, .15);
    }

    .report-header h2 {
        margin: 0;
        font-weight: 700;
    }

    .report-header .meta {
        margin-top: 6px;
        color: rgba(255,255,255,.72);
        font-size: 13px;
    }

    .report-card {
        background: white;
        border: 1px solid #e8ecf3;
        border-radius: 14px;
        box-shadow: 0 4px 14px rgba(30,45,70,.05);
        overflow: hidden;
    }

    .report-toolbar {
        padding: 14px 18px;
        border-bottom: 1px solid #edf0f5;
        background: #fff;
    }

    .report-table-wrapper {
        overflow: auto;
        max-height: calc(100vh - 280px);
    }

    .report-table {
        margin: 0;
        font-size: 13px;
    }

    .report-table th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #f8fafc;
        color: #4b5563;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .03em;
        white-space: nowrap;
        border-bottom: 1px solid #dfe4ec;
        padding: 11px 13px;
    }

    .report-table td {
        white-space: nowrap;
        padding: 10px 13px;
        border-color: #edf0f5;
    }

    .report-table tbody tr:hover {
        background: #f8fafc;
    }

    .result-count {
        font-size: 12px;
        color: #687386;
    }

    .sql-card {
        background: #111827;
        border-radius: 12px;
        overflow: hidden;
    }

    .sql-header {
        padding: 10px 14px;
        color: #cbd5e1;
        border-bottom: 1px solid #263244;
        font-size: 12px;
        font-weight: 700;
    }

    .sql-code {
        padding: 15px;
        color: #d1d5db;
        font-family: monospace;
        font-size: 12px;
        line-height: 1.6;
        max-height: 350px;
        overflow: auto;
        white-space: pre-wrap;
    }

    .error-card {
        border-radius: 14px;
        border: 1px solid #f1c4c4;
        background: #fff5f5;
        padding: 20px;
        color: #842029;
    }

    @media(max-width:768px) {

        .report-page {
            padding: 15px;
        }

    }

</style>


<div class="report-page">

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="report-header mb-4">

        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">

            <div>

                <h2>

                    <i class="bi bi-bar-chart-line me-2"></i>

                    {{ $query->query_name }}

                </h2>

                <div class="meta">

                    @if($query->report)

                        <i class="bi bi-file-earmark-text me-1"></i>

                        {{ $query->report->name }}

                        <span class="mx-2">•</span>

                    @endif

                    Query #{{ $query->id }}

                    <span class="mx-2">•</span>

                    {{ $query->query_mode === 'custom'
                        ? 'Custom SQL'
                        : 'Visual Builder'
                    }}

                </div>

            </div>


            <div class="d-flex gap-2">

                @if($query->report)

                    <a
                        href="{{ route(
                            'reports.library.query-builder',
                            $query->report
                        ) }}"
                        class="btn btn-light btn-sm"
                    >

                        <i class="bi bi-pencil-square me-1"></i>

                        Edit Query

                    </a>

                @endif


                <a
                    href="{{ route('report-queries.index') }}"
                    class="btn btn-outline-light btn-sm"
                >

                    <i class="bi bi-arrow-left me-1"></i>

                    All Queries

                </a>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- ERROR --}}
    {{-- ========================================================= --}}

    @if(isset($error))

        <div class="error-card mb-4">

            <div class="fw-bold mb-2">

                <i class="bi bi-exclamation-triangle me-2"></i>

                Query execution failed

            </div>

            <div class="small">

                {{ $error }}

            </div>

        </div>

    @else

        {{-- ===================================================== --}}
        {{-- RESULT --}}
        {{-- ===================================================== --}}

        <div class="report-card mb-4">

            <div class="report-toolbar">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                    <div>

                        <strong>
                            Query Result
                        </strong>

                        <span class="result-count ms-2">

                            {{ count($rows) }}
                            {{ count($rows) === 1 ? 'row' : 'rows' }}

                        </span>

                    </div>

                    <div>

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            onclick="exportTableToCsv()"
                        >

                            <i class="bi bi-download me-1"></i>

                            Export CSV

                        </button>

                    </div>

                </div>

            </div>

            {{-- =========================================================
     DYNAMIC REPORT FILTERS
========================================================= --}}

@php

    $savedFilters =
        $query->filters_json ?? [];

    $reportFilters =
        collect($savedFilters)
            ->filter(function ($filter) {
                return !empty($filter['runtime']);
            });

@endphp


@if($reportFilters->count())

    <div class="report-card mb-4">

        <div class="report-card-header">

            <div>
                <h5 class="mb-1">
                    <i class="bi bi-funnel me-2"></i>
                    Report Filters
                </h5>

                <div class="text-muted small">
                    Filter the report data without changing the saved query.
                </div>
            </div>

        </div>


        <div class="report-card-body">

            <form
                method="GET"
                action="{{ route('report-queries.report', $query) }}"
                id="reportFilterForm"
            >

                <div class="row g-3">

                    @foreach($reportFilters as $filterIndex => $filter)

                        @php

                            $column =
                                $filter['column'] ?? '';

                            $type =
                                $filter['type'] ?? 'search';

                            $label =
                                $filter['label']
                                ?? \Illuminate\Support\Str::headline(
                                    str_replace(
                                        '.',
                                        ' ',
                                        $column
                                    )
                                );

                            $current =
                                request(
                                    'filters.' . $filterIndex
                                );

                        @endphp


                        {{-- DATE RANGE --}}
                        @if($type === 'date_range')

                            @php

                                $dateValue =
                                    is_array($current)
                                        ? $current
                                        : [];

                            @endphp

                            <div class="col-md-6">

                                <label class="form-label fw-semibold">
                                    {{ $label }}
                                </label>

                                <div class="row g-2">

                                    <div class="col">

                                        <label class="small text-muted">
                                            From
                                        </label>

                                        <input
                                            type="date"
                                            name="filters[{{ $filterIndex }}][from]"
                                            class="form-control"
                                            value="{{ $dateValue['from'] ?? '' }}"
                                        >

                                    </div>


                                    <div class="col">

                                        <label class="small text-muted">
                                            To
                                        </label>

                                        <input
                                            type="date"
                                            name="filters[{{ $filterIndex }}][to]"
                                            class="form-control"
                                            value="{{ $dateValue['to'] ?? '' }}"
                                        >

                                    </div>

                                </div>

                            </div>


                        {{-- MULTI SELECT --}}
                        @elseif($type === 'multi_select')

                            @php

                                $selected =
                                    is_array($current)
                                        ? $current
                                        : [];

                                $fixedOptions =
                                    $filter['options'] ?? [];

                            @endphp


                            <div class="col-md-4">

                                <label class="form-label fw-semibold">
                                    {{ $label }}
                                </label>

                                <select
                                    name="filters[{{ $filterIndex }}][]"
                                    class="form-select dynamic-filter-select"
                                    data-column="{{ $column }}"
                                    multiple
                                    style="min-height: 46px;"
                                >

                                    @foreach($fixedOptions as $option)

                                        <option
                                            value="{{ $option }}"
                                            @selected(
                                                in_array(
                                                    (string) $option,
                                                    array_map(
                                                        'strval',
                                                        $selected
                                                    ),
                                                    true
                                                )
                                            )
                                        >
                                            {{ $option }}
                                        </option>

                                    @endforeach

                                </select>

                                <div class="small text-muted mt-1">
                                    Select one or more values
                                </div>

                            </div>


                        {{-- SELECT --}}
                        @elseif($type === 'select')

                            @php

                                $fixedOptions =
                                    $filter['options'] ?? [];

                            @endphp

                            <div class="col-md-4">

                                <label class="form-label fw-semibold">
                                    {{ $label }}
                                </label>

                                <select
                                    name="filters[{{ $filterIndex }}]"
                                    class="form-select"
                                >

                                    <option value="">
                                        All
                                    </option>

                                    @foreach($fixedOptions as $option)

                                        <option
                                            value="{{ $option }}"
                                            @selected(
                                                (string) $current ===
                                                (string) $option
                                            )
                                        >
                                            {{ $option }}
                                        </option>

                                    @endforeach

                                </select>

                            </div>


                        {{-- NUMBER RANGE --}}
                        @elseif($type === 'number_range')

                            @php

                                $numberValue =
                                    is_array($current)
                                        ? $current
                                        : [];

                            @endphp

                            <div class="col-md-6">

                                <label class="form-label fw-semibold">
                                    {{ $label }}
                                </label>

                                <div class="row g-2">

                                    <div class="col">

                                        <input
                                            type="number"
                                            step="any"
                                            name="filters[{{ $filterIndex }}][min]"
                                            class="form-control"
                                            placeholder="Minimum"
                                            value="{{ $numberValue['min'] ?? '' }}"
                                        >

                                    </div>

                                    <div class="col">

                                        <input
                                            type="number"
                                            step="any"
                                            name="filters[{{ $filterIndex }}][max]"
                                            class="form-control"
                                            placeholder="Maximum"
                                            value="{{ $numberValue['max'] ?? '' }}"
                                        >

                                    </div>

                                </div>

                            </div>


                        {{-- SEARCH --}}
                        @else

                            <div class="col-md-4">

                                <label class="form-label fw-semibold">
                                    {{ $label }}
                                </label>

                                <input
                                    type="text"
                                    name="filters[{{ $filterIndex }}]"
                                    class="form-control"
                                    value="{{ is_array($current) ? '' : $current }}"
                                    placeholder="Search {{ strtolower($label) }}..."
                                >

                            </div>

                        @endif

                    @endforeach

                </div>


                <div class="d-flex gap-2 mt-4">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        <i class="bi bi-search me-1"></i>
                        Apply Filters
                    </button>


                    <a
                        href="{{ route('report-queries.report', $query) }}"
                        class="btn btn-light border"
                    >
                        <i class="bi bi-arrow-counterclockwise me-1"></i>
                        Reset
                    </a>

                </div>

            </form>

        </div>

    </div>

@endif


            @if(!empty($rows))

                <div class="report-table-wrapper">

                    <table
                        class="table report-table"
                        id="reportResultTable"
                    >

                        <thead>

                            <tr>

                                <th>
                                    #
                                </th>

                                @foreach($columns as $column)

                                    <th>
                                        {{ $column }}
                                    </th>

                                @endforeach

                            </tr>

                        </thead>

                        <tbody>

                            @foreach($rows as $index => $row)

                                <tr>

                                    <td class="text-muted">
                                        {{ $index + 1 }}
                                    </td>

                                    @foreach($columns as $column)

                                        <td>

                                            @php
                                                $value = $row->{$column} ?? null;
                                            @endphp

                                            @if($value === null)

                                                <span class="text-muted">
                                                    —
                                                </span>

                                            @else

                                                {{ $value }}

                                            @endif

                                        </td>

                                    @endforeach

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @else

                <div class="text-center py-5">

                    <i
                        class="bi bi-inbox"
                        style="font-size:42px; opacity:.3;"
                    ></i>

                    <h5 class="mt-3">
                        No records found
                    </h5>

                    <p class="text-muted mb-0">
                        The query executed successfully but returned no rows.
                    </p>

                </div>

            @endif

        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- SQL --}}
    {{-- ========================================================= --}}

  <div class="sql-card">
    <div class="sql-header">
        <i class="bi bi-code-slash me-2"></i>
        Executed SQL
    </div>
    
    <div class="sql-code">
        <pre class="m-0"><code>{{ is_array($sql) ? json_encode($sql, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $sql }}</code></pre>
    </div>
</div>

</div>


<script>

function exportTableToCsv()
{
    const table =
        document.getElementById(
            'reportResultTable'
        );

    if (!table) {
        return;
    }

    const rows =
        Array.from(
            table.querySelectorAll('tr')
        );

    const csv =
        rows
            .map(row => {

                const cells =
                    Array.from(
                        row.querySelectorAll(
                            'th, td'
                        )
                    );

                return cells
                    .map(cell => {

                        let value =
                            cell.innerText
                                .replace(
                                    /"/g,
                                    '""'
                                )
                                .trim();

                        return `"${value}"`;

                    })
                    .join(',');

            })
            .join('\n');

    const blob =
        new Blob(
            [csv],
            {
                type: 'text/csv;charset=utf-8;'
            }
        );

    const url =
        URL.createObjectURL(blob);

    const link =
        document.createElement('a');

    link.href = url;

    link.download =
        @json($query->query_name)
        + '.csv';

    document.body.appendChild(link);

    link.click();

    document.body.removeChild(link);

    URL.revokeObjectURL(url);
}

</script>

@endsection