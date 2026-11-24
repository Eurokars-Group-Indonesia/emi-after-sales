<?php

namespace App\Http\Controllers\Sales\Access;

use App\Http\Controllers\Afsel\AfselMenuController;

/** Access Control Sales: master menu sidebar Sales ATPM & Sales Dealer. */
class SalesMenuController extends AfselMenuController
{
    use SalesAccessContext;

    protected function routePrefixes(string $type): array
    {
        return [
            'atpm'   => ['sales.atpm.'],
            'dealer' => ['sales.dealer.'],
        ][$type] ?? [];
    }
}
