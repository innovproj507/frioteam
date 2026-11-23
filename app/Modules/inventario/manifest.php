<?php
return [
    'label'        => 'Inventario',
    'version'      => '1.0.0',
    'icon'         => 'fas fa-boxes',
    'color'        => 'blue',
    'hex'          => '#0ea5e9',
    'description'  => 'Gestión completa de productos, categorías, marcas, proveedores, depósitos, compras, traslados y conteos físicos.',
    'category'     => 'Operaciones',
    'depends'      => ['core'],
    'auto_install' => false,
    'menu_order'   => 20,
    'menu' => [
        ['type' => 'section', 'label' => 'Inventario'],
        // «Productos» va arriba del todo (bajo Dashboard): ver menu_top de core/manifest.php
        ['type' => 'link', 'label' => 'Categorías',      'icon' => 'fas fa-tags',                 'href' => '/inventario/categorias', 'permission' => 'categorias.ver'],
        ['type' => 'link', 'label' => 'Marcas',          'icon' => 'fas fa-certificate',          'href' => '/inventario/marcas', 'permission' => 'categorias.ver'],
        ['type' => 'link', 'label' => 'Proveedores',     'icon' => 'fas fa-truck',                'href' => '/inventario/proveedores', 'permission' => 'proveedores.ver'],
        ['type' => 'link', 'label' => 'Depósitos',       'icon' => 'fas fa-warehouse',            'href' => '/inventario/depositos', 'permission' => 'depositos.ver'],
        ['type' => 'link', 'label' => 'Compras',         'icon' => 'fas fa-file-invoice-dollar',  'href' => '/compras', 'permission' => 'compras.ver'],
        ['type' => 'link', 'label' => 'Traslados',       'icon' => 'fas fa-exchange-alt',         'href' => '/inventario/traslados', 'permission' => 'traslados.ver'],
        ['type' => 'link', 'label' => 'Kardex',          'icon' => 'fas fa-stream',               'href' => '/inventario/kardex', 'permission' => 'inventario.ver'],
        ['type' => 'link', 'label' => 'Alertas',         'icon' => 'fas fa-bell',                 'href' => '/inventario/alertas', 'permission' => 'alertas.ver'],
        ['type' => 'link', 'label' => 'Conteo Físico',   'icon' => 'fas fa-clipboard-check',      'href' => '/inventario/conteos', 'permission' => 'conteos.ver'],
        ['type' => 'link', 'label' => 'OC Automáticas',  'icon' => 'fas fa-robot',                'href' => '/compras/sugerencias', 'permission' => 'sugerencias.ver'],
    ],
];
