<?php $this->extend('layouts/app'); ?>
<?php $this->section('content'); ?>

<?php
$tipoLabels = [
    'stock_minimo'        => ['label' => 'Stock Mínimo',         'icon' => 'fa-arrow-down',         'color' => 'amber'],
    'stock_exceso'        => ['label' => 'Stock en Exceso',      'icon' => 'fa-arrow-up',            'color' => 'blue'],
    'vencimiento_proximo' => ['label' => 'Próximo a Vencer',     'icon' => 'fa-clock',               'color' => 'orange'],
    'lote_vencido'        => ['label' => 'Lote Vencido',         'icon' => 'fa-skull-crossbones',    'color' => 'red'],
    'rotacion_lenta'      => ['label' => 'Rotación Lenta',       'icon' => 'fa-snooze',              'color' => 'gray'],
];
$prioridadClasses = [
    'critica' => 'bg-red-100 text-red-600',
    'alta'    => 'bg-orange-100 text-orange-600',
    'media'   => 'bg-amber-100 text-amber-600',
    'baja'    => 'bg-gray-100 text-gray-500',
];
$prioridadIconos = ['critica' => 'fa-circle-exclamation', 'alta' => 'fa-triangle-exclamation', 'media' => 'fa-flag', 'baja' => 'fa-flag'];
?>

<!-- Resumen -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <?php foreach (['critica' => ['Críticas','red','fa-fire'], 'alta' => ['Altas','orange','fa-arrow-up'], 'media' => ['Medias','amber','fa-minus'], 'baja' => ['Bajas','gray','fa-arrow-down']] as $p => [$label, $color, $icon]): ?>
    <div class="rounded-2xl p-5 shadow-sm bg-gradient-to-br from-<?= $color ?>-500 to-<?= $color ?>-600 text-white">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <p class="text-xs font-semibold text-white/80"><?= $label ?></p>
                <h3 class="text-xl font-extrabold mt-1"><?= $resumen[$p] ?? 0 ?></h3>
            </div>
            <span class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center shrink-0"><i class="fas <?= $icon ?>"></i></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Acciones globales -->
<?php if (!empty($alertas)): ?>
<div class="flex items-center justify-between mb-4">
    <p class="text-sm text-gray-500"><?= count($alertas) ?> alerta(s) activa(s) — generadas en este momento</p>
    <form method="POST" action="/inventario/alertas/resolver-todas">
        <?= csrf_field() ?>
        <input type="hidden" name="tipo" value="">
        <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition">
            <i class="fas fa-check-double mr-1"></i> Resolver todas
        </button>
    </form>
</div>
<?php endif; ?>

<!-- Lista de alertas agrupadas por tipo -->
<?php
$agrupadas = [];
foreach ($alertas as $a) { $agrupadas[$a['tipo']][] = $a; }
?>

<?php if (empty($alertas)): ?>
<div class="bg-white rounded-2xl shadow-sm p-12 text-center">
    <i class="fas fa-check-circle text-5xl text-emerald-400 mb-3"></i>
    <p class="text-lg font-semibold text-gray-700">Sin alertas activas</p>
    <p class="text-sm text-gray-400 mt-1">El inventario está en buen estado.</p>
</div>
<?php else: ?>
<?php foreach ($agrupadas as $tipo => $grupo): ?>
<?php $meta = $tipoLabels[$tipo] ?? ['label' => $tipo, 'icon' => 'fa-bell', 'color' => 'gray']; ?>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-5">
    <!-- Cabecera del grupo -->
    <div class="flex items-center justify-between px-5 py-3 bg-gray-50 border-b">
        <div class="flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-<?= $meta['color'] ?>-500 text-white flex items-center justify-center shrink-0"><i class="fas <?= $meta['icon'] ?> text-xs"></i></span>
            <span class="font-semibold text-gray-700 text-sm"><?= $meta['label'] ?></span>
            <span class="ml-1 px-2 py-0.5 bg-<?= $meta['color'] ?>-500 text-white rounded-full text-xs font-bold"><?= count($grupo) ?></span>
        </div>
        <form method="POST" action="/inventario/alertas/resolver-todas">
            <?= csrf_field() ?>
            <input type="hidden" name="tipo" value="<?= $tipo ?>">
            <button class="text-xs text-gray-500 hover:text-gray-800 transition inline-flex items-center gap-1" title="Marcar como resueltas todas las alertas de este grupo"><i class="fas fa-check-double"></i> Resolver grupo</button>
        </form>
    </div>

    <!-- Filas -->
    <table class="w-full text-sm">
        <thead class="text-[10px] text-gray-400 uppercase bg-gray-50/50">
            <tr>
                <th class="px-4 py-2 text-left"><i class="fas fa-message mr-1"></i>Mensaje</th>
                <th class="px-4 py-2 text-center"><i class="fas fa-flag mr-1"></i>Prioridad</th>
                <th class="px-4 py-2 text-center w-24"><i class="fas fa-bolt mr-1"></i>Acción</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
        <?php foreach ($grupo as $alerta): ?>
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3">
                    <p class="text-gray-800"><?= esc($alerta['mensaje']) ?></p>
                    <?php if ($alerta['producto_nombre']): ?>
                    <p class="text-xs text-gray-400 mt-0.5">
                        <span class="font-mono"><?= esc($alerta['producto_codigo']) ?></span>
                        <?php if ($alerta['deposito_nombre']): ?> · <?= esc($alerta['deposito_nombre']) ?><?php endif; ?>
                    </p>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap <?= $prioridadClasses[$alerta['prioridad']] ?? 'bg-gray-100 text-gray-500' ?>"
                          title="Prioridad <?= esc($alerta['prioridad']) ?>"><i class="fas <?= $prioridadIconos[$alerta['prioridad']] ?? 'fa-flag' ?> text-[10px]"></i> <?= esc(ucfirst($alerta['prioridad'])) ?></span>
                </td>
                <td class="px-4 py-3 text-center">
                    <form method="POST" action="/inventario/alertas/<?= $alerta['alerta_id'] ?>/resolver">
                        <?= csrf_field() ?>
                        <button class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white transition-colors inline-flex items-center justify-center" title="Marcar como resuelta" aria-label="Marcar como resuelta">
                            <i class="fas fa-check text-xs"></i>
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php $this->endSection(); ?>
