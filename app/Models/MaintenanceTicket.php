<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceTicket extends Model
{
    protected $fillable = [
        'ticket_no', 'equipment_code', 'machine_name', 'department_name',
        'requester_name', 'requester_contact', 'raised_at', 'problem_category',
        'priority', 'problem_description', 'production_affected', 'request_remarks',
        'inspector_name', 'inspected_at', 'inspection_findings', 'suspected_cause',
        'downtime_hours', 'inspection_recommendation', 'inspection_remarks',
        'expert_name', 'assessed_at', 'root_cause', 'corrective_action',
        'preventive_action', 'estimated_cost', 'expert_remarks',
        'approver_name', 'approval_decided_at', 'approval_decision',
        'approved_budget', 'approval_remarks', 'technician_name',
        'repair_started_at', 'repair_completed_at', 'repair_work_done',
        'actual_cost', 'actual_downtime_hours', 'verifier_name', 'verified_at',
        'verification_result', 'verification_remarks', 'status',
    ];

    protected $casts = [
        'raised_at' => 'date',
        'inspected_at' => 'datetime',
        'assessed_at' => 'datetime',
        'approval_decided_at' => 'datetime',
        'repair_started_at' => 'datetime',
        'repair_completed_at' => 'datetime',
        'verified_at' => 'datetime',
        'production_affected' => 'boolean',
        'downtime_hours' => 'decimal:2',
        'actual_downtime_hours' => 'decimal:2',
        'estimated_cost' => 'decimal:2',
        'approved_budget' => 'decimal:2',
        'actual_cost' => 'decimal:2',
    ];

    public function spares(): HasMany
    {
        return $this->hasMany(MaintenanceTicketSpare::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(MaintenanceTicketHistory::class)->latest();
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
