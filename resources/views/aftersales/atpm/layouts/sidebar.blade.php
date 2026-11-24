@include('aftersales.components.sidebar', [
    'portal' => 'ATPM',
    'menus'  => $MenuAtpmAfterSales ?? collect(),
])
