<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\Lead\Lead;
use App\Models\Opportunity\Opportunity;
use App\Models\Booking\Booking;
use App\Models\Job\Job;

use App\Observers\LeadObserver;
use App\Observers\OpportunityObserver;
use App\Observers\BookingObserver;
use App\Observers\JobObserver;

class ObserverServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Bind all observers in one place
        if (class_exists(Lead::class))        { Lead::observe(LeadObserver::class); }
        if (class_exists(Opportunity::class)) { Opportunity::observe(OpportunityObserver::class); }
        if (class_exists(Booking::class))     { Booking::observe(BookingObserver::class); }
        if (class_exists(Job::class))         { Job::observe(JobObserver::class); }
    }
}
