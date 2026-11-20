<?php
/**
 * Contenedor + script del modal "Ver entrega". Se incluye una vez en cada pantalla que lista entregas:
 *   <?= view('partials/entrega_modal') ?>
 * y se abre con  verEntrega(id).
 */
?>
<div id="entrega-detalle-modal" class="fixed inset-0 bg-black/45 z-50 hidden items-start justify-center overflow-y-auto p-4 sm:p-8" onclick="if (event.target === this) cerrarEntregaModal()">
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl my-4">
        <button type="button" onclick="cerrarEntregaModal()" title="Cerrar" class="absolute top-3 right-4 z-10 text-3xl leading-none text-gray-300 hover:text-gray-500">&times;</button>
        <div id="entrega-detalle-contenido" class="min-h-[160px]"></div>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('entrega-detalle-modal');
    const cont  = document.getElementById('entrega-detalle-contenido');

    window.cerrarEntregaModal = function () {
        modal.classList.add('hidden'); modal.classList.remove('flex'); cont.innerHTML = '';
        document.body.style.overflow = '';
    };

    window.verEntrega = async function (id) {
        cont.innerHTML = '<div class="py-16 text-center text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Cargando entrega…</div>';
        modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.style.overflow = 'hidden';
        try {
            const r = await fetch('/entregas/' + encodeURIComponent(id) + '/modal', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!r.ok) throw new Error('No se pudo cargar la entrega');
            cont.innerHTML = await r.text();
        } catch (e) {
            cont.innerHTML = '<div class="py-16 text-center text-red-500">' + e.message + '</div>';
        }
    };

    window.imprimirEntregaModal = async function (id, btn) {
        const html = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Imprimiendo…';
        try {
            const r = await fetch('/entregas/' + id + '/imprimir', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>', 'X-Requested-With': 'XMLHttpRequest' },
                body: '{}'
            });
            const res = await r.json().catch(() => ({}));
            if (!r.ok || res.ok !== true) throw new Error(res.error || 'La impresora no confirmó la entrega');
            btn.innerHTML = '<i class="fas fa-check"></i> Enviada';
        } catch (e) { alert('No se pudo imprimir la entrega: ' + e.message); btn.innerHTML = html; }
        setTimeout(() => { btn.disabled = false; btn.innerHTML = html; }, 2500);
    };

    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.classList.contains('hidden')) cerrarEntregaModal(); });
})();
</script>
