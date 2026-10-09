<?php

namespace App\Http\Controllers;

use App\Models\ReportLibrary;
use App\Models\ReportQuery;
use App\Services\SqlQueryGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReportQueryController extends Controller
{
    protected SqlQueryGenerator $sqlGenerator;

    public function __construct(SqlQueryGenerator $sqlGenerator)
    {
        $this->sqlGenerator = $sqlGenerator;
    }


    public function index(Request $request)
    {
        $query = ReportQuery::query()
            ->with('report')
            ->latest('updated_at');

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = trim(
                $request->input('search')
            );

            $query->where(function ($q) use ($search) {

                $q->where(
                    'query_name',
                    'LIKE',
                    "%{$search}%"
                );

                $q->orWhereHas(
                    'report',
                    function ($reportQuery) use ($search) {

                        $reportQuery->where(
                            'name',
                            'LIKE',
                            "%{$search}%"
                        );
                    }
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Report filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('report_id')) {

            $query->where(
                'report_library_id',
                $request->input('report_id')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Query mode
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled('query_mode') &&
            in_array(
                $request->input('query_mode'),
                ['builder', 'custom'],
                true
            )
        ) {

            $query->where(
                'query_mode',
                $request->input('query_mode')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {

            if ($request->input('status') === 'active') {

                $query->where(
                    'is_active',
                    true
                );

            } elseif (
                $request->input('status') === 'inactive'
            ) {

                $query->where(
                    'is_active',
                    false
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $queries = $query
            ->paginate(15)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Report dropdown
        |--------------------------------------------------------------------------
        */

        $reports = \App\Models\ReportLibrary::query()
            ->orderBy('report_name')
            ->get();

        return view(
            'reports.queries.index',
            compact(
                'queries',
                'reports'
            )
        );
    }
        /*
    |--------------------------------------------------------------------------
    | View Saved Report
    |--------------------------------------------------------------------------
    */

    public function viewReportFaltu(Request $request, ReportQuery $query)
    {
        
        $query->load('report');

        $results = [];
        $sql = null;
        $error = null;

        try {
            /*
            * Dynamic filters need builder metadata.
            * Custom SQL is deliberately not rewritten.
            */
            if ($query->query_mode !== 'builder') {
                throw new \RuntimeException(
                    'This query is saved as Custom SQL. Open it in Query Builder and save it as a builder query to use dynamic report filters.'
                );
            }
            
            $data = [
                'tables' => $query->tables_json ?? [],
                'joins' => $query->joins_json ?? [],
                'columns' => $query->columns_json ?? [],
                'filters' => $this->compileReportFilters(
                    $query->filters_json ?? [],
                    $request->input('filters', [])
                ),
                'group_by' => $query->group_by_json ?? [],
                'order_by' => $query->order_by_json ?? [],
            ];
            
            $sql = $this->buildSql($data);
            
            // buildSql() adds a trailing semicolon.
            $sql = rtrim(trim($sql), ';');

            // Prevent the report page from returning unlimited rows.
            $sql .= "\nLIMIT 500";

            if (!$this->isSafeSelectQuery($sql)) {
                throw new \RuntimeException(
                    'The generated report query failed the SELECT safety check.'
                );
            }

            $results = DB::select($sql);

        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return view('reports.queries.report', [
            'query' => $query,
            'results' => $results,
            'sql' => $sql,
            'error' => $error,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Convert saved report filter definitions into SQL filters
    |--------------------------------------------------------------------------
    */

    private function compileReportFilters(
        array $savedFilters,
        array $inputFilters
    ): array {
        $compiled = [];

        foreach ($savedFilters as $index => $filter) {

            $column = trim((string) ($filter['column'] ?? ''));

            if ($column === '') {
                continue;
            }

            /*
            * Allow only simple table_alias.column identifiers.
            */
            if (!preg_match(
                '/^[A-Za-z][A-Za-z0-9_]*\.[A-Za-z0-9_]+$/',
                $column
            )) {
                throw new \RuntimeException(
                    "Invalid filter column: {$column}"
                );
            }

            /*
            * Fixed SQL filters remain unchanged.
            * Runtime filter definitions are compiled below.
            */
            if (empty($filter['runtime'])) {

                if (empty($filter['operator'])) {
                    continue;
                }

                $compiled[] = $filter;
                continue;
            }

            if (!array_key_exists($index, $inputFilters)) {
                continue;
            }

            $value = $inputFilters[$index];

            $type = strtolower(
                trim((string) ($filter['type'] ?? 'search'))
            );

            $allowedTypes = [
                'search',
                'select',
                'multi_select',
                'date_range',
                'number_range',
            ];

            if (!in_array($type, $allowedTypes, true)) {
                throw new \RuntimeException(
                    "Unsupported report filter type: {$type}"
                );
            }

            /*
            * DATE RANGE
            */
            if ($type === 'date_range') {

                if (!is_array($value)) {
                    continue;
                }

                $from = trim((string) ($value['from'] ?? ''));
                $to = trim((string) ($value['to'] ?? ''));

                foreach (['from' => $from, 'to' => $to] as $key => $date) {
                    if ($date === '') {
                        continue;
                    }

                    $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

                    if (!$parsed || $parsed->format('Y-m-d') !== $date) {
                        throw new \RuntimeException(
                            "Invalid {$key} date for {$filter['label']}."
                        );
                    }
                }

                if ($from !== '' && $to !== '' && $from > $to) {
                    throw new \RuntimeException(
                        'The From Date cannot be later than the To Date.'
                    );
                }

                if ($from !== '') {
                    $compiled[] = [
                        'column' => $column,
                        'operator' => '>=',
                        'value' => $from . ' 00:00:00',
                    ];
                }

                if ($to !== '') {
                    $nextDay = (new \DateTimeImmutable($to))
                        ->modify('+1 day')
                        ->format('Y-m-d');

                    $compiled[] = [
                        'column' => $column,
                        'operator' => '<',
                        'value' => $nextDay . ' 00:00:00',
                    ];
                }

                continue;
            }

            /*
            * NUMBER RANGE
            */
            if ($type === 'number_range') {

                if (!is_array($value)) {
                    continue;
                }

                $min = $value['min'] ?? '';
                $max = $value['max'] ?? '';

                if ($min !== '' && !is_numeric($min)) {
                    throw new \RuntimeException(
                        'Minimum value must be numeric.'
                    );
                }

                if ($max !== '' && !is_numeric($max)) {
                    throw new \RuntimeException(
                        'Maximum value must be numeric.'
                    );
                }

                if (
                    $min !== '' &&
                    $max !== '' &&
                    (float) $min > (float) $max
                ) {
                    throw new \RuntimeException(
                        'Minimum value cannot exceed maximum value.'
                    );
                }

                if ($min !== '') {
                    $compiled[] = [
                        'column' => $column,
                        'operator' => '>=',
                        'value' => $min,
                    ];
                }

                if ($max !== '') {
                    $compiled[] = [
                        'column' => $column,
                        'operator' => '<=',
                        'value' => $max,
                    ];
                }

                continue;
            }

            /*
            * SELECT / MULTI-SELECT / SEARCH
            */
            if ($type === 'search') {

                if (is_array($value)) {
                    continue;
                }

                $value = trim((string) $value);

                if ($value === '') {
                    continue;
                }

                $compiled[] = [
                    'column' => $column,
                    'operator' => 'LIKE',
                    'value' => '%' . $value . '%',
                ];

                continue;
            }

            if ($type === 'select') {

                if (is_array($value)) {
                    continue;
                }

                $value = trim((string) $value);

                if ($value === '') {
                    continue;
                }

                $options = $filter['options'] ?? [];

                if (
                    !empty($options) &&
                    !in_array($value, array_map('strval', $options), true)
                ) {
                    throw new \RuntimeException(
                        'Invalid value selected for ' .
                        ($filter['label'] ?? $column) . '.'
                    );
                }

                $compiled[] = [
                    'column' => $column,
                    'operator' => '=',
                    'value' => $value,
                ];

                continue;
            }

            if ($type === 'multi_select') {

                $values = is_array($value) ? $value : [$value];

                $values = array_values(array_unique(array_filter(
                    array_map(
                        fn ($item) => trim((string) $item),
                        $values
                    ),
                    fn ($item) => $item !== ''
                )));

                if (!$values) {
                    continue;
                }

                $options = $filter['options'] ?? [];

                if (!empty($options)) {
                    $allowedOptions = array_map('strval', $options);

                    foreach ($values as $selected) {
                        if (!in_array($selected, $allowedOptions, true)) {
                            throw new \RuntimeException(
                                'Invalid value selected for ' .
                                ($filter['label'] ?? $column) . '.'
                            );
                        }
                    }
                }

                $compiled[] = [
                    'column' => $column,
                    'operator' => 'IN',
                    'value' => $values,
                ];
            }
        }

        return $compiled;
    }

    public function viewReport(Request $request, ReportQuery $query)
    {

        // dd($request,  $query);
        $error = null;
        /*
        |--------------------------------------------------------------------------
        | Make sure the report relationship exists
        |--------------------------------------------------------------------------
        */

        $query->load('report');

        /*
        |--------------------------------------------------------------------------
        | Get SQL according to query mode
        |--------------------------------------------------------------------------
        */

        if ($query->query_mode === 'custom') {

            $sql = trim(
                (string) $query->custom_sql
            );

        } else {

            $sql = trim(
                (string) $query->generated_sql
            );
        }

        if ($sql === '') {

            return redirect()
                ->route('report-queries.index')
                ->with(
                    'error',
                    'This saved query does not contain executable SQL.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Merge Runtime Request Filters into Base CTE Query
        |--------------------------------------------------------------------------
        */
        $runtimeFilters = $request->input('filters', []);

        if (!empty($runtimeFilters) && is_array($runtimeFilters)) {
            $sql = $this->applyRuntimeFiltersToSql($sql, $runtimeFilters);
        }

        /*
        |--------------------------------------------------------------------------
        | Security
        |--------------------------------------------------------------------------
        */

        if (!$this->isSafeSelectQuery($sql)) {

            return redirect()
                ->route('report-queries.index')
                ->with(
                    'error',
                    'This query cannot be executed because it is not a valid SELECT query.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Remove trailing semicolon
        |--------------------------------------------------------------------------
        */

        $sql = rtrim(
            $sql,
            " \t\n\r\0\x0B;"
        );

        /*
        |--------------------------------------------------------------------------
        | Execute
        |--------------------------------------------------------------------------
        */

        try {

            $rows = DB::select($sql);

            $columns = [];

            if (!empty($rows)) {

                $columns = array_keys(
                    get_object_vars(
                        $rows[0]
                    )
                );
            }

            $results = DB::select($sql);

            return view(
                'reports.queries.report',
                compact(
                    'query',
                    'rows',
                    'columns',
                    'sql',
                    'error',
                    'results'
                )
            );

        } catch (\Throwable $e) {

            return view(
                'reports.queries.report',
                [
                    'query' => $query,
                    'rows' => [],
                    'columns' => [],
                    'sql' => $sql,
                    'error' => $e->getMessage(),
                    'results' => [],
                ]
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Builder Page
    |--------------------------------------------------------------------------
    */

    public function builder(ReportLibrary $report)
    {
        $tables = $this->getTables();

        $query = $report->queries()
            ->where('is_active', true)
            ->latest()
            ->first();

        return view(
            'reports.library.query-builder',
            compact(
                'report',
                'tables',
                'query'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Get Database Tables
    |--------------------------------------------------------------------------
    */

    public function databaseTables()
    {
        return response()->json([
            'tables' => $this->getTables(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Get Columns
    |--------------------------------------------------------------------------
    */

    public function databaseColumns(Request $request)
    {
        $request->validate([
            'table' => [
                'required',
                'string',
                'regex:/^[A-Za-z0-9_]+$/'
            ],
        ]);

        $table = $request->table;

        if (!in_array($table, $this->getTables(), true)) {
            return response()->json([
                'message' => 'Invalid table.'
            ], 422);
        }

        $columns = DB::select(
            "SHOW COLUMNS FROM `{$table}`"
        );

        return response()->json([
            'columns' => collect($columns)
                ->map(function ($column) {
                    return $column->Field;
                })
                ->values()
                ->all(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Generate SQL
    |--------------------------------------------------------------------------
    */

    public function generate(
    Request $request,
    ReportLibrary $report
    ) {
        $data = $request->validate([

            /*
            |--------------------------------------------------------------------------
            | Existing Builder
            |--------------------------------------------------------------------------
            */

            'tables' => 'required|array|min:1',

            'tables.*.table' => [
                'required',
                'string',
                'regex:/^[A-Za-z0-9_]+$/'
            ],

            'tables.*.alias' => [
                'required',
                'string',
                'regex:/^[A-Za-z][A-Za-z0-9_]*$/'
            ],

            'joins' => 'nullable|array',

            'joins.*.type' => [
                'nullable',
                'in:INNER,LEFT,RIGHT'
            ],

            'joins.*.table' => [
                'nullable',
                'string',
                'regex:/^[A-Za-z0-9_]+$/'
            ],

            'joins.*.alias' => [
                'nullable',
                'string',
                'regex:/^[A-Za-z][A-Za-z0-9_]*$/'
            ],

            'joins.*.left' => [
                'nullable',
                'string',
                'regex:/^[A-Za-z][A-Za-z0-9_]*\.[A-Za-z0-9_]+$/'
            ],

            'joins.*.operator' => [
                'nullable',
                'in:=,>,<,>=,<=,<>'
            ],

            'joins.*.right' => [
                'nullable',
                'string',
                'regex:/^[A-Za-z][A-Za-z0-9_]*\.[A-Za-z0-9_]+$/'
            ],

            'columns' => 'required|array|min:1',

            'columns.*.expression' => [
                'required',
                'string'
            ],

            'columns.*.function' => [
                'nullable',
                'in:NONE,SUM,COUNT,AVG,MIN,MAX'
            ],

            'columns.*.alias' => [
                'nullable',
                'string',
                'regex:/^[A-Za-z][A-Za-z0-9_]*$/'
            ],

            'filters' => 'nullable|array',

            'filters.*.column' => [
                'nullable',
                'string'
            ],

            'filters.*.operator' => [
                'nullable',
                'in:=,!=,>,<,>=,<=,LIKE,NOT LIKE,IS NULL,IS NOT NULL,IN,NOT IN'
            ],

            'filters.*.value' => 'nullable',

            'group_by' => 'nullable|array',

            'group_by.*' => [
                'string'
            ],

            'order_by' => 'nullable|array',

            'order_by.*.column' => [
                'nullable',
                'string'
            ],

            'order_by.*.direction' => [
                'nullable',
                'in:ASC,DESC'
            ],

            /*
            |--------------------------------------------------------------------------
            | Advanced
            |--------------------------------------------------------------------------
            */

            'distinct' => 'nullable|boolean',

            'limit' => 'nullable|integer|min:1|max:5000',

            'cte' => 'nullable|array',

            'cte.enabled' => 'nullable|boolean',

            'cte.name' => [
                'nullable',
                'string',
                'regex:/^[A-Za-z][A-Za-z0-9_]*$/'
            ],

            'calculated_columns' => 'nullable|array',

            'calculated_columns.*.expression' => [
                'required',
                'string'
            ],

            'calculated_columns.*.alias' => [
                'required',
                'string',
                'regex:/^[A-Za-z][A-Za-z0-9_]*$/'
            ],

            'pivot' => 'nullable|array',

            'pivot.enabled' => 'nullable|boolean',

            'pivot.rows' => 'nullable|array',

            'pivot.rows.*' => [
                'string',
                'regex:/^[A-Za-z_][A-Za-z0-9_]*$/'
            ],

            'pivot.column' => [
                'nullable',
                'string',
                'regex:/^[A-Za-z_][A-Za-z0-9_]*$/'
            ],

            'pivot.values' => 'nullable|array',

            'pivot.values.*' => 'string',

            'pivot.value' => [
                'nullable',
                'string',
                'regex:/^[A-Za-z_][A-Za-z0-9_]*$/'
            ],

            'pivot.aggregate' => [
                'nullable',
                'in:SUM,COUNT,AVG,MIN,MAX'
            ],

            'pivot.dimensions' => 'nullable|array',

            'pivot.dimensions.*.column' => [
                'required',
                'string',
                'regex:/^[A-Za-z_][A-Za-z0-9_]*$/'
            ],

            'pivot.dimensions.*.values' => 'required|array',

            'pivot.dimensions.*.values.*' => 'string',

            'having' => 'nullable|array',

            'having.*.expression' => [
                'nullable',
                'string'
            ],

            'having.*.operator' => [
                'nullable',
                'in:=,!=,>,<,>=,<=,LIKE,NOT LIKE,IS NULL,IS NOT NULL'
            ],

            'having.*.value' => 'nullable',
        ]);

        try {

            $sql = $this->sqlGenerator->generate([
                'tables' => $data['tables'] ?? [],
                'joins' => $data['joins'] ?? [],
                'columns' => $data['columns'] ?? [],
                'filters' => $data['filters'] ?? [],
                'group_by' => $data['group_by'] ?? [],
                'order_by' => $data['order_by'] ?? [],

                'distinct' =>
                    $data['distinct'] ?? false,

                'limit' =>
                    $data['limit'] ?? 500,

                'cte' =>
                    $data['cte'] ?? null,

                'calculated_columns' =>
                    $data['calculated_columns'] ?? [],

                'pivot' =>
                    $data['pivot'] ?? null,

                'having' =>
                    $data['having'] ?? [],
            ]);

            return response()->json([
                'success' => true,
                'sql' => $sql,
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function generate213(
        Request $request,
        ReportLibrary $report
    ) {
        $data = $this->validateBuilderRequest($request);

        try {

            $definition = $this->buildGeneratorDefinition(
                $data
            );

            $sql = $this->sqlGenerator->generate(
                $definition
            );

            return response()->json([
                'success' => true,
                'sql' => $sql,
            ]);

        } catch (InvalidArgumentException $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to generate SQL.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Save Query
    |--------------------------------------------------------------------------
    */
    public function save(
    Request $request,
    ReportLibrary $report
    ) {
        $request->validate([

            'query_name' =>
                'required|string|max:150',

            'query_mode' =>
                'required|in:builder,custom',

            'tables_json' => 'nullable',
            'joins_json' => 'nullable',
            'columns_json' => 'nullable',
            'filters_json' => 'nullable',
            'group_by_json' => 'nullable',
            'order_by_json' => 'nullable',

            'cte_json' => 'nullable',
            'calculated_columns_json' => 'nullable',
            'pivot_json' => 'nullable',
            'having_json' => 'nullable',

            'distinct_flag' =>
                'nullable|boolean',

            'limit_value' =>
                'nullable|integer|min:1|max:5000',

            'generated_sql' =>
                'nullable|string',

            'custom_sql' =>
                'nullable|string',
        ]);

        $query = $report->queries()->first();

        if (!$query) {

            $query = new ReportQuery();

            $query->report_library_id =
                $report->id;

            $query->created_by =
                auth()->id();
        }

        $query->query_name =
            $request->query_name;

        $query->query_mode =
            $request->query_mode;

        /*
        |--------------------------------------------------------------------------
        | Existing
        |--------------------------------------------------------------------------
        */

        $query->tables_json =
            $this->decodeJson(
                $request->tables_json
            );

        $query->joins_json =
            $this->decodeJson(
                $request->joins_json
            );

        $query->columns_json =
            $this->decodeJson(
                $request->columns_json
            );

        $query->filters_json =
            $this->decodeJson(
                $request->filters_json
            );

        $query->group_by_json =
            $this->decodeJson(
                $request->group_by_json
            );

        $query->order_by_json =
            $this->decodeJson(
                $request->order_by_json
            );

        /*
        |--------------------------------------------------------------------------
        | Advanced
        |--------------------------------------------------------------------------
        */

        $query->cte_json =
            $this->decodeJson(
                $request->cte_json
            );

        $query->calculated_columns_json =
            $this->decodeJson(
                $request->calculated_columns_json
            );

        $query->pivot_json =
            $this->decodeJson(
                $request->pivot_json
            );

        $query->having_json =
            $this->decodeJson(
                $request->having_json
            );

        $query->distinct_flag =
            $request->boolean(
                'distinct_flag'
            );

        $query->limit_value =
            (int) (
                $request->limit_value
                ?? 500
            );

        /*
        |--------------------------------------------------------------------------
        | SQL
        |--------------------------------------------------------------------------
        */

        $query->generated_sql =
            $request->generated_sql;

        $query->custom_sql =
            $request->custom_sql;

        $query->is_active = true;

        $query->updated_by =
            auth()->id();

        $query->save();

        return response()->json([
            'success' => true,
            'message' =>
                'Query saved successfully.',
            'id' => $query->id,
        ]);
    }

    public function save23(
        Request $request,
        ReportLibrary $report
    ) {
        $request->validate([
            'query_name' => [
                'required',
                'string',
                'max:150'
            ],

            'query_mode' => [
                'required',
                'in:builder,custom'
            ],

            'tables_json' => 'nullable',
            'joins_json' => 'nullable',
            'columns_json' => 'nullable',
            'filters_json' => 'nullable',
            'group_by_json' => 'nullable',
            'order_by_json' => 'nullable',

            /*
             * Advanced
             */
            'cte_json' => 'nullable',
            'calculated_columns_json' => 'nullable',
            'pivot_json' => 'nullable',
            'having_json' => 'nullable',

            'distinct_flag' => [
                'nullable',
                'boolean'
            ],

            'limit_value' => [
                'nullable',
                'integer',
                'min:1',
                'max:5000'
            ],

            'generated_sql' => 'nullable|string',
            'custom_sql' => 'nullable|string',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Existing behavior preserved
        |--------------------------------------------------------------------------
        |
        | We update the first query belonging to the report.
        |
        */

        $query = $report->queries()->first();

        if (!$query) {

            $query = new ReportQuery();

            $query->report_library_id = $report->id;

            $query->created_by = auth()->id();
        }

        $query->query_name = $request->query_name;

        $query->query_mode = $request->query_mode;


        /*
        |--------------------------------------------------------------------------
        | Existing Builder Fields
        |--------------------------------------------------------------------------
        */

        $query->tables_json =
            $this->decodeJson(
                $request->tables_json
            );

        $query->joins_json =
            $this->decodeJson(
                $request->joins_json
            );

        $query->columns_json =
            $this->decodeJson(
                $request->columns_json
            );

        $query->filters_json =
            $this->decodeJson(
                $request->filters_json
            );

        $query->group_by_json =
            $this->decodeJson(
                $request->group_by_json
            );

        $query->order_by_json =
            $this->decodeJson(
                $request->order_by_json
            );


        /*
        |--------------------------------------------------------------------------
        | NEW Advanced Fields
        |--------------------------------------------------------------------------
        */

        $query->cte_json =
            $this->decodeJson(
                $request->cte_json
            );

        $query->calculated_columns_json =
            $this->decodeJson(
                $request->calculated_columns_json
            );

        $query->pivot_json =
            $this->decodeJson(
                $request->pivot_json
            );

        $query->having_json =
            $this->decodeJson(
                $request->having_json
            );


        /*
        |--------------------------------------------------------------------------
        | DISTINCT
        |--------------------------------------------------------------------------
        */

        $query->distinct_flag =
            $request->boolean(
                'distinct_flag'
            );


        /*
        |--------------------------------------------------------------------------
        | LIMIT
        |--------------------------------------------------------------------------
        */

        $query->limit_value =
            min(
                max(
                    (int) (
                        $request->limit_value
                        ?? 500
                    ),
                    1
                ),
                5000
            );


        /*
        |--------------------------------------------------------------------------
        | SQL
        |--------------------------------------------------------------------------
        */

        $query->generated_sql =
            $request->generated_sql;

        $query->custom_sql =
            $request->custom_sql;


        /*
        |--------------------------------------------------------------------------
        | Status / Audit
        |--------------------------------------------------------------------------
        */

        $query->is_active = true;

        $query->updated_by = auth()->id();

        $query->save();

        return response()->json([
            'success' => true,
            'message' => 'Query saved successfully.',
            'id' => $query->id,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Execute SQL
    |--------------------------------------------------------------------------
    */

    public function execute(
        Request $request,
        ReportLibrary $report
    ) {
        $request->validate([
            'sql' => [
                'required',
                'string'
            ],
        ]);

        $sql = trim(
            $request->sql
        );

        /*
        |--------------------------------------------------------------------------
        | Safety Validation
        |--------------------------------------------------------------------------
        */

        if (!$this->isSafeSelectQuery($sql)) {

            return response()->json([
                'success' => false,
                'message' =>
                    'Only safe SELECT / WITH queries are allowed.'
            ], 422);
        }


        try {

            /*
            |--------------------------------------------------------------------------
            | Remove trailing semicolon
            |--------------------------------------------------------------------------
            */

            $sql = rtrim(
                $sql,
                " \t\n\r\0\x0B;"
            );


            /*
            |--------------------------------------------------------------------------
            | Result Safety Limit
            |--------------------------------------------------------------------------
            |
            | We don't simply append:
            |
            |     LIMIT 500
            |
            | because the generated SQL may already contain LIMIT.
            |
            | Instead wrap the user's SELECT.
            |
            */

            $safeSql = "
                SELECT *
                FROM (
                    {$sql}
                ) AS report_result
                LIMIT 500
            ";


            $rows = DB::select(
                $safeSql
            );


            /*
            |--------------------------------------------------------------------------
            | Detect Columns
            |--------------------------------------------------------------------------
            */

            $columns = [];

            if (!empty($rows)) {

                $columns = array_keys(
                    get_object_vars(
                        $rows[0]
                    )
                );
            }


            return response()->json([
                'success' => true,
                'count' => count($rows),
                'columns' => $columns,
                'rows' => $rows,
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' =>
                    $e->getMessage(),
            ], 422);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Convert Request Data To Generator Definition
    |--------------------------------------------------------------------------
    */

    private function buildGeneratorDefinition(
        array $data
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Tables
        |--------------------------------------------------------------------------
        */

        $tables = [];

        foreach ($data['tables'] ?? [] as $table) {

            $tables[] = [
                'table' =>
                    $table['table'],

                'alias' =>
                    $table['alias'] ?? null,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Joins
        |--------------------------------------------------------------------------
        */

        $joins = [];

        foreach ($data['joins'] ?? [] as $join) {

            if (
                empty($join['table']) ||
                empty($join['left']) ||
                empty($join['right'])
            ) {
                continue;
            }

            $joins[] = [
                'type' =>
                    strtoupper(
                        $join['type']
                        ?? 'INNER'
                    ),

                'table' =>
                    $join['table'],

                'alias' =>
                    $join['alias']
                    ?? null,

                'condition' =>
                    $join['left']
                    . ' '
                    . ($join['operator'] ?? '=')
                    . ' '
                    . $join['right'],
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Columns
        |--------------------------------------------------------------------------
        */

        $columns = [];

        foreach ($data['columns'] ?? [] as $column) {

            if (empty($column['expression'])) {
                continue;
            }

            $expression =
                $column['expression'];

            $function =
                strtoupper(
                    $column['function']
                    ?? 'NONE'
                );

            if ($function !== 'NONE') {

                $expression =
                    "{$function}({$expression})";
            }

            $columns[] = [
                'expression' =>
                    $expression,

                'alias' =>
                    $column['alias']
                    ?? null,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        $filters = [];

        foreach ($data['filters'] ?? [] as $filter) {

            if (
                empty($filter['column']) ||
                empty($filter['operator'])
            ) {
                continue;
            }

            $filters[] = [
                'expression' =>
                    $filter['column'],

                'operator' =>
                    strtoupper(
                        $filter['operator']
                    ),

                'value' =>
                    $filter['value']
                    ?? null,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Group By
        |--------------------------------------------------------------------------
        */

        $groupBy = [];

        foreach (
            $data['group_by'] ?? []
            as $group
        ) {

            if (!empty($group)) {
                $groupBy[] = $group;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Order By
        |--------------------------------------------------------------------------
        */

        $orderBy = [];

        foreach (
            $data['order_by'] ?? []
            as $order
        ) {

            if (empty($order['column'])) {
                continue;
            }

            $orderBy[] = [
                'expression' =>
                    $order['column'],

                'direction' =>
                    strtoupper(
                        $order['direction']
                        ?? 'ASC'
                    ),
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Advanced Fields
        |--------------------------------------------------------------------------
        */

        $cte =
            $this->decodeJson(
                $data['cte_json']
                ?? null
            );

        $calculatedColumns =
            $this->decodeJson(
                $data['calculated_columns_json']
                ?? null
            );

        $pivot =
            $this->decodeJson(
                $data['pivot_json']
                ?? null
            );

        $having =
            $this->decodeJson(
                $data['having_json']
                ?? null
            );


        /*
        |--------------------------------------------------------------------------
        | Return Unified Definition
        |--------------------------------------------------------------------------
        */

        return [
            'query_mode' =>
                $data['query_mode']
                ?? 'builder',

            'tables_json' =>
                $tables,

            'joins_json' =>
                $joins,

            'columns_json' =>
                $columns,

            'filters_json' =>
                $filters,

            'group_by_json' =>
                $groupBy,

            'order_by_json' =>
                $orderBy,

            'cte_json' =>
                $cte,

            'calculated_columns_json' =>
                $calculatedColumns,

            'pivot_json' =>
                $pivot,

            'having_json' =>
                $having,

            'distinct_flag' =>
                !empty(
                    $data['distinct_flag']
                ),

            'limit_value' =>
                $data['limit_value']
                ?? 500,

            'custom_sql' =>
                $data['custom_sql']
                ?? null,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Builder Request Validation
    |--------------------------------------------------------------------------
    */

    private function validateBuilderRequest(
        Request $request
    ): array {

        return $request->validate([

            /*
            |--------------------------------------------------------------------------
            | Query Mode
            |--------------------------------------------------------------------------
            */

            'query_mode' => [
                'nullable',
                'in:builder,custom'
            ],


            /*
            |--------------------------------------------------------------------------
            | Tables
            |--------------------------------------------------------------------------
            */

            'tables' =>
                'required|array|min:1',

            'tables.*.table' => [
                'required',
                'string',
                'regex:/^[A-Za-z0-9_]+$/'
            ],

            'tables.*.alias' => [
                'required',
                'string',
                'regex:/^[A-Za-z][A-Za-z0-9_]*$/'
            ],


            /*
            |--------------------------------------------------------------------------
            | Joins
            |--------------------------------------------------------------------------
            */

            'joins' =>
                'nullable|array',

            'joins.*.type' => [
                'nullable',
                'in:INNER,LEFT,RIGHT,FULL,CROSS'
            ],

            'joins.*.table' => [
                'nullable',
                'string',
                'regex:/^[A-Za-z0-9_]+$/'
            ],

            'joins.*.alias' => [
                'nullable',
                'string',
                'regex:/^[A-Za-z][A-Za-z0-9_]*$/'
            ],

            'joins.*.left' => [
                'nullable',
                'string',
                'regex:/^[A-Za-z][A-Za-z0-9_]*\.[A-Za-z0-9_]+$/'
            ],

            'joins.*.operator' => [
                'nullable',
                'in:=,>,<,>=,<=,<>'
            ],

            'joins.*.right' => [
                'nullable',
                'string',
                'regex:/^[A-Za-z][A-Za-z0-9_]*\.[A-Za-z0-9_]+$/'
            ],


            /*
            |--------------------------------------------------------------------------
            | Columns
            |--------------------------------------------------------------------------
            */

            'columns' =>
                'required|array|min:1',

            /*
             * We deliberately allow expressions here.
             *
             * This is necessary for things such as:
             *
             * DATE_FORMAT(...)
             *
             * CASE WHEN ...
             * CONCAT(...)
             * etc.
             */
            'columns.*.expression' => [
                'required',
                'string',
                'max:1000'
            ],

            'columns.*.function' => [
                'nullable',
                'in:NONE,SUM,COUNT,AVG,MIN,MAX'
            ],

            'columns.*.alias' => [
                'nullable',
                'string',
                'regex:/^[A-Za-z][A-Za-z0-9_]*$/'
            ],


            /*
            |--------------------------------------------------------------------------
            | Filters
            |--------------------------------------------------------------------------
            */

            'filters' =>
                'nullable|array',

            'filters.*.column' => [
                'nullable',
                'string',
                'max:255'
            ],

            'filters.*.operator' => [
                'nullable',
                'in:=,!=,>,<,>=,<=,LIKE,NOT LIKE,IS NULL,IS NOT NULL,IN,NOT IN,BETWEEN'
            ],

            'filters.*.value' =>
                'nullable',


            /*
            |--------------------------------------------------------------------------
            | Group By
            |--------------------------------------------------------------------------
            */

            'group_by' =>
                'nullable|array',

            'group_by.*' => [
                'string',
                'max:255'
            ],


            /*
            |--------------------------------------------------------------------------
            | Order By
            |--------------------------------------------------------------------------
            */

            'order_by' =>
                'nullable|array',

            'order_by.*.column' => [
                'nullable',
                'string',
                'max:255'
            ],

            'order_by.*.direction' => [
                'nullable',
                'in:ASC,DESC'
            ],


            /*
            |--------------------------------------------------------------------------
            | Advanced JSON
            |--------------------------------------------------------------------------
            */

            'cte_json' =>
                'nullable',

            'calculated_columns_json' =>
                'nullable',

            'pivot_json' =>
                'nullable',

            'having_json' =>
                'nullable',


            /*
            |--------------------------------------------------------------------------
            | DISTINCT
            |--------------------------------------------------------------------------
            */

            'distinct_flag' => [
                'nullable',
                'boolean'
            ],


            /*
            |--------------------------------------------------------------------------
            | LIMIT
            |--------------------------------------------------------------------------
            */

            'limit_value' => [
                'nullable',
                'integer',
                'min:1',
                'max:5000'
            ],


            /*
            |--------------------------------------------------------------------------
            | Custom SQL
            |--------------------------------------------------------------------------
            */

            'custom_sql' =>
                'nullable|string',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | SELECT Safety
    |--------------------------------------------------------------------------
    */

    private function isSafeSelectQuery(
        string $sql
    ): bool {

        $normalized =
            strtoupper(
                trim($sql)
            );


        /*
        |--------------------------------------------------------------------------
        | Must start with SELECT or WITH
        |--------------------------------------------------------------------------
        */

        if (
            !str_starts_with(
                $normalized,
                'SELECT'
            )
            &&
            !str_starts_with(
                $normalized,
                'WITH'
            )
        ) {
            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Remove one trailing semicolon
        |--------------------------------------------------------------------------
        */

        $withoutTrailingSemicolon =
            rtrim(
                $normalized,
                " \t\n\r\0\x0B;"
            );


        /*
        |--------------------------------------------------------------------------
        | No multiple statements
        |--------------------------------------------------------------------------
        */

        if (
            str_contains(
                $withoutTrailingSemicolon,
                ';'
            )
        ) {
            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | Dangerous SQL
        |--------------------------------------------------------------------------
        */

        $dangerous = [
            'INSERT ',
            'UPDATE ',
            'DELETE ',
            'DROP ',
            'ALTER ',
            'TRUNCATE ',
            'CREATE ',
            'RENAME ',
            'REPLACE ',
            'GRANT ',
            'REVOKE ',
            'CALL ',
            'LOAD DATA',
            'INTO OUTFILE',
            'INTO DUMPFILE',
        ];


        foreach ($dangerous as $keyword) {

            if (
                str_contains(
                    $normalized,
                    $keyword
                )
            ) {
                return false;
            }
        }


        return true;
    }


    /*
    |--------------------------------------------------------------------------
    | Database Tables
    |--------------------------------------------------------------------------
    */

    private function getTables(): array
    {
        $database =
            DB::connection()
                ->getDatabaseName();

        $rows = DB::select(
            "SELECT TABLE_NAME
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ?
             ORDER BY TABLE_NAME",
            [$database]
        );

        return collect($rows)
            ->pluck('TABLE_NAME')
            ->values()
            ->all();
    }


    /*
    |--------------------------------------------------------------------------
    | JSON Helper
    |--------------------------------------------------------------------------
    */

    private function decodeJson($value)
    {
        if (
            $value === null ||
            $value === ''
        ) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded =
            json_decode(
                $value,
                true
            );

        return json_last_error() === JSON_ERROR_NONE
            ? $decoded
            : null;
    }

    private function buildSql(array $data): string
    {
        $tables = $data['tables'] ?? [];
        $columns = $data['columns'] ?? [];

        if (empty($tables) || empty($columns)) {
            throw new \RuntimeException(
                'Add at least one table and one column before generating the report.'
            );
        }

        $firstTable = $tables[0];

        $sql = "SELECT\n";
        $selectParts = [];

        foreach ($columns as $column) {

            $expression = trim(
                (string) ($column['expression'] ?? '')
            );

            if ($expression === '') {
                continue;
            }

            if (!preg_match(
                '/^[A-Za-z][A-Za-z0-9_]*\.[A-Za-z0-9_]+$/',
                $expression
            )) {
                throw new \RuntimeException(
                    "Invalid column expression: {$expression}"
                );
            }

            $function = strtoupper(
                $column['function'] ?? 'NONE'
            );

            if (!in_array(
                $function,
                ['NONE', 'SUM', 'COUNT', 'AVG', 'MIN', 'MAX'],
                true
            )) {
                throw new \RuntimeException(
                    "Unsupported aggregate function: {$function}"
                );
            }

            if ($function !== 'NONE') {
                $expression = "{$function}({$expression})";
            }

            $alias = trim((string) ($column['alias'] ?? ''));

            if ($alias !== '') {

                if (!preg_match(
                    '/^[A-Za-z][A-Za-z0-9_]*$/',
                    $alias
                )) {
                    throw new \RuntimeException(
                        "Invalid column alias: {$alias}"
                    );
                }

                $expression .= " AS `{$alias}`";
            }

            $selectParts[] = "    {$expression}";
        }

        if (empty($selectParts)) {
            throw new \RuntimeException(
                'No valid columns were selected.'
            );
        }

        $sql .= implode(",\n", $selectParts);

        /*
        * FROM
        */
        $tableName = $firstTable['table'] ?? '';
        $tableAlias = $firstTable['alias'] ?? '';

        if (!preg_match('/^[A-Za-z0-9_]+$/', $tableName)) {
            throw new \RuntimeException('Invalid FROM table.');
        }

        $sql .= "\nFROM `{$tableName}`";

        if ($tableAlias !== '') {

            if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $tableAlias)) {
                throw new \RuntimeException('Invalid FROM alias.');
            }

            $sql .= " AS `{$tableAlias}`";
        }

        /*
        * JOINS
        */
        foreach ($data['joins'] ?? [] as $join) {

            $table = $join['table'] ?? '';
            $alias = $join['alias'] ?? '';
            $left = $join['left'] ?? '';
            $right = $join['right'] ?? '';
            $type = strtoupper($join['type'] ?? 'INNER');
            $operator = $join['operator'] ?? '=';

            if (!$table || !$alias || !$left || !$right) {
                continue;
            }

            if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
                throw new \RuntimeException('Invalid JOIN table.');
            }

            if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $alias)) {
                throw new \RuntimeException('Invalid JOIN alias.');
            }

            foreach ([$left, $right] as $joinColumn) {
                if (!preg_match(
                    '/^[A-Za-z][A-Za-z0-9_]*\.[A-Za-z0-9_]+$/',
                    $joinColumn
                )) {
                    throw new \RuntimeException('Invalid JOIN column.');
                }
            }

            if (!in_array($type, ['INNER', 'LEFT', 'RIGHT'], true)) {
                throw new \RuntimeException('Invalid JOIN type.');
            }

            if (!in_array($operator, ['=', '>', '<', '>=', '<=', '<>'], true)) {
                throw new \RuntimeException('Invalid JOIN operator.');
            }

            $sql .= "\n{$type} JOIN `{$table}` AS `{$alias}`";
            $sql .= "\n    ON {$left} {$operator} {$right}";
        }

        /*
        * WHERE
        */
        $where = [];

        foreach ($data['filters'] ?? [] as $filter) {

            $column = trim((string) ($filter['column'] ?? ''));
            $operator = strtoupper(trim((string) ($filter['operator'] ?? '')));

            if ($column === '' || $operator === '') {
                continue;
            }

            if (!preg_match(
                '/^[A-Za-z][A-Za-z0-9_]*\.[A-Za-z0-9_]+$/',
                $column
            )) {
                throw new \RuntimeException(
                    "Invalid filter column: {$column}"
                );
            }

            if (in_array($operator, ['IS NULL', 'IS NOT NULL'], true)) {
                $where[] = "{$column} {$operator}";
                continue;
            }

            $allowedOperators = [
                '=', '!=', '<>', '>', '<', '>=', '<=',
                'LIKE', 'NOT LIKE', 'IN', 'NOT IN',
            ];

            if (!in_array($operator, $allowedOperators, true)) {
                throw new \RuntimeException(
                    "Unsupported filter operator: {$operator}"
                );
            }

            $value = $filter['value'] ?? null;

            if ($operator === 'IN' || $operator === 'NOT IN') {

                $values = is_array($value) ? $value : [$value];

                if (empty($values)) {
                    continue;
                }

                $quotedValues = array_map(
                    fn ($item) => $this->quoteValue($item),
                    $values
                );

                $where[] = $column . ' ' . $operator .
                    ' (' . implode(', ', $quotedValues) . ')';

                continue;
            }

            if (!array_key_exists('value', $filter)) {
                continue;
            }

            $where[] = $column . ' ' . $operator . ' ' .
                $this->quoteValue($value);
        }

        if ($where) {
            $sql .= "\nWHERE\n    " . implode("\n    AND ", $where);
        }

        /*
        * GROUP BY
        */
        $groups = array_filter($data['group_by'] ?? []);

        if ($groups) {

            // foreach ($groups as $group) {
            //     if (!preg_match(
            //         '/^[A-Za-z][A-Za-z0-9_]*\.[A-Za-z0-9_]+$/',
            //         $group
            //     )) {
                    
            //         throw new \RuntimeException(
                        
            //             "Invalid GROUP BY column: {$group}"
            //         );
            //     }
            // }

            $sql .= "\nGROUP BY\n    " . implode(",\n    ", $groups);
        }

        /*
        * ORDER BY
        */
        $orders = [];

        foreach ($data['order_by'] ?? [] as $order) {

            $column = trim((string) ($order['column'] ?? ''));

            if ($column === '') {
                continue;
            }

            if (!preg_match(
                '/^[A-Za-z][A-Za-z0-9_]*\.[A-Za-z0-9_]+$/',
                $column
            )) {
                throw new \RuntimeException(
                    "Invalid ORDER BY column: {$column}"
                );
            }

            $direction = strtoupper($order['direction'] ?? 'ASC');

            if (!in_array($direction, ['ASC', 'DESC'], true)) {
                $direction = 'ASC';
            }

            $orders[] = "{$column} {$direction}";
        }

        if ($orders) {
            $sql .= "\nORDER BY\n    " . implode(",\n    ", $orders);
        }

        

        return $sql . ';';
    }
    /*
    |--------------------------------------------------------------------------
    | Load Dynamic Report Filter Options
    |--------------------------------------------------------------------------
    */

    public function filterOptions(
        Request $request,
        ReportQuery $query
    ) {
        $request->validate([
            'column' => [
                'required',
                'string',
                'regex:/^[A-Za-z][A-Za-z0-9_]*\.[A-Za-z0-9_]+$/',
            ],
        ]);

        $column = $request->input('column');
        $filters = $query->filters_json ?? [];

        $definition = null;

        foreach ($filters as $filter) {
            if (
                !empty($filter['runtime']) &&
                ($filter['column'] ?? '') === $column
            ) {
                $definition = $filter;
                break;
            }
        }

        if (!$definition) {
            return response()->json([
                'message' => 'This column is not a configured report filter.',
            ], 422);
        }

        if (!in_array(
            $definition['type'] ?? '',
            ['select', 'multi_select'],
            true
        )) {
            return response()->json([
                'message' => 'This filter does not use dropdown options.',
            ], 422);
        }

        /*
        * Use explicitly configured options when provided.
        */
        $configuredOptions = $definition['options'] ?? [];

        if (!empty($configuredOptions)) {
            $options = collect($configuredOptions)
                ->filter(fn ($value) => is_scalar($value))
                ->map(fn ($value) => (string) $value)
                ->unique()
                ->values();

            return response()->json([
                'options' => $options->map(fn ($value) => [
                    'value' => $value,
                    'label' => $value,
                ]),
            ]);
        }

        /*
        * Otherwise, load options from the saved query's FROM table.
        */
        $tables = $query->tables_json ?? [];
        $firstTable = $tables[0] ?? null;

        if (!$firstTable || empty($firstTable['table'])) {
            return response()->json([
                'options' => [],
            ]);
        }

        $tableName = $firstTable['table'];
        $tableAlias = $firstTable['alias'] ?? '';

        if (!preg_match('/^[A-Za-z0-9_]+$/', $tableName)) {
            return response()->json([
                'message' => 'Invalid source table.',
            ], 422);
        }

        $from = $tableName;

        if ($tableAlias !== '') {
            if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $tableAlias)) {
                return response()->json([
                    'message' => 'Invalid source table alias.',
                ], 422);
            }

            $from .= ' as ' . $tableAlias;
        }

        $builder = DB::table($from);

        /*
        * Rebuild the saved joins.
        */
        foreach ($query->joins_json ?? [] as $join) {
            $table = $join['table'] ?? '';
            $alias = $join['alias'] ?? '';
            $left = $join['left'] ?? '';
            $right = $join['right'] ?? '';
            $type = strtoupper($join['type'] ?? 'INNER');
            $operator = $join['operator'] ?? '=';

            if (!$table || !$alias || !$left || !$right) {
                continue;
            }

            if (
                !preg_match('/^[A-Za-z0-9_]+$/', $table) ||
                !preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $alias) ||
                !preg_match('/^[A-Za-z][A-Za-z0-9_]*\.[A-Za-z0-9_]+$/', $left) ||
                !preg_match('/^[A-Za-z][A-Za-z0-9_]*\.[A-Za-z0-9_]+$/', $right) ||
                !in_array($type, ['INNER', 'LEFT', 'RIGHT'], true) ||
                !in_array($operator, ['=', '>', '<', '>=', '<=', '<>'], true)
            ) {
                continue;
            }

            $joinTable = $table . ' as ' . $alias;

            if ($type === 'LEFT') {
                $builder->leftJoin($joinTable, $left, $operator, $right);
            } elseif ($type === 'RIGHT') {
                $builder->rightJoin($joinTable, $left, $operator, $right);
            } else {
                $builder->join($joinTable, $left, $operator, $right);
            }
        }

        /*
        * Apply the query's fixed filters when calculating options.
        */
        foreach ($filters as $filter) {
            if (
                !empty($filter['runtime']) ||
                empty($filter['column']) ||
                empty($filter['operator'])
            ) {
                continue;
            }

            $fixedColumn = $filter['column'];
            $operator = strtoupper($filter['operator']);
            $value = $filter['value'] ?? null;

            if (!preg_match(
                '/^[A-Za-z][A-Za-z0-9_]*\.[A-Za-z0-9_]+$/',
                $fixedColumn
            )) {
                continue;
            }

            if ($operator === 'IS NULL') {
                $builder->whereNull($fixedColumn);
            } elseif ($operator === 'IS NOT NULL') {
                $builder->whereNotNull($fixedColumn);
            } elseif (in_array($operator, ['IN', 'NOT IN'], true)) {
                $values = is_array($value)
                    ? $value
                    : preg_split('/[\r\n,]+/', (string) $value);

                $values = array_values(array_filter(
                    array_map(fn ($v) => trim((string) $v), $values),
                    fn ($v) => $v !== ''
                ));

                if ($values) {
                    if ($operator === 'IN') {
                        $builder->whereIn($fixedColumn, $values);
                    } else {
                        $builder->whereNotIn($fixedColumn, $values);
                    }
                }
            } elseif (in_array($operator, [
                '=', '!=', '<>', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE',
            ], true)) {
                $builder->where($fixedColumn, $operator, $value);
            }
        }

        $options = $builder
            ->whereNotNull($column)
            ->selectRaw("{$column} AS filter_value")
            ->distinct()
            ->orderBy('filter_value')
            ->limit(300)
            ->pluck('filter_value')
            ->map(fn ($value) => (string) $value)
            ->values();

        return response()->json([
            'options' => $options->map(fn ($value) => [
                'value' => $value,
                'label' => $value,
            ]),
        ]);
    }
    private function quoteValue($value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        return DB::connection()
            ->getPdo()
            ->quote((string) $value);
    }

    /**
     * Appends runtime filter conditions into the SQL CTE query block.
     *
     * @param string $sql
     * @param array $filters
     * @return string
     */
    protected function applyRuntimeFiltersToSql34324(string $sql, array $filters): string
    {
        $conditions = [];

        foreach ($filters as $filter) {
            $column = $filter['column'] ?? null;
            $type = $filter['type'] ?? 'text';
            $value = $filter['value'] ?? null;

            if (!$column || $value === null || $value === '') {
                continue;
            }

            // Quote database columns wrapped with table aliases properly (e.g. ib.DIV_CODE -> `ib`.`DIV_CODE`)
            $formattedColumn = implode('.', array_map(function ($part) {
                return '`' . trim($part, '`') . '`';
            }, explode('.', $column)));

            // 1. MULTI SELECT (IN CLAUSE)
            if ($type === 'multi_select' || is_array($value)) {

                
                $flatValues = \Illuminate\Support\Arr::flatten((array)$value);
                $cleanValues = array_filter(array_map(function ($v) {
                    return is_scalar($v) && trim((string) $v) !== '' ? "'" . addslashes(trim((string) $v)) . "'" : null;
                }, $flatValues));

                if (!empty($cleanValues)) {$conditions[] = "{$formattedColumn} IN (" . implode(', ', $cleanValues) . ")";
                }
            }
            // 2. SEARCH / TEXT (LIKE CLAUSE)
            elseif ($type === 'search' ||$type === 'text') {
                if (is_scalar($value) && trim((string) $value) !== '') {$escaped = addslashes(trim((string) $value));$conditions[] = "{$formattedColumn} LIKE '%{$escaped}%'";
                }
            }
            // 3. SINGLE SELECT (EQUAL CLAUSE)
            elseif ($type === 'select') {
                if (is_scalar($value) && trim((string) $value) !== '') {$escaped = addslashes(trim((string) $value));$conditions[] = "{$formattedColumn} = '{$escaped}'";
                }
            }
            // 4. DATE RANGE
            elseif ($type === 'date_range' && is_array($value)) {
                if (!empty($value['from'])) {$from = addslashes($value['from']);$conditions[] = "{$formattedColumn} >= '{$from}'";
                }
                if (!empty($value['to'])) {$to = addslashes($value['to']);$conditions[] = "{$formattedColumn} <= '{$to}'";
                }
            }
            // 5. NUMBER RANGE
            elseif ($type === 'number_range' && is_array($value)) {
                if (isset($value['min']) && is_numeric($value['min'])) {$conditions[] = "{$formattedColumn} >= " . (float) $value['min'];
                }
                if (isset($value['max']) && is_numeric($value['max'])) {$conditions[] = "{$formattedColumn} <= " . (float) $value['max'];
                }
            }
        }

        if (empty($conditions)) {
            return $sql;
        }

        $extraWhere = implode("\n              AND ", $conditions);

        // If query contains GROUP BY inside base CTE, insert conditions directly above it
        if (preg_match('/GROUP\s+BY/i', $sql)) {
            return preg_replace(
                '/(GROUP\s+BY)/i',
                "AND {$extraWhere}\n          $1",
                $sql,
                1
            );
        }

        // Fallback: Append WHERE / AND to bottom of query if no GROUP BY found
        if (stripos($sql, 'WHERE') !== false) {
            return preg_replace('/WHERE/i', "WHERE {$extraWhere} AND", $sql, 1);
        }

        return $sql . "\nWHERE " . $extraWhere;
    }


    /**
 * Dynamic SQL filter replacer/injector for CTE base queries.
 *
 * @param string $sql
 * @param array $filters
 * @return string
 */
protected function applyRuntimeFiltersToSql2222(string $sql, array$filters): string
{
    foreach ($filters as$filter) {
        $column =$filter['column'] ?? null;
        $type   =$filter['type'] ?? 'text';
        $value  =$filter['value'] ?? null;

        // Skip empty or unselected filter inputs
        if (!$column || $value === null || $value === '' || (is_array($value) && empty(array_filter($value)))) {
            continue;
        }

        // 1. Format raw column name (e.g., 'ib.DIV_CODE' or 'DIV_CODE')
        $colParts = explode('.',$column);
        $rawColName = end($colParts); // 'DIV_CODE'

        // 2. Build the new SQL expression based on filter type
        $newCondition = null;

        if ($type === 'multi_select' ||  is_array($value)) {
            $flatValues = \Illuminate\Support\Arr::flatten((array)$value);
            $cleanValues = array_filter(array_map(function ($v) {
                return is_scalar($v) && trim((string) $v) !== '' ? "'" . addslashes(trim((string) $v)) . "'" : null;
            }, $flatValues));

            if (!empty($cleanValues)) {$newCondition = "`{$column}` IN (" . implode(', ', $cleanValues) . ")";
            }
        } elseif ($type === 'search' || $type === 'text') {
            if (is_scalar($value) && trim((string) $value) !== '') {$escaped = addslashes(trim((string) $value));$newCondition = "`{$column}` LIKE '%{$escaped}%'";
            }
        } elseif ($type === 'select') {
            if (is_scalar($value) && trim((string) $value) !== '') {$escaped = addslashes(trim((string) $value));$newCondition = "`{$column}` = '{$escaped}'";
            }
        }

        if (!$newCondition) {
            continue;
        }

        // 3. Regex pattern to check if this column already exists in an existing WHERE condition
        // Matches e.g.: `ib`.`DIV_CODE` IN (...) OR ib.DIV_CODE IN (...)
        $existingPattern = '/(?:`?[a-zA-Z0-9_]+`?\.)?`?' . preg_quote($rawColName, '/') . '`?\s+(?:IN\s*\([^)]+\)|=\s*\'[^\']*\')/i';

        if (preg_match($existingPattern,$sql)) {
            // Replace old column condition with the new runtime condition
            $sql = preg_replace($existingPattern, $newCondition,$sql, 1);
        } else {
            // If condition doesn't exist, inject into the base WHERE clause before GROUP BY
            if (preg_match('/GROUP\s+BY/i', $sql)) {
                $sql = preg_replace('/(GROUP\s+BY)/i', "AND {$newCondition}\n      $1", $sql, 1);
            } elseif (stripos($sql, 'WHERE') !== false) {$sql = preg_replace('/WHERE/i', "WHERE {$newCondition} AND", $sql, 1);
            } else {
                $sql .= "\nWHERE " . $newCondition;
            }
        }
    }

    return $sql;
}

/**
 * Dynamic SQL filter replacer/injector for CTE base queries.
 *
 * @param string $sql
 * @param array $filters
 * @return string
 */
protected function applyRuntimeFiltersToSqlMostCorrect(string $sql, array $filters): string
{
    foreach ($filters as $filter) {
        $column = $filter['column'] ?? null;
        $type   = $filter['type'] ?? 'text';
        $value  = $filter['value'] ?? null;

        if (!$column || $value === null || $value === '' || (is_array($value) && empty(array_filter($value)))) {
            continue;
        }

        // 1. Format column identifier cleanly (e.g., 'ib.DIV_CODE' -> `ib`.`DIV_CODE`)
        $colParts = explode('.', $column);
        $formattedColumn = implode('.', array_map(fn($part) => '`' . trim($part, '`') . '`', $colParts));
        $rawColName = end($colParts); // 'DIV_CODE'

        // 2. Build the runtime filter clause
        $newCondition = null;

        if ($type === 'multi_select' || is_array($value)) {
            $flatValues = \Illuminate\Support\Arr::flatten((array)$value);
            $cleanValues = array_filter(array_map(function ($v) {
                return is_scalar($v) && trim((string) $v) !== '' ? "'" . addslashes(trim((string) $v)) . "'" : null;
            }, $flatValues));

            if (!empty($cleanValues)) {$newCondition = "{$formattedColumn} IN (" . implode(', ', $cleanValues) . ")";
            }
        } elseif ($type === 'search' ||$type === 'text') {
            if (is_scalar($value) && trim((string) $value) !== '') {$escaped = addslashes(trim((string) $value));$newCondition = "{$formattedColumn} LIKE '%{$escaped}%'";
            }
        } elseif ($type === 'select') {
            if (is_scalar($value) && trim((string) $value) !== '') {$escaped = addslashes(trim((string) $value));$newCondition = "{$formattedColumn} = '{$escaped}'";
            }
        }

        if (!$newCondition) {
            continue;
        }

        // 3. Match existing condition pattern to replace old filters
        $existingPattern = '/(?:`?[a-zA-Z0-9_]+`?\.)?`?' . preg_quote($rawColName, '/') . '`?\s+(?:IN\s*\([^)]+\)|=\s*\'[^\']*\')/i';

        if (preg_match($existingPattern,$sql)) {
            $sql = preg_replace($existingPattern, $newCondition,$sql, 1);
        } else {
            if (preg_match('/GROUP\s+BY/i', $sql)) {
                $sql = preg_replace('/(GROUP\s+BY)/i', "AND {$newCondition}\n      $1", $sql, 1);
            } elseif (stripos($sql, 'WHERE') !== false) {$sql = preg_replace('/WHERE/i', "WHERE {$newCondition} AND", $sql, 1);
            } else {
                $sql .= "\nWHERE " . $newCondition;
            }
        }
    }

    return $sql;
}

/**
 * Dynamic SQL filter replacer and SELECT pivot column sanitizer.
 *
 * @param string $sql
 * @param array $filters
 * @return string
 */
protected function applyRuntimeFiltersToSql(string $sql, array $filters): string
{
    foreach ($filters as $filter) {
        $column = $filter['column'] ?? null;
        $type   = $filter['type'] ?? 'text';
        $value  = $filter['value'] ?? null;

        if (!$column || $value === null || $value === '' || (is_array($value) && empty(array_filter($value)))) {
            continue;
        }

        // 1. Format column names
        $colParts = explode('.', $column);
        $formattedColumn = implode('.', array_map(fn($part) => '`' . trim($part, '`') . '`', $colParts));
        $rawColName = end($colParts); // e.g. 'DIV_CODE'

        // 2. Build runtime condition
        $newCondition = null;
        $selectedValues = [];

        if ($type === 'multi_select' || is_array($value)) {
            $selectedValues = array_values(array_filter(array_map(function ($v) {
                return is_scalar($v) && trim((string)$v) !== '' ? trim((string) $v) : null;             }, \Illuminate\Support\Arr::flatten((array)$value))));

            if (!empty($selectedValues)) {$quotedValues = array_map(fn($v) => "'" . addslashes($v) . "'", $selectedValues);$newCondition = "{$formattedColumn} IN (" . implode(', ', $quotedValues) . ")";
            }
        } elseif ($type === 'search' ||$type === 'text') {
            if (is_scalar($value) && trim((string) $value) !== '') {$escaped = addslashes(trim((string) $value));$newCondition = "{$formattedColumn} LIKE '%{$escaped}%'";
            }
        } elseif ($type === 'select') {
            if (is_scalar($value) && trim((string)$value) !== '') {
                $escaped = addslashes(trim((string)$value));
                $selectedValues[] =$escaped;
                $newCondition = "{$formattedColumn} = '{$escaped}'";
            }
        }

        if (!$newCondition) {
            continue;
        }

        // 3. Inject or Replace condition inside CTE WHERE clause
        $existingPattern = '/(?:`?[a-zA-Z0-9_]+`?\.)?`?' . preg_quote($rawColName, '/') . '`?\s+(?:IN\s*\([^)]+\)|=\s*\'[^\']*\')/i';

        if (preg_match($existingPattern,$sql)) {
            $sql = preg_replace($existingPattern, $newCondition,$sql, 1);
        } else {
            if (preg_match('/GROUP\s+BY/i', $sql)) {
                $sql = preg_replace('/(GROUP\s+BY)/i', "AND {$newCondition}\n      $1", $sql, 1);
            } elseif (stripos($sql, 'WHERE') !== false) {$sql = preg_replace('/WHERE/i', "WHERE {$newCondition} AND", $sql, 1);
            } else {
                $sql .= "\nWHERE " . $newCondition;
            }
        }

        // 4. Clean up outer SELECT list for filtered values (e.g., DIV_CODE)
        if (!empty($selectedValues) && strtoupper($rawColName) === 'DIV_CODE') {
            $sql =$this->filterOuterSelectColumns($sql,$selectedValues, 'DIV_CODE');
        }
    }

    return $sql;
}

/**
 * Removes SUM(CASE WHEN ... DIV_CODE = 'X' ...) lines for unselected values.
 *
 * @param string $sql
 * @param array $allowedValues
 * @param string $columnName
 * @return string
 */
protected function filterOuterSelectColumns(string $sql, array $allowedValues, string$columnName): string
{
    // Match line pattern: SUM( CASE WHEN ... `DIV_CODE` = 'VALUE' ... ) AS `...`,
    $lines = explode("\n", $sql);$filteredLines = [];

    foreach ($lines as$line) {
        // Match conditional SUM columns containing the filter column (e.g., DIV_CODE = 'AH')
        if (preg_match('/SUM\s*\(\s*CASE\s+WHEN.*?`?' . preg_quote($columnName, '/') . '`?\s*=\s*\'([^\']+)\'/i', $line,$matches)) {
            $valueInSql =$matches[1];

            // Drop line if division value is not in selected list
            if (!in_array($valueInSql,$allowedValues, true)) {
                continue;
            }
        }

        $filteredLines[] =$line;
    }

    $rebuiltSql = implode("\n", $filteredLines);

    // Fix lingering trailing commas before FROM base
    return preg_replace('/,\s*(\r?\n\s*FROM\s+base)/i', '$1',$rebuiltSql);
}
}