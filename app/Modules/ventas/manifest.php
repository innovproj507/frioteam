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
        // Menú desplegable "Cotizaciones": las del POS / sistema y las que llegan de la tienda web
        ['type' => 'group', 'label' => 'Cotizaciones', 'icon' => 'fas fa-file-invoice',
         'open_prefixes' => ['/ventas/cotizaciones/'],
         'open_exact'    => ['/ventas/cotizaciones'],
         'children' => [
            ['label' => 'Cotizaciones POS',  'title' => 'Cotizaciones hechas en el POS y en el sistema', 'icon' => 'fas fa-cash-register', 'href' => '/ventas/cotizaciones?origen=pos', 'permission' => 'cotizaciones.ver'],
            ['label' => 'Cotizaciones web',  'title' => 'Cotizaciones que piden los clientes en la tienda web', 'icon' => 'fas fa-globe', 'href' => '/ventas/cotizaciones?origen=web', 'permission' => 'cotizaciones.ver'],
            ['label' => 'Nueva cotización',  'title' => 'Crear una cotización nueva', 'icon' => 'fas fa-plus-circle', 'href' => '/ventas/cotizaciones/nueva', 'permission' => 'cotizaciones.crear'],
         ]],
        ['type' => 'link', 'label' => 'Mi Caja',           'icon' => 'fas fa-cash-register',  'href' => '/caja',   'permission' => 'caja.mi_caja'],
        ['type' => 'link', 'label' => 'Cajas (arqueos)',   'icon' => 'fas fa-scale-balanced', 'href' => '/cajas',  'permission' => 'cajas.ver'],
        // Listado de todas las entregas (despachos) que se realizan
        ['type' => 'link', 'label' => 'Envíos',            'icon' => 'fas fa-truck',          'href' => '/entregas', 'permission' => 'ventas.crear'],
    ],
];
