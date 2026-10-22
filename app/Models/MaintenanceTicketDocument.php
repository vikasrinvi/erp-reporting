<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceTicketDocument extends Model
{
    protected $fillable = [
        'maintenance_ticket_id',
        'stage',
        'document_name',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'uploaded_by',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(
            MaintenanceTicket::class,
            'maintenance_ticket_id'
        );
    }
    
    public function user()
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id');
    }


    public function getUploadedByNameAttribute(){
         
        return $this->user()->value('name');

    }
}
