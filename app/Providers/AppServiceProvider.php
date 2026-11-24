<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;
use App\Services\Afsel\AfselRbacService;
use App\Services\Rbac\RbacEngine;
use App\Services\Sales\SalesRbacService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Singleton agar per-request cache role/permission terpakai ulang
        $this->app->singleton(AfselRbacService::class);
        $this->app->singleton(SalesRbacService::class);
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

        # Blade: @afselcan('permission.slug') ... @endafselcan
        Blade::if('afselcan', fn (string $slug) => afsel_can($slug));

        # Blade: @salescan('permission.slug') ... @endsalescan  (RBAC Sales)
        Blade::if('salescan', fn (string $slug) => sales_can($slug));

        # Menu sidebar dari tabel RBAC (role aktif + override per user), logika sama dengan eurokars-exam.
        # Composer dipasang hanya di view sidebar, bukan '*', agar query menu tidak jalan di setiap view.
        View::composer('aftersales.atpm.layouts.sidebar', function ($view) {
            $view->with('MenuAtpmAfterSales', $this->rbacMenu(AfselRbacService::class, 'atpm', 'aftersales'));
        });

        View::composer('aftersales.dealer.layouts.sidebar', function ($view) {
            $view->with('MenuDealer', $this->rbacMenu(AfselRbacService::class, 'dealer', 'aftersales'));
        });

        # Sales (RBAC Sales: ms_sales_atpm_* / ms_sales_dealer_*). Home Sales juga memakai menu ini (akses cepat).
        View::composer(['sales.atpm.layouts.sidebar', 'sales.atpm.page_home.home'], function ($view) {
            $view->with('SalesAtpmMenuUser', $this->rbacMenu(SalesRbacService::class, 'atpm', 'sales'));
        });

        View::composer(['sales.dealer.layouts.sidebar', 'sales.dealer.page_home.home'], function ($view) {
            $view->with('SalesDealerMenuUser', $this->rbacMenu(SalesRbacService::class, 'dealer', 'sales'));
        });
    }

    /**
     * Menu sidebar (tree) untuk user yang sedang login.
     * @param class-string<RbacEngine> $service  AfselRbacService (After Sales) / SalesRbacService (Sales)
     * $type : tipe user (atpm / dealer), wajib sama dengan session user.loginAs
     * $app  : nilai session user.app yang wajib cocok (aftersales / sales)
     */
    private function rbacMenu(string $service, string $type, string $app)
    {
        $userId = (string) session('user.id');

        if ($userId === '' || session('user.loginAs') !== $type || session('user.app') !== $app) {
            return collect();
        }

        return app($service)->getMenuTree($type, $userId);
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
