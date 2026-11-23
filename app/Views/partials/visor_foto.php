<?php
/**
 * Visor de fotos en ventana (modal). Se incluye una sola vez en layouts/app.php.
 * Para usarlo en cualquier pantalla basta con marcar un elemento clicable:
 *   <button type="button" data-ampliar data-src="/assets/uploads/x.jpg" data-titulo="Nombre" data-codigo="COD-1">…miniatura…</button>
 * Cierra con la X, la tecla Esc o un clic fuera de la foto.
 */
?>
<div id="visor-foto" class="hidden fixed inset-0 z-[100] items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm"
     role="dialog" aria-modal="true" aria-label="Foto del producto" tabindex="-1">
    <div class="relative max-w-3xl w-full bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[92vh]">
        <button type="button" id="visor-cerrar" aria-label="Cerrar la foto" title="Cerrar (Esc)"
                class="absolute top-3 right-3 z-10 w-9 h-9 rounded-full bg-white/90 text-slate-600 hover:bg-red-500 hover:text-white shadow flex items-center justify-center transition-colors">
            <i class="fas fa-xmark"></i>
        </button>
        <div class="flex-1 min-h-0 bg-slate-50 flex items-center justify-center p-3 relative">
            <i id="visor-cargando" class="fas fa-spinner fa-spin text-sky-500 text-2xl absolute"></i>
            <img id="visor-img" src="" alt="" class="max-h-[72vh] max-w-full object-contain rounded-lg">
            <p id="visor-error" class="hidden text-sm text-slate-400 text-center"><i class="fas fa-image text-3xl block mb-2 text-slate-300"></i>No se pudo cargar la foto</p>
        </div>
        <div class="px-5 py-3 border-t border-slate-100 flex items-center gap-3">
            <span class="w-9 h-9 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center shrink-0"><i class="fas fa-box"></i></span>
            <div class="min-w-0">
                <p id="visor-titulo" class="font-bold text-slate-800 leading-tight truncate"></p>
                <p id="visor-codigo" class="text-xs font-mono text-slate-400"></p>
            </div>
            <a id="visor-abrir" href="#" target="_blank" rel="noopener" title="Abrir la foto en una pestaña nueva" aria-label="Abrir la foto en una pestaña nueva"
               class="ml-auto w-9 h-9 rounded-lg bg-slate-100 text-slate-500 hover:bg-sky-500 hover:text-white flex items-center justify-center transition-colors shrink-0"><i class="fas fa-up-right-from-square text-sm"></i></a>
        </div>
    </div>
</div>
<script>
(function () {
    var visor = document.getElementById('visor-foto');
    if (!visor) return;
    var img = document.getElementById('visor-img'), cargando = document.getElementById('visor-cargando'), error = document.getElementById('visor-error');
    var previo = null;

    function abrir(src, titulo, codigo) {
        previo = document.activeElement;
        error.classList.add('hidden'); img.classList.remove('hidden'); cargando.classList.remove('hidden');
        img.alt = titulo || '';
        img.onload  = function () { cargando.classList.add('hidden'); };
        img.onerror = function () { cargando.classList.add('hidden'); img.classList.add('hidden'); error.classList.remove('hidden'); };
        img.src = src;
        document.getElementById('visor-titulo').textContent = titulo || '';
        document.getElementById('visor-codigo').textContent = codigo || '';
        document.getElementById('visor-abrir').href = src;
        visor.classList.remove('hidden'); visor.classList.add('flex');
        document.body.style.overflow = 'hidden';
        document.getElementById('visor-cerrar').focus();
    }
    function cerrar() {
        visor.classList.add('hidden'); visor.classList.remove('flex');
        img.src = ''; document.body.style.overflow = '';
        if (previo && previo.focus) previo.focus();
    }

    // Cualquier elemento con data-ampliar abre la foto (funciona también en filas cargadas después)
    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-ampliar]');
        if (el) {
            e.preventDefault();
            var src = el.getAttribute('data-src') || (el.tagName === 'IMG' ? el.src : (el.querySelector('img') || {}).src);
            if (src) abrir(src, el.getAttribute('data-titulo'), el.getAttribute('data-codigo'));
            return;
        }
        if (!visor.classList.contains('hidden') && (e.target === visor || e.target.closest('#visor-cerrar'))) cerrar();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !visor.classList.contains('hidden')) cerrar();
    });
})();
</script>
