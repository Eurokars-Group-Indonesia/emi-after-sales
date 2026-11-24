<?php

namespace App\Http\Controllers;

/**
 * Home WRS Connect Sales - Dealer.
 * Menu (akses cepat & sidebar) dari RBAC Sales Dealer (ms_sales_dealer_*), diisi lewat view composer.
 */
class SalesDealerHomeController
{
    public function index()
    {
        return view('sales.dealer.page_home.home');
    }
}
