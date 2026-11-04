<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dispatch_stock_overrides', function (Blueprint $table) {
            $table->timestamp('stock_jh_updated_at')->nullable()->after('stock_jh');
            $table->timestamp('stock_bp_updated_at')->nullable()->after('stock_bp');
        });
    }

    public function down(): void
    {
        Schema::table('dispatch_stock_overrides', function (Blueprint $table) {
            $table->dropColumn(['stock_jh_updated_at', 'stock_bp_updated_at']);
        });
    }
};
