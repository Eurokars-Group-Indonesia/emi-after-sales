<?php

namespace App\Services\Afsel;

use App\Models\Afsel\AtpmMenu;
use App\Models\Afsel\AtpmRole;
use App\Models\Afsel\DealerMenu;
use App\Models\Afsel\DealerRole;
use App\Services\Rbac\RbacEngine;

/**
 * RBAC WRS Connect - After Sales (ATPM & Dealer).
 * Logika ada di RbacEngine; class ini hanya berisi konfigurasi tabel After Sales.
 * RBAC Sales ada di App\Services\Sales\SalesRbacService (tabel, session & cache terpisah).
 */
class AfselRbacService extends RbacEngine
{
    /** Session key role aktif After Sales */
    public const SESSION_ROLE_KEY = 'user.afsel_role_id';

    protected const CACHE_PREFIX = 'afsel_rbac';

    public const TYPES = [
        'atpm' => [
            'prefix'     => 'ms_afsel_atpm_',
            'user_table' => 'ms_afsel_atpm_user',
            'user_key'   => 'kd_atpm_user',
            'user_name'  => 'nm_atpm_user',
            'role_model' => AtpmRole::class,
            'menu_model' => AtpmMenu::class,
            'home_route' => 'aftersales.atpm.home',
            'app'        => 'aftersales',
        ],
        'dealer' => [
            'prefix'     => 'ms_afsel_dealer_',
            'user_table' => 'ms_afsel_dealer_user',
            'user_key'   => 'kd_dealer_user',
            'user_name'  => 'nm_dealer_user',
            'role_model' => DealerRole::class,
            'menu_model' => DealerMenu::class,
            'home_route' => 'aftersales.dealer.home',
            'app'        => 'aftersales',
        ],
    ];

    protected function cacheTtl(): int
    {
        return (int) config('afsel.cache_ttl', 3600);
    }
}
