<?php

namespace App\Http\Controllers\Sales\Access;

use App\Services\Sales\SalesRbacService;

/**
 * Konteks halaman Access Control WRS Connect Sales.
 * Controller Sales memakai ulang logika controller After Sales (App\Http\Controllers\Afsel\*)
 * tetapi dengan service RBAC Sales (tabel ms_sales_*), layout Sales, route sales.atpm.access.*,
 * dan permission Sales (sales_can). Data RBAC After Sales tidak bisa diakses dari sini.
 */
trait SalesAccessContext
{
    public function __construct(SalesRbacService $rbac)
    {
        $this->rbac = $rbac;
    }

    protected function context(): array
    {
        return [
            'layout'          => 'sales.atpm.layouts.app',
            'navtop'          => 'sales.atpm.layouts.navtop',
            'sidebar'         => 'sales.atpm.layouts.sidebar',
            'route'           => 'sales.atpm.access.',
            'home'            => 'sales.atpm.home',
            'app'             => 'Sales',
            'perm_middleware' => 'sales.permission',
            'perm_directive'  => 'salescan',
        ];
    }

    protected function can(string $slug): bool
    {
        return sales_can($slug);
    }
}
