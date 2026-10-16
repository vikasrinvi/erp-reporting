<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceTicketHistory extends Model
{
    protected $fillable = [
        'maintenance_ticket_id', 'action', 'from_status', 'to_status',
        'performed_by', 'remarks', 'details',
    ];

    protected $casts = ['details' => 'array'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(MaintenanceTicket::class, 'maintenance_ticket_id');
    }
}
