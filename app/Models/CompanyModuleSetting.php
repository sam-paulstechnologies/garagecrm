<?php

namespace App\Models;

use App\Models\System\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyModuleSetting extends Model
{
    protected $fillable = [
        'company_id',
        'module_key',
        'enabled',
        'locked',
        'notes',
        'enabled_by',
        'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'locked' => 'boolean',
    ];

    public static function catalog(): array
    {
        return [
            'leads' => ['name' => 'Leads', 'description' => 'Lead capture, import, scoring, and qualification.'],
            'clients' => ['name' => 'Clients', 'description' => 'Customer records, vehicles, and contact history.'],
            'opportunities' => ['name' => 'Opportunities', 'description' => 'Sales pipeline from qualified lead to booking.'],
            'bookings' => ['name' => 'Bookings', 'description' => 'Booking confirmation, rescheduling, and job conversion.'],
            'jobs' => ['name' => 'Jobs', 'description' => 'Workshop job tracking and completion.'],
            'invoices' => ['name' => 'Invoices', 'description' => 'Invoice capture, payment state, and ROI readiness.'],
            'inbox' => ['name' => 'Inbox', 'description' => 'WhatsApp-first customer conversation hub.'],
            'reports' => ['name' => 'Reports', 'description' => 'Garage operating summaries and retention reports.'],
            'growth' => ['name' => 'Growth', 'description' => 'Lead sources, journeys, audience, and retention tools.'],
            'calendar' => ['name' => 'Calendar', 'description' => 'Booking confirmation calendar.'],
            'meta' => ['name' => 'Meta / Media Team', 'description' => 'Meta lead forms and WhatsApp channel setup.'],
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function enabledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enabled_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
