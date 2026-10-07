<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SqlQueryGenerator
{
    /**
     * Generate SQL from the existing builder structure.
     *
     * Supports:
     * - normal SELECT
     * - calculated columns
     * - CTE
     * - pivot / cross-tab
     * - HAVING
     * - DISTINCT
     * - LIMIT
     */
    public function generate(array $data): string
    {
        if (
            !empty($data['pivot']['enabled']) &&
            !empty($data['cte']['enabled'])
        ) {
            return $this->buildCtePivotQuery($data);
        }

        if (!empty($data['cte']['enabled'])) {
            return $this->buildCteQuery($data);
        }

        return $this->buildNormalQuery($data);
    }


    /*
    |--------------------------------------------------------------------------
    | Normal Query
    |--------------------------------------------------------------------------
    */

    private function buildNormalQuery(array $data): string
    {
        $tables = $data['tables'] ?? [];

        if (empty($tables)) {
            throw new InvalidArgumentException(
                'At least one table is required.'
            );
        }

        $selectParts = [];

        if (!empty($data['distinct'])) {
            $selectPrefix = 'SELECT DISTINCT';
        } else {
            $selectPrefix = 'SELECT';
        }

        foreach ($data['columns'] ?? [] as $column) {

            if (empty($column['expression'])) {
                continue;
            }

            $expression = $this->buildColumnExpression($column);

            $selectParts[] = "    {$expression}";
        }

        /*
         * Calculated columns
         */
        foreach ($data['calculated_columns'] ?? [] as $column) {

            if (
                empty($column['expression']) ||
                empty($column['alias'])
            ) {
                continue;
            }

            $expression = $column['expression'];

            $alias = $this->quoteIdentifier(
                $column['alias']
            );

            $selectParts[] =
                "    {$expression} AS {$alias}";
        }

        if (empty($selectParts)) {
            throw new InvalidArgumentException(
                'At least one column is required.'
            );
        }

        $sql = $selectPrefix . "\n";

        $sql .= implode(",\n", $selectParts);

        /*
         * FROM
         */
        $firstTable = $tables[0];

        $sql .= "\nFROM ";

        $sql .= $this->quoteIdentifier(
            $firstTable['table']
        );

        if (!empty($firstTable['alias'])) {

            $sql .= " AS " .
                $this->validateAlias(
                    $firstTable['alias']
                );
        }

        /*
         * Joins
         */
        foreach ($data['joins'] ?? [] as $join) {

            if (
                empty($join['table']) ||
                empty($join['alias']) ||
                empty($join['left']) ||
                empty($join['right'])
            ) {
                continue;
            }

            $type = strtoupper(
                $join['type'] ?? 'INNER'
            );

            if (
                !in_array(
                    $type,
                    ['INNER', 'LEFT', 'RIGHT'],
                    true
                )
            ) {
                $type = 'INNER';
            }

            $operator =
                $join['operator'] ?? '=';

            if (
                !in_array(
                    $operator,
                    ['=', '>', '<', '>=', '<=', '<>'],
                    true
                )
            ) {
                $operator = '=';
            }

            $sql .= "\n{$type} JOIN ";

            $sql .= $this->quoteIdentifier(
                $join['table']
            );

            $sql .= " AS " .
                $this->validateAlias(
                    $join['alias']
                );

            $sql .= "\n    ON ";

            $sql .= $this->validateColumnReference(
                $join['left']
            );

            $sql .= " {$operator} ";

            $sql .= $this->validateColumnReference(
                $join['right']
            );
        }

        /*
         * WHERE
         */
        $where = $this->buildWhere(
            $data['filters'] ?? []
        );

        if (!empty($where)) {

            $sql .= "\nWHERE\n";

            $sql .= "    ";

            $sql .= implode(
                "\n    AND ",
                $where
            );
        }

        /*
         * GROUP BY
         */
        $groups = [];

        foreach ($data['group_by'] ?? [] as $group) {

            if (empty($group)) {
                continue;
            }

            $groups[] = $group;
        }

        if (!empty($groups)) {

            $sql .= "\nGROUP BY\n";

            $sql .= "    ";

            $sql .= implode(
                ",\n    ",
                $groups
            );
        }

        /*
         * HAVING
         */
        $having = $this->buildHaving(
            $data['having'] ?? []
        );

        if (!empty($having)) {

            $sql .= "\nHAVING\n";

            $sql .= "    ";

            $sql .= implode(
                "\n    AND ",
                $having
            );
        }

        /*
         * ORDER BY
         */
        $orders = $this->buildOrderBy(
            $data['order_by'] ?? []
        );

        if (!empty($orders)) {

            $sql .= "\nORDER BY\n";

            $sql .= "    ";

            $sql .= implode(
                ",\n    ",
                $orders
            );
        }

        /*
         * LIMIT
         */
        $limit = $this->getLimit(
            $data['limit'] ?? 500
        );

        if ($limit !== null) {
            $sql .= "\nLIMIT {$limit}";
        }

        $sql .= ";";

        return $sql;
    }


    /*
    |--------------------------------------------------------------------------
    | CTE Query
    |--------------------------------------------------------------------------
    */

    private function buildCteQuery(array $data): string
    {
        $cteName =
            $data['cte']['name'] ?? 'base';

        $cteName =
            $this->validateAlias($cteName);

        /*
         * Build normal query without outer
         * CTE settings.
         */
        $baseData = $data;

        $baseData['cte'] = null;
        $baseData['pivot'] = null;

        $baseSql =
            $this->buildNormalQuery($baseData);

        $baseSql =
            rtrim(
                $baseSql,
                " \t\n\r\0\x0B;"
            );

        $sql =
            "WITH {$cteName} AS (\n";

        $sql .= $this->indentSql(
            $baseSql,
            1
        );

        $sql .= "\n)\n";

        /*
         * If there is no special outer query,
         * simply select from the CTE.
         */
        $rows = $data['outer_columns'] ?? [];

        if (empty($rows)) {

            $sql .=
                "SELECT *\nFROM {$cteName}";

            $limit = $this->getLimit(
                $data['limit'] ?? 500
            );

            if ($limit !== null) {
                $sql .= "\nLIMIT {$limit}";
            }

            $sql .= ";";

            return $sql;
        }

        $select = [];

        foreach ($rows as $row) {

            $select[] =
                "    " .
                $this->validateColumnReference(
                    $row
                );
        }

        $sql .= "SELECT\n";

        $sql .= implode(
            ",\n",
            $select
        );

        $sql .= "\nFROM {$cteName}";

        $sql .= ";";

        return $sql;
    }


    /*
    |--------------------------------------------------------------------------
    | CTE + Pivot
    |--------------------------------------------------------------------------
    */

    private function buildCtePivotQuery(array $data): string
    {
        $cte =
            $data['cte'] ?? [];

        $pivot =
            $data['pivot'] ?? [];

        $cteName =
            $this->validateAlias(
                $cte['name'] ?? 'base'
            );

        /*
         * Build the inner query.
         */
        $baseData = $data;

        $baseData['cte'] = null;
        $baseData['pivot'] = null;

        $baseSql =
            $this->buildNormalQuery($baseData);

        $baseSql =
            rtrim(
                $baseSql,
                " \t\n\r\0\x0B;"
            );

        /*
         * CTE
         */
        $sql =
            "WITH {$cteName} AS (\n";

        $sql .= $this->indentSql(
            $baseSql,
            1
        );

        $sql .= "\n)\n";

        /*
         * Pivot rows
         */
        $rows =
            $pivot['rows'] ?? [];

        if (empty($rows)) {
            throw new InvalidArgumentException(
                'Pivot requires at least one row field.'
            );
        }

        $selectParts = [];

        foreach ($rows as $row) {

            if (empty($row)) {
                continue;
            }

            $selectParts[] =
                "    " .
                $this->validateIdentifier(
                    $row
                );
        }

        /*
         * Pivot expressions
         */
        $pivotColumn =
            $pivot['column'] ?? null;

        $valueColumn =
            $pivot['value'] ?? null;

        $aggregate =
            strtoupper(
                $pivot['aggregate'] ?? 'SUM'
            );

        if (
            empty($pivotColumn) ||
            empty($valueColumn)
        ) {
            throw new InvalidArgumentException(
                'Pivot requires column and value.'
            );
        }

        if (
            !in_array(
                $aggregate,
                ['SUM', 'COUNT', 'AVG', 'MIN', 'MAX'],
                true
            )
        ) {
            $aggregate = 'SUM';
        }

        $pivotValues =
            $pivot['values'] ?? [];

        /*
         * Allow dimensions such as:
         *
         * DIV_CODE:
         * AG, AH, AM...
         */
        $dimensions =
            $pivot['dimensions'] ?? [];

        if (empty($pivotValues)) {
            throw new InvalidArgumentException(
                'Pivot requires at least one pivot value.'
            );
        }

        /*
         * If no dimensions exist, generate:
         *
         * SUM(
         *   CASE
         *      WHEN YM = '2026-04'
         *      THEN QTY
         *      ELSE 0
         *   END
         * ) AS APR
         */
        if (empty($dimensions)) {

            foreach ($pivotValues as $value) {

                $alias =
                    $this->makePivotAlias(
                        $value
                    );

                $condition =
                    $this->quoteIdentifier(
                        $pivotColumn
                    );

                $valueSql =
                    $this->quoteValue($value);

                $expression =
                    "{$aggregate}(
                        CASE
                            WHEN {$condition} = {$valueSql}
                            THEN {$this->quoteIdentifier($valueColumn)}
                            ELSE 0
                        END
                    )";

                $expression =
                    $this->cleanMultiline(
                        $expression
                    );

                $selectParts[] =
                    "    {$expression} AS `{$alias}`";
            }
        }

        /*
         * With dimensions:
         *
         * YM × DIV_CODE
         */
        else {

            foreach ($pivotValues as $pivotValue) {

                $dimensionCombinations =
                    $this->buildDimensionCombinations(
                        $dimensions
                    );

                foreach (
                    $dimensionCombinations
                    as $combination
                ) {

                    $conditions = [];

                    /*
                     * Pivot column
                     */
                    $conditions[] =
                        $this->quoteIdentifier(
                            $pivotColumn
                        )
                        . " = "
                        . $this->quoteValue(
                            $pivotValue
                        );

                    /*
                     * Dimensions
                     */
                    foreach (
                        $combination
                        as $dimensionColumn => $dimensionValue
                    ) {

                        $conditions[] =
                            $this->quoteIdentifier(
                                $dimensionColumn
                            )
                            . " = "
                            . $this->quoteValue(
                                $dimensionValue
                            );
                    }

                    $conditionSql =
                        implode(
                            " AND ",
                            $conditions
                        );

                    $expression =
                        "{$aggregate}(
                            CASE
                                WHEN {$conditionSql}
                                THEN {$this->quoteIdentifier($valueColumn)}
                                ELSE 0
                            END
                        )";

                    $expression =
                        $this->cleanMultiline(
                            $expression
                        );

                    $parts =
                        [$pivotValue];

                    foreach (
                        $combination
                        as $dimensionValue
                    ) {
                        $parts[] =
                            $dimensionValue;
                    }

                    $alias =
                        $this->makePivotAlias(
                            implode(
                                '_',
                                $parts
                            )
                        );

                    $selectParts[] =
                        "    {$expression} AS `{$alias}`";
                }
            }
        }

        /*
         * Outer SELECT
         */
        $sql .= "SELECT\n";

        $sql .= implode(
            ",\n",
            $selectParts
        );

        $sql .= "\nFROM {$cteName}";

        /*
         * GROUP BY
         */
        $groupRows = [];

        foreach ($rows as $row) {

            $groupRows[] =
                $this->validateIdentifier(
                    $row
                );
        }

        if (!empty($groupRows)) {

            $sql .= "\nGROUP BY\n";

            $sql .= "    ";

            $sql .= implode(
                ",\n    ",
                $groupRows
            );
        }

        /*
         * Outer ORDER BY
         */
        $orders =
            $data['order_by'] ?? [];

        if (!empty($orders)) {

            $orderParts = [];

            foreach ($orders as $order) {

                if (empty($order['column'])) {
                    continue;
                }

                $direction =
                    strtoupper(
                        $order['direction'] ?? 'ASC'
                    );

                if (
                    !in_array(
                        $direction,
                        ['ASC', 'DESC'],
                        true
                    )
                ) {
                    $direction = 'ASC';
                }

                /*
                 * Pivot output uses plain aliases.
                 */
                $column =
                    $this->validateIdentifier(
                        $order['column']
                    );

                $orderParts[] =
                    "{$column} {$direction}";
            }

            if (!empty($orderParts)) {

                $sql .= "\nORDER BY\n";

                $sql .= "    ";

                $sql .= implode(
                    ",\n    ",
                    $orderParts
                );
            }
        }

        /*
         * Limit
         */
        $limit =
            $this->getLimit(
                $data['limit'] ?? 500
            );

        if ($limit !== null) {
            $sql .= "\nLIMIT {$limit}";
        }

        $sql .= ";";

        return $sql;
    }


    /*
    |--------------------------------------------------------------------------
    | Column Expression
    |--------------------------------------------------------------------------
    */

    private function buildColumnExpression(
        array $column
    ): string {

        $expression =
            $column['expression'];

        $function =
            strtoupper(
                $column['function'] ?? 'NONE'
            );

        if (
            !in_array(
                $function,
                [
                    'NONE',
                    'SUM',
                    'COUNT',
                    'AVG',
                    'MIN',
                    'MAX'
                ],
                true
            )
        ) {
            $function = 'NONE';
        }

        if ($function !== 'NONE') {

            if ($function === 'COUNT') {

                $expression =
                    "COUNT({$expression})";

            } else {

                $expression =
                    "{$function}({$expression})";
            }
        }

        if (!empty($column['alias'])) {

            $expression .=
                " AS " .
                $this->quoteIdentifier(
                    $column['alias']
                );
        }

        return $expression;
    }


    /*
    |--------------------------------------------------------------------------
    | WHERE
    |--------------------------------------------------------------------------
    */

    private function buildWhere(array $filters): array
    {
        $where = [];

        foreach ($filters as $filter) {

            if (
                empty($filter['column']) ||
                empty($filter['operator'])
            ) {
                continue;
            }

            $column =
                $this->validateColumnReference(
                    $filter['column']
                );

            $operator =
                strtoupper(
                    $filter['operator']
                );

            $allowed = [
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
                'NOT IN',
            ];

            if (
                !in_array(
                    $operator,
                    $allowed,
                    true
                )
            ) {
                continue;
            }

            if (
                $operator === 'IS NULL' ||
                $operator === 'IS NOT NULL'
            ) {

                $where[] =
                    "{$column} {$operator}";

                continue;
            }

            if (
                in_array(
                    $operator,
                    ['IN', 'NOT IN'],
                    true
                )
            ) {

                $values =
                    $filter['value'] ?? [];

                if (!is_array($values)) {
                    $values =
                        array_map(
                            'trim',
                            explode(',', $values)
                        );
                }

                if (empty($values)) {
                    continue;
                }

                $quoted = [];

                foreach ($values as $value) {
                    $quoted[] =
                        $this->quoteValue($value);
                }

                $where[] =
                    "{$column} {$operator} ("
                    . implode(', ', $quoted)
                    . ")";

                continue;
            }

            if (!array_key_exists(
                'value',
                $filter
            )) {
                continue;
            }

            $where[] =
                "{$column} {$operator} "
                . $this->quoteValue(
                    $filter['value']
                );
        }

        return $where;
    }


    /*
    |--------------------------------------------------------------------------
    | HAVING
    |--------------------------------------------------------------------------
    */

    private function buildHaving(array $having): array
    {
        $result = [];

        foreach ($having as $item) {

            if (
                empty($item['expression']) ||
                empty($item['operator'])
            ) {
                continue;
            }

            $operator =
                strtoupper(
                    $item['operator']
                );

            if (
                !in_array(
                    $operator,
                    [
                        '=',
                        '!=',
                        '>',
                        '<',
                        '>=',
                        '<=',
                        'LIKE',
                        'NOT LIKE',
                        'IS NULL',
                        'IS NOT NULL'
                    ],
                    true
                )
            ) {
                continue;
            }

            $expression =
                $item['expression'];

            if (
                $operator === 'IS NULL' ||
                $operator === 'IS NOT NULL'
            ) {

                $result[] =
                    "{$expression} {$operator}";

                continue;
            }

            if (
                !array_key_exists(
                    'value',
                    $item
                )
            ) {
                continue;
            }

            $result[] =
                "{$expression} {$operator} "
                . $this->quoteValue(
                    $item['value']
                );
        }

        return $result;
    }


    /*
    |--------------------------------------------------------------------------
    | ORDER BY
    |--------------------------------------------------------------------------
    */

    private function buildOrderBy(array $orders): array
    {
        $result = [];

        foreach ($orders as $order) {

            if (empty($order['column'])) {
                continue;
            }

            $direction =
                strtoupper(
                    $order['direction'] ?? 'ASC'
                );

            if (
                !in_array(
                    $direction,
                    ['ASC', 'DESC'],
                    true
                )
            ) {
                $direction = 'ASC';
            }

            $result[] =
                $order['column']
                . " {$direction}";
        }

        return $result;
    }


    /*
    |--------------------------------------------------------------------------
    | Dimension Combinations
    |--------------------------------------------------------------------------
    */

    private function buildDimensionCombinations(
        array $dimensions
    ): array {

        $result = [[]];

        foreach ($dimensions as $dimension) {

            $column =
                $dimension['column'] ?? null;

            $values =
                $dimension['values'] ?? [];

            if (
                empty($column) ||
                empty($values)
            ) {
                continue;
            }

            $newResult = [];

            foreach ($result as $existing) {

                foreach ($values as $value) {

                    $copy = $existing;

                    $copy[$column] = $value;

                    $newResult[] = $copy;
                }
            }

            $result = $newResult;
        }

        return $result;
    }


    /*
    |--------------------------------------------------------------------------
    | Pivot Alias
    |--------------------------------------------------------------------------
    */

    private function makePivotAlias(
        string $value
    ): string {

        /*
         * 2026-04
         * becomes APR
         */
        if (
            preg_match(
                '/^\d{4}-(\d{2})$/',
                $value,
                $matches
            )
        ) {

            $month =
                (int) $matches[1];

            $months = [
                1 => 'JAN',
                2 => 'FEB',
                3 => 'MAR',
                4 => 'APR',
                5 => 'MAY',
                6 => 'JUN',
                7 => 'JUL',
                8 => 'AUG',
                9 => 'SEP',
                10 => 'OCT',
                11 => 'NOV',
                12 => 'DEC',
            ];

            return $months[$month]
                ?? $value;
        }

        return strtoupper(
            preg_replace(
                '/[^A-Za-z0-9_]+/',
                '_',
                $value
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Identifier Validation
    |--------------------------------------------------------------------------
    */

    private function validateIdentifier(
        string $identifier
    ): string {

        if (
            !preg_match(
                '/^[A-Za-z_][A-Za-z0-9_]*$/',
                $identifier
            )
        ) {
            throw new InvalidArgumentException(
                "Invalid identifier: {$identifier}"
            );
        }

        return "`{$identifier}`";
    }


    private function quoteIdentifier(
        string $identifier
    ): string {

        /*
         * Supports:
         * table.column
         */
        if (
            preg_match(
                '/^([A-Za-z_][A-Za-z0-9_]*)\.([A-Za-z_][A-Za-z0-9_]*)$/',
                $identifier,
                $matches
            )
        ) {

            return "`{$matches[1]}`.`{$matches[2]}`";
        }

        return $this->validateIdentifier(
            $identifier
        );
    }


    private function validateAlias(
        string $alias
    ): string {

        if (
            !preg_match(
                '/^[A-Za-z][A-Za-z0-9_]*$/',
                $alias
            )
        ) {
            throw new InvalidArgumentException(
                "Invalid alias: {$alias}"
            );
        }

        return $alias;
    }


    private function validateColumnReference(
        string $column
    ): string {

        return $this->quoteIdentifier(
            $column
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Quote Value
    |--------------------------------------------------------------------------
    */

    private function quoteValue($value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        return DB::connection()
            ->getPdo()
            ->quote(
                (string) $value
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Limit
    |--------------------------------------------------------------------------
    */

    private function getLimit($limit): ?int
    {
        if ($limit === null) {
            return null;
        }

        $limit = (int) $limit;

        if ($limit <= 0) {
            return null;
        }

        return min($limit, 5000);
    }


    /*
    |--------------------------------------------------------------------------
    | Formatting
    |--------------------------------------------------------------------------
    */

    private function indentSql(
        string $sql,
        int $level
    ): string {

        $prefix =
            str_repeat(
                '    ',
                $level
            );

        return implode(
            "\n",
            array_map(
                function ($line) use ($prefix) {

                    return $prefix . $line;

                },
                explode("\n", $sql)
            )
        );
    }


    private function cleanMultiline(
        string $sql
    ): string {

        return preg_replace(
            '/\s+/',
            ' ',
            trim($sql)
        );
    }
}