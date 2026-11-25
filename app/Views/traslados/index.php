<?php $this->extend('layouts/app'); ?>
<?php $this->section('content'); ?>

<?php $s = $stats ?? []; $traslados = $traslados ?? []; $pagination = $pagination ?? []; ?>

<!-- Barra de búsqueda -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-4">
    <form method="GET" id="search-form" class="flex flex-wrap items-end gap-3">

        <div class="flex-1 min-w-48">
            <label class="block text-xs font-semibold text-gray-500 mb-1"><i class="fas fa-user mr-1"></i>Buscar por usuario</label>
            <div class="relative">
                <i class="fas fa-user absolute left-0 top-2 text-gray-400 text-xs"></i>
                <input type="text" name="buscar" id="search-input"
                    value="<?= esc($buscar ?? '') ?>"
                    placeholder="Nombre de quien realiza el traslado..."
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

        <div class="flex items-center gap-2 ml-auto">
            <span class="text-xs font-medium text-gray-400 whitespace-nowrap">
                <?= number_format($pagination['total'] ?? 0) ?> traslado(s)
                <?php if (!empty($buscar)): ?>
                <span class="text-sky-500">— filtrado</span>
                <?php endif; ?>
            </span>
            <?php if (!empty($buscar)): ?>
            <a href="/inventario/traslados?lista=1"
                class="inline-flex items-center gap-1.5 px-3 py-2 border border-gray-200 text-gray-500 rounded-lg text-sm hover:bg-gray-50 transition"
                title="Quitar la búsqueda">
                <i class="fas fa-xmark"></i> Limpiar
            </a>
            <?php endif; ?>
            <a href="/inventario/traslados/nuevo"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-sky-500 text-white rounded-lg text-sm font-semibold hover:bg-sky-600 transition shadow-sm">
                <i class="fas fa-exchange-alt"></i> Nuevo Traslado
            </a>
        </div>
    </form>
</div>

<!-- Tabla -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden" x-data="trasladosList()">

    <div class="px-5 py-3.5 bg-gray-50/80 border-b border-gray-100 flex items-center justify-between">
        <span class="text-xs font-medium text-gray-500">Ordenado por más reciente</span>
        <span class="text-xs text-gray-400">
            Pág. <?= $pagination['current_page'] ?? 1 ?> / <?= $pagination['total_pages'] ?? 1 ?>
        </span>
    </div>

    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-100 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                <th class="px-5 py-3.5 bg-gray-50/80"><i class="fas fa-hashtag mr-1"></i>Traslado</th>
                <th class="px-5 py-3.5 bg-gray-50/80"><i class="fas fa-warehouse mr-1"></i>Origen</th>
                <th class="px-5 py-3.5 bg-gray-50/80"><i class="fas fa-location-arrow mr-1"></i>Destino</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-center"><i class="fas fa-toggle-on mr-1"></i>Estado</th>
                <th class="px-5 py-3.5 bg-gray-50/80"><i class="fas fa-user mr-1"></i>Enviado por</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-center">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            <?php foreach ($traslados as $t): ?>
            <?php
                $badgeMap = [
                    'recibido'    => ['bg-emerald-100 text-emerald-600', 'fa-check',        'Recibido'],
                    'en_transito' => ['bg-amber-100 text-amber-600',     'fa-truck-moving', 'En tránsito'],
                    'borrador'    => ['bg-gray-100 text-gray-500',       'fa-pen-ruler',    'Borrador'],
                    'cancelado'   => ['bg-red-100 text-red-500',         'fa-ban',          'Cancelado'],
                ];
                [$badgeClass, $badgeIcon, $badgeTxt] = $badgeMap[$t['estado']] ?? ['bg-gray-100 text-gray-500', 'fa-circle-question', ucfirst(str_replace('_', ' ', $t['estado']))];
            ?>
            <tr class="hover:bg-sky-50/40 transition-colors group">

                <!-- # -->
                <td class="px-5 py-3.5">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                            <i class="fas fa-exchange-alt text-xs"></i>
                        </div>
                        <button type="button" onclick="verTraslado(<?= (int) $t['traslado_id'] ?>)" title="Ver los productos de este traslado" class="font-mono text-xs font-bold text-sky-600 hover:underline">#<?= $t['traslado_id'] ?></button>
                    </div>
                </td>

                <!-- Origen -->
                <td class="px-5 py-3.5 font-semibold text-gray-700 text-sm">
                    <?= esc($t['origen_nombre']) ?>
                </td>

                <!-- Destino -->
                <td class="px-5 py-3.5 text-gray-600 text-sm">
                    <div class="flex items-center gap-1.5">
                        <i class="fas fa-arrow-right text-gray-300 text-xs"></i>
                        <?= esc($t['destino_nombre']) ?>
                    </div>
                </td>

                <!-- Estado -->
                <td class="px-5 py-3.5 text-center">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap <?= $badgeClass ?>" title="<?= esc($badgeTxt) ?>"><i class="fas <?= $badgeIcon ?> text-[10px]"></i> <?= esc($badgeTxt) ?></span>
                </td>

                <!-- Enviado por -->
                <td class="px-5 py-3.5">
                    <p class="text-sm font-semibold text-gray-700"><?= esc($t['usuario_envia_nombre']) ?></p>
                    <p class="text-xs text-gray-400 mt-0.5"><?= date('d/m/Y H:i', strtotime($t['fecha_envio'])) ?></p>
                </td>

                <!-- Acciones -->
                <td class="px-5 py-3.5 text-center">
                    <div class="flex items-center justify-center gap-1.5">
                        <button type="button" onclick="verTraslado(<?= (int) $t['traslado_id'] ?>)" title="Ver detalle (productos)" aria-label="Ver detalle del traslado"
                            class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 hover:bg-sky-500 hover:text-white transition-colors flex items-center justify-center">
                            <i class="fas fa-eye text-xs"></i>
                        </button>
                        <?php if ($t['estado'] === 'en_transito'): ?>
                        <button type="button" @click="recibirTraslado(<?= (int) $t['traslado_id'] ?>)" title="Confirmar la recepción" aria-label="Confirmar la recepción"
                            class="w-8 h-8 rounded-lg bg-emerald-500 text-white hover:bg-emerald-600 transition-colors flex items-center justify-center shadow-sm">
                            <i class="fas fa-check text-xs"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>

            <?php if (empty($traslados)): ?>
            <tr>
                <td colspan="6" class="px-4 py-16 text-center">
                    <div class="inline-flex flex-col items-center gap-2">
                        <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center">
                            <i class="fas fa-exchange-alt text-gray-300 text-2xl"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-400">No se encontraron traslados</p>
                        <?php if (empty($buscar)): ?>
                        <a href="/inventario/traslados/nuevo"
                            class="mt-1 text-sky-500 hover:underline text-sm font-medium">
                            Crear el primer traslado
                        </a>
                        <?php else: ?>
                        <a href="/inventario/traslados?lista=1" class="mt-1 text-gray-400 hover:text-sky-500 text-xs">
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


