<?php

namespace App\Providers;

use App\Events\KpiAssessmentApproved;
use App\Events\KpiAssessmentLocked;
use App\Events\KpiAssessmentRejected;
use App\Events\KpiAssessmentReviewed;
use App\Events\KpiAssessmentSubmitted;
use App\Events\KpiAssigned;
use App\Events\KpiAssignmentCancelled;
use App\Listeners\SendKpiAssessmentApprovedNotification;
use App\Listeners\SendKpiAssessmentLockedNotification;
use App\Listeners\SendKpiAssessmentRejectedNotification;
use App\Listeners\SendKpiAssessmentReviewedNotification;
use App\Listeners\SendKpiAssessmentSubmittedNotification;
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
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        User::observe(UserObserver::class);
        KpiPeriod::observe(KpiPeriodObserver::class);
        KpiTemplate::observe(KpiTemplateObserver::class);
        KpiTemplateItem::observe(KpiTemplateItemObserver::class);
        KpiScoreRule::observe(KpiScoreRuleObserver::class);

        Event::listen(KpiAssigned::class, SendKpiAssignedNotification::class);
        Event::listen(KpiAssignmentCancelled::class, SendKpiAssignmentCancelledNotification::class);
        Event::listen(KpiAssessmentSubmitted::class, SendKpiAssessmentSubmittedNotification::class);
        Event::listen(KpiAssessmentReviewed::class, SendKpiAssessmentReviewedNotification::class);
        Event::listen(KpiAssessmentApproved::class, SendKpiAssessmentApprovedNotification::class);
        Event::listen(KpiAssessmentRejected::class, SendKpiAssessmentRejectedNotification::class);
        Event::listen(KpiAssessmentLocked::class, SendKpiAssessmentLockedNotification::class);

        View::share('branding', config('branding'));
    }
}
