{{-- Menu dari RBAC Sales ATPM (ms_sales_atpm_*), diisi view composer di AppServiceProvider --}}
@include('aftersales.components.sidebar', [
    'brand'  => 'SALES',
    'portal' => 'ATPM',
    'theme'  => 'sales',
    'menus'  => $SalesAtpmMenuUser ?? collect(),
])
