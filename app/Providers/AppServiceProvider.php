<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use App\Observers\AuditLogObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Document::observe(AuditLogObserver::class);
        Category::observe(AuditLogObserver::class);
        User::observe(AuditLogObserver::class);
    }
}
