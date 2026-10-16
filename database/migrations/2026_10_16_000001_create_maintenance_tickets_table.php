<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maintenance_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_no')->unique();

            // Machine snapshot: preserves the name/department even if equipment data changes later.
            $table->string('equipment_code');
            $table->string('machine_name');
            $table->string('department_name')->nullable();

            // Request
            $table->string('requester_name');
            $table->string('requester_contact')->nullable();
            $table->date('raised_at');
            $table->string('problem_category'); // breakdown, spare, adjustment, inspection, other
            $table->string('priority')->default('normal'); // low, normal, high, critical
            $table->text('problem_description');
            $table->boolean('production_affected')->default(false);
            $table->text('request_remarks')->nullable();

            // Stage 1: inspection
            $table->string('inspector_name')->nullable();
            $table->dateTime('inspected_at')->nullable();
            $table->text('inspection_findings')->nullable();
            $table->text('suspected_cause')->nullable();
            $table->decimal('downtime_hours', 10, 2)->nullable();
            $table->text('inspection_recommendation')->nullable();
            $table->text('inspection_remarks')->nullable();

            // Stage 2: expert assessment
            $table->string('expert_name')->nullable();
            $table->dateTime('assessed_at')->nullable();
            $table->text('root_cause')->nullable();
            $table->text('corrective_action')->nullable();
            $table->text('preventive_action')->nullable();
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->text('expert_remarks')->nullable();

            // Stage 3: budget/admin approval
            $table->string('approver_name')->nullable();
            $table->dateTime('approval_decided_at')->nullable();
            $table->string('approval_decision')->nullable(); // approved, rejected, clarification
            $table->decimal('approved_budget', 12, 2)->nullable();
            $table->text('approval_remarks')->nullable();

            // Stage 4: repair and verification
            $table->string('technician_name')->nullable();
            $table->dateTime('repair_started_at')->nullable();
            $table->dateTime('repair_completed_at')->nullable();
            $table->text('repair_work_done')->nullable();
            $table->decimal('actual_cost', 12, 2)->nullable();
            $table->decimal('actual_downtime_hours', 10, 2)->nullable();
            $table->string('verifier_name')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->string('verification_result')->nullable(); // passed, failed
            $table->text('verification_remarks')->nullable();

            $table->string('status')->default('pending_inspection')->index();
            $table->timestamps();

            $table->index(['equipment_code', 'raised_at']);
            $table->index(['department_name', 'raised_at']);
        });

        Schema::create('maintenance_ticket_spares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_ticket_id')
                ->constrained('maintenance_tickets')->cascadeOnDelete();
            $table->string('spare_name');
            $table->string('part_number')->nullable();
            $table->decimal('quantity_required', 12, 2)->default(1);
            $table->decimal('quantity_approved', 12, 2)->nullable();
            $table->decimal('quantity_used', 12, 2)->nullable();
            $table->decimal('estimated_unit_cost', 12, 2)->nullable();
            $table->decimal('actual_unit_cost', 12, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('maintenance_ticket_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_ticket_id')
                ->constrained('maintenance_tickets')->cascadeOnDelete();
            $table->string('action');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('performed_by');
            $table->text('remarks')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
            $table->index(['maintenance_ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_ticket_histories');
        Schema::dropIfExists('maintenance_ticket_spares');
        Schema::dropIfExists('maintenance_tickets');
    }
};
