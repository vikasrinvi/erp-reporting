<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceTicketSpare extends Model
{
    protected $fillable = [
        'maintenance_ticket_id', 'spare_name', 'part_number',
        'quantity_required', 'quantity_approved', 'quantity_used',
        'estimated_unit_cost', 'actual_unit_cost', 'remarks',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(MaintenanceTicket::class, 'maintenance_ticket_id');
    }
}
