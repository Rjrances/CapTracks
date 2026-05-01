<?php

namespace App\Providers;

use App\Models\GroupMilestoneTask;
use App\Observers\GroupMilestoneTaskObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }
    public function boot(): void
    {
        GroupMilestoneTask::observe(GroupMilestoneTaskObserver::class);
    }
}