<!-- Ventana: detalle del traslado -->
<style>
@media print {
    body * { visibility: hidden !important; }
    #traslado-modal, #traslado-modal * { visibility: visible !important; }
    #traslado-modal { position: absolute !important; inset: 0 !important; background: #fff !important; overflow: visible !important; padding: 0 !important; }
    #traslado-modal button { display: none !important; }
}
</style>
<div id="traslado-modal" class="fixed inset-0 bg-black/45 z-50 hidden items-start justify-center overflow-y-auto p-4 sm:p-8" onclick="if (event.target === this) cerrarTraslado()">
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl">
        <button type="button" onclick="cerrarTraslado()" title="Cerrar" class="absolute top-3 right-4 z-10 text-3xl leading-none text-gray-300 hover:text-gray-500">&times;</button>
        <div id="traslado-modal-contenido"></div>
    </div>
</div>
<?php $this->endSection(); ?>

<?php $this->section('extra_js'); ?>
<script>
    /* ── Debounce de búsqueda ── */
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

    /* ── Detalle del traslado en una ventana ── */
    function cerrarTraslado() {
        var m = document.getElementById('traslado-modal');
        m.classList.add('hidden'); m.classList.remove('flex');
        document.getElementById('traslado-modal-contenido').innerHTML = '';
        document.body.style.overflow = '';
    }
    async function verTraslado(id) {
        var m = document.getElementById('traslado-modal'), c = document.getElementById('traslado-modal-contenido');
        c.innerHTML = '<div class="py-16 text-center text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Cargando traslado…</div>';
        m.classList.remove('hidden'); m.classList.add('flex'); document.body.style.overflow = 'hidden';
        try {
            var r = await fetch('/inventario/traslados/' + encodeURIComponent(id) + '/modal', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!r.ok) throw new Error('No se pudo cargar el traslado');
            c.innerHTML = await r.text();
        } catch (e) { c.innerHTML = '<div class="py-16 text-center text-red-500">' + e.message + '</div>'; }
    }
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') cerrarTraslado(); });

    /* ── Alpine: confirmar recepción ── */
    function trasladosList() {
        return {
            async recibirTraslado(id) {
                if (!confirm('¿Confirmar recepción? El stock se actualizará en el depósito de destino.')) return;
                try {
                    const response = await fetch(`/inventario/traslados/${id}/recibir`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' }
                    });
                    const res = await response.json();
                    if (res.success) { window.location.reload(); }
                    else { alert('Error: ' + res.error); }
                } catch (error) {
                    alert('Ocurrió un error al procesar la recepción');
                }
            }
        };
    }
</script>
<?php $this->endSection(); ?>
