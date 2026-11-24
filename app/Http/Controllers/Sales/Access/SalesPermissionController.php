<?php

namespace App\Http\Controllers\Sales\Access;

use App\Http\Controllers\Afsel\AfselPermissionController;

/** Access Control Sales: master permission (slug dipakai middleware sales.permission / @salescan). */
class SalesPermissionController extends AfselPermissionController
{
    use SalesAccessContext;

    protected function middlewareNames(): array
    {
        return ['auth' => 'sales.auth', 'permission' => 'sales.permission'];
    }
}
