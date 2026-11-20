<?php
/**
 * Detalle de una entrega para mostrar dentro de una ventana (modal), como en el sistema anterior.
 * Es un fragmento (sin layout): lo pide por fetch la pantalla y lo inserta en el modal.
 *
 * @var array $entrega $items $empresa
 * @var string $preparado
 * @var bool $cups
 */
$nombre = (string) (($empresa['razon_social'] ?? '') ?: ($empresa['nombre_comercial'] ?? ''));
$ruc    = trim((string) ($empresa['ruc'] ?? ''));
if ($ruc !== '' && !empty($empresa['dv'])) $ruc .= ' DV' . $empresa['dv'];
$web    = strtoupper((string) preg_replace('#^https?://#i', '', trim((string) ($empresa['sitio_web'] ?? ''))));
$nota   = trim((string) ($entrega['nota'] ?? ''));
$id     = (int) $entrega['entrega_id'];
?>
<div class="px-7 pt-6 pb-4">

    <div class="text-center mb-5">
        <h2 class="text-2xl text-gray-900 tracking-wide"><?= esc($nombre) ?></h2>
        <p class="text-[11px] text-gray-700 leading-tight">REFRIGERACIÓN Y AIRE ACONDICIONADO</p>
        <?php if ($ruc !== ''): ?><p class="text-sm font-medium text-gray-900">RUC: <?= esc($ruc) ?></p><?php endif; ?>
        <?php if ($web !== ''): ?><p class="text-[9px] text-gray-700"><?= esc($web) ?></p><?php endif; ?>
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

    <h3 class="text-lg text-gray-800 mt-5 mb-2">Artículos</h3>
    <table class="w-full text-sm border-collapse">
        <thead>
            <tr class="text-white" style="background:#3d86c6;">
                <th class="w-14 px-3 py-3 font-bold text-center border border-white/30">No</th>
                <th class="px-3 py-3 font-bold text-center border border-white/30">Descripción</th>
                <th class="w-28 px-3 py-3 font-bold text-center border border-white/30">Cantidad</th>
            </tr>
        </thead>
        <tbody>
        <?php $n = 1; foreach ($items as $it): ?>
            <tr class="<?= $n % 2 === 0 ? 'bg-gray-50' : 'bg-white' ?>">
                <td class="px-3 py-3 text-center border border-gray-200"><?= $n++ ?></td>
                <td class="px-3 py-3 border border-gray-200"><?= esc($it['nombre']) ?><?= $it['codigo'] !== null && $it['codigo'] !== '' ? ' (' . esc($it['codigo']) . ')' : '' ?></td>
                <td class="px-3 py-3 text-center border border-gray-200"><?= esc(number_format((float) $it['cantidad'], 2, '.', '')) ?></td>
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
    <a href="/entregas/<?= $id ?>/pdf" class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
        <i class="fas fa-file-pdf text-red-500"></i> PDF
    </a>
    <?php if (!empty($cups)): ?>
    <button type="button" onclick="imprimirEntregaModal(<?= $id ?>, this)" class="inline-flex items-center gap-1.5 px-4 py-2 bg-sky-600 text-white rounded-lg text-sm font-semibold hover:bg-sky-700">
        <i class="fas fa-print"></i> Imprimir
    </button>
    <?php else: ?>
    <a href="/entregas/<?= $id ?>" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 bg-sky-600 text-white rounded-lg text-sm font-semibold hover:bg-sky-700">
        <i class="fas fa-print"></i> Imprimir
    </a>
    <?php endif; ?>
    <button type="button" onclick="cerrarEntregaModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-white">Cerrar</button>
</div>
