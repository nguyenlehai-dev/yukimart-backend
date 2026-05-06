<?php

namespace App\Providers;

use App\Modules\ShopProduct\Models\ShopEntry;
use App\Modules\ShopProduct\Observers\ShopEntryObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        ShopEntry::observe(ShopEntryObserver::class);
    }
}
