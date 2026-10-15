
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_maintenance_reports', function (Blueprint $table) {
            $table->id();
            $table->string('vrno')->unique();
            $table->date('report_date')->index();

            $table->string('equipment_code')->index();
            $table->string('machine_name');
            $table->string('section_name')->nullable();
            $table->string('machine_capacity')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('last_service_date')->nullable();
            $table->date('service_due_date')->nullable();

            $table->decimal('installed_capacity', 14, 3)->default(0);
            $table->decimal('working_hours', 6, 2)->default(8);
            $table->decimal('expected_output', 14, 3)->default(0);
            $table->decimal('actual_output', 14, 3)->default(0);
            $table->decimal('variance', 14, 3)->default(0);

            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_maintenance_reports');
    }
};
