<?php

namespace App\Services;

use Carbon\Carbon;
use InvalidArgumentException;

class SqlQueryGenerator-1
{
    /**
     * Generate SQL from ReportQuery configuration.
     */
    public function generate(array $definition): string
    {
        $this->validateDefinition($definition);

        /*
        |--------------------------------------------------------------------------
        | CUSTOM SQL
        |--------------------------------------------------------------------------
        */

        if (($definition['query_mode'] ?? 'builder') === 'custom') {
            return $this->validateCustomSql(
                $definition['custom_sql'] ?? ''
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PIVOT / CTE QUERY
        |--------------------------------------------------------------------------
        */

        $pivot = $definition['pivot_json'] ?? null;
        $cte = $definition['cte_json'] ?? null;

        if (!empty($pivot['enabled'])) {
            return $this->generatePivotQuery($definition);
        }

        if (!empty($cte['enabled'])) {
            return $this->generateCteQuery($definition);
        }

        /*
        |--------------------------------------------------------------------------
        | NORMAL QUERY
        |--------------------------------------------------------------------------
        */

        return $this->generateNormalQuery($definition);
    }


    /**
     * Generate standard SELECT query.
     */
    protected function generateNormalQuery(array $definition): string
    {
        $sql = [];

        /*
        |--------------------------------------------------------------------------
        | SELECT
        |--------------------------------------------------------------------------
        */

        $distinct = !empty($definition['distinct_flag'])
            ? 'DISTINCT '
            : '';

        $columns = $this->buildColumns(
            $definition['columns_json'] ?? [],
            $definition['calculated_columns_json'] ?? []
        );

        if (empty($columns)) {
            $columns = ['*'];
        }

        $sql[] = 'SELECT ' . $distinct . implode(",\n       ", $columns);


        /*
        |--------------------------------------------------------------------------
        | FROM
        |--------------------------------------------------------------------------
        */

        $sql[] = 'FROM ' . $this->buildTables(
            $definition['tables_json'] ?? []
        );


        /*
        |--------------------------------------------------------------------------
        | JOINS
        |--------------------------------------------------------------------------
        */

        $joins = $this->buildJoins(
            $definition['joins_json'] ?? []
        );

        if ($joins !== '') {
            $sql[] = $joins;
        }


        /*
        |--------------------------------------------------------------------------
        | WHERE
        |--------------------------------------------------------------------------
        */

        $where = $this->buildWhere(
            $definition['filters_json'] ?? []
        );

        if ($where !== '') {
            $sql[] = 'WHERE ' . $where;
        }


        /*
        |--------------------------------------------------------------------------
        | GROUP BY
        |--------------------------------------------------------------------------
        */

        $groupBy = $this->buildGroupBy(
            $definition['group_by_json'] ?? []
        );

        if ($groupBy !== '') {
            $sql[] = 'GROUP BY ' . $groupBy;
        }


        /*
        |--------------------------------------------------------------------------
        | HAVING
        |--------------------------------------------------------------------------
        */

        $having = $this->buildHaving(
            $definition['having_json'] ?? []
        );

        if ($having !== '') {
            $sql[] = 'HAVING ' . $having;
        }


        /*
        |--------------------------------------------------------------------------
        | ORDER BY
        |--------------------------------------------------------------------------
        */

        $orderBy = $this->buildOrderBy(
            $definition['order_by_json'] ?? []
        );

        if ($orderBy !== '') {
            $sql[] = 'ORDER BY ' . $orderBy;
        }


        /*
        |--------------------------------------------------------------------------
        | LIMIT
        |--------------------------------------------------------------------------
        */

        $limit = $this->getLimit(
            $definition['limit_value'] ?? 500
        );

        if ($limit !== null) {
            $sql[] = 'LIMIT ' . $limit;
        }

        return implode("\n", $sql);
    }


    /**
     * Generate:
     *
     * WITH base AS (...)
     * SELECT ...
     * FROM base
     * ...
     */
    protected function generateCteQuery(array $definition): string
    {
        $cte = $definition['cte_json'] ?? [];

        $cteName = $cte['name'] ?? 'base';

        $this->validateIdentifier($cteName);

        /*
         * Build the existing builder query as the CTE.
         *
         * We deliberately disable:
         * - CTE
         * - pivot
         * - final ordering
         * - final limit
         *
         * because those belong to the outer query.
         */
        $baseDefinition = $definition;

        $baseDefinition['cte_json'] = null;
        $baseDefinition['pivot_json'] = null;
        $baseDefinition['order_by_json'] = [];
        $baseDefinition['limit_value'] = null;

        $baseSql = $this->generateNormalQuery($baseDefinition);

        return "WITH {$cteName} AS (\n"
            . $this->indentSql($baseSql)
            . "\n)\n"
            . $this->generateOuterQueryFromCte($definition, $cteName);
    }


    /**
     * Generate pivot/cross-tab query.
     *
     * Example:
     *
     * WITH base AS (...)
     *
     * SELECT
     *     ITEM_CODE,
     *     ITEM_NAME,
     *     SUM(CASE WHEN YM='2026-04'
     *              AND DIV_CODE='AG'
     *              THEN QTY ELSE 0 END) AS APR_AG
     *
     * FROM base
     *
     * GROUP BY ITEM_CODE, ITEM_NAME
     */
    protected function generatePivotQuery(array $definition): string
    {
        $pivot = $definition['pivot_json'] ?? [];

        if (empty($pivot['enabled'])) {
            return $this->generateNormalQuery($definition);
        }

        /*
        |--------------------------------------------------------------------------
        | Base source
        |--------------------------------------------------------------------------
        */

        $cte = $definition['cte_json'] ?? [];

        if (!empty($cte['enabled'])) {
            $cteName = $cte['name'] ?? 'base';
        } else {
            $cteName = 'base';
        }

        $this->validateIdentifier($cteName);

        /*
        |--------------------------------------------------------------------------
        | Build CTE body
        |--------------------------------------------------------------------------
        */

        $baseDefinition = $definition;

        $baseDefinition['cte_json'] = null;
        $baseDefinition['pivot_json'] = null;

        /*
         * The order/limit of the base query should not affect
         * the pivot.
         */
        $baseDefinition['order_by_json'] = [];
        $baseDefinition['limit_value'] = null;

        $baseSql = $this->generateNormalQuery($baseDefinition);


        /*
        |--------------------------------------------------------------------------
        | Pivot rows
        |--------------------------------------------------------------------------
        */

        $rows = $pivot['rows'] ?? [];

        if (empty($rows)) {
            throw new InvalidArgumentException(
                'Pivot requires at least one row field.'
            );
        }

        foreach ($rows as $row) {
            $this->validateIdentifier($row);
        }


        /*
        |--------------------------------------------------------------------------
        | Pivot column
        |--------------------------------------------------------------------------
        */

        $pivotColumn = $pivot['column'] ?? null;

        if (!$pivotColumn) {
            throw new InvalidArgumentException(
                'Pivot column is required.'
            );
        }

        $this->validateIdentifier($pivotColumn);


        /*
        |--------------------------------------------------------------------------
        | Pivot value
        |--------------------------------------------------------------------------
        */

        $valueColumn = $pivot['value'] ?? null;

        if (!$valueColumn) {
            throw new InvalidArgumentException(
                'Pivot value is required.'
            );
        }

        $this->validateIdentifier($valueColumn);


        /*
        |--------------------------------------------------------------------------
        | Aggregate
        |--------------------------------------------------------------------------
        */

        $aggregate = strtoupper(
            $pivot['aggregate'] ?? 'SUM'
        );

        $allowedAggregates = [
            'SUM',
            'COUNT',
            'AVG',
            'MIN',
            'MAX',
        ];

        if (!in_array($aggregate, $allowedAggregates, true)) {
            throw new InvalidArgumentException(
                "Invalid pivot aggregate: {$aggregate}"
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SELECT rows
        |--------------------------------------------------------------------------
        */

        $select = [];

        foreach ($rows as $row) {
            $select[] = $row;
        }


        /*
        |--------------------------------------------------------------------------
        | Pivot values
        |--------------------------------------------------------------------------
        */

        $pivotValues = $pivot['values'] ?? [];

        /*
         * If values aren't explicitly supplied, use:
         *
         * values: [
         *     ['value' => '2026-04', 'label' => 'APR']
         * ]
         */

        if (empty($pivotValues)) {
            throw new InvalidArgumentException(
                'Pivot requires at least one pivot value.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Pivot dimensions
        |--------------------------------------------------------------------------
        */

        $dimensions = $pivot['dimensions'] ?? [];

        /*
         * Convert:
         *
         * [
         *     [
         *         'column' => 'DIV_CODE',
         *         'values' => ['AG','AH']
         *     ]
         * ]
         *
         * into combinations.
         */

        $dimensionCombinations = $this->buildDimensionCombinations(
            $dimensions
        );


        /*
        |--------------------------------------------------------------------------
        | Generate pivot columns
        |--------------------------------------------------------------------------
        */

        $pivotExpressions = [];

        foreach ($pivotValues as $pivotValue) {

            if (is_array($pivotValue)) {
                $value = $pivotValue['value'] ?? '';
                $label = $pivotValue['label'] ?? $value;
            } else {
                $value = $pivotValue;
                $label = $pivotValue;
            }

            foreach ($dimensionCombinations as $combination) {

                $conditions = [];

                $conditions[] =
                    $pivotColumn . ' = ' . $this->quoteValue($value);

                foreach ($combination as $dimension) {

                    $conditions[] =
                        $dimension['column']
                        . ' = '
                        . $this->quoteValue($dimension['value']);
                }

                $aliasParts = [
                    $label
                ];

                foreach ($combination as $dimension) {
                    $aliasParts[] = $dimension['value'];
                }

                $alias = $this->makeAlias(
                    implode('_', $aliasParts)
                );

                $expression =
                    "{$aggregate}(\n"
                    . "        CASE WHEN "
                    . implode(' AND ', $conditions)
                    . "\n"
                    . "             THEN {$valueColumn}\n"
                    . "             ELSE 0\n"
                    . "        END\n"
                    . "    ) AS {$alias}";

                $pivotExpressions[] = $expression;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Build SELECT
        |--------------------------------------------------------------------------
        */

        $selectSql = [];

        foreach ($select as $column) {
            $selectSql[] = $column;
        }

        foreach ($pivotExpressions as $expression) {
            $selectSql[] = $expression;
        }


        /*
        |--------------------------------------------------------------------------
        | Final query
        |--------------------------------------------------------------------------
        */

        $sql = [];

        $sql[] = "WITH {$cteName} AS (";
        $sql[] = $this->indentSql($baseSql);
        $sql[] = ')';

        $distinct = !empty($definition['distinct_flag'])
            ? 'DISTINCT '
            : '';

        $sql[] =
            'SELECT '
            . $distinct
            . implode(",\n       ", $selectSql);

        $sql[] = "FROM {$cteName}";

        /*
        |--------------------------------------------------------------------------
        | GROUP BY
        |--------------------------------------------------------------------------
        */

        $sql[] = 'GROUP BY ' . implode(', ', $rows);


        /*
        |--------------------------------------------------------------------------
        | ORDER BY
        |--------------------------------------------------------------------------
        */

        $orderBy = $this->buildPivotOrderBy(
            $definition['order_by_json'] ?? [],
            $rows
        );

        if ($orderBy !== '') {
            $sql[] = 'ORDER BY ' . $orderBy;
        }


        /*
        |--------------------------------------------------------------------------
        | LIMIT
        |--------------------------------------------------------------------------
        */

        $limit = $this->getLimit(
            $definition['limit_value'] ?? 500
        );

        if ($limit !== null) {
            $sql[] = 'LIMIT ' . $limit;
        }

        return implode("\n", $sql);
    }


    /**
     * Generate outer query for a normal CTE without pivot.
     */
    protected function generateOuterQueryFromCte(
        array $definition,
        string $cteName
    ): string {

        $columns = $this->buildColumns(
            $definition['columns_json'] ?? [],
            $definition['calculated_columns_json'] ?? []
        );

        if (empty($columns)) {
            $columns = ['*'];
        }

        $sql = [];

        $distinct = !empty($definition['distinct_flag'])
            ? 'DISTINCT '
            : '';

        $sql[] =
            'SELECT '
            . $distinct
            . implode(",\n       ", $columns);

        $sql[] = "FROM {$cteName}";


        $where = $this->buildWhere(
            $definition['filters_json'] ?? []
        );

        /*
         * Usually filters are already inside the CTE.
         * Therefore don't repeat them in the outer query.
         */

        $outerGroupBy = $definition['outer_group_by_json'] ?? [];

        if (!empty($outerGroupBy)) {
            $sql[] = 'GROUP BY '
                . $this->buildGroupBy($outerGroupBy);
        }

        $having = $this->buildHaving(
            $definition['having_json'] ?? []
        );

        if ($having !== '') {
            $sql[] = 'HAVING ' . $having;
        }

        $orderBy = $this->buildOrderBy(
            $definition['order_by_json'] ?? []
        );

        if ($orderBy !== '') {
            $sql[] = 'ORDER BY ' . $orderBy;
        }

        $limit = $this->getLimit(
            $definition['limit_value'] ?? 500
        );

        if ($limit !== null) {
            $sql[] = 'LIMIT ' . $limit;
        }

        return implode("\n", $sql);
    }


    /*
    |--------------------------------------------------------------------------
    | COLUMNS
    |--------------------------------------------------------------------------
    */

    protected function buildColumns(
        array $columns,
        array $calculatedColumns
    ): array {

        $result = [];

        foreach ($columns as $column) {

            if (is_string($column)) {
                $result[] = $column;
                continue;
            }

            $expression = $column['expression'] ?? null;

            if (!$expression) {
                continue;
            }

            $alias = $column['alias'] ?? null;

            if ($alias) {
                $this->validateIdentifier($alias);

                $result[] =
                    $expression . ' AS ' . $alias;
            } else {
                $result[] = $expression;
            }
        }


        foreach ($calculatedColumns as $column) {

            $expression = $column['expression'] ?? null;

            if (!$expression) {
                continue;
            }

            $alias = $column['alias'] ?? null;

            if (!$alias) {
                throw new InvalidArgumentException(
                    'Calculated column requires an alias.'
                );
            }

            $this->validateIdentifier($alias);

            $result[] =
                $expression . ' AS ' . $alias;
        }

        return $result;
    }


    /*
    |--------------------------------------------------------------------------
    | TABLES
    |--------------------------------------------------------------------------
    */

    protected function buildTables(array $tables): string
    {
        $result = [];

        foreach ($tables as $table) {

            if (is_string($table)) {
                $this->validateTableExpression($table);

                $result[] = $table;
                continue;
            }

            $name = $table['table'] ?? null;

            if (!$name) {
                continue;
            }

            $this->validateIdentifier($name);

            $alias = $table['alias'] ?? null;

            if ($alias) {
                $this->validateIdentifier($alias);

                $result[] =
                    $name . ' AS ' . $alias;
            } else {
                $result[] = $name;
            }
        }

        if (empty($result)) {
            throw new InvalidArgumentException(
                'At least one table is required.'
            );
        }

        return implode(', ', $result);
    }


    /*
    |--------------------------------------------------------------------------
    | JOINS
    |--------------------------------------------------------------------------
    */

    protected function buildJoins(array $joins): string
    {
        $result = [];

        foreach ($joins as $join) {

            $type = strtoupper(
                $join['type'] ?? 'INNER'
            );

            $allowed = [
                'INNER',
                'LEFT',
                'RIGHT',
                'FULL',
                'CROSS',
            ];

            if (!in_array($type, $allowed, true)) {
                throw new InvalidArgumentException(
                    "Invalid join type: {$type}"
                );
            }

            $table = $join['table'] ?? null;

            if (!$table) {
                continue;
            }

            $this->validateIdentifier($table);

            $alias = $join['alias'] ?? null;

            $condition = $join['condition'] ?? null;

            if ($type === 'CROSS') {

                $joinSql =
                    "CROSS JOIN {$table}";

            } else {

                if (!$condition) {
                    throw new InvalidArgumentException(
                        'Join condition is required.'
                    );
                }

                $joinSql =
                    "{$type} JOIN {$table}";

                if ($alias) {
                    $this->validateIdentifier($alias);

                    $joinSql .= " AS {$alias}";
                }

                $joinSql .= " ON {$condition}";
            }

            $result[] = $joinSql;
        }

        return implode("\n", $result);
    }


    /*
    |--------------------------------------------------------------------------
    | WHERE
    |--------------------------------------------------------------------------
    */

    protected function buildWhere(array $filters): string
    {
        $conditions = [];

        foreach ($filters as $filter) {

            $expression = $filter['expression'] ?? null;
            $operator = strtoupper(
                trim($filter['operator'] ?? '=')
            );

            if (!$expression) {
                continue;
            }

            $allowedOperators = [
                '=',
                '!=',
                '<>',
                '>',
                '>=',
                '<',
                '<=',
                'LIKE',
                'NOT LIKE',
                'IS NULL',
                'IS NOT NULL',
                'IN',
                'NOT IN',
                'BETWEEN',
            ];

            if (!in_array($operator, $allowedOperators, true)) {
                throw new InvalidArgumentException(
                    "Invalid filter operator: {$operator}"
                );
            }


            if ($operator === 'IS NULL') {
                $conditions[] =
                    "{$expression} IS NULL";

                continue;
            }


            if ($operator === 'IS NOT NULL') {
                $conditions[] =
                    "{$expression} IS NOT NULL";

                continue;
            }


            if ($operator === 'IN' || $operator === 'NOT IN') {

                $values = $filter['value'] ?? [];

                if (!is_array($values)) {
                    $values = [$values];
                }

                if (empty($values)) {
                    continue;
                }

                $quoted = array_map(
                    fn ($value) => $this->quoteValue($value),
                    $values
                );

                $conditions[] =
                    "{$expression} {$operator} ("
                    . implode(', ', $quoted)
                    . ')';

                continue;
            }


            if ($operator === 'BETWEEN') {

                $values = $filter['value'] ?? [];

                if (!is_array($values) || count($values) !== 2) {
                    throw new InvalidArgumentException(
                        'BETWEEN requires two values.'
                    );
                }

                $conditions[] =
                    "{$expression} BETWEEN "
                    . $this->quoteValue($values[0])
                    . ' AND '
                    . $this->quoteValue($values[1]);

                continue;
            }


            $value = $filter['value'] ?? null;

            $conditions[] =
                "{$expression} {$operator} "
                . $this->quoteValue($value);
        }

        return implode("\n  AND ", $conditions);
    }


    /*
    |--------------------------------------------------------------------------
    | GROUP BY
    |--------------------------------------------------------------------------
    */

    protected function buildGroupBy(array $groups): string
    {
        $result = [];

        foreach ($groups as $group) {

            if (is_string($group)) {
                $result[] = $group;
                continue;
            }

            if (!empty($group['expression'])) {
                $result[] = $group['expression'];
            }
        }

        return implode(', ', $result);
    }


    /*
    |--------------------------------------------------------------------------
    | HAVING
    |--------------------------------------------------------------------------
    */

    protected function buildHaving(array $filters): string
    {
        $conditions = [];

        foreach ($filters as $filter) {

            $expression = $filter['expression'] ?? null;

            if (!$expression) {
                continue;
            }

            $operator = strtoupper(
                trim($filter['operator'] ?? '=')
            );

            $value = $filter['value'] ?? null;

            if ($operator === 'IS NULL') {
                $conditions[] =
                    "{$expression} IS NULL";

                continue;
            }

            if ($operator === 'IS NOT NULL') {
                $conditions[] =
                    "{$expression} IS NOT NULL";

                continue;
            }

            $conditions[] =
                "{$expression} {$operator} "
                . $this->quoteValue($value);
        }

        return implode("\n  AND ", $conditions);
    }


    /*
    |--------------------------------------------------------------------------
    | ORDER BY
    |--------------------------------------------------------------------------
    */

    protected function buildOrderBy(array $orders): string
    {
        $result = [];

        foreach ($orders as $order) {

            if (is_string($order)) {
                $result[] = $order;
                continue;
            }

            $expression = $order['expression'] ?? null;

            if (!$expression) {
                continue;
            }

            $direction = strtoupper(
                $order['direction'] ?? 'ASC'
            );

            if (!in_array($direction, ['ASC', 'DESC'], true)) {
                $direction = 'ASC';
            }

            $result[] =
                "{$expression} {$direction}";
        }

        return implode(', ', $result);
    }


    /**
     * Special order handling for pivot queries.
     */
    protected function buildPivotOrderBy(
        array $orders,
        array $rows
    ): string {

        if (empty($orders)) {
            return '';
        }

        $result = [];

        foreach ($orders as $order) {

            if (is_string($order)) {
                $expression = $order;
                $direction = 'ASC';
            } else {
                $expression = $order['expression'] ?? null;
                $direction = strtoupper(
                    $order['direction'] ?? 'ASC'
                );
            }

            if (!$expression) {
                continue;
            }

            if (!in_array($direction, ['ASC', 'DESC'], true)) {
                $direction = 'ASC';
            }

            /*
             * If expression is already a grouped row, use it directly.
             */
            if (in_array($expression, $rows, true)) {

                $result[] =
                    "{$expression} {$direction}";

                continue;
            }

            /*
             * Otherwise use MIN().
             *
             * This makes:
             *
             * ORDER BY ITEM_CATG
             *
             * safe in a grouped pivot query even if ITEM_CATG
             * isn't one of the displayed rows.
             */
            $result[] =
                "MIN({$expression}) {$direction}";
        }

        return implode(', ', $result);
    }


    /*
    |--------------------------------------------------------------------------
    | DIMENSION COMBINATIONS
    |--------------------------------------------------------------------------
    */

    protected function buildDimensionCombinations(
        array $dimensions
    ): array {

        if (empty($dimensions)) {
            return [[]];
        }

        $combinations = [[]];

        foreach ($dimensions as $dimension) {

            $column = $dimension['column'] ?? null;
            $values = $dimension['values'] ?? [];

            if (!$column) {
                continue;
            }

            $this->validateIdentifier($column);

            $newCombinations = [];

            foreach ($combinations as $combination) {

                foreach ($values as $value) {

                    $newCombination = $combination;

                    $newCombination[] = [
                        'column' => $column,
                        'value' => $value,
                    ];

                    $newCombinations[] =
                        $newCombination;
                }
            }

            $combinations = $newCombinations;
        }

        return $combinations;
    }


    /*
    |--------------------------------------------------------------------------
    | LIMIT
    |--------------------------------------------------------------------------
    */

    protected function getLimit($limit): ?int
    {
        if ($limit === null || $limit === '') {
            return null;
        }

        $limit = (int) $limit;

        if ($limit <= 0) {
            return null;
        }

        /*
         * Hard safety cap.
         */
        return min($limit, 5000);
    }


    /*
    |--------------------------------------------------------------------------
    | VALUE QUOTING
    |--------------------------------------------------------------------------
    */

    protected function quoteValue($value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        /*
         * Escape single quotes for MySQL.
         */
        return "'" . str_replace(
            "'",
            "''",
            (string) $value
        ) . "'";
    }


    /*
    |--------------------------------------------------------------------------
    | ALIAS
    |--------------------------------------------------------------------------
    */

    protected function makeAlias(string $value): string
    {
        /*
         * Example:
         *
         * APR_AG
         * SEP_JH
         * 2026-04_AG -> X2026_04_AG
         */

        $value = strtoupper($value);

        $value = preg_replace(
            '/[^A-Z0-9_]+/',
            '_',
            $value
        );

        $value = trim($value, '_');

        if ($value === '') {
            $value = 'PIVOT_VALUE';
        }

        if (preg_match('/^[0-9]/', $value)) {
            $value = 'X_' . $value;
        }

        return $value;
    }


    /*
    |--------------------------------------------------------------------------
    | IDENTIFIER VALIDATION
    |--------------------------------------------------------------------------
    */

    protected function validateIdentifier(string $identifier): void
    {
        /*
         * Supports:
         *
         * table
         * alias
         *
         * but NOT arbitrary SQL.
         *
         * Expressions such as:
         *
         * ib.ITEM_CODE
         *
         * are handled separately and aren't passed here.
         */

        if (!preg_match(
            '/^[A-Za-z_][A-Za-z0-9_]*$/',
            $identifier
        )) {
            throw new InvalidArgumentException(
                "Invalid SQL identifier: {$identifier}"
            );
        }
    }


    protected function validateTableExpression(string $table): void
    {
        /*
         * Permit:
         *
         * table
         * table AS alias
         *
         * while rejecting obvious injection.
         */

        if (
            str_contains($table, ';') ||
            str_contains($table, '--') ||
            str_contains($table, '/*') ||
            str_contains($table, '*/')
        ) {
            throw new InvalidArgumentException(
                'Invalid table expression.'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CUSTOM SQL VALIDATION
    |--------------------------------------------------------------------------
    */

    protected function validateCustomSql(string $sql): string
    {
        $sql = trim($sql);

        if ($sql === '') {
            throw new InvalidArgumentException(
                'Custom SQL cannot be empty.'
            );
        }

        /*
         * Remove one optional trailing semicolon.
         */
        $sql = rtrim($sql, " \t\n\r;");

        /*
         * Only SELECT / WITH is allowed.
         */
        if (!preg_match(
            '/^(SELECT|WITH)\b/i',
            $sql
        )) {
            throw new InvalidArgumentException(
                'Only SELECT or WITH queries are allowed.'
            );
        }

        /*
         * Reject multiple statements.
         */
        if (str_contains($sql, ';')) {
            throw new InvalidArgumentException(
                'Multiple SQL statements are not allowed.'
            );
        }

        /*
         * Reject destructive / administrative statements.
         */
        $blocked = [
            'INSERT',
            'UPDATE',
            'DELETE',
            'DROP',
            'ALTER',
            'TRUNCATE',
            'CREATE',
            'RENAME',
            'GRANT',
            'REVOKE',
            'CALL',
            'SET',
            'LOAD',
            'OUTFILE',
            'DUMPFILE',
        ];

        foreach ($blocked as $keyword) {

            if (preg_match(
                '/\b' . preg_quote($keyword, '/') . '\b/i',
                $sql
            )) {
                throw new InvalidArgumentException(
                    "SQL keyword '{$keyword}' is not allowed."
                );
            }
        }

        return $sql;
    }


    /*
    |--------------------------------------------------------------------------
    | DEFINITION VALIDATION
    |--------------------------------------------------------------------------
    */

    protected function validateDefinition(array $definition): void
    {
        $mode = $definition['query_mode'] ?? 'builder';

        if (!in_array($mode, ['builder', 'custom'], true)) {
            throw new InvalidArgumentException(
                'Invalid query mode.'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | INDENT SQL
    |--------------------------------------------------------------------------
    */

    protected function indentSql(
        string $sql,
        string $indent = '    '
    ): string {

        return implode(
            "\n",
            array_map(
                fn ($line) => $indent . $line,
                explode("\n", $sql)
            )
        );
    }
}