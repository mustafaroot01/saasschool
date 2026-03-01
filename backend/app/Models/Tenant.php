<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains, \App\Traits\LogsActivity;

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'logo',
            'contact_email',
            'contact_phone',
            'status',
            'plan_id',
            'subscription_end_date',
            'primary_color',
            'secondary_color',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
