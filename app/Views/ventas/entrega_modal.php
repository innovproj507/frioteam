<?php
/**
 * Detalle de una entrega para mostrar dentro de una ventana (modal), como en el sistema anterior.
 * Es un fragmento (sin layout): lo pide por fetch la pantalla y lo inserta en el modal.
 *
 * @var array $entrega $items $empresa $depositos
 * @var bool $bodega  hoja para bodega: muestra la existencia de cada depósito
 * @var string $preparado
 * @var bool $cups
 */
$nombre = (string) (($empresa['razon_social'] ?? '') ?: ($empresa['nombre_comercial'] ?? ''));
$ruc    = trim((string) ($empresa['ruc'] ?? ''));
if ($ruc !== '' && !empty($empresa['dv'])) $ruc .= ' DV' . $empresa['dv'];
$web    = strtoupper((string) preg_replace('#^https?://#i', '', trim((string) ($empresa['sitio_web'] ?? ''))));
$nota   = trim((string) ($entrega['nota'] ?? ''));
$id     = (int) $entrega['entrega_id'];
$bodega = !empty($bodega);
$qs     = $bodega ? '?bodega=1' : '';
?>
<div class="px-7 pt-6 pb-4">

    <div class="text-center mb-5">
        <h2 class="text-2xl text-gray-900 tracking-wide"><?= esc($nombre) ?></h2>
        <p class="text-[11px] text-gray-700 leading-tight">REFRIGERACIÓN Y AIRE ACONDICIONADO</p>
        <?php if ($ruc !== ''): ?><p class="text-sm font-medium text-gray-900">RUC: <?= esc($ruc) ?></p><?php endif; ?>
        <?php if ($web !== ''): ?><p class="text-[9px] text-gray-700"><?= esc($web) ?></p><?php endif; ?>
        <?php if (!empty($bodega)): ?><p class="text-xs font-bold text-gray-900 mt-1.5 tracking-wide">HOJA PARA BODEGA</p><?php endif; ?>
    </div>

    <table class="w-full text-sm border border-gray-200 border-collapse">
        <tbody>
            <tr><td class="w-[30%] px-4 py-2.5 border border-gray-200 text-gray-700">Fecha</td>
                <td class="px-4 py-2.5 border border-gray-200"><?= date('d/m/Y H:i', strtotime((string) $entrega['fecha'])) ?></td></tr>
            <tr><td class="px-4 py-2.5 border border-gray-200 text-gray-700">Entrega de Referencia</td>
                <td class="px-4 py-2.5 border border-gray-200 font-semibold"><?= esc($entrega['numero']) ?></td></tr>
            <tr><td class="px-4 py-2.5 border border-gray-200 text-gray-700">Referencia Venta</td>
                <td class="px-4 py-2.5 border border-gray-200"><a class="text-sky-600 hover:underline" href="/ventas/venta/<?= (int) $entrega['venta_id'] ?>"><?= esc($entrega['numero_factura']) ?></a></td></tr>
            <tr><td class="px-4 py-2.5 border border-gray-200 text-gray-700">Cliente</td>
                <td class="px-4 py-2.5 border border-gray-200"><?= esc($entrega['cliente']) ?></td></tr>
            <tr><td class="px-4 py-2.5 border border-gray-200 text-gray-700 align-top">Dirección</td>
                <td class="px-4 py-2.5 border border-gray-200"><?= nl2br(esc((string) $entrega['direccion'])) ?></td></tr>
            <?php if ($nota !== ''): ?>
            <tr><td class="px-4 py-2.5 border border-gray-200 text-gray-700 align-top">Nota</td>
                <td class="px-4 py-2.5 border border-gray-200"><?= nl2br(esc($nota)) ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <table class="w-full text-sm border border-gray-200 border-collapse mt-5">
        <thead>
            <tr class="text-gray-900">
                <th class="w-14 px-3 py-2.5 font-bold text-center border border-gray-200">No</th>
                <th class="px-3 py-2.5 font-bold text-center border border-gray-200">Descripción</th>
                <?php foreach (($depositos ?? []) as $d): ?>
                <th class="w-28 px-3 py-2.5 font-bold text-center border border-gray-200"><?= esc(mb_strtolower((string) $d['nombre'])) ?></th>
                <?php endforeach; ?>
                <th class="w-28 px-3 py-2.5 font-bold text-center border border-gray-200">Cantidad</th>
            </tr>
        </thead>
        <tbody>
        <?php $n = 1; foreach ($items as $it): ?>
            <tr>
                <td class="px-3 py-2.5 text-center border border-gray-200"><?= $n++ ?></td>
                <td class="px-3 py-2.5 border border-gray-200"><?= esc($it['nombre']) ?><?= $it['codigo'] !== null && $it['codigo'] !== '' ? ' (' . esc($it['codigo']) . ')' : '' ?></td>
                <?php foreach (($depositos ?? []) as $d): $st = (float) ($it['stock'][(int) $d['deposito_id']]['existencia'] ?? 0); $ub = (string) ($it['stock'][(int) $d['deposito_id']]['ubicacion'] ?? ''); ?><td class="px-3 py-2 text-center border border-gray-200 leading-tight"><?php if ($bodega && $st > 0): ?><span class="font-semibold"><?= esc(number_format($st, 2, '.', '')) ?></span><?php if ($ub !== ''): ?><span class="block text-[11px] text-gray-500"><?= esc($ub) ?></span><?php endif; ?><?php endif; ?></td><?php endforeach; ?>
                <td class="px-3 py-2.5 text-center border border-gray-200"><?= esc(number_format((float) $it['cantidad'], 2, '.', '')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="grid grid-cols-3 gap-8 mt-7 text-sm text-gray-800">
        <div><p>Preparado por: <?= esc($preparado) ?></p><div class="h-14 border-b border-gray-500"></div><p class="mt-1">Firma</p></div>
        <div><p>Entregado por:</p><div class="h-14 border-b border-gray-500"></div><p class="mt-1">Firma</p></div>
        <div><p>Recibido por:</p><div class="h-14 border-b border-gray-500"></div><p class="mt-1">Firma</p></div>
    </div>
</div>

<div class="flex flex-wrap items-center justify-end gap-2 px-7 py-4 border-t border-gray-100 bg-gray-50/70 rounded-b-2xl">
    <?php if ($bodega): ?>
    <button type="button" onclick="verEntrega(<?= $id ?>)" class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 mr-auto" title="Ver la copia para el cliente">
        <i class="fas fa-user text-sky-500"></i> Para el cliente
    </button>
    <?php else: ?>
    <button type="button" onclick="verEntrega(<?= $id ?>, true)" class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 mr-auto" title="Hoja con la existencia de cada depósito, para entregar a bodega">
        <i class="fas fa-warehouse text-amber-500"></i> Para bodega
    </button>
    <?php endif; ?>
    <a href="/entregas/<?= $id ?>/pdf<?= $qs ?>" class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
        <i class="fas fa-file-pdf text-red-500"></i> PDF
    </a>
    <?php if (!empty($cups)): ?>
    <button type="button" onclick="imprimirEntregaModal(<?= $id ?>, this, <?= $bodega ? 'true' : 'false' ?>)" class="inline-flex items-center gap-1.5 px-4 py-2 bg-sky-600 text-white rounded-lg text-sm font-semibold hover:bg-sky-700">
        <i class="fas fa-print"></i> Imprimir
    </button>
    <?php else: ?>
    <a href="/entregas/<?= $id ?><?= $qs ?>" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 bg-sky-600 text-white rounded-lg text-sm font-semibold hover:bg-sky-700">
        <i class="fas fa-print"></i> Imprimir
    </a>
    <?php endif; ?>
    <button type="button" onclick="cerrarEntregaModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-white">Cerrar</button>
</div>
