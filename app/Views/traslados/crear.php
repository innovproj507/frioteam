<?php $this->extend('layouts/app'); ?>
<?php $this->section('content'); ?>

<div x-data="trasladoForm()" x-init="init()">

    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-gray-400 mb-3">
        <a href="/inventario/traslados?lista=1" class="hover:text-gray-600 transition-colors">Traslados</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-700 font-medium">Añadir Traslado</span>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">

        <!-- Título -->
        <div class="px-6 pt-5 pb-3 border-b border-gray-100">
            <h2 class="text-xl font-bold text-sky-700 flex items-center gap-2"><i class="fas fa-plus-circle text-sky-500"></i> Añadir Traslado</h2>
            <p class="text-sm text-gray-500 mt-1">Complete la información de abajo. Los campos marcados con <b>*</b> son necesarios.</p>
        </div>

        <!-- Datos del traslado -->
        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-5">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Fecha *</label>
                <input type="text" value="<?= date('d/m/Y H:i') ?>" readonly
                       class="w-full px-3 py-2 text-sm bg-gray-50 border border-gray-200 rounded-lg text-gray-600">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Referencia</label>
                <input type="text" x-model="referencia" maxlength="60"
                       class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-sky-500">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Para Almacén *</label>
                <select x-model="traslado.destino_id"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-sky-500 bg-white">
                    <option value="">Seleccionar Para Almacén</option>
                    <?php foreach ($depositos_destino as $d): ?>
                    <option value="<?= $d['deposito_id'] ?>"><?= htmlspecialchars($d['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Estado *</label>
                <select x-model="estado"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-sky-500 bg-white">
                    <option value="completado">Completado (mueve el stock ahora)</option>
                    <option value="transito">En tránsito (se recibe después)</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Notas</label>
                <input type="text" x-model="traslado.notas" maxlength="250" placeholder="Motivo del traslado u observaciones"
                       class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-sky-500">
            </div>
        </div>

        <!-- Aviso + almacén de origen -->
        <div class="px-6 pb-2">
            <div x-show="!listo()" x-cloak class="rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-sm font-semibold px-4 py-2.5 mb-4">
                Por favor, seleccione el almacén de origen y de destino antes de agregar cualquier producto.
            </div>
            <div class="max-w-md">
                <label class="block text-sm font-semibold text-gray-700 mb-1">A partir de Almacén *</label>
                <select x-model="traslado.origen_id"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-sky-500 bg-white">
                    <option value="">Seleccionar A partir de Almacén</option>
                    <?php foreach ($depositos_origen as $d): ?>
                    <option value="<?= $d['deposito_id'] ?>"><?= htmlspecialchars($d['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Escáner / buscador -->
        <div class="px-6 pt-4 pb-2">
            <div class="relative" @click.away="resultados = []">
                <div class="flex items-stretch rounded-lg border border-gray-300 overflow-hidden" :class="listo() ? 'focus-within:ring-1 focus-within:ring-sky-500' : 'opacity-60'">
                    <span class="px-4 flex items-center bg-gray-50 border-r border-gray-200 text-gray-500 text-xl"><i class="fas fa-barcode"></i></span>
                    <input type="text" x-ref="buscador" x-model="searchQuery" @input="programarBusqueda()"
                           @keydown.enter.prevent="agregarConEnter()" autocomplete="off"
                           :disabled="!listo()"
                           :placeholder="items.length || listo() ? 'Escribe o escanea el código y presiona Enter para agregar…' : 'Por favor, añadir productos a la lista de orden'"
                           class="w-full px-4 py-3 text-base outline-none disabled:bg-gray-50">
                </div>
                <p x-show="sinResultado" x-cloak class="mt-1 text-xs font-semibold text-red-600">No se encontró ningún producto con ese código.</p>

                <!-- Sugerencias (cuando el texto coincide con varios productos) -->
                <div x-show="resultados.length > 0" x-cloak
                     class="absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-100 rounded-xl shadow-2xl max-h-72 overflow-y-auto">
                    <template x-for="p in resultados" :key="p.producto_id">
                        <div @click="seleccionarProducto(p)"
                             class="p-3 border-b border-gray-50 hover:bg-sky-50 cursor-pointer transition-all flex items-center justify-between">
                            <div>
                                <div class="text-base font-mono font-bold text-gray-900" x-text="p.codigo"></div>
                                <div class="text-sm text-gray-600" x-text="p.nombre"></div>
                            </div>
                            <i class="fas fa-plus-circle text-sky-400 text-sm"></i>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Lista de productos -->
        <div class="px-6 pb-4 pt-2 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-wider w-10">#</th>
                        <th class="px-3 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-wider w-44">Código</th>
                        <th class="px-3 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-wider">Producto</th>
                        <th class="px-3 py-3 text-center text-[10px] font-black text-gray-500 uppercase tracking-wider">Cantidad</th>
                        <th class="px-3 py-3 w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="(item, index) in items" :key="item.producto_id">
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-3 py-3 text-sm text-gray-400" x-text="index + 1"></td>
                            <td class="px-3 py-3"><span class="text-base font-mono font-bold text-gray-900" x-text="item.codigo"></span></td>
                            <td class="px-3 py-3 text-sm text-gray-700" x-text="item.nombre"></td>
                            <td class="px-3 py-3">
                                <div class="flex justify-center items-center gap-2">
                                    <button type="button" @click="item.cantidad > 1 ? item.cantidad-- : null"
                                        class="w-7 h-7 rounded-lg bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-gray-200 text-sm">−</button>
                                    <input type="number" min="1" x-model.number="item.cantidad"
                                        class="w-20 text-center py-1.5 text-sm bg-gray-50 rounded-lg border border-gray-200 focus:border-sky-400 focus:ring-0 outline-none font-semibold">
                                    <button type="button" @click="item.cantidad++"
                                        class="w-7 h-7 rounded-lg bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-gray-200 text-sm">+</button>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-right">
                                <button type="button" @click="removeItem(index)" class="text-gray-300 hover:text-red-500 transition-colors"><i class="fas fa-times-circle"></i></button>
                            </td>
                        </tr>
                    </template>
                    <template x-if="items.length === 0">
                        <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-gray-400 italic">Por favor, añadir productos a la lista de orden.</td></tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Totales -->
        <div class="mx-6 mb-5 rounded-lg bg-gray-50 border border-gray-100 px-5 py-3 flex flex-wrap items-center gap-x-10 gap-y-1 text-sm">
            <div><span class="text-gray-500">Artículos</span> <b class="ml-1 text-gray-900" x-text="items.length"></b></div>
            <div><span class="text-gray-500">Total unidades</span> <b class="ml-1 text-gray-900" x-text="totalUnidades()"></b></div>
        </div>

        <!-- Botones -->
        <div class="px-6 pb-6 flex gap-2">
            <button type="button" @click="saveTraslado()" :disabled="guardando"
                class="inline-flex items-center gap-1.5 px-6 py-2.5 bg-sky-500 text-white rounded-lg text-sm font-semibold hover:bg-sky-600 transition shadow-sm disabled:opacity-60">
                <i class="fas fa-paper-plane"></i> <span x-text="estado === 'completado' ? 'Enviar y completar' : 'Enviar mercancía'"></span>
            </button>
            <button type="button" @click="reiniciar()"
                class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-red-50 text-red-600 rounded-lg text-sm font-semibold hover:bg-red-100 transition">Restablecer</button>
            <a href="/inventario/traslados?lista=1" class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-white border border-gray-200 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 transition">Cancelar</a>
        </div>
    </div>
</div>

<script>
function trasladoForm() {
    return {
        searchQuery: '',
        sinResultado: false,
        guardando: false,
        timer: null,
        seq: 0,   // identifica la búsqueda vigente: las respuestas de búsquedas viejas se descartan
        resultados: [],
        items: [],
        referencia: '',
        estado: 'completado',
        traslado: { origen_id: '', destino_id: '', notas: '' },

        init() {
            // Al elegir origen y destino queda listo el escáner
            const enfocar = () => this.$nextTick(() => { if (this.listo() && this.$refs.buscador) this.$refs.buscador.focus(); });
            this.$watch('traslado.origen_id', enfocar);
            this.$watch('traslado.destino_id', enfocar);
        },
        listo() { return !!this.traslado.origen_id && !!this.traslado.destino_id; },
        totalUnidades() { return this.items.reduce((s, i) => s + (parseFloat(i.cantidad) || 0), 0); },

        programarBusqueda() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.buscarProductos(), 300);
        },
        async buscarProductos() {
            this.sinResultado = false;
            const mia = ++this.seq;
            if (this.searchQuery.length < 2) { this.resultados = []; return; }
            try {
                const response = await fetch(`/api/productos/buscar?q=${encodeURIComponent(this.searchQuery)}`);
                const data = await response.json();
                if (mia !== this.seq) return;   // ya hubo Enter o se siguió escribiendo: respuesta vieja
                this.resultados = data.productos || [];
            } catch (error) {
                console.error('Error buscando productos:', error);
            }
        },
        // Enter en el buscador: si el código identifica un solo producto (código exacto, lector o único resultado) se agrega solo
        async agregarConEnter() {
            clearTimeout(this.timer);   // Enter manda: la búsqueda automática pendiente ya no hace falta
            const q = this.searchQuery.trim();
            this.sinResultado = false;
            if (q === '') return;
            const mia = ++this.seq;   // invalida cualquier búsqueda automática que siga en camino
            try {
                const response = await fetch(`/api/productos/buscar?q=${encodeURIComponent(q)}`);
                const data = await response.json();
                if (mia !== this.seq) return;
                const lista = data.productos || [];
                if (lista.length === 1 || (data.exacto && lista.length > 0)) {
                    this.seleccionarProducto(lista[0]);
                } else if (lista.length === 0) {
                    this.resultados = [];
                    this.sinResultado = true;
                } else {
                    this.resultados = lista;   // varios parecidos: elige uno de la lista
                }
            } catch (error) {
                console.error('Error buscando productos:', error);
            }
        },
        seleccionarProducto(p) {
            clearTimeout(this.timer);
            this.seq++;   // al agregar, ninguna respuesta pendiente debe reabrir la lista
            const existing = this.items.find(i => i.producto_id === p.producto_id);
            if (existing) {
                existing.cantidad++;
            } else {
                this.items.push({ producto_id: p.producto_id, nombre: p.nombre, codigo: p.codigo, cantidad: 1 });
            }
            this.searchQuery = '';
            this.resultados = [];
            this.sinResultado = false;
            this.$nextTick(() => this.$refs.buscador && this.$refs.buscador.focus());   // listo para el siguiente código
        },
        removeItem(index) {
            this.items.splice(index, 1);
        },
        reiniciar() {
            if (this.items.length && !confirm('¿Borrar todo y empezar de nuevo?')) return;
            this.items = []; this.referencia = ''; this.searchQuery = ''; this.resultados = [];
            this.traslado = { origen_id: '', destino_id: '', notas: '' };
            this.estado = 'completado';
        },
        async saveTraslado() {
            if (!this.traslado.origen_id) return alert('Seleccione el almacén de origen (A partir de Almacén)');
            if (!this.traslado.destino_id) return alert('Seleccione el almacén de destino (Para Almacén)');
            if (this.traslado.origen_id == this.traslado.destino_id) return alert('Origen y destino deben ser diferentes');
            if (this.items.length === 0) return alert('Agregue al menos un producto');
            const completo = this.estado === 'completado';
            if (!confirm(completo ? '¿Enviar la mercancía y dejar el traslado completado (el stock se mueve ahora)?' : '¿Desea realizar el envío de mercancía?')) return;
            this.guardando = true;
            try {
                const response = await fetch('/inventario/traslados/guardar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' },
                    body: JSON.stringify({ ...this.traslado, referencia: this.referencia.trim(), completado: completo, items: this.items })
                });
                const res = await response.json();
                if (res.success) {
                    window.location.href = '/inventario/traslados?lista=1';
                } else {
                    alert('Error: ' + res.error);
                    this.guardando = false;
                }
            } catch (error) {
                alert('Ocurrió un error al guardar el traslado');
                this.guardando = false;
            }
        }
    }
}
</script>

<?php $this->endSection(); ?>
