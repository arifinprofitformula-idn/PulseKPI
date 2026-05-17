<?php

namespace App\Providers;

use App\Events\KpiAssigned;
use App\Events\KpiAssignmentCancelled;
use App\Listeners\SendKpiAssignedNotification;
use App\Listeners\SendKpiAssignmentCancelledNotification;
use App\Models\KpiPeriod;
use App\Models\KpiScoreRule;
use App\Models\KpiTemplate;
use App\Models\KpiTemplateItem;
use App\Models\User;
use App\Observers\KpiPeriodObserver;
use App\Observers\KpiScoreRuleObserver;
use App\Observers\KpiTemplateItemObserver;
use App\Observers\KpiTemplateObserver;
use App\Observers\UserObserver;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
        KpiPeriod::observe(KpiPeriodObserver::class);
        KpiTemplate::observe(KpiTemplateObserver::class);
        KpiTemplateItem::observe(KpiTemplateItemObserver::class);
        KpiScoreRule::observe(KpiScoreRuleObserver::class);

        Event::listen(KpiAssigned::class, SendKpiAssignedNotification::class);
        Event::listen(KpiAssignmentCancelled::class, SendKpiAssignmentCancelledNotification::class);
    }
}
