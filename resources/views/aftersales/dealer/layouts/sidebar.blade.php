@include('aftersales.components.sidebar', [
    'portal' => 'Dealer',
    'menus'  => $MenuDealer ?? collect(),
])
