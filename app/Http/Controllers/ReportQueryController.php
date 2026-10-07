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
}