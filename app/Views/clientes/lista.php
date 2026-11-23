<?php $this->extend('layouts/app'); ?>
<?php $this->section('content'); ?>

<?php $s = $stats ?? []; ?>

<!-- Barra de búsqueda -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-4">
    <form method="GET" id="search-form" class="flex flex-wrap items-end gap-3">

        <div class="flex-1 min-w-48">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Buscar</label>
            <div class="relative">
                <i class="fas fa-search absolute left-0 top-2 text-gray-400 text-xs"></i>
                <input type="text" name="buscar" id="search-input"
                    value="<?= esc($buscar ?? '') ?>"
                    placeholder="Código, nombre o RUC..."
                    class="w-full pl-5 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
                <div id="search-spinner" class="absolute right-0 top-2 hidden">
                    <i class="fas fa-spinner fa-spin text-sky-500 text-xs"></i>
                </div>
            </div>
        </div>

        <?php
        // Los filtros de estado y tipo son enlaces: conservan la búsqueda y la cantidad por página
        $urlFiltro = fn (array $x) => '/clientes?' . http_build_query(array_filter(array_merge([
            'buscar' => $buscar ?? '', 'estado' => $estado_filtro ?? '', 'tipo' => $tipo_filtro ?? '', 'por_pagina' => $pagination['per_page'] ?? 25,
        ], $x), fn ($v) => $v !== '' && $v !== null));
        $chip = 'inline-flex items-center justify-center gap-1 h-8 min-w-8 px-2.5 rounded-lg text-xs font-semibold transition-colors';
        $estadoSel = $estado_filtro ?? '';
        $tipoSel   = $tipo_filtro ?? '';
        ?>
        <input type="hidden" name="estado" value="<?= esc($estadoSel) ?>">
        <input type="hidden" name="tipo" value="<?= esc($tipoSel) ?>">

        <div>
            <p class="block text-xs font-semibold text-gray-500 mb-1"><i class="fas fa-toggle-on mr-1"></i>Estado</p>
            <div class="inline-flex bg-gray-50 border border-gray-100 rounded-xl p-0.5 gap-0.5">
                <a href="<?= $urlFiltro(['estado' => '']) ?>" data-filtro="estado" data-valor="" class="<?= $chip ?> <?= $estadoSel === '' ? 'bg-slate-800 text-white' : 'text-gray-500 hover:text-slate-800' ?>" title="Todos los estados">Todos</a>
                <a href="<?= $urlFiltro(['estado' => 'activo']) ?>" data-filtro="estado" data-valor="activo" class="<?= $chip ?> <?= $estadoSel === 'activo' ? 'bg-emerald-500 text-white' : 'text-gray-500 hover:text-emerald-600' ?>" title="Solo activos" aria-label="Solo activos">
                    <i class="fas fa-circle-check"></i> Activos <span class="opacity-80"><?= (int) ($s['activos'] ?? 0) ?></span></a>
                <a href="<?= $urlFiltro(['estado' => 'inactivo']) ?>" data-filtro="estado" data-valor="inactivo" class="<?= $chip ?> <?= $estadoSel === 'inactivo' ? 'bg-red-500 text-white' : 'text-gray-500 hover:text-red-500' ?>" title="Solo inactivos" aria-label="Solo inactivos">
                    <i class="fas fa-circle-xmark"></i> Inactivos <span class="opacity-80"><?= (int) ($s['inactivos'] ?? 0) ?></span></a>
                <?php if (!empty($ve_eliminados)): ?>
                <a href="<?= $urlFiltro(['estado' => 'eliminado']) ?>" data-filtro="estado" data-valor="eliminado" title="Clientes eliminados (solo los ve el administrador principal)" aria-label="Clientes eliminados" class="<?= $chip ?> <?= $estadoSel === 'eliminado' ? 'bg-slate-600 text-white' : 'text-gray-400 hover:text-slate-700' ?>">
                    <i class="fas fa-trash-can"></i> Eliminados <span class="opacity-80"><?= (int) ($s['eliminados'] ?? 0) ?></span></a>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <p class="block text-xs font-semibold text-gray-500 mb-1"><i class="fas fa-tag mr-1"></i>Tipo</p>
            <div class="inline-flex bg-gray-50 border border-gray-100 rounded-xl p-0.5 gap-0.5">
                <a href="<?= $urlFiltro(['tipo' => '']) ?>" data-filtro="tipo" data-valor="" class="<?= $chip ?> <?= $tipoSel === '' ? 'bg-slate-800 text-white' : 'text-gray-500 hover:text-slate-800' ?>" title="Todos los tipos">Todos</a>
                <a href="<?= $urlFiltro(['tipo' => 'natural']) ?>" data-filtro="tipo" data-valor="natural" class="<?= $chip ?> <?= $tipoSel === 'natural' ? 'bg-sky-500 text-white' : 'text-gray-500 hover:text-sky-600' ?>" title="Personas (natural)" aria-label="Personas (natural)"><i class="fas fa-user"></i> Natural</a>
                <a href="<?= $urlFiltro(['tipo' => 'juridico']) ?>" data-filtro="tipo" data-valor="juridico" class="<?= $chip ?> <?= $tipoSel === 'juridico' ? 'bg-blue-600 text-white' : 'text-gray-500 hover:text-blue-600' ?>" title="Empresas (jurídico)" aria-label="Empresas (jurídico)"><i class="fas fa-building"></i> Jurídico</a>
            </div>
        </div>

        <div class="min-w-24">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Por página</label>
            <select name="por_pagina" onchange="this.form.submit()"
                class="w-full py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none appearance-none">
                <?php foreach ([10, 25, 50, 100] as $n): ?>
                <option value="<?= $n ?>" <?= (($pagination['per_page'] ?? 25) == $n) ? 'selected' : '' ?>><?= $n ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex items-center gap-2 ml-auto">
            <span class="text-xs font-medium text-gray-400 whitespace-nowrap">
                <?= number_format($pagination['total'] ?? 0) ?> cliente(s)
                <?php if (!empty($buscar) || !empty($estado_filtro) || !empty($tipo_filtro)): ?>
                <span class="text-sky-500">— filtrado</span>
                <?php endif; ?>
            </span>
            <?php if (!empty($buscar) || !empty($estado_filtro) || !empty($tipo_filtro)): ?>
            <a href="/clientes"
                class="inline-flex items-center gap-1 px-3 py-2 border border-gray-200 text-gray-400 rounded-lg text-sm hover:bg-gray-50 transition"
                title="Limpiar filtros">
                <i class="fas fa-times"></i>
            </a>
            <?php endif; ?>
            <?php if (\App\Libraries\Auth::can('clientes.editar')):
                try { $nSolWeb = (int) \App\Libraries\Db::value("SELECT COUNT(*) FROM tienda_solicitudes WHERE estado = 'pendiente'"); } catch (\Throwable $e) { $nSolWeb = 0; } ?>
            <a href="/clientes/solicitudes-web" class="relative inline-flex items-center gap-1.5 px-4 py-2 border border-sky-200 text-sky-700 rounded-lg text-sm font-semibold hover:bg-sky-50 transition"
               title="Solicitudes de acceso de la tienda web<?= $nSolWeb > 0 ? ' (' . $nSolWeb . ' pendientes)' : '' ?>">
                <i class="fas fa-globe"></i> Solicitudes web<?php if ($nSolWeb > 0): ?> <span class="bg-rose-500 text-white text-xs font-bold rounded-full px-2"><?= $nSolWeb ?></span><?php endif; ?>
            </a>
            <?php endif; ?>
            <a href="/clientes/nuevo"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-sky-500 text-white rounded-lg text-sm font-semibold hover:bg-sky-600 transition shadow-sm">
                <i class="fas fa-user-plus"></i> Nuevo Cliente
            </a>
        </div>
    </form>
</div>

<!-- Tabla -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

    <div class="px-5 py-3.5 bg-gray-50/80 border-b border-gray-100 flex items-center justify-between">
        <span class="text-xs font-medium text-gray-500">Ordenado por nombre</span>
        <span class="text-xs text-gray-400">
            Pág. <?= $pagination['current_page'] ?? 1 ?> / <?= $pagination['total_pages'] ?? 1 ?>
        </span>
    </div>

    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-100 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                <th class="px-5 py-3.5 bg-gray-50/80">Cliente</th>
                <th class="px-5 py-3.5 bg-gray-50/80">Código / RUC</th>
                <th class="px-5 py-3.5 bg-gray-50/80">Contacto</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-center" ><i class="fas fa-tag mr-1"></i>Tipo</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-center" ><i class="fas fa-toggle-on mr-1"></i>Estado</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-right">Saldo</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-center">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            <?php foreach (($clientes ?? []) as $cliente): ?>
            <tr class="hover:bg-sky-50/40 transition-colors group">

                <!-- Cliente -->
                <td class="px-5 py-3.5 <?= $cliente->estado !== 'activo' ? 'opacity-50' : '' ?>">
                    <div class="flex items-center gap-3">
                        <?php if (!empty($cliente->foto)): ?>
                        <button type="button" data-ampliar data-src="/assets/uploads/<?= esc($cliente->foto, 'attr') ?>" data-titulo="<?= esc($cliente->nombre, 'attr') ?>" data-codigo="<?= esc((string) $cliente->codigo, 'attr') ?>"
                                title="Ampliar foto" class="w-8 h-8 rounded-lg overflow-hidden shrink-0 cursor-zoom-in"><img src="/assets/uploads/<?= esc($cliente->foto, 'attr') ?>" alt="" class="w-full h-full object-cover"></button>
                        <?php else: ?>
                        <span class="w-8 h-8 rounded-lg bg-sky-500 text-white flex items-center justify-center text-xs shrink-0 shadow-sm shadow-sky-500/30"><i class="fas fa-user"></i></span>
                        <?php endif; ?>
                        <div>
                            <p class="text-sm font-bold text-gray-800"><?= esc($cliente->nombre) ?></p>
                            <p class="text-xs text-gray-400"><?= esc($cliente->email ?: '—') ?></p>
                        </div>
                    </div>
                </td>

                <!-- Código / RUC -->
                <td class="px-5 py-3.5 <?= $cliente->estado !== 'activo' ? 'opacity-50' : '' ?>">
                    <p class="text-xs font-mono text-gray-500"><?= esc($cliente->codigo ?: '—') ?></p>
                    <?php if (!empty($cliente->ruc)): ?>
                    <p class="text-xs text-gray-400">RUC: <?= esc($cliente->ruc) ?></p>
                    <?php endif; ?>
                </td>

                <!-- Contacto -->
                <td class="px-5 py-3.5 text-sm text-gray-600 <?= $cliente->estado !== 'activo' ? 'opacity-50' : '' ?>">
                    <?php if (!empty($cliente->telefono)): ?><i class="fas fa-phone text-gray-300 text-xs mr-1.5"></i><?= esc($cliente->telefono) ?><?php else: ?>—<?php endif; ?>
                </td>

                <!-- Tipo: ícono -->
                <td class="px-5 py-3.5 text-center">
                    <?php if (($cliente->tipo ?? '') === 'juridico'): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap bg-blue-50 text-blue-600" title="Jurídico (empresa)"><i class="fas fa-building text-[10px]"></i> Jurídico</span>
                    <?php else: ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap bg-sky-50 text-sky-600" title="Natural (persona)"><i class="fas fa-user text-[10px]"></i> Natural</span>
                    <?php endif; ?>
                </td>

                <!-- Estado: ícono -->
                <td class="px-5 py-3.5 text-center">
                    <?php if ($cliente->estado === 'activo'): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap bg-emerald-100 text-emerald-600" title="Activo"><i class="fas fa-check text-[10px]"></i> Activo</span>
                    <?php elseif ($cliente->estado === 'eliminado'): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap bg-slate-200 text-slate-600" title="Eliminado (oculto para los demás usuarios)"><i class="fas fa-trash-can text-[10px]"></i> Eliminado</span>
                    <?php else: ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap bg-red-100 text-red-500" title="Inactivo"><i class="fas fa-xmark text-[10px]"></i> Inactivo</span>
                    <?php endif; ?>
                </td>

                <!-- Saldo -->
                <td class="px-5 py-3.5 text-right text-sm font-bold
                    <?= ((float)($cliente->saldo_pendiente ?? 0)) > 0 ? 'text-red-600' : 'text-gray-500' ?>">
                    $<?= number_format((float)($cliente->saldo_pendiente ?? 0), 2) ?>
                </td>

                <!-- Acciones: solo íconos -->
                <td class="px-5 py-3.5">
                    <?php if ($cliente->estado === 'eliminado'): ?>
                    <div class="flex items-center justify-center gap-1.5">
                        <form method="post" action="/clientes/<?= (int) $cliente->cliente_id ?>/restaurar" class="inline"
                              onsubmit="return confirm('¿Restaurar a <?= esc($cliente->nombre, 'js') ?>? Volverá a aparecer en las listas.')">
                            <?= csrf_field() ?>
                            <button type="submit" title="Restaurar cliente (volver a mostrarlo)" aria-label="Restaurar cliente"
                                    class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white transition-colors flex items-center justify-center"><i class="fas fa-rotate-left text-xs"></i></button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="flex items-center justify-center gap-1.5">
                        <a href="/clientes/<?= (int) $cliente->cliente_id ?>" title="Ver ficha del cliente" aria-label="Ver ficha del cliente"
                           class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-500 hover:text-white transition-colors flex items-center justify-center"><i class="fas fa-eye text-xs"></i></a>
                        <a href="/clientes/<?= (int) $cliente->cliente_id ?>/editar" title="Editar cliente" aria-label="Editar cliente"
                           class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-500 hover:text-white transition-colors flex items-center justify-center"><i class="fas fa-pen text-xs"></i></a>
                        <a href="/ventas/nueva?cliente=<?= (int) $cliente->cliente_id ?>" title="Nueva venta a este cliente" aria-label="Nueva venta a este cliente"
                           class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white transition-colors flex items-center justify-center"><i class="fas fa-cart-shopping text-xs"></i></a>
                        <form method="post" action="/clientes/<?= (int) $cliente->cliente_id ?>/eliminar" class="inline"
                              onsubmit="return confirm('¿Eliminar a <?= esc($cliente->nombre, 'js') ?>?')">
                            <?= csrf_field() ?>
                            <button type="submit" title="Eliminar cliente" aria-label="Eliminar cliente"
                                    class="w-8 h-8 rounded-lg bg-slate-100 text-slate-400 hover:bg-red-500 hover:text-white transition-colors flex items-center justify-center"><i class="fas fa-trash text-xs"></i></button>
                        </form>
                    </div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>

            <?php if (empty($clientes)): ?>
            <tr>
                <td colspan="7" class="px-4 py-16 text-center">
                    <div class="inline-flex flex-col items-center gap-2">
                        <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center">
                            <i class="fas fa-users text-gray-300 text-2xl"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-400">No se encontraron clientes</p>
                        <?php if (empty($buscar) && empty($estado_filtro) && empty($tipo_filtro)): ?>
                        <a href="/clientes/nuevo" class="mt-1 text-sky-500 hover:underline text-sm font-medium">
                            Registrar el primer cliente
                        </a>
                        <?php else: ?>
                        <a href="/clientes" class="mt-1 text-gray-400 hover:text-sky-500 text-xs">
                            Limpiar filtros
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (($pagination['total_pages'] ?? 1) > 1): ?>
<div class="mt-4">
    <?php echo view('partials/pagination', ['pagination' => $pagination]); ?>
</div>
<?php endif; ?>

<?php $this->endSection(); ?>

<?php $this->section('extra_js'); ?>
<script>
    // Los filtros de estado/tipo usan lo que hay escrito en el buscador en este momento
    document.querySelectorAll('#search-form [data-filtro]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            var f = document.getElementById('search-form');
            f.elements[a.dataset.filtro].value = a.dataset.valor;
            f.submit();
        });
    });
    var searchTimeout;
    var searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            var spinner = document.getElementById('search-spinner');
            if (e.target.value.length >= 2 || e.target.value.length === 0) {
                if (spinner) spinner.classList.remove('hidden');
                searchTimeout = setTimeout(function() {
                    buscarSinRecargar(document.getElementById('search-form'));
                }, 500);
            }
        });
    }
</script>
<?php $this->endSection(); ?>
