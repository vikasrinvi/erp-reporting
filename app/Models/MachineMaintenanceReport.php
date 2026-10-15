<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MachineMaintenanceReport extends Model
{
    protected $table = 'machine_maintenance_reports';

    protected $fillable = [
        'vrno',
        'report_date',
        'equipment_code',
        'machine_name',
        'section_name',
        'machine_capacity',
        'purchase_date',
        'last_service_date',
        'service_due_date',
        'installed_capacity',
        'working_hours',
        'expected_output',
        'actual_output',
        'variance',
        'remarks',
    ];

    protected $casts = [
        'report_date' => 'date',
        'purchase_date' => 'date',
        'last_service_date' => 'date',
        'service_due_date' => 'date',
        'installed_capacity' => 'decimal:3',
        'working_hours' => 'decimal:2',
        'expected_output' => 'decimal:3',
        'actual_output' => 'decimal:3',
        'variance' => 'decimal:3',
    ];
}
