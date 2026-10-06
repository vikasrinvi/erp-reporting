<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_library', function (Blueprint $table) {

            $table->id();

            // =========================================================
            // BASIC INFORMATION
            // =========================================================

            $table->string('report_code', 50)->unique();
            $table->string('report_name', 150);

            $table->string('module', 100)->nullable();
            $table->string('sub_module', 100)->nullable();

            $table->string('report_type', 50)->nullable();

            $table->text('description')->nullable();
            $table->text('purpose')->nullable();
            $table->text('business_logic')->nullable();


            // =========================================================
            // DATABASE / DATA SOURCE
            // =========================================================

            $table->string('main_table', 150)->nullable();

            $table->text('data_source')->nullable();

            $table->text('table_relationships')->nullable();


            // =========================================================
            // REPORT LOGIC
            // =========================================================

            $table->text('filters')->nullable();

            $table->text('default_filters')->nullable();

            $table->text('grouping_details')->nullable();

            $table->text('calculation_details')->nullable();

            $table->text('columns_displayed')->nullable();

            $table->text('sorting')->nullable();


            // =========================================================
            // OUTPUT / APPLICATION
            // =========================================================

            $table->string('output_types', 255)->nullable();

            $table->string('route', 255)->nullable();

            $table->string('controller', 255)->nullable();

            $table->string('view', 255)->nullable();

            $table->text('procedure_function')->nullable();


            // =========================================================
            // SECURITY / MANAGEMENT
            // =========================================================

            $table->string('access_role', 255)->nullable();

            $table->enum('status', [
                'active',
                'inactive'
            ])->default('active');

            $table->string('version', 20)->default('1.0');


            // =========================================================
            // AUDIT
            // =========================================================

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamp('created_at')->nullable();

            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamp('updated_at')->nullable();


            // =========================================================
            // NOTES
            // =========================================================

            $table->text('notes')->nullable();


            // =========================================================
            // INDEXES
            // =========================================================

            $table->index('module');
            $table->index('sub_module');
            $table->index('report_type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_library');
    }
};