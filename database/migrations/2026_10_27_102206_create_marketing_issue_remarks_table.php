
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_issue_remarks', function (Blueprint $table) {
            $table->id();

            $table->string('marketing_category', 100)->default('');
            $table->string('prod_code', 100);
            $table->string('item_code', 100);

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->unique(
                ['marketing_category', 'prod_code', 'item_code'],
                'marketing_issue_remarks_item_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_issue_remarks');
    }
};
