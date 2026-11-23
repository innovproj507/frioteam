<?php
/**
 * Historial de ventas o de compras de un producto.
 * @var string $tipo 'ventas' | 'compras'
 * @var array $producto $filas $resumen
 * @var int $productoId
 * @var string $desde $hasta
 */
$this->extend('layouts/app');
$this->section('content');

$esVentas = $tipo === 'ventas';
$nombre   = (string) ($producto['nombre'] ?? '');
$codigo   = (string) ($producto['codigo'] ?? '');
$m        = fn ($v) => '$' . number_format((float) $v, 2);
$n        = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ','), '0'), '.');
$pill     = fn (string $txt, string $cls, string $ico) => '<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap ' . $cls . '"><i class="fas ' . $ico . ' text-[10px]"></i> ' . $txt . '</span>';
$th       = 'px-4 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider';
?>
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="/inventario" class="hover:text-gray-600">Productos</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <a href="/inventario/<?= (int) $productoId ?>" class="hover:text-gray-600"><?= esc($nombre) ?></a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-700 font-medium"><?= $esVentas ? 'Ventas' : 'Compras' ?></span>
    </div>
    <div class="flex gap-2">
        <a href="/inventario/<?= (int) $productoId ?>" class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50"><i class="fas fa-arrow-left"></i> Volver al producto</a>
        <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50"><i class="fas fa-print"></i> Imprimir</button>
    </div>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-4">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-lg font-bold text-gray-800"><i class="fas <?= $esVentas ? 'fa-cash-register text-emerald-500' : 'fa-truck-ramp-box text-amber-500' ?> mr-1.5"></i> Historial de <?= $esVentas ? 'ventas' : 'compras' ?></p>
            <p class="text-sm text-gray-500"><?= esc($nombre) ?><?= $codigo !== '' ? ' · <span class="font-mono">' . esc($codigo) . '</span>' : '' ?></p>
        </div>
        <form method="get" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1"><i class="fas fa-calendar-day mr-1"></i>Desde</label>
                <input type="date" name="desde" value="<?= esc($desde, 'attr') ?>" class="py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1"><i class="fas fa-calendar-day mr-1"></i>Hasta</label>
                <input type="date" name="hasta" value="<?= esc($hasta, 'attr') ?>" class="py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
            </div>
            <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-sky-500 text-white rounded-lg text-sm font-semibold hover:bg-sky-600"><i class="fas fa-filter"></i> Filtrar</button>
        </form>
    </div>
    <p class="mt-3 text-sm text-gray-600">
        <b><?= (int) $resumen['movimientos'] ?></b> <?= $esVentas ? 'ventas' : 'compras' ?> ·
        <b><?= $n($resumen['unidades']) ?></b> unidades <?= $esVentas ? 'vendidas (sin anuladas ni devueltas)' : 'compradas (sin canceladas)' ?> ·
        <b><?= $m($resumen['monto']) ?></b>
        <?php if (count($filas) >= 500): ?><span class="text-amber-600">· se muestran las 500 más recientes; usa las fechas para acotar</span><?php endif; ?>
    </p>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr class="text-left">
                    <?php if ($esVentas): ?>
                    <th class="<?= $th ?>"><i class="fas fa-calendar mr-1"></i>Fecha</th>
                    <th class="<?= $th ?>"><i class="fas fa-file-invoice mr-1"></i>Factura</th>
                    <th class="<?= $th ?>"><i class="fas fa-user mr-1"></i>Cliente</th>
                    <th class="<?= $th ?>"><i class="fas fa-user-tag mr-1"></i>Facturador</th>
                    <th class="<?= $th ?>"><i class="fas fa-warehouse mr-1"></i>Depósito</th>
                    <th class="<?= $th ?> text-right">Cantidad</th>
                    <th class="<?= $th ?> text-right">Precio</th>
                    <th class="<?= $th ?> text-right">Total</th>
                    <th class="<?= $th ?> text-center"><i class="fas fa-toggle-on mr-1"></i>Estado</th>
                    <?php else: ?>
                    <th class="<?= $th ?>"><i class="fas fa-calendar mr-1"></i>Fecha</th>
                    <th class="<?= $th ?>"><i class="fas fa-file-lines mr-1"></i>Compra</th>
                    <th class="<?= $th ?>"><i class="fas fa-truck mr-1"></i>Proveedor</th>
                    <th class="<?= $th ?>"><i class="fas fa-warehouse mr-1"></i>Depósito</th>
                    <th class="<?= $th ?> text-right">Cantidad</th>
                    <th class="<?= $th ?> text-right">Costo</th>
                    <th class="<?= $th ?> text-right">Total</th>
                    <th class="<?= $th ?> text-center"><i class="fas fa-toggle-on mr-1"></i>Estado</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
            <?php foreach ($filas as $f): ?>
                <?php if ($esVentas):
                    $anulada = $f['estado'] === 'anulada'; ?>
                <tr class="hover:bg-gray-50/50 <?= $anulada ? 'opacity-60' : '' ?>">
                    <td class="px-4 py-2.5 whitespace-nowrap text-gray-600"><?= date('d/m/Y H:i', strtotime((string) $f['fecha'])) ?></td>
                    <td class="px-4 py-2.5 whitespace-nowrap"><a href="/ventas/venta/<?= (int) $f['venta_id'] ?>" class="font-bold text-sky-600 hover:underline"><?= esc($f['numero_factura']) ?></a></td>
                    <td class="px-4 py-2.5 text-gray-800"><?= esc($f['cliente'] ?: 'Consumidor Final') ?></td>
                    <td class="px-4 py-2.5 whitespace-nowrap text-gray-600"><?= $f['origen'] === 'pos' && !empty($f['facturador']) ? esc($f['facturador']) : '<span class="text-gray-300">—</span>' ?></td>
                    <td class="px-4 py-2.5 whitespace-nowrap text-gray-600"><?= esc($f['deposito'] ?? '—') ?></td>
                    <td class="px-4 py-2.5 text-right font-semibold"><?= $n($f['cantidad']) ?><?= (float) $f['cantidad_devuelta'] > 0 ? ' <span class="text-xs text-rose-500" title="Devueltas">(-' . $n($f['cantidad_devuelta']) . ')</span>' : '' ?></td>
                    <td class="px-4 py-2.5 text-right text-gray-600"><?= $m($f['precio']) ?></td>
                    <td class="px-4 py-2.5 text-right font-bold <?= $anulada ? 'line-through text-gray-400' : 'text-gray-900' ?>"><?= $m($f['total_linea']) ?></td>
                    <td class="px-4 py-2.5 text-center"><?php
                        echo match ($f['estado']) {
                            'anulada'   => $pill('Anulada', 'bg-red-100 text-red-600', 'fa-ban'),
                            'pendiente' => $pill('Pendiente', 'bg-amber-100 text-amber-700', 'fa-clock'),
                            default     => $pill('Pagada', 'bg-emerald-100 text-emerald-700', 'fa-check'),
                        }; ?></td>
                </tr>
                <?php else:
                    $cancelada = $f['estado'] === 'cancelada'; ?>
                <tr class="hover:bg-gray-50/50 <?= $cancelada ? 'opacity-60' : '' ?>">
                    <td class="px-4 py-2.5 whitespace-nowrap text-gray-600"><?= date('d/m/Y', strtotime((string) $f['fecha_compra'])) ?></td>
                    <td class="px-4 py-2.5 whitespace-nowrap"><a href="/compras/<?= (int) $f['compra_id'] ?>" class="font-bold text-sky-600 hover:underline"><?= esc($f['numero_factura'] ?: '#' . $f['compra_id']) ?></a><?= !empty($f['numero_factura_proveedor']) ? '<div class="text-xs text-gray-400">Fact. prov. ' . esc($f['numero_factura_proveedor']) . '</div>' : '' ?></td>
                    <td class="px-4 py-2.5 text-gray-800"><?= esc($f['proveedor'] ?: '—') ?></td>
                    <td class="px-4 py-2.5 whitespace-nowrap text-gray-600"><?= esc($f['deposito'] ?? '—') ?></td>
                    <td class="px-4 py-2.5 text-right font-semibold"><?= $n($f['cantidad']) ?></td>
                    <td class="px-4 py-2.5 text-right text-gray-600"><?= $m($f['costo']) ?></td>
                    <td class="px-4 py-2.5 text-right font-bold <?= $cancelada ? 'line-through text-gray-400' : 'text-gray-900' ?>"><?= $m($f['total_linea']) ?></td>
                    <td class="px-4 py-2.5 text-center"><?php
                        echo match ($f['estado']) {
                            'recibida'              => $pill('Recibida', 'bg-emerald-100 text-emerald-700', 'fa-check'),
                            'parcialmente_recibida' => $pill('Parcial', 'bg-sky-100 text-sky-700', 'fa-circle-half-stroke'),
                            'cancelada'             => $pill('Cancelada', 'bg-red-100 text-red-600', 'fa-ban'),
                            default                 => $pill('Pendiente', 'bg-amber-100 text-amber-700', 'fa-clock'),
                        }; ?></td>
                </tr>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if (!$filas): ?>
                <tr><td colspan="9" class="px-4 py-10 text-center text-sm text-gray-400"><i class="fas fa-inbox text-2xl text-gray-300 block mb-2"></i>Este producto no tiene <?= $esVentas ? 'ventas' : 'compras' ?> en este periodo.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection(); ?>
