<?php

namespace App\Providers;

use App\Models\Student;
use App\View\LayoutNavigation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

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
        View::composer('layouts.app', function (ViewInstance $view): void {
            $supervisorName = null;
            $industryName = null;

            if (request()->is('student*')) {
                /** @var Student|null $student */
                $student = Auth::guard('student')->user();
                $student?->loadMissing(['lecturer', 'supervisor']);
                $supervisorName = $student?->lecturer?->name;
                $industryName = $student?->supervisor?->name;
            }

            $view->with(compact('supervisorName', 'industryName'));
            $view->with('navItems', LayoutNavigation::itemsFor(request()));
        });
    }
}
