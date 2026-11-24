<?php

namespace App\Http\Controllers\Sales\Access;

use App\Http\Controllers\Afsel\AfselRoleController;

/** Access Control Sales: master role + permission & menu per role. */
class SalesRoleController extends AfselRoleController
{
    use SalesAccessContext;
}
