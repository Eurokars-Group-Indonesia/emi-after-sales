{{-- Menu dari RBAC Sales Dealer (ms_sales_dealer_*), diisi view composer di AppServiceProvider --}}
@include('aftersales.components.sidebar', [
    'brand'  => 'SALES',
    'portal' => 'Dealer',
    'theme'  => 'sales',
    'menus'  => $SalesDealerMenuUser ?? collect(),
])
