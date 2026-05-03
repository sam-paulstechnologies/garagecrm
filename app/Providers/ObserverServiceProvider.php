<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use App\Models\Job\Booking;
use App\Models\Job\Job;

use App\Observers\LeadObserver;
use App\Observers\OpportunityObserver;
use App\Observers\BookingObserver;
use App\Observers\JobObserver;

class ObserverServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Lead::observe(LeadObserver::class);
        Opportunity::observe(OpportunityObserver::class);
        Booking::observe(BookingObserver::class);
        Job::observe(JobObserver::class);
    }
}