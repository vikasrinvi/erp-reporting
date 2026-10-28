<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DispatchStockOverride extends Model
{
    protected $table = 'dispatch_stock_overrides';

    protected $fillable = [
        'item_code',
        'stock_jh',
        'stock_bp',
    ];
}
