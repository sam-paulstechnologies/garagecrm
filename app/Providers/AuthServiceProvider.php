<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // ==== AI + Chat / Conversations ====
        \App\Models\Conversation::class => \App\Policies\ConversationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // If you want to add custom Gates later, add here.
        //
        // Gate::define('view-admin-panel', fn($user) => $user->role === 'admin');
    }
}
