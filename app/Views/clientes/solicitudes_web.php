<?php $this->extend('layouts/app'); ?>
<?php $this->section('content'); ?>

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden mb-6">
    <div class="p-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="font-bold text-slate-800"><i class="fas fa-globe text-sky-500 mr-2"></i>Pendientes de aprobar <span class="text-sm font-semibold text-slate-400">(<?= count($pendientes) ?>)</span></h2>
        <a href="/clientes" class="text-sm text-sky-600 hover:underline">← Clientes</a>
    </div>
    <?php if (!$pendientes): ?>
        <p class="p-8 text-center text-slate-400 text-sm">No hay solicitudes pendientes.</p>
    <?php endif; ?>
    <?php foreach ($pendientes as $s): ?>
    <div class="p-4 border-b border-slate-100 last:border-0 flex flex-wrap items-center gap-4">
        <div class="flex-1 min-w-[240px]">
            <p class="font-bold text-slate-800"><?= esc($s['nombre']) ?></p>
            <p class="text-sm text-slate-500"><?= esc($s['email']) ?> · <?= esc($s['telefono']) ?><?= $s['ruc'] ? ' · RUC ' . esc($s['ruc']) : '' ?></p>
            <?php if ($s['direccion']): ?><p class="text-xs text-slate-400"><?= esc($s['direccion']) ?></p><?php endif; ?>
            <p class="text-xs text-slate-400 mt-0.5">Solicitó el <?= date('d/m/Y H:i', strtotime($s['creado'])) ?></p>
        </div>
        <form method="post" action="/clientes/solicitudes-web/<?= (int) $s['solicitud_id'] ?>/aprobar" class="flex items-center gap-2">
            <?= csrf_field() ?>
            <label class="text-xs font-semibold text-slate-500">Precio que verá</label>
            <select name="tipo_precio" class="border border-slate-200 rounded-lg px-2 py-1.5 text-sm">
                <option value="c" selected>Precio C</option>
                <option value="b">Precio B</option>
                <option value="a">Precio A</option>
            </select>
            <button class="px-4 py-1.5 bg-emerald-600 text-white rounded-lg text-sm font-bold hover:bg-emerald-700"><i class="fas fa-check mr-1"></i>Aprobar</button>
        </form>
        <form method="post" action="/clientes/solicitudes-web/<?= (int) $s['solicitud_id'] ?>/rechazar" class="flex items-center gap-2" onsubmit="return confirm('¿Rechazar esta solicitud?')">
            <?= csrf_field() ?>
            <input name="motivo" placeholder="Motivo (opcional)" class="border border-slate-200 rounded-lg px-2 py-1.5 text-sm w-40">
            <button class="px-3 py-1.5 border border-rose-200 text-rose-600 rounded-lg text-sm hover:bg-rose-50">Rechazar</button>
        </form>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($resueltas): ?>
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="p-4 border-b border-slate-100 font-bold text-slate-800">Resueltas recientemente</div>
    <table class="w-full text-sm">
        <?php foreach ($resueltas as $s): ?>
        <tr class="border-b border-slate-50">
            <td class="px-4 py-2"><?= esc($s['nombre']) ?><div class="text-xs text-slate-400"><?= esc($s['email']) ?></div></td>
            <td class="px-4 py-2"><?= $s['estado'] === 'aprobada' ? '<span class="text-emerald-600 font-semibold">Aprobada · precio ' . strtoupper($s['tipo_precio']) . '</span>' : '<span class="text-rose-600 font-semibold">Rechazada</span>' . ($s['motivo'] ? ' · ' . esc($s['motivo']) : '') ?></td>
            <td class="px-4 py-2 text-slate-400"><?= $s['resuelto_en'] ? date('d/m/Y H:i', strtotime($s['resuelto_en'])) : '' ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>

<?php $this->endSection(); ?>
