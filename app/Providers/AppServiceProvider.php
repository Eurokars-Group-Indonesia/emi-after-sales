<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use App\Models\SalesAtpmMenuUser;
use App\Models\SalesAtpmMasterMenu;


use App\Models\MenuAtpmAfterSales;
use App\Models\MenuDealer;


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
        // \Illuminate\Pagination\Paginator::useBootstrapFive();
        
        // // Set pagination to show max 5 links on each side
        // \Illuminate\Pagination\Paginator::defaultSimpleView('pagination::bootstrap-5');
        
        // // Register Microsoft Azure Socialite Provider using Custom Provider
        // \Illuminate\Support\Facades\Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
        //     $event->extendSocialite('azure', \App\Services\CustomAzureProvider::class);
        // });
        
        # Configure rate limiting
        $this->configureRateLimiting();

        # Menu after sales atpm
        View::composer('*', function ($view) {
            $menus = MenuAtpmAfterSales::whereNull('parent_id')
                ->orderBy('order')
                ->with('children')
                ->get();

            $view->with('MenuAtpmAfterSales', $menus);
        });

        // # Menu sales atpm
        View::composer('*', function ($view) {

           $kdAtpmUser = session('user.id');

           $userMenuIds = SalesAtpmMenuUser::where('is_active', true)
                                ->where('fk_kd_atpm_user', $kdAtpmUser)
                                ->pluck('fk_sales_atpm_master_menu')
                                ->toArray();

            $menus = SalesAtpmMasterMenu::whereNull('parent_id')
                ->whereIn('id', $userMenuIds)
                ->where('is_active', true)
                ->orderBy('order', 'asc')
                ->with([
                    'children' => function ($query) use ($userMenuIds) {
                        $query->whereIn('id', $userMenuIds)
                            ->where('is_active', true)
                            ->orderBy('order', 'asc');
                    },
                    'children.children' => function ($query) use ($userMenuIds) {
                        $query->whereIn('id', $userMenuIds)
                            ->where('is_active', true)
                            ->orderBy('order', 'asc');
                    }
                ])
                ->get();

                        // dd($menus);

            $view->with('SalesAtpmMenuUser', $menus);
        });



        View::composer('*', function ($view) {
            $menus = MenuDealer::whereNull('parent_id')
                ->orderBy('order')
                ->with('children')
                ->get();

            $view->with('MenuDealer', $menus);
        });
    }
    
    /**
     * Configure rate limiting for the application
     */
    protected function configureRateLimiting(): void
    {
        \Illuminate\Support\Facades\RateLimiter::for('web', function ($request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(100)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function ($request, $headers) {
                    return response()->view('errors.429', [], 429)->withHeaders($headers);
                });
        });
        
        \Illuminate\Support\Facades\RateLimiter::for('api', function ($request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip());
        });
    }
}
