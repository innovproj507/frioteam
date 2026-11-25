<?php
/**
 * Detalle de un traslado para mostrarlo en una ventana (fragmento sin layout).
 * @var array $t     traslado con origen_nombre, destino_nombre, envia_*, recibe_*
 * @var array $items productos del traslado
 */
$estados = [
    'recibido'    => ['bg-emerald-100 text-emerald-700', 'fa-check',        'Recibido'],
    'en_transito' => ['bg-amber-100 text-amber-700',     'fa-truck-moving', 'En tránsito'],
    'pendiente'   => ['bg-gray-100 text-gray-600',       'fa-clock',        'Pendiente'],
    'cancelado'   => ['bg-red-100 text-red-600',         'fa-ban',          'Cancelado'],
];
[$cls, $ico, $txt] = $estados[$t['estado']] ?? ['bg-gray-100 text-gray-500', 'fa-circle-question', ucfirst(str_replace('_', ' ', (string) $t['estado']))];
$quien  = fn (?string $nom, ?string $usr) => trim((string) $nom) !== '' ? trim((string) $nom) : (string) $usr;
$notas  = trim((string) ($t['notas'] ?? ''));
$n      = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ','), '0'), '.');
$totalU = array_sum(array_map(fn ($i) => (float) $i['cantidad'], $items));
$th     = 'px-3 py-2.5 font-bold text-gray-900 border border-gray-200';
$td     = 'px-3 py-2.5 border border-gray-200';
?>
<div class="px-7 pt-6 pb-4">
    <div class="flex items-start justify-between gap-3 mb-4 pr-8">
        <div>
            <h2 class="text-xl font-bold text-gray-900"><i class="fas fa-right-left text-sky-500 mr-2"></i>Traslado #<?= (int) $t['traslado_id'] ?></h2>
            <p class="text-sm text-gray-500 mt-0.5"><?= esc($t['origen_nombre']) ?> <i class="fas fa-arrow-right text-gray-300 text-xs mx-1"></i> <?= esc($t['destino_nombre']) ?></p>
        </div>
        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap <?= $cls ?>"><i class="fas <?= $ico ?> text-[10px]"></i> <?= esc($txt) ?></span>
    </div>

    <table class="w-full text-sm border border-gray-200 border-collapse mb-4">
        <tbody>
            <tr><td class="w-[30%] <?= $td ?> text-gray-600">Enviado</td>
                <td class="<?= $td ?>"><?= date('d/m/Y H:i', strtotime((string) $t['fecha_envio'])) ?> · <?= esc($quien($t['envia_nombre'], $t['envia_usuario']) ?: '—') ?></td></tr>
            <?php if (!empty($t['fecha_recepcion'])): ?>
            <tr><td class="<?= $td ?> text-gray-600">Recibido</td>
                <td class="<?= $td ?>"><?= date('d/m/Y H:i', strtotime((string) $t['fecha_recepcion'])) ?> · <?= esc($quien($t['recibe_nombre'], $t['recibe_usuario']) ?: '—') ?></td></tr>
            <?php endif; ?>
            <?php if ($notas !== ''): ?>
            <tr><td class="<?= $td ?> text-gray-600 align-top">Notas</td>
                <td class="<?= $td ?>"><?= nl2br(esc($notas)) ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <table class="w-full text-sm border border-gray-200 border-collapse">
        <thead>
            <tr>
                <th class="w-12 <?= $th ?> text-center">No</th>
                <th class="<?= $th ?> text-left"><i class="fas fa-box mr-1"></i>Producto</th>
                <th class="w-36 <?= $th ?> text-center"><i class="fas fa-location-dot mr-1"></i>Ubic. <?= esc($t['origen_nombre']) ?></th>
                <th class="w-36 <?= $th ?> text-center"><i class="fas fa-location-dot mr-1"></i>Ubic. <?= esc($t['destino_nombre']) ?></th>
                <th class="w-24 <?= $th ?> text-center">Cantidad</th>
            </tr>
        </thead>
        <tbody>
        <?php $i = 1; foreach ($items as $it): ?>
            <tr>
                <td class="<?= $td ?> text-center"><?= $i++ ?></td>
                <td class="<?= $td ?>"><?= esc($it['nombre']) ?> <span class="font-mono text-xs font-bold text-gray-600">(<?= esc($it['codigo']) ?>)</span></td>
                <td class="<?= $td ?> text-center font-mono text-xs font-semibold text-gray-700"><?= $it['ubicacion_origen'] !== '' ? esc($it['ubicacion_origen']) : '<span class="text-gray-300">—</span>' ?></td>
                <td class="<?= $td ?> text-center font-mono text-xs font-semibold text-gray-700"><?= $it['ubicacion_destino'] !== '' ? esc($it['ubicacion_destino']) : '<span class="text-gray-300">—</span>' ?></td>
                <td class="<?= $td ?> text-center font-bold"><?= $n($it['cantidad']) ?>
                    <?php if ($t['estado'] === 'recibido' && (float) $it['cantidad_recibida'] != (float) $it['cantidad']): ?><span class="block text-[10px] font-normal text-amber-600">recibidas: <?= $n($it['cantidad_recibida']) ?></span><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?>
            <tr><td colspan="5" class="<?= $td ?> text-center text-gray-400 py-6">Este traslado no tiene productos.</td></tr>
        <?php endif; ?>
            <tr class="bg-gray-50">
                <td colspan="4" class="<?= $td ?> text-right font-bold">Total de unidades</td>
                <td class="<?= $td ?> text-center font-bold"><?= $n($totalU) ?></td>
            </tr>
        </tbody>
    </table>
</div>

<div class="flex flex-wrap items-center justify-end gap-2 px-7 py-4 border-t border-gray-100 bg-gray-50/70 rounded-b-2xl">
    <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50"><i class="fas fa-print"></i> Imprimir</button>
    <button type="button" onclick="cerrarTraslado()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-white">Cerrar</button>
</div>
