<?php $this->extend('layouts/app'); ?>
<?php $this->section('content'); ?>

<?php $m = fn ($v) => '$' . number_format((float) $v, 2); ?>

<form method="GET" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-3 mb-4 flex flex-wrap items-end gap-3">
    <div><label class="block text-xs font-semibold text-gray-500 mb-1"><i class="fas fa-calendar-day mr-1"></i>Desde</label><input type="date" name="desde" value="<?= esc($desde, 'attr') ?>" class="py-1.5 text-sm border-0 border-b border-gray-200 focus:ring-0 focus:border-sky-400"></div>
    <div><label class="block text-xs font-semibold text-gray-500 mb-1"><i class="fas fa-calendar-check mr-1"></i>Hasta</label><input type="date" name="hasta" value="<?= esc($hasta, 'attr') ?>" class="py-1.5 text-sm border-0 border-b border-gray-200 focus:ring-0 focus:border-sky-400"></div>
    <div><label class="block text-xs font-semibold text-gray-500 mb-1"><i class="fas fa-user mr-1"></i>Cajero</label>
        <select name="usuario" class="py-1.5 text-sm border-0 border-b border-gray-200 focus:ring-0 focus:border-sky-400">
            <option value="">Todos</option>
            <?php foreach ($usuarios as $u): ?><option value="<?= (int) $u['id'] ?>" <?= (int) $usuario === (int) $u['id'] ? 'selected' : '' ?>><?= esc($u['username']) ?></option><?php endforeach; ?>
        </select></div>
    <button class="px-4 py-2 bg-sky-500 text-white rounded-lg text-sm font-semibold hover:bg-sky-600"><i class="fas fa-filter mr-1"></i> Filtrar</button>
</form>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead><tr class="text-xs font-semibold text-gray-500 uppercase tracking-wider text-left bg-gray-50">
            <th class="px-4 py-3"><i class="fas fa-cash-register mr-1"></i>Caja</th><th class="px-4 py-3"><i class="fas fa-store mr-1"></i>Tienda</th><th class="px-4 py-3"><i class="fas fa-user mr-1"></i>Cajero</th><th class="px-4 py-3"><i class="fas fa-lock-open mr-1"></i>Apertura</th><th class="px-4 py-3"><i class="fas fa-lock mr-1"></i>Cierre</th>
            <th class="px-4 py-3 text-right"><i class="fas fa-receipt mr-1"></i>Ventas</th><th class="px-4 py-3 text-right"><i class="fas fa-money-bill-wave mr-1"></i>Efectivo esperado</th><th class="px-4 py-3 text-right"><i class="fas fa-hand-holding-dollar mr-1"></i>Contado</th><th class="px-4 py-3 text-right"><i class="fas fa-scale-balanced mr-1"></i>Diferencia</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-50">
        <?php foreach ($cajas as $c): $d = (float) $c['diferencia_efectivo']; ?>
            <tr class="hover:bg-gray-50 cursor-pointer" onclick="location.href='/cajas/<?= (int) $c['caja_id'] ?>'">
                <td class="px-4 py-2.5 font-semibold text-sky-600">#<?= (int) $c['caja_id'] ?></td>
                <td class="px-4 py-2.5"><?= esc($c['sucursal']) ?></td>
                <td class="px-4 py-2.5"><?= esc($c['cajero']) ?></td>
                <td class="px-4 py-2.5 text-xs"><?= date('d/m/Y H:i', strtotime($c['abierta_en'])) ?></td>
                <td class="px-4 py-2.5 text-xs"><?= $c['estado'] === 'abierta' ? '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700" title="Caja abierta ahora"><i class="fas fa-lock-open text-[10px]"></i> Abierta</span>' : date('d/m/Y H:i', strtotime($c['cerrada_en'])) ?></td>
                <td class="px-4 py-2.5 text-right"><?= $m($c['ventas_total']) ?> <span class="text-xs text-gray-400">(<?= (int) $c['ventas_cantidad'] ?>)</span></td>
                <td class="px-4 py-2.5 text-right"><?= $c['estado'] === 'cerrada' ? $m($c['efectivo_esperado']) : '—' ?></td>
                <td class="px-4 py-2.5 text-right"><?= $c['estado'] === 'cerrada' ? $m($c['efectivo_contado']) : '—' ?></td>
                <td class="px-4 py-2.5 text-right font-bold <?= $c['estado'] !== 'cerrada' ? 'text-gray-300' : (abs($d) < 0.005 ? 'text-emerald-600' : ($d > 0 ? 'text-sky-600' : 'text-red-600')) ?>">
                    <?php if ($c['estado'] !== 'cerrada'): ?>—<?php elseif (abs($d) < 0.005): ?><span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap bg-emerald-100 text-emerald-600" title="Cuadra"><i class="fas fa-check text-[10px]"></i> Cuadra</span><?php else: ?><span title="<?= $d > 0 ? 'Sobrante' : 'Faltante' ?>"><i class="fas <?= $d > 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' ?> text-xs mr-1"></i><?= ($d > 0 ? '+' : '−') . $m(abs($d)) ?></span><?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$cajas): ?><tr><td colspan="9" class="px-4 py-10 text-center text-gray-400">No hay cajas en este periodo.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php $this->endSection(); ?>
