<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportLibrary extends Model
{
    protected $table = 'report_library';

    protected $fillable = [

        // Basic
        'report_code',
        'report_name',
        'module',
        'sub_module',
        'report_type',
        'description',
        'purpose',
        'business_logic',

        // Database
        'main_table',
        'data_source',
        'table_relationships',

        // Report logic
        'filters',
        'default_filters',
        'grouping_details',
        'calculation_details',
        'columns_displayed',
        'sorting',

        // Application
        'output_types',
        'route',
        'controller',
        'view',
        'procedure_function',

        // Security
        'access_role',
        'status',
        'version',

        // Audit
        'created_by',
        'updated_by',

        // Notes
        'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}