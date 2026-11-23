<?php $this->extend('layouts/app'); ?>
<?php $this->section('content'); ?>

<?php $s = $stats ?? []; $depositos = $depositos ?? []; $pagination = $pagination ?? []; ?>

<!-- Barra de filtros -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-3 mb-4">
    <form method="GET" id="search-form" class="flex flex-wrap items-end gap-3">

        <div class="flex-1 min-w-48">
            <label class="block text-xs font-semibold text-gray-500 mb-1"><i class="fas fa-magnifying-glass mr-1"></i>Buscar</label>
            <div class="relative">
                <i class="fas fa-search absolute left-0 top-2 text-gray-400 text-xs"></i>
                <input type="text" name="buscar" id="search-input"
                    value="<?= esc($buscar ?? '') ?>"
                    placeholder="Nombre o código..."
                    class="w-full pl-5 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
                <div id="search-spinner" class="absolute right-0 top-2 hidden">
                    <i class="fas fa-spinner fa-spin text-sky-500 text-xs"></i>
                </div>
            </div>
        </div>

        <div class="min-w-28">
            <label class="block text-xs font-semibold text-gray-500 mb-1"><i class="fas fa-list-ol mr-1"></i>Por página</label>
            <select name="por_pagina" onchange="this.form.submit()"
                class="w-full py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none appearance-none">
                <?php foreach ([10, 25, 50, 100] as $n): ?>
                <option value="<?= $n ?>" <?= (($pagination['per_page'] ?? 25) == $n) ? 'selected' : '' ?>><?= $n ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex gap-2">
            <?php if (!empty($buscar)): ?>
            <a href="/inventario/depositos"
                class="inline-flex items-center gap-1.5 px-3 py-2 border border-gray-200 text-gray-500 rounded-lg text-sm hover:bg-gray-50 transition"
                title="Quitar la búsqueda">
                <i class="fas fa-xmark"></i> Limpiar
            </a>
            <?php endif; ?>
            <a href="/inventario/depositos/nuevo"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-sky-500 text-white rounded-lg text-sm font-semibold hover:bg-sky-600 transition shadow-sm">
                <i class="fas fa-plus"></i> Nuevo Depósito
            </a>
        </div>
    </form>
</div>

<!-- Tabla -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

    <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
        <span class="text-xs font-medium text-gray-500">
            <?= number_format($pagination['total'] ?? 0) ?> depósito(s)
            <?php if (!empty($buscar)): ?>
            <span class="ml-1 text-sky-500">— filtrado</span>
            <?php endif; ?>
        </span>
        <span class="text-xs text-gray-400">
            Pág. <?= $pagination['current_page'] ?? 1 ?> / <?= $pagination['total_pages'] ?? 1 ?>
        </span>
    </div>

    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-100 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">
                <th class="px-4 py-3 bg-white"><i class="fas fa-warehouse mr-1"></i>Depósito</th>
                <th class="px-4 py-3 bg-white"><i class="fas fa-align-left mr-1"></i>Descripción</th>
                <th class="px-4 py-3 bg-white text-center"><i class="fas fa-box mr-1"></i>Productos</th>
                <th class="px-4 py-3 bg-white text-center"><i class="fas fa-cubes mr-1"></i>Stock</th>
                <th class="px-4 py-3 bg-white text-center"><i class="fas fa-toggle-on mr-1"></i>Estado</th>
                <th class="px-4 py-3 bg-white text-center">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            <?php foreach ($depositos as $dep): ?>
            <?php $activo = ($dep['estado'] ?? 'activo') === 'activo'; ?>
            <tr class="hover:bg-sky-50/40 transition-colors group">

                <!-- Nombre + código -->
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                            <i class="fas fa-warehouse text-xs"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-semibold text-gray-800 group-hover:text-sky-700 transition-colors">
                                    <?= esc($dep['nombre']) ?>
                                </span>
                                <?php if (!empty($dep['es_principal'])): ?>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700" title="Depósito principal"><i class="fas fa-star"></i> Principal</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-gray-400 font-mono mt-0.5"><?= esc($dep['codigo']) ?></p>
                        </div>
                    </div>
                </td>

                <!-- Descripción -->
                <td class="px-4 py-3 text-xs text-gray-500 max-w-xs">
                    <span class="line-clamp-1"><?= esc($dep['descripcion'] ?? '—') ?></span>
                </td>

                <!-- Productos -->
                <td class="px-4 py-3 text-center">
                    <?php $totalProd = (int)($dep['total_productos'] ?? 0); ?>
                    <?php if ($totalProd > 0): ?>
                    <span class="inline-flex items-center justify-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 min-w-[2rem]" title="<?= (int) $totalProd ?> productos" aria-label="<?= (int) $totalProd ?> productos">
                        <i class="fas fa-box text-[10px]"></i><?= $totalProd ?>
                    </span>
                    <?php else: ?>
                    <span class="text-gray-300 text-xs">0</span>
                    <?php endif; ?>
                </td>

                <!-- Stock -->
                <td class="px-4 py-3 text-center">
                    <?php $stock = (float)($dep['stock_total'] ?? 0); ?>
                    <span class="text-xs font-semibold <?= $stock > 0 ? 'text-gray-700' : 'text-gray-300' ?>">
                        <?= number_format($stock) ?>
                    </span>
                </td>

                <!-- Estado -->
                <td class="px-4 py-3 text-center">
                    <?php if ($activo): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap bg-emerald-100 text-emerald-600" title="Activo"><i class="fas fa-check text-[10px]"></i> Activo</span>
                    <?php else: ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap bg-red-100 text-red-500" title="Inactivo"><i class="fas fa-xmark text-[10px]"></i> Inactivo</span>
                    <?php endif; ?>
                </td>

                <!-- Acciones -->
                <td class="px-4 py-3 text-center">
                    <div class="flex items-center justify-center gap-1.5">
                        <a href="/inventario/depositos/<?= $dep['deposito_id'] ?>"
                            class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-500 hover:text-white transition-colors flex items-center justify-center" title="Ver el inventario del depósito" aria-label="Ver el inventario del depósito">
                            <i class="fas fa-boxes text-xs"></i>
                        </a>
                        <a href="/inventario/depositos/<?= $dep['deposito_id'] ?>/editar"
                            class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-500 hover:text-white transition-colors flex items-center justify-center" title="Editar depósito" aria-label="Editar depósito">
                            <i class="fas fa-pen text-xs"></i>
                        </a>
                        <?php if ((int)($dep['stock_total'] ?? 0) === 0): ?>
                        <form action="/inventario/depositos/<?= $dep['deposito_id'] ?>/eliminar" method="POST"
                            class="inline" onsubmit="return confirm('¿Eliminar el depósito «<?= esc(addslashes($dep['nombre'])) ?>»?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-400 hover:bg-red-500 hover:text-white transition-colors flex items-center justify-center" title="Eliminar depósito" aria-label="Eliminar depósito">
                                <i class="fas fa-trash text-xs"></i>
                            </button>
                        </form>
                        <?php else: ?>
                        <span class="w-8 h-8 rounded-lg bg-gray-100 text-gray-300 cursor-not-allowed flex items-center justify-center" title="No se puede eliminar: tiene stock, traslade primero" aria-label="No se puede eliminar: tiene stock">
                            <i class="fas fa-trash text-xs"></i>
                        </span>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>

            <?php if (empty($depositos)): ?>
            <tr>
                <td colspan="6" class="px-4 py-16 text-center">
                    <div class="inline-flex flex-col items-center gap-2">
                        <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center">
                            <i class="fas fa-warehouse text-gray-300 text-2xl"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-400">No se encontraron depósitos</p>
                        <?php if (empty($buscar)): ?>
                        <a href="/inventario/depositos/nuevo"
                            class="mt-1 text-sky-500 hover:underline text-sm font-medium">
                            Crear el primer depósito
                        </a>
                        <?php else: ?>
                        <a href="/inventario/depositos" class="mt-1 text-gray-400 hover:text-sky-500 text-xs">
                            Limpiar búsqueda
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
    var searchTimeout;
    var searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            var spinner = document.getElementById('search-spinner');
            if (e.target.value.length >= 2 || e.target.value.length === 0) {
                spinner.classList.remove('hidden');
                searchTimeout = setTimeout(function() {
                    buscarSinRecargar(document.getElementById('search-form'));
                }, 500);
            }
        });
    }
</script>
<?php $this->endSection(); ?>
