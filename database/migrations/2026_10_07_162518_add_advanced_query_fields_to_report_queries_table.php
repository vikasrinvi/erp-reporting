<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_queries', function (Blueprint $table) {

            // CTE / WITH configuration
            $table->json('cte_json')
                ->nullable()
                ->after('order_by_json');

            // Calculated / expression-based columns
            $table->json('calculated_columns_json')
                ->nullable()
                ->after('cte_json');

            // Pivot / Cross-tab configuration
            $table->json('pivot_json')
                ->nullable()
                ->after('calculated_columns_json');

            // HAVING conditions
            $table->json('having_json')
                ->nullable()
                ->after('pivot_json');

            // SELECT DISTINCT
            $table->boolean('distinct_flag')
                ->default(false)
                ->after('having_json');

            // Safety/result limit
            $table->unsignedInteger('limit_value')
                ->default(500)
                ->after('distinct_flag');
        });
    }

    public function down(): void
    {
        Schema::table('report_queries', function (Blueprint $table) {
            $table->dropColumn([
                'cte_json',
                'calculated_columns_json',
                'pivot_json',
                'having_json',
                'distinct_flag',
                'limit_value',
            ]);
        });
    }
};