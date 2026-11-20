<?php
return [
    'label'        => 'Ventas',
    'version'      => '1.0.0',
    'icon'         => 'fas fa-cash-register',
    'color'        => 'emerald',
    'hex'          => '#10b981',
    'description'  => 'Punto de venta (POS), historial de ventas, facturación y cotizaciones a clientes.',
    'category'     => 'Ventas',
    'depends'      => ['core', 'inventario', 'clientes'],
    'auto_install' => false,
    'menu_order'   => 5,
    'menu' => [
        ['type' => 'section', 'label' => 'Ventas'],
        ['type' => 'link', 'label' => 'Punto de Venta',    'icon' => 'fas fa-cash-register',  'href' => '/ventas/pos', 'permission' => 'pos.usar'],
        // Menú desplegable "Ventas": lista de ventas normales / al por mayor, lista del POS y nueva venta
        ['type' => 'group', 'label' => 'Ventas', 'icon' => 'fas fa-shopping-cart',
         'open_prefixes' => ['/ventas/venta/', '/ventas/nueva', '/ventas/diarias'],
         'open_exact'    => ['/ventas'],
         'children' => [
            ['label' => 'Lista de ventas',        'title' => 'Ventas dentro del sistema o al por mayor', 'icon' => 'fas fa-list',          'href' => '/ventas?origen=normal', 'permission' => 'ventas.ver_normales'],
            ['label' => 'Ventas en POS',          'title' => 'Lista de ventas en punto de venta',        'icon' => 'fas fa-cash-register', 'href' => '/ventas?origen=pos'],
            ['label' => 'Agregar nueva venta',    'title' => 'Crear una venta nueva (factura)',          'icon' => 'fas fa-plus-circle',   'href' => '/ventas/nueva', 'permission' => 'ventas.crear'],
         ]],
        ['type' => 'link', 'label' => 'Cotizaciones',      'icon' => 'fas fa-file-invoice',   'href' => '/ventas/cotizaciones', 'permission' => 'cotizaciones.ver'],
        ['type' => 'link', 'label' => 'Mi Caja',           'icon' => 'fas fa-cash-register',  'href' => '/caja',   'permission' => 'caja.mi_caja'],
        ['type' => 'link', 'label' => 'Cajas (arqueos)',   'icon' => 'fas fa-scale-balanced', 'href' => '/cajas',  'permission' => 'cajas.ver'],
        // Listado de todas las entregas (despachos) que se realizan
        ['type' => 'link', 'label' => 'Envíos',            'icon' => 'fas fa-truck',          'href' => '/entregas', 'permission' => 'ventas.crear'],
    ],
];
