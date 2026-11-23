<?php $this->extend('layouts/app'); ?>
<?php $this->section('content'); ?>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-4">
    <form method="get" class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-64">
            <label class="block text-xs font-semibold text-gray-500 mb-1"><i class="fas fa-magnifying-glass mr-1"></i>Buscar</label>
            <input type="text" name="buscar" value="<?= esc($buscar ?? '') ?>" placeholder="Número de entrega, factura o cliente"
                   class="w-full py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
        </div>
        <button class="px-4 py-2 bg-sky-500 text-white rounded-lg text-sm font-semibold hover:bg-sky-600 transition">
            <i class="fas fa-magnifying-glass mr-1"></i> Buscar
        </button>
    </form>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-gray-100">
        <thead>
            <tr>
                <th class="px-5 py-3.5 bg-gray-50/80 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"><i class="fas fa-truck-fast mr-1"></i>Entrega</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"><i class="fas fa-calendar-day mr-1"></i>Fecha</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"><i class="fas fa-receipt mr-1"></i>Venta</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"><i class="fas fa-user mr-1"></i>Cliente</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
        <?php foreach ($entregas as $e): ?>
            <tr class="hover:bg-gray-50/60">
                <td class="px-5 py-3 text-sm font-bold text-gray-800"><a href="#" onclick="verEntrega(<?= (int) $e['entrega_id'] ?>); return false;" class="hover:text-sky-600"><?= esc($e['numero']) ?></a></td>
                <td class="px-5 py-3 whitespace-nowrap"><?php $tsE = strtotime((string) $e['fecha']); ?><div class="text-sm text-gray-700 leading-tight"><?= date('d/m/Y', $tsE) ?></div><div class="text-xs text-gray-400"><?= date('H:i', $tsE) ?></div></td>
                <td class="px-5 py-3 text-sm"><a class="text-sky-600 hover:underline" href="/ventas/venta/<?= (int) $e['venta_id'] ?>"><?= esc($e['numero_factura']) ?></a></td>
                <td class="px-5 py-3 text-sm text-gray-800"><?= esc($e['cliente']) ?></td>
                <td class="px-5 py-3 text-right whitespace-nowrap text-sm">
                    <div class="flex justify-end gap-1.5">
                    <button type="button" onclick="verEntrega(<?= (int) $e['entrega_id'] ?>)" title="Ver detalle de la entrega" aria-label="Ver detalle de la entrega"
                            class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-500 hover:text-white transition-colors flex items-center justify-center"><i class="fas fa-eye text-xs"></i></button>
                    <a href="/entregas/<?= (int) $e['entrega_id'] ?>/pdf" title="Descargar PDF" aria-label="Descargar PDF"
                       class="w-8 h-8 rounded-lg bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white transition-colors flex items-center justify-center"><i class="fas fa-file-pdf text-xs"></i></a>
                    <?php if (!empty($cups)): ?>
                    <button type="button" onclick="imprimirEntrega(<?= (int) $e['entrega_id'] ?>, this)" title="Imprimir entrega" aria-label="Imprimir entrega"
                            class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white transition-colors flex items-center justify-center"><i class="fas fa-print text-xs"></i></button>
                    <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($entregas)): ?>
            <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-gray-400">No hay entregas.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?= view('partials/pagination', ['pagination' => $pagination ?? []]) ?>
</div>

<?= view('partials/entrega_modal') ?>

<script>
async function imprimirEntrega(id, btn) {
    const html = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    try {
        const r = await fetch('/entregas/' + id + '/imprimir', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>', 'X-Requested-With': 'XMLHttpRequest' },
            body: '{}'
        });
        const res = await r.json().catch(() => ({}));
        if (!r.ok || res.ok !== true) throw new Error(res.error || 'La impresora no confirmó la entrega');
        btn.innerHTML = '<i class="fas fa-check text-emerald-600"></i>';
    } catch (e) { alert('No se pudo imprimir la entrega: ' + e.message); btn.innerHTML = html; }
    setTimeout(() => { btn.disabled = false; btn.innerHTML = html; }, 2500);
}
</script>
<?php $this->endSection(); ?>
