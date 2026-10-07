<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_queries', function (Blueprint $table) {

            $table->id();

            $table->foreignId('report_library_id')
                ->constrained('report_library')
                ->cascadeOnDelete();

            $table->string('query_name', 150);

            $table->enum('query_mode', [
                'builder',
                'custom'
            ])->default('builder');

            $table->json('tables_json')->nullable();
            $table->json('joins_json')->nullable();
            $table->json('columns_json')->nullable();
            $table->json('filters_json')->nullable();
            $table->json('group_by_json')->nullable();
            $table->json('order_by_json')->nullable();

            $table->longText('generated_sql')->nullable();
            $table->longText('custom_sql')->nullable();

            $table->boolean('is_active')->default(true);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->index('report_library_id');
            $table->index('query_mode');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_queries');
    }
};