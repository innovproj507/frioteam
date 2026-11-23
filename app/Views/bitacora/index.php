<?php $this->extend('layouts/app'); ?>
<?php $this->section('content'); ?>

<!-- Filter bar -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-4">
    <form method="GET" action="/bitacora" class="flex flex-wrap items-end gap-3">
        <div class="min-w-40">
            <label class="block text-xs font-semibold text-gray-500 mb-1"><i class="fas fa-puzzle-piece mr-1"></i>Módulo</label>
            <select name="modulo" class="w-full py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
                <option value="">Todos</option>
                <?php foreach ($modulos as $mod): ?>
                    <option value="<?= esc($mod) ?>" <?= $filtros['modulo'] === $mod ? 'selected' : '' ?>>
                        <?= esc(ucfirst($mod)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex-1 min-w-40">
            <label class="block text-xs font-semibold text-gray-500 mb-1"><i class="fas fa-user mr-1"></i>Usuario</label>
            <input type="text" name="usuario" value="<?= esc($filtros['usuario']) ?>" placeholder="Nombre de usuario"
                   class="w-full py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
        </div>
        <div class="min-w-36">
            <label class="block text-xs font-semibold text-gray-500 mb-1"><i class="fas fa-calendar-day mr-1"></i>Desde</label>
            <input type="date" name="desde" value="<?= esc($filtros['desde']) ?>"
                   class="w-full py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
        </div>
        <div class="min-w-36">
            <label class="block text-xs font-semibold text-gray-500 mb-1"><i class="fas fa-calendar-check mr-1"></i>Hasta</label>
            <input type="date" name="hasta" value="<?= esc($filtros['hasta']) ?>"
                   class="w-full py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-sky-500 text-white rounded-lg text-sm font-semibold hover:bg-sky-600 transition shadow-sm">
                <i class="fas fa-filter"></i> Filtrar
            </button>
            <a href="/bitacora" class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                <i class="fas fa-xmark"></i> Limpiar
            </a>
        </div>
    </form>
</div>

<!-- Table -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-50">
        <span class="text-xs text-gray-400"><?= number_format($total) ?> eventos</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                    <th class="px-5 py-3.5 bg-gray-50/80"><i class="fas fa-clock mr-1"></i>Fecha</th>
                    <th class="px-5 py-3.5 bg-gray-50/80"><i class="fas fa-user mr-1"></i>Usuario</th>
                    <th class="px-5 py-3.5 bg-gray-50/80"><i class="fas fa-puzzle-piece mr-1"></i>Módulo</th>
                    <th class="px-5 py-3.5 bg-gray-50/80"><i class="fas fa-bolt mr-1"></i>Acción</th>
                    <th class="px-5 py-3.5 bg-gray-50/80"><i class="fas fa-align-left mr-1"></i>Descripción</th>
                    <th class="px-5 py-3.5 bg-gray-50/80"><i class="fas fa-network-wired mr-1"></i>IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="6" class="px-4 py-10 text-center text-gray-400">
                        <i class="fas fa-history text-3xl mb-2 block text-gray-200"></i>
                        No hay eventos registrados
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <?php
                            $moduloIconos = [
                                'auth'        => ['fa-right-to-bracket', 'bg-blue-50 text-blue-600',       'Acceso'],
                                'ventas'      => ['fa-receipt',          'bg-emerald-50 text-emerald-600', 'Ventas'],
                                'compras'     => ['fa-truck-ramp-box',   'bg-orange-50 text-orange-600',   'Compras'],
                                'productos'   => ['fa-box',              'bg-sky-50 text-sky-600',         'Productos'],
                                'inventario'  => ['fa-warehouse',        'bg-teal-50 text-teal-600',       'Inventario'],
                                'usuarios'    => ['fa-users',            'bg-rose-50 text-rose-600',       'Usuarios'],
                                'conteos'     => ['fa-clipboard-check',  'bg-amber-50 text-amber-600',     'Conteos'],
                            ];
                            [$modIco, $modCol, $modTxt] = $moduloIconos[$log['modulo']] ?? ['fa-circle-dot', 'bg-gray-100 text-gray-500', ucfirst((string) $log['modulo'])];
                        ?>
                        <tr class="hover:bg-sky-50/40 transition-colors">
                            <td class="px-5 py-3.5 text-gray-400 text-xs whitespace-nowrap">
                                <?= date('d/m/Y H:i', strtotime($log['fecha_registro'])) ?>
                            </td>
                            <td class="px-5 py-3.5 font-bold text-gray-800">
                                <?= esc($log['username'] ?? '—') ?>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap <?= $modCol ?>" title="<?= esc($modTxt) ?>"><i class="fas <?= $modIco ?> text-[10px]"></i> <?= esc($modTxt) ?></span>
                            </td>
                            <td class="px-5 py-3.5 font-mono text-xs text-gray-600">
                                <?= esc($log['accion']) ?>
                            </td>
                            <td class="px-5 py-3.5 text-gray-600 max-w-xs truncate" title="<?= esc($log['descripcion']) ?>">
                                <?= esc($log['descripcion']) ?>
                            </td>
                            <td class="px-5 py-3.5 font-mono text-xs text-gray-400">
                                <?= esc($log['ip'] ?? '') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_paginas > 1): ?>
    <div class="px-4 py-3 border-t border-gray-50 flex items-center justify-between">
        <span class="text-xs text-gray-400">Página <?= $pagina ?> de <?= $total_paginas ?></span>
        <div class="flex gap-1">
            <?php if ($pagina > 1): ?>
                <a href="?<?= http_build_query(array_merge($filtros, ['page' => $pagina - 1])) ?>"
                   class="px-3 py-1 rounded-lg bg-gray-100 hover:bg-gray-200 text-xs">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>
            <?php for ($p = max(1, $pagina - 2); $p <= min($total_paginas, $pagina + 2); $p++): ?>
                <a href="?<?= http_build_query(array_merge($filtros, ['page' => $p])) ?>"
                   class="px-3 py-1 rounded-lg text-xs <?= $p === $pagina ? 'bg-sky-500 text-white' : 'bg-gray-100 hover:bg-gray-200' ?>">
                    <?= $p ?>
                </a>
            <?php endfor; ?>
            <?php if ($pagina < $total_paginas): ?>
                <a href="?<?= http_build_query(array_merge($filtros, ['page' => $pagina + 1])) ?>"
                   class="px-3 py-1 rounded-lg bg-gray-100 hover:bg-gray-200 text-xs">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php $this->endSection(); ?>
