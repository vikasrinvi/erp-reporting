@extends('layouts.app')

@section('content')

@php
    $savedFilters = $query->filters_json ?? [];

    $runtimeFilters = collect($savedFilters)
        ->filter(fn ($filter) => !empty($filter['runtime']) && !empty($filter['type']));
    
    $resultColumns = !empty($results)
        ? array_keys((array) $results[0])
        : [];
@endphp

<link
    href="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/css/tom-select.bootstrap5.min.css"
    rel="stylesheet"
>

<style>
    .report-page {
        background: #f5f7fb;
        min-height: 100vh;
        padding: 24px;
    }

    .report-card {
        background: #fff;
        border: 1px solid #e7eaf0;
        border-radius: 12px;
        padding: 22px;
        margin-bottom: 20px;
        box-shadow: 0 4px 16px rgba(23, 32, 51, .04);
    }

    .report-heading {
        background: linear-gradient(120deg, #172033, #273b63);
        color: white;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 20px;
    }

    .report-heading .text-muted {
        color: #d5dced !important;
    }

    .report-table-wrap {
        overflow-x: auto;
    }

    .report-table-wrap th,
    .report-table-wrap td {
        white-space: nowrap;
        padding: 10px 12px;
        font-size: 13px;
    }

    .report-table-wrap thead th {
        background: #f0f3f9;
    }

    .filter-label {
        font-weight: 600;
        margin-bottom: 7px;
    }

    .report-sql {
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        background: #172033;
        color: #e5edf9;
        padding: 16px;
        border-radius: 8px;
        font-size: 12px;
    }
</style>

<div class="report-page">

    <div class="report-heading">
        <h3 class="mb-2">
            {{ $query->query_name }}
        </h3>

        <div class="text-muted">
            {{ $query->report->name ?? 'Saved Report' }}
        </div>

        <div class="mt-3 d-flex flex-wrap gap-2">
            <a
                href="{{ url()->previous() }}"
                class="btn btn-light btn-sm"
            >
                Back
            </a>
        </div>
    </div>


    @if($error)

        <div class="alert alert-danger">
            <strong>Unable to generate the report.</strong>
            <div class="mt-1">{{ $error }}</div>
        </div>

    @endif




@if($runtimeFilters->isNotEmpty())

    <div class="report-card">

        <h5 class="mb-1">Report Filters</h5>

        <p class="text-muted small mb-4">
            Change the filters below and apply them to refresh the report.
        </p>

        <form
            method="GET"
            action="{{ route('report-queries.report', $query) }}"
        >

            <div class="row g-3">

                {{-- Date Format Selector including Complete Dates --}}
                <div class="col-md-4">
                    <label class="filter-label">Display Date Format</label>
                    <select class="form-select" name="date_format">
                        {{-- Complete / Full Dates --}}
                        <option value="%Y-%m-%d" @selected(request('date_format') === '%Y-%m-%d')>YYYY-MM-DD (2026-04-01)</option>
                        <option value="%d-%b-%Y" @selected(request('date_format') === '%d-%b-%Y')>DD-Mon-YYYY (01-Apr-2026)</option>
                        
                        {{-- Monthly Formats --}}
                        <option value="%Y-%m" @selected(request('date_format', '%Y-%m') === '%Y-%m')>YYYY-MM (2026-04)</option>
                        <option value="%b %Y" @selected(request('date_format') === '%b %Y')>Mon YYYY (Apr 2026)</option>
                        <option value="%M %Y" @selected(request('date_format') === '%M %Y')>Month YYYY (April 2026)</option>
                    </select>
                </div>

                @foreach($runtimeFilters as $filterIndex => $filter)

                    @php
                        $column = $filter['column'] ?? '';
                        $label  = $filter['label']
                            ?: \Illuminate\Support\Str::headline(
                                str_replace('.', ' ', $column)
                            );

                        $type = $filter['type'] ?? 'text';

                        // Safely extract selected value from request
                        $rawCurrent = request()->input('filters.' . $filterIndex);
                        
                        if (is_array($rawCurrent) && array_key_exists('value', $rawCurrent)) {
                            $current = $rawCurrent['value'];
                        } else {
                            $current = $rawCurrent;
                        }

                        $options = $filter['options'] ?? [];
                    @endphp

                    {{-- Metadata Hidden Inputs sent to Controller --}}
                    <input type="hidden" name="filters[{{ $filterIndex }}][column]" value="{{ $column }}">
                    <input type="hidden" name="filters[{{ $filterIndex }}][type]" value="{{ $type }}">
                    <input type="hidden" name="filters[{{ $filterIndex }}][label]" value="{{ $label }}">

                    {{-- DATE RANGE --}}
                    @if($type === 'date_range')

                        @php
                            $current = is_array($current) ? $current : [];
                        @endphp

                        <div class="col-md-6">

                            <div class="row g-2">

                                <div class="col">
                                <label class="filter-label">
                                {{ $label }}
                                    <span class="form-label small text-muted">
                                        From Date
                                    </span>
                                </label>
                                
                                    <input
                                        type="date"
                                        class="form-control"
                                        name="filters[{{ $filterIndex }}][value][from]"
                                        value="{{ is_string($current['from'] ?? null) ? $current['from'] : '' }}"
                                    >
                                </div>

                                <div class="col">
                                    <label class="form-label small text-muted">
                                        To Date
                                    </label>

                                    <input
                                        type="date"
                                        class="form-control"
                                        name="filters[{{ $filterIndex }}][value][to]"
                                        value="{{ is_string($current['to'] ?? null) ? $current['to'] : '' }}"
                                    >
                                </div>

                            </div>

                        </div>

                    {{-- NUMBER RANGE --}}
                    @elseif($type === 'number_range')

                        @php
                            $current = is_array($current) ? $current : [];
                        @endphp

                        <div class="col-md-6">

                            <label class="filter-label">
                                {{ $label }}
                            </label>

                            <div class="row g-2">

                                <div class="col">
                                    <input
                                        type="number"
                                        step="any"
                                        class="form-control"
                                        name="filters[{{ $filterIndex }}][value][min]"
                                        placeholder="Minimum"
                                        value="{{ is_scalar($current['min'] ?? null) ? $current['min'] : '' }}"
                                    >
                                </div>

                                <div class="col">
                                    <input
                                        type="number"
                                        step="any"
                                        class="form-control"
                                        name="filters[{{ $filterIndex }}][value][max]"
                                        placeholder="Maximum"
                                        value="{{ is_scalar($current['max'] ?? null) ? $current['max'] : '' }}"
                                    >
                                </div>

                            </div>

                        </div>

                    {{-- MULTI-SELECT --}}
                    @elseif($type === 'multi_select')

                        @php
                            $selectedValues = \Illuminate\Support\Arr::flatten(is_array($current) ? $current : [$current]);
                            $selectedStrings = array_filter(array_map(function ($item) {
                                return is_scalar($item) ? (string) $item : null;
                            }, $selectedValues), fn($item) => !is_null($item));
                        @endphp

                        <div class="col-md-4">

                            <label class="filter-label">
                                {{ $label }}
                            </label>

                            <select
                                multiple
                                class="form-select report-dynamic-select"
                                name="filters[{{ $filterIndex }}][value][]"
                                data-column="{{ $column }}"
                                data-index="{{ $filterIndex }}"
                            >

                                @foreach($options as $option)

                                    <option
                                        value="{{ $option }}"
                                        @selected(in_array((string) $option, $selectedStrings, true))
                                    >
                                        {{ $option }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                    {{-- SINGLE SELECT --}}
                    @elseif($type === 'select')

                        <div class="col-md-4">

                            <label class="filter-label">
                                {{ $label }}
                            </label>

                            <select
                                class="form-select report-dynamic-select"
                                name="filters[{{ $filterIndex }}][value]"
                                data-column="{{ $column }}"
                                data-index="{{ $filterIndex }}"
                            >

                                <option value="">All</option>

                                @foreach($options as $option)

                                    <option
                                        value="{{ $option }}"
                                        @selected(is_scalar($current) && (string) $current === (string) $option)
                                    >
                                        {{ $option }}
                                    </option>

                                @endforeach

                            </select>

                        </div>

                    {{-- SEARCH TEXT --}}
                    @else

                        <div class="col-md-4">

                            <label class="filter-label">
                                {{ $label }}
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                name="filters[{{ $filterIndex }}][value]"
                                value="{{ is_scalar($current) ? $current : '' }}"
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
                    Reset
                </a>

            </div>

        </form>

    </div>

@endif


    {{-- RESULTS --}}

    <div class="report-card">

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">

            <div>
                <h5 class="mb-1">Report Results</h5>

                <div class="text-muted small">
                    {{ count($results) }} row(s) returned; maximum 500 rows.
                </div>
            </div>

            <button
                type="button"
                class="btn btn-outline-secondary btn-sm"
                onclick="downloadReportCsv()"
                @disabled(empty($results))
            >
                <i class="bi bi-download me-1"></i>
                Export CSV
            </button>

        </div>


        @if(!empty($results))

            <div class="report-table-wrap">

                <table class="table table-bordered table-hover align-middle" id="reportResultsTable">

                    <thead>
                        <tr>
                            @foreach($resultColumns as $column)
                                <th>{{ $column }}</th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($results as $row)
                            <tr>
                                @foreach($resultColumns as $column)
                                    <td>{{ data_get($row, $column) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>

                </table>

            </div>

        @else

            @if(!$error)
                <div class="text-center text-muted py-5">
                    No records found for the selected filters.
                </div>
            @endif

        @endif

    </div>


    {{-- GENERATED SQL --}}

    @if($sql)

        <div class="report-card">

            <h6 class="mb-3">Executed SQL</h6>

            <pre class="report-sql">{{ $sql }}</pre>

        </div>

    @endif

</div>


<script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/js/tom-select.complete.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.report-dynamic-select').forEach(function (element) {

        const column = element.dataset.column;
        const index = element.dataset.index;

        const configuredOptions = element.options.length > 0;
        const selectedValues = Array.from(element.selectedOptions)
            .map(option => option.value);

        /*
         * When the builder has no explicit options,
         * retrieve unique values from the saved query.
         */
        const loadOptions = configuredOptions
            ? Promise.resolve([])
            : fetch(
                @json(route('report-queries.filter-options', $query)) +
                '?column=' + encodeURIComponent(column)
            ).then(response => {
                if (!response.ok) {
                    throw new Error('Unable to load dropdown options.');
                }
                return response.json();
            }).then(data => data.options || []);

        loadOptions.then(options => {

            options.forEach(option => {

                const exists = Array.from(element.options)
                    .some(existing => existing.value === String(option.value));

                if (!exists) {
                    element.add(new Option(
                        option.label,
                        option.value,
                        false,
                        false
                    ));
                }
            });

            const tomSelect = new TomSelect(element, {
                create: false,
                maxItems: element.multiple ? null : 1,
                plugins: element.multiple ? ['remove_button'] : [],
                placeholder: 'Search or select...',
                searchField: ['text', 'value']
            });

            /*
             * Restore selections after asynchronous options load.
             */
            if (selectedValues.length) {
                tomSelect.setValue(selectedValues, true);
            }

        }).catch(error => {
            console.error('Filter option loading failed:', error);
        });

    });

});


function downloadReportCsv() {

    const table = document.getElementById('reportResultsTable');

    if (!table) {
        return;
    }

    const rows = Array.from(table.querySelectorAll('tr'));

    const csv = rows.map(row => {

        return Array.from(row.querySelectorAll('th, td'))
            .map(cell => {
                const value = cell.innerText.replace(/"/g, '""');
                return '"' + value + '"';
            })
            .join(',');

    }).join('\r\n');

    const blob = new Blob(
        ['\uFEFF' + csv],
        { type: 'text/csv;charset=utf-8;' }
    );

    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = 'report.csv';
    link.click();

    URL.revokeObjectURL(url);
}
</script>

@endsection