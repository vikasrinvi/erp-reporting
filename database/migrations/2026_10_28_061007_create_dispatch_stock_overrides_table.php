<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatch_stock_overrides', function (Blueprint $table) {
            $table->id();
            $table->string('item_code', 100)->unique();
            $table->decimal('stock_jh', 18, 3)->nullable();
            $table->decimal('stock_bp', 18, 3)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_stock_overrides');
    }
};
