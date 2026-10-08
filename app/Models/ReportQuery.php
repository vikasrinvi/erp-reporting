<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportQuery extends Model
{
    protected $table = 'report_queries';

    protected $fillable = [
        'report_library_id',
        'query_name',
        'query_mode',

        'tables_json',
        'joins_json',
        'columns_json',
        'filters_json',
        'group_by_json',
        'order_by_json',

        // Advanced query builder
        'cte_json',
        'calculated_columns_json',
        'pivot_json',
        'having_json',
        'distinct_flag',
        'limit_value',

        'generated_sql',
        'custom_sql',

        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tables_json' => 'array',
        'joins_json' => 'array',
        'columns_json' => 'array',
        'filters_json' => 'array',
        'group_by_json' => 'array',
        'order_by_json' => 'array',

        // Advanced query builder
        'cte_json' => 'array',
        'calculated_columns_json' => 'array',
        'pivot_json' => 'array',
        'having_json' => 'array',

        'distinct_flag' => 'boolean',
        'limit_value' => 'integer',

        'is_active' => 'boolean',
    ];

    public function report()
    {
        return $this->belongsTo(
            ReportLibrary::class,
            'report_library_id'
        );
    }
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}