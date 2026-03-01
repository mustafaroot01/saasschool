<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Plan;
use App\Models\Tenant;
use App\Traits\LogsActivity;

class Subscription extends Model
{
    use LogsActivity;
    protected $fillable = [
        'tenant_id',
        'plan_id',
        'start_date',
        'end_date',
        'status',
        'amount',
        'currency',
        'invoice_number',
        'payment_status',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
