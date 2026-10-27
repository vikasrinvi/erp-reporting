<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketingIssueRemark extends Model
{
    protected $table = 'marketing_issue_remarks';

    protected $fillable = [
        'marketing_category',
        'prod_code',
        'item_code',
        'remarks',
    ];
}
