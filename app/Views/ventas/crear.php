<?php $this->extend('layouts/app'); ?>
<?php $this->section('content'); ?>
<?php $puede_fecha_anterior = ($tipo_documento ?? 'venta') === 'venta' && \App\Libraries\Auth::can('ventas.fecha_anterior'); ?>

<div x-data="ventasForm()">

    <!-- Toast notifications -->
    <div class="fixed top-6 right-6 z-[100] flex flex-col gap-3 pointer-events-none">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="toast.show"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-x-8 scale-95"
                 x-transition:enter-end="opacity-100 translate-x-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-x-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-x-8 scale-95"
                 class="pointer-events-auto flex items-center p-4 rounded-xl shadow-2xl border min-w-[300px] max-w-md"
                 :class="{
                    'bg-emerald-500 border-emerald-400 text-white': toast.type === 'success',
                    'bg-red-500 border-red-400 text-white': toast.type === 'error',
                    'bg-amber-500 border-amber-400 text-white': toast.type === 'warning'
                 }">
                <i class="fas mr-3" :class="{
                    'fa-check-circle': toast.type === 'success',
                    'fa-exclamation-circle': toast.type === 'error',
                    'fa-exclamation-triangle': toast.type === 'warning'
                }"></i>
                <p class="text-sm font-semibold flex-1" x-text="toast.message"></p>
                <button @click="removeToast(toast.id)" class="ml-3 opacity-70 hover:opacity-100">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>
        </template>
    </div>

    <!-- Breadcrumb -->
    <p class="text-xs text-gray-400 mb-2">
        <?php if (($tipo_documento ?? 'venta') === 'cotizacion'): ?>
        <a href="/ventas/cotizaciones" class="hover:text-sky-500 transition-colors">Cotizaciones</a>
        <span class="mx-1">/</span>
        <span class="text-gray-600 font-medium">Nueva Cotización</span>
        <?php else: ?>
        <a href="/ventas" class="hover:text-sky-500 transition-colors">Ventas</a>
        <span class="mx-1">/</span>
        <span class="text-gray-600 font-medium">Nueva Venta</span>
        <?php endif; ?>
    </p>

    <!-- Barra de acciones + pipeline -->
    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
        <div class="flex items-center gap-2">
            <!-- Tipo documento toggle -->
            <div class="flex items-center bg-white p-1 rounded-lg border border-gray-200 shadow-sm">
                <button @click="tipoDocumento = 'venta'; estado = 'pagada'"
                        :class="tipoDocumento === 'venta' ? 'bg-sky-500 text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50'"
                        class="px-4 py-1.5 rounded-md text-sm font-semibold transition-all flex items-center gap-1.5">
                    <i class="fas fa-cash-register text-xs"></i> Factura
                </button>
                <button @click="tipoDocumento = 'cotizacion'; estado = 'pendiente'"
                        :class="tipoDocumento === 'cotizacion' ? 'bg-amber-500 text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50'"
                        class="px-4 py-1.5 rounded-md text-sm font-semibold transition-all flex items-center gap-1.5">
                    <i class="fas fa-file-alt text-xs"></i> Cotización
                </button>
            </div>

        </div>

        <!-- Pipeline venta -->
        <div x-show="false" class="flex items-stretch text-xs font-semibold select-none">
            <div class="flex items-center bg-sky-500 text-white pl-4 pr-6 py-2 rounded-l-lg relative">
                Borrador
                <span class="absolute right-0 top-0 h-full w-3 overflow-hidden translate-x-2.5 z-10">
                    <span class="block w-6 h-6 bg-sky-500 rotate-45 origin-top-left mt-0.5 ml-0.5"></span>
                </span>
            </div>
            <div class="flex items-center bg-gray-100 text-gray-400 pl-7 pr-5 py-2 rounded-r-lg">
                Pagada
            </div>
        </div>

        <!-- Pipeline cotización -->
        <div x-show="tipoDocumento === 'cotizacion'" class="flex items-stretch text-xs font-semibold select-none">
            <div class="flex items-center bg-amber-500 text-white pl-4 pr-6 py-2 rounded-l-lg relative">
                Solicitud
                <span class="absolute right-0 top-0 h-full w-3 overflow-hidden translate-x-2.5 z-10">
                    <span class="block w-6 h-6 bg-amber-500 rotate-45 origin-top-left mt-0.5 ml-0.5"></span>
                </span>
            </div>
            <div class="flex items-center bg-gray-100 text-gray-400 pl-7 pr-6 py-2 relative">
                Aprobada
                <span class="absolute right-0 top-0 h-full w-3 overflow-hidden translate-x-2.5 z-10">
                    <span class="block w-6 h-6 bg-gray-100 rotate-45 origin-top-left mt-0.5 ml-0.5"></span>
                </span>
            </div>
            <div class="flex items-center bg-gray-100 text-gray-400 pl-7 pr-5 py-2 rounded-r-lg">
                Convertida
            </div>
        </div>
    </div>

    <!-- Documento principal -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

        <!-- Título -->
        <div class="px-8 pt-7 pb-5">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-1"
               x-text="tipoDocumento === 'venta' ? 'Nueva Factura · N° <?= $siguiente_factura ?>' : 'Nueva Cotización · N° <?= $siguiente_cotizacion ?>'"></p>
            <h1 class="text-3xl font-bold text-gray-800"
                x-text="tipoDocumento === 'venta' ? 'Nueva Venta' : 'Nueva Cotización'"></h1>
        </div>

        <!-- Campos encabezado: 2 columnas -->
        <div class="px-8 pb-6 grid grid-cols-1 md:grid-cols-2 gap-x-16 gap-y-6 border-b border-gray-100">

            <!-- Columna izquierda: Cliente -->
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Cliente</label>

                <!-- Cliente elegido -->
                <div x-show="clienteId" class="rounded-xl border border-sky-100 bg-sky-50/60 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-bold text-gray-800 truncate" x-text="clienteNombre"></p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                <span x-show="clienteRuc && clienteRuc !== 'N/A'">RUC <span x-text="clienteRuc"></span> · </span>
                                <span x-show="clienteTelefono && clienteTelefono !== 'N/A'">Tel. <span x-text="clienteTelefono"></span></span>
                            </p>
                        </div>
                        <button type="button" @click="quitarCliente()" class="shrink-0 text-xs font-semibold text-sky-600 hover:text-sky-800">
                            <i class="fas fa-rotate"></i> Cambiar
                        </button>
                    </div>
                    <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-lg bg-white px-2 py-1.5">
                            <p class="text-[10px] uppercase text-gray-400 font-semibold">Debe</p>
                            <p class="text-sm font-bold" :class="clienteSaldo > 0 ? 'text-red-600' : 'text-gray-700'" x-text="'$' + clienteSaldo.toFixed(2)"></p>
                        </div>
                        <div class="rounded-lg bg-white px-2 py-1.5">
                            <p class="text-[10px] uppercase text-gray-400 font-semibold">Límite crédito</p>
                            <p class="text-sm font-bold text-gray-700" x-text="'$' + clienteLimite.toFixed(2)"></p>
                        </div>
                        <div class="rounded-lg bg-white px-2 py-1.5">
                            <p class="text-[10px] uppercase text-gray-400 font-semibold">Disponible</p>
                            <p class="text-sm font-bold text-emerald-600" x-text="'$' + Math.max(0, clienteLimite - clienteSaldo).toFixed(2)"></p>
                        </div>
                    </div>
                </div>

                <!-- Buscar / crear cliente -->
                <div x-show="!clienteId" class="relative" @click.away="abiertoCliente = false">
                    <div class="relative">
                        <i class="fas fa-user absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 text-sm"></i>
                        <input type="text" x-model="searchCliente" x-ref="buscarCliente"
                               @focus="abiertoCliente = true" @input="abiertoCliente = true"
                               @keydown.escape="abiertoCliente = false"
                               placeholder="Consumidor Final — escribe para buscar o crear un cliente"
                               class="w-full pl-9 pr-3 py-2.5 text-sm rounded-xl border border-gray-200 focus:ring-1 focus:ring-sky-400 focus:border-sky-400 outline-none">
                    </div>
                    <div x-show="abiertoCliente" x-cloak
                         class="absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-100 rounded-xl shadow-2xl max-h-80 overflow-y-auto">
                        <template x-for="c in clientesFiltrados()" :key="c.id">
                            <div @click="elegirCliente(c)"
                                 class="px-4 py-2.5 border-b border-gray-50 hover:bg-sky-50 cursor-pointer flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 truncate" x-text="c.nombre"></p>
                                    <p class="text-xs text-gray-400" x-text="[c.codigo, c.ruc, c.telefono].filter(Boolean).join(' · ')"></p>
                                </div>
                                <span x-show="c.saldo > 0" class="shrink-0 text-xs font-semibold text-red-600" x-text="'Debe $' + c.saldo.toFixed(2)"></span>
                            </div>
                        </template>
                        <div x-show="searchCliente.trim() && !clientesFiltrados().length" class="px-4 py-3 text-sm text-gray-400">No hay clientes con ese nombre.</div>
                        <?php if (\App\Libraries\Auth::can('clientes.crear')): ?>
                        <div @click="abrirNuevoCliente()"
                             class="px-4 py-3 bg-emerald-50 hover:bg-emerald-100 cursor-pointer text-sm font-semibold text-emerald-700 sticky bottom-0">
                            <i class="fas fa-user-plus mr-1"></i>
                            <span x-text="searchCliente.trim() ? 'Crear cliente «' + searchCliente.trim() + '»' : 'Crear cliente nuevo'"></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-400">Sin cliente la venta queda a <b>Consumidor Final</b>.</p>
                </div>

                <!-- Crear cliente rápido -->
                <div x-show="nuevoCliente.abierto" x-cloak class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 space-y-3">
                    <p class="text-sm font-bold text-emerald-800"><i class="fas fa-user-plus mr-1"></i> Nuevo cliente</p>
                    <input type="text" x-model="nuevoCliente.nombre" placeholder="Nombre *" class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 focus:ring-1 focus:ring-emerald-400 outline-none">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" x-model="nuevoCliente.telefono" placeholder="Teléfono" class="px-3 py-2 text-sm rounded-lg border border-gray-200 focus:ring-1 focus:ring-emerald-400 outline-none">
                        <input type="text" x-model="nuevoCliente.ruc" placeholder="Cédula / RUC" class="px-3 py-2 text-sm rounded-lg border border-gray-200 focus:ring-1 focus:ring-emerald-400 outline-none">
                    </div>
                    <?php if (\App\Libraries\Auth::can('clientes.editar')): ?>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Límite de crédito <span class="font-normal text-gray-400">(para venderle a crédito)</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-400 text-sm">$</span>
                            <input type="number" min="0" step="0.01" x-model="nuevoCliente.limite" class="w-full pl-7 pr-3 py-2 text-sm rounded-lg border border-gray-200 focus:ring-1 focus:ring-emerald-400 outline-none">
                        </div>
                    </div>
                    <?php endif; ?>
                    <p x-show="nuevoCliente.error" class="text-xs text-red-600" x-text="nuevoCliente.error"></p>
                    <div class="flex gap-2">
                        <button type="button" @click="crearCliente()" :disabled="nuevoCliente.guardando"
                                class="flex-1 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg disabled:opacity-50">
                            <span x-text="nuevoCliente.guardando ? 'Creando…' : 'Crear y usar'"></span>
                        </button>
                        <button type="button" @click="nuevoCliente.abierto = false" class="px-4 py-2 text-sm text-gray-500 border border-gray-200 rounded-lg bg-white">Cancelar</button>
                    </div>
                </div>
            </div>

            <!-- Columna derecha: Pago y fecha -->
            <div class="space-y-4">
                <div x-show="tipoDocumento === 'venta'">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Forma de pago</label>
                    <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                        <template x-for="f in [['efectivo','Efectivo','fa-money-bill-wave'],['yappy','Yappy','fa-mobile-screen'],['transferencia','Transferencia','fa-building-columns'],['tarjeta','Tarjeta','fa-credit-card'],['credito','Crédito','fa-hand-holding-dollar']]" :key="f[0]">
                            <button type="button" @click="formaPago = f[0]"
                                    :class="formaPago === f[0] ? (f[0] === 'credito' ? 'bg-amber-500 border-amber-500 text-white' : 'bg-sky-500 border-sky-500 text-white') : 'bg-white border-gray-200 text-gray-600 hover:border-sky-300'"
                                    class="flex flex-col items-center gap-1 rounded-xl border px-2 py-2 text-xs font-semibold transition">
                                <i class="fas" :class="f[2]"></i><span x-text="f[1]"></span>
                            </button>
                        </template>
                    </div>
                    <!-- Aviso de crédito -->
                    <div x-show="formaPago === 'credito'" class="mt-3 rounded-xl border px-3 py-2 text-xs"
                         :class="avisoCredito().nivel === 'ok' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800'">
                        <span x-text="avisoCredito().texto"></span>
                        <?php if (\App\Libraries\Auth::can('clientes.editar')): ?>
                        <button type="button" x-show="avisoCredito().subirA > 0" @click="subirLimite(avisoCredito().subirA)"
                                class="ml-1 font-bold underline" x-text="'Subir límite a $' + avisoCredito().subirA.toFixed(2)"></button>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($puede_fecha_anterior): ?>
                <div x-show="tipoDocumento === 'venta'">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Fecha de la venta</label>
                    <input type="date" x-model="fechaVenta" max="<?= date('Y-m-d') ?>"
                           class="w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 focus:ring-1 focus:ring-sky-400 outline-none">
                    <p x-show="fechaVenta && fechaVenta !== hoy" class="mt-1.5 text-xs text-amber-700">
                        Día anterior: solo a <b>crédito</b>. Entra en la caja de ese día de esta tienda (aunque ya esté cerrada) y no cambia su efectivo.
                    </p>
                </div>
                <?php endif; ?>

                <div x-show="tipoDocumento === 'cotizacion'">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Estado de la cotización</label>
                    <select x-model="estado" class="w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 focus:ring-1 focus:ring-sky-400 outline-none">
                        <option value="borrador">Borrador</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="aprobada">Aprobada</option>
                        <option value="rechazada">Rechazada</option>
                    </select>
                </div>
                <div x-show="tipoDocumento === 'cotizacion'">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Válida hasta</label>
                    <input type="date" value="<?= $fecha_validez ?>" class="w-full px-3 py-2.5 text-sm rounded-xl border border-gray-200 focus:ring-1 focus:ring-sky-400 outline-none">
                </div>
            </div>
        </div>

        <!-- TABS -->
        <div x-data="{ tab: 'productos' }">

            <div class="flex border-b border-gray-100 px-8">
                <button @click="tab = 'productos'"
                        :class="tab === 'productos' ? 'border-b-2 border-sky-500 text-sky-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                        class="text-sm py-3 px-1 mr-8 transition-colors -mb-px">
                    Productos
                </button>
                <button @click="tab = 'notas'"
                        :class="tab === 'notas' ? 'border-b-2 border-sky-500 text-sky-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                        class="text-sm py-3 px-1 transition-colors -mb-px">
                    Notas
                </button>
            </div>

            <!-- Tab: Productos -->
            <div x-show="tab === 'productos'" class="px-6 pt-4 pb-2">

                <!-- Buscador -->
                <div class="flex gap-2 mb-3" @click.away="resultados = []">
                    <?php if (count($depositos ?? []) > 1): ?>
                    <div class="w-44 shrink-0" title="Depósito de donde sale la mercancía de esta venta">
                        <select x-model="depositoId" @change="resultados = []; if (query.trim()) buscarProductos(); refrescarStock()"
                                class="w-full py-2 pl-3 pr-8 text-sm rounded-lg border border-sky-200 bg-sky-50 text-sky-800 font-semibold focus:ring-1 focus:ring-sky-400 outline-none">
                            <?php foreach ($depositos as $d): $d = (array) $d; ?>
                                <option value="<?= (int) $d['deposito_id'] ?>"><?= esc($d['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="relative flex-1">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 text-sm"></i>
                        <input type="text" x-model="query" x-ref="buscarProducto" autocomplete="off"
                               @input.debounce.300ms="buscarProductos()"
                               @keydown.enter.prevent="buscarProductos(true)"
                               placeholder="Escanea o escribe el código (se agrega solo si es exacto) o busca por nombre..."
                               class="w-full pl-9 pr-4 py-2 text-sm rounded-lg border border-gray-200 focus:ring-1 focus:ring-sky-400 focus:border-sky-400 outline-none bg-gray-50">

                        <div x-show="resultados.length > 0" x-transition.opacity
                             class="absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-100 rounded-xl shadow-2xl max-h-80 overflow-y-auto">
                            <template x-for="p in resultados" :key="p.producto_id">
                                <div @click="seleccionarProducto(p)"
                                     class="px-4 py-3 border-b border-gray-50 hover:bg-sky-50 cursor-pointer flex items-center justify-between group">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center group-hover:bg-white">
                                            <i class="fas fa-box text-gray-300 group-hover:text-sky-400 text-xs"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-gray-800" x-text="p.nombre"></div>
                                            <div class="text-xs text-gray-400">
                                                <span class="text-sky-500" x-text="p.codigo"></span>
                                                · Stock: <span :class="p.stock > 0 ? 'text-emerald-600' : 'text-red-500'" x-text="p.stock"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-bold text-sky-600" x-text="'$' + parseFloat(p.precio_a).toFixed(2)"></div>
                                        <div class="text-[10px] text-gray-400 uppercase">Precio 1</div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="w-32 shrink-0" title="Porcentaje que se aplica al precio de TODOS los productos de la lista (positivo sube, negativo baja)">
                        <div class="relative">
                            <input type="number" step="1" x-model="porcentaje" id="aplicar-porcentaje" @keydown="if (event.key === '.' || event.key === ',') event.preventDefault()"
                                   @input.debounce.300ms="aplicarPorcentaje()" @keydown.enter.prevent="aplicarPorcentaje()"
                                   placeholder="Aplicar %"
                                   class="w-full py-2 pl-3 pr-7 text-sm rounded-lg border border-amber-200 bg-amber-50 text-amber-900 font-semibold placeholder-amber-400 focus:ring-1 focus:ring-amber-400 outline-none">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-amber-500 text-sm pointer-events-none">%</span>
                        </div>
                    </div>
                </div>

                <!-- Tabla de items: Nombre (Código del producto) · Precio único · Cantidad · Total -->
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="py-2.5 pr-4 text-left text-xs font-semibold text-gray-500">Nombre (Código del producto)</th>
                                <th class="py-2.5 px-3 text-right text-xs font-semibold text-gray-500 w-56">Precio único</th>
                                <th class="py-2.5 px-3 text-center text-xs font-semibold text-gray-500 w-24">Cantidad</th>
                                <th class="py-2.5 pl-3 text-right text-xs font-semibold text-gray-500 w-28">Total</th>
                                <th class="w-8"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <template x-for="(item, index) in items" :key="item.id">
                                <tr :class="filaClase(item)" class="transition-colors" :title="avisoStock(item)">
                                    <td class="py-3 pr-4">
                                        <div class="text-sm font-semibold text-gray-800">
                                            <button type="button" @click="verFicha(item)" class="text-left hover:text-sky-600 hover:underline decoration-dotted underline-offset-2 transition-colors" title="Ver datos, depósitos y ubicación del producto" x-text="item.nombre"></button>
                                            <span class="text-base font-black tracking-wide text-gray-900" style="font-weight:800" x-text="'(' + item.codigo + ')'"></span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button" x-show="item.precio_base_a > 0" @click="item.precio_ref = item.precio_base_a; item.precio = precioConPorcentaje(item.precio_ref); calcularTotales()"
                                                :class="(item.precio_ref != null ? item.precio_ref : item.precio) === item.precio_base_a ? 'bg-sky-500 text-white' : 'bg-gray-100 text-gray-400 hover:bg-sky-50'"
                                                title="Precio A" class="px-1.5 py-1 rounded text-[10px] font-bold transition-colors leading-none">A</button>
                                            <button type="button" x-show="item.precio_base_b > 0" @click="item.precio_ref = item.precio_base_b; item.precio = precioConPorcentaje(item.precio_ref); calcularTotales()"
                                                :class="(item.precio_ref != null ? item.precio_ref : item.precio) === item.precio_base_b ? 'bg-emerald-500 text-white' : 'bg-gray-100 text-gray-400 hover:bg-emerald-50'"
                                                title="Precio B" class="px-1.5 py-1 rounded text-[10px] font-bold transition-colors leading-none">B</button>
                                            <button type="button" x-show="item.precio_base_c > 0" @click="item.precio_ref = item.precio_base_c; item.precio = precioConPorcentaje(item.precio_ref); calcularTotales()"
                                                :class="(item.precio_ref != null ? item.precio_ref : item.precio) === item.precio_base_c ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-400 hover:bg-orange-50'"
                                                title="Precio C" class="px-1.5 py-1 rounded text-[10px] font-bold transition-colors leading-none">C</button>
                                            <div class="relative inline-flex items-center">
                                                <span class="absolute left-1.5 text-xs text-gray-400">$</span>
                                                <input type="number" x-model.number="item.precio" @change="item.precio_ref = item.precio / factorPorcentaje(); calcularTotales()" step="0.01"
                                                       class="w-24 pl-5 text-right text-sm py-1 bg-gray-50 rounded border border-gray-200 focus:border-sky-400 focus:ring-0 font-semibold">
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <input type="number" x-model.number="item.cantidad" @change="calcularTotales()" min="1"
                                               class="w-16 text-center text-sm py-1 bg-gray-50 rounded border border-gray-200 focus:border-sky-400 focus:ring-0">
                                    </td>
                                    <td class="py-3 pl-3 text-right text-sm font-bold text-gray-700"
                                        x-text="'$' + calcularTotalLinea(item).toFixed(2)"></td>
                                    <td class="py-3 text-center">
                                        <button @click="eliminarItem(index)" class="text-gray-300 hover:text-red-400 transition-colors">
                                            <i class="fas fa-times text-sm"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="items.length === 0">
                                <tr>
                                    <td colspan="5" class="py-16 text-center text-sm text-gray-400 italic">
                                        <i class="fas fa-shopping-basket text-gray-200 text-3xl block mb-2"></i>
                                        No hay productos. Busca un producto para comenzar.
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab: Notas -->
            <div x-show="tab === 'notas'" class="px-8 py-5">
                <textarea x-model="observaciones" rows="5"
                          placeholder="Notas internas o para el cliente..."
                          class="w-full text-sm text-gray-700 bg-transparent border-0 resize-none outline-none placeholder-gray-300 focus:ring-0 p-0"></textarea>
            </div>

        </div>

        <!-- Pie: Totales -->
        <div class="relative px-8 py-5 border-t border-gray-100 flex items-end justify-end flex-wrap gap-4">
            <div class="flex items-center gap-2 md:absolute md:left-1/2 md:-translate-x-1/2 md:bottom-5">
                <button @click="procesarDocumento()" :disabled="processing"
                    class="bg-sky-500 hover:bg-sky-600 text-white text-sm font-semibold py-2 px-5 rounded-lg shadow-sm transition-colors flex items-center gap-2 disabled:opacity-50">
                <i x-show="!processing" class="fas fa-save text-xs"></i>
                <i x-show="processing" class="fas fa-spinner fa-spin text-xs"></i>
                <span x-text="tipoDocumento === 'venta' ? 'Guardar Venta' : 'Guardar Cotización'"></span>
                </button>
                <a href="<?= ($tipo_documento ?? 'venta') === 'cotizacion' ? '/ventas/cotizaciones' : '/ventas' ?>" @click="borrarBorrador()"
                   class="text-sm font-medium text-gray-500 hover:text-red-500 py-2 px-4 rounded-lg border border-gray-200 hover:border-red-200 transition-colors">
                Cancelar
                </a>
                </div>
            <div class="text-right space-y-1.5 min-w-[280px]">
                <div class="flex items-center justify-between gap-16 text-sm text-gray-500">
                    <span>Subtotal:</span>
                    <span class="font-semibold text-gray-700" x-text="'$' + subtotal.toFixed(2)"></span>
                </div>
                <div class="flex items-center justify-between gap-16 text-sm text-gray-500">
                    <span>Descuento:</span>
                    <div class="flex items-center gap-1">
                        <span class="text-gray-400 text-xs">$</span>
                        <input type="number" x-model.number="descuentoGlobal" @change="calcularTotales()"
                               class="w-20 text-right py-0.5 px-2 text-sm bg-gray-50 rounded border border-gray-200 focus:border-sky-400 focus:ring-0">
                    </div>
                </div>
                <div class="flex items-center justify-between gap-16 text-sm text-gray-500"<?= tasa_itbms() > 0 ? "" : " style=\"display:none\"" ?>>
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" x-model="conItbms" @change="calcularTotales()" class="rounded border-gray-300 text-sky-500 focus:ring-sky-400">
                        <span>ITBMS (<?= rtrim(rtrim(number_format(tasa_itbms() * 100, 2), "0"), ".") ?>%):</span>
                    </label>
                    <span class="font-semibold text-gray-700" x-text="'$' + itbms.toFixed(2)"></span>
                </div>
                <div class="flex items-center justify-between gap-16 pt-2 border-t border-gray-100 text-sm">
                    <span class="font-semibold text-gray-700">Total:</span>
                    <span class="text-xl font-extrabold text-sky-600" x-text="'$' + total.toFixed(2)"></span>
                </div>
            </div>
        </div>

    </div><!-- /documento -->


    <!-- Ventana: datos del producto, existencia y ubicación por depósito -->
    <div x-show="ficha.abierta" x-cloak @keydown.escape.window="ficha.abierta = false" @click.self="ficha.abierta = false"
         class="fixed inset-0 z-50 bg-black/45 flex items-start justify-center overflow-y-auto p-4 sm:p-8">
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl" @click.stop>
            <button type="button" @click="ficha.abierta = false" title="Cerrar" class="absolute top-3 right-4 text-3xl leading-none text-gray-300 hover:text-gray-500">&times;</button>
            <div x-show="ficha.cargando" class="py-16 text-center text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Cargando producto…</div>
            <div x-show="ficha.error" class="py-16 text-center text-red-500" x-text="ficha.error"></div>
            <template x-if="ficha.datos">
                <div class="p-6">
                    <div class="flex items-start gap-4 mb-4 pr-8">
                        <template x-if="ficha.datos.producto.imagen">
                            <img :src="ficha.datos.producto.imagen" alt="" class="w-24 h-24 rounded-xl object-cover bg-gray-100 shrink-0">
                        </template>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 leading-tight" x-text="ficha.datos.producto.nombre"></h3>
                            <p class="text-sm text-gray-500 mt-0.5"><span class="font-mono font-bold text-gray-700" x-text="ficha.datos.producto.codigo"></span></p>
                            <span x-show="ficha.datos.producto.estado !== 'activo'" class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-500"><i class="fas fa-eye-slash"></i> Inactivo</span>
                        </div>
                    </div>

                    <dl class="grid grid-cols-2 gap-x-6 gap-y-1.5 text-sm mb-4">
                        <div class="flex justify-between gap-3 border-b border-gray-50 py-1"><dt class="text-gray-500">Código de barras</dt><dd class="font-mono text-gray-800" x-text="ficha.datos.producto.codigo_barras || '—'"></dd></div>
                        <div class="flex justify-between gap-3 border-b border-gray-50 py-1"><dt class="text-gray-500">Marca</dt><dd class="text-gray-800" x-text="ficha.datos.producto.marca || '—'"></dd></div>
                        <div class="flex justify-between gap-3 border-b border-gray-50 py-1"><dt class="text-gray-500">Categoría</dt><dd class="text-gray-800 text-right" x-text="ficha.datos.producto.categoria || '—'"></dd></div>
                        <div class="flex justify-between gap-3 border-b border-gray-50 py-1"><dt class="text-gray-500">Proveedor</dt><dd class="text-gray-800 text-right" x-text="ficha.datos.producto.proveedor || '—'"></dd></div>
                        <div class="flex justify-between gap-3 border-b border-gray-50 py-1"><dt class="text-gray-500">Precio A</dt><dd class="font-bold text-sky-700" x-text="ficha.datos.precios.a > 0 ? '$' + Number(ficha.datos.precios.a).toFixed(2) : '—'"></dd></div>
                        <div class="flex justify-between gap-3 border-b border-gray-50 py-1"><dt class="text-gray-500">Precio B</dt><dd class="font-bold text-emerald-700" x-text="ficha.datos.precios.b > 0 ? '$' + Number(ficha.datos.precios.b).toFixed(2) : '—'"></dd></div>
                        <div class="flex justify-between gap-3 border-b border-gray-50 py-1"><dt class="text-gray-500">Precio C</dt><dd class="font-bold text-orange-600" x-text="ficha.datos.precios.c > 0 ? '$' + Number(ficha.datos.precios.c).toFixed(2) : '—'"></dd></div>
                        <div class="flex justify-between gap-3 border-b border-gray-50 py-1" x-show="ficha.datos.costo !== null"><dt class="text-gray-500">Costo</dt><dd class="text-gray-800" x-text="'$' + Number(ficha.datos.costo || 0).toFixed(2)"></dd></div>
                    </dl>
                    <p x-show="ficha.datos.producto.descripcion" class="text-sm text-gray-600 mb-4" x-text="ficha.datos.producto.descripcion"></p>

                    <table class="w-full text-sm border border-gray-200 border-collapse">
                        <thead>
                            <tr class="text-gray-900">
                                <th class="px-3 py-2 text-left font-bold border border-gray-200"><i class="fas fa-warehouse mr-1"></i>Depósito</th>
                                <th class="px-3 py-2 text-center font-bold border border-gray-200 w-28"><i class="fas fa-cubes mr-1"></i>Cantidad</th>
                                <th class="px-3 py-2 text-center font-bold border border-gray-200 w-40"><i class="fas fa-location-dot mr-1"></i>Ubicación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="d in ficha.datos.depositos" :key="d.deposito_id">
                                <tr :class="String(d.deposito_id) === String(depositoId) ? 'bg-sky-50' : ''">
                                    <td class="px-3 py-2 border border-gray-200">
                                        <span x-text="d.nombre"></span>
                                        <span x-show="String(d.deposito_id) === String(depositoId)" class="ml-1 text-[10px] font-bold text-sky-600">ESTA VENTA</span>
                                    </td>
                                    <td class="px-3 py-2 text-center border border-gray-200 font-bold" :class="Number(d.existencia) > 0 ? 'text-gray-900' : 'text-rose-500'" x-text="Number(d.existencia).toFixed(2).replace(/\.00$/, '')"></td>
                                    <td class="px-3 py-2 text-center border border-gray-200 font-mono font-semibold text-gray-700" x-text="d.ubicacion || '—'"></td>
                                </tr>
                            </template>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-2 border border-gray-200 font-bold">Total</td>
                                <td class="px-3 py-2 text-center border border-gray-200 font-bold" x-text="Number(ficha.datos.total).toFixed(2).replace(/\.00$/, '')"></td>
                                <td class="border border-gray-200"></td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Traslado entre depósitos, sin salir de la venta -->
                    <div x-show="puedeTrasladar && ficha.datos.depositos.length > 1" class="mt-4 rounded-xl border border-gray-200 p-3">
                        <p class="text-sm font-bold text-gray-800 mb-2"><i class="fas fa-right-left text-sky-500 mr-1.5"></i>Hacer traslado de este producto</p>
                        <div class="flex flex-wrap items-end gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Desde</label>
                                <select x-model="ficha.traslado.desde" class="py-1.5 pl-2 pr-7 text-sm rounded-lg border border-gray-200 focus:ring-1 focus:ring-sky-400 outline-none">
                                    <option value="">Elegir…</option>
                                    <template x-for="d in ficha.datos.depositos" :key="'o' + d.deposito_id">
                                        <option :value="String(d.deposito_id)" :disabled="Number(d.existencia) <= 0" x-text="d.nombre + ' (' + Number(d.existencia) + ')'"></option>
                                    </template>
                                </select>
                            </div>
                            <i class="fas fa-arrow-right text-gray-300 pb-2.5"></i>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Hacia</label>
                                <select x-model="ficha.traslado.hacia" class="py-1.5 pl-2 pr-7 text-sm rounded-lg border border-gray-200 focus:ring-1 focus:ring-sky-400 outline-none">
                                    <template x-for="d in ficha.datos.depositos" :key="'d' + d.deposito_id">
                                        <option :value="String(d.deposito_id)" x-text="d.nombre"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Cantidad</label>
                                <input type="number" min="1" step="1" x-model.number="ficha.traslado.cantidad" class="w-24 py-1.5 px-2 text-sm text-center rounded-lg border border-gray-200 focus:ring-1 focus:ring-sky-400 outline-none">
                            </div>
                            <button type="button" @click="hacerTraslado()" :disabled="ficha.traslado.enviando"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-sky-500 text-white rounded-lg text-sm font-semibold hover:bg-sky-600 disabled:opacity-50">
                                <i class="fas" :class="ficha.traslado.enviando ? 'fa-spinner fa-spin' : 'fa-right-left'"></i> Trasladar
                            </button>
                        </div>
                        <p x-show="ficha.traslado.error" class="mt-2 text-xs text-red-600" x-text="ficha.traslado.error"></p>
                        <p x-show="ficha.traslado.ok" class="mt-2 text-xs font-semibold text-emerald-600"><i class="fas fa-check mr-1"></i><span x-text="ficha.traslado.ok"></span></p>
                        <p class="mt-1.5 text-[11px] text-gray-400">El traslado queda completado: el stock se mueve ahora y queda en el historial de traslados.</p>
                    </div>
                </div>
            </template>
            <div class="flex justify-end gap-2 px-6 py-3 border-t border-gray-100 bg-gray-50/70 rounded-b-2xl">
                <a x-show="ficha.datos" :href="ficha.datos ? '/inventario/' + ficha.datos.producto.producto_id : '#'" target="_blank" class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50"><i class="fas fa-up-right-from-square mr-1"></i> Abrir producto</a>
                <button type="button" @click="ficha.abierta = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-white">Cerrar</button>
            </div>
        </div>
    </div>

</div><!-- /x-data -->

<script>
    function ventasForm() {
        return {
            tipoDocumento: '<?= $tipo_documento ?? 'venta' ?>',
            clienteId: '',
            clienteNombre: 'Consumidor Final',
            searchCliente: '',
            clienteSaldo: 0,
            clienteLimite: 0,
            abiertoCliente: false,
            nuevoCliente: { abierto: false, nombre: '', telefono: '', ruc: '', limite: '', guardando: false, error: '' },
            // Clientes activos (para buscar sin ir al servidor) con su saldo y límite de crédito
            clientes: <?= json_encode(array_map(fn ($c) => [
                'id'        => (string) $c['cliente_id'],
                'codigo'    => (string) ($c['codigo'] ?? ''),
                'nombre'    => (string) ($c['nombre'] ?? ''),
                'ruc'       => (string) ($c['ruc'] ?? ''),
                'telefono'  => (string) ($c['telefono'] ?? ''),
                'direccion' => (string) ($c['direccion'] ?? ''),
                'saldo'     => (float) ($c['saldo_pendiente'] ?? 0),
                'limite'    => (float) ($c['limite_credito'] ?? 0),
            ], $clientes ?? []), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>,
            clienteRuc: 'N/A',
            clienteTelefono: 'N/A',
            clienteDireccion: 'Consumidor Final',
            depositoId: '<?= (int) ($deposito_defecto ?? (((array) ($depositos[0] ?? []))['deposito_id'] ?? 0)) ?>',
            // Ventas normales / al por mayor: a crédito por defecto (se puede cambiar a efectivo, Yappy, etc.)
            formaPago: '<?= ($tipo_documento ?? 'venta') === 'cotizacion' ? 'efectivo' : 'credito' ?>',
            sinLimite: <?= filter_var(env('credito.sin_limite', false), FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false' ?>,
            hoy: '<?= date('Y-m-d') ?>',
            fechaVenta: '<?= date('Y-m-d') ?>',
            estado: '<?= ($tipo_documento ?? 'venta') === 'cotizacion' ? 'pendiente' : 'pagada' ?>',
            observaciones: '',
            query: '',
            cantidadBusqueda: 1,
            porcentaje: '',   // % que se aplica a TODOS los productos de la lista (+ sube, - baja)
            resultados: [],
            productoSeleccionado: null,
            items: [],
            ficha: { abierta: false, cargando: false, error: '', datos: null, item: null, traslado: { desde: '', hacia: '', cantidad: 1, enviando: false, error: '', ok: '' } },
            puedeTrasladar: <?= \App\Libraries\Auth::can('traslados.crear') ? 'true' : 'false' ?>,
            subtotal: 0,
            itbms: 0,
            conItbms: true,   // casilla ITBMS: marcada por defecto
            descuentoGlobal: 0,
            total: 0,
            cotizacionOrigenId: null,
            processing: false,
            toasts: [],

            init() {
                const preload = <?= json_encode($cotizacion_preload ?? null) ?>;
                if (preload) {
                    this.clienteId = preload.cliente_id || '';
                    this.clienteNombre = preload.cliente_nombre;
                    const pc = this.clientes.find(c => c.id === String(preload.cliente_id || ''));
                    if (pc) { this.clienteSaldo = pc.saldo; this.clienteLimite = pc.limite; }
                    this.clienteRuc = preload.cliente_ruc;
                    this.clienteTelefono = preload.cliente_telefono;
                    this.clienteDireccion = preload.cliente_direccion;
                    this.observaciones = preload.notas;
                    this.items = preload.items;
                    this.items.forEach(i => { if (i.precio_ref == null) i.precio_ref = i.precio; });
                    this.cotizacionOrigenId = preload.cotizacion_id;
                    this.calcularTotales();
                    this.refrescarStock();
                    this.notify('Datos de cotización cargados', 'success');
                }
                else { this.restaurarBorrador(); }
                this.$watch('tipoDocumento', (val) => {
                    this.estado = val === 'venta' ? 'pagada' : 'pendiente';
                    this.guardarBorrador();
                });
                ['clienteId', 'formaPago', 'depositoId', 'observaciones', 'conItbms', 'descuentoGlobal'].forEach(k => this.$watch(k, () => this.guardarBorrador()));
            },

            // ── Borrador en el navegador (como el carrito del POS): si se recarga la página o se cierra por error,
            // la venta que se estaba haciendo se recupera. Se borra al guardar la venta o al pulsar Cancelar.
            claveBorrador: 'venta_borrador_<?= (int) \App\Libraries\Auth::id() ?>',
            guardarBorrador() {
                try {
                    if (!this.items.length) { localStorage.removeItem(this.claveBorrador); return; }
                    localStorage.setItem(this.claveBorrador, JSON.stringify({
                        tipo: this.tipoDocumento, clienteId: this.clienteId, clienteNombre: this.clienteNombre,
                        clienteRuc: this.clienteRuc, clienteTelefono: this.clienteTelefono, clienteDireccion: this.clienteDireccion,
                        formaPago: this.formaPago, depositoId: this.depositoId, observaciones: this.observaciones,
                        conItbms: this.conItbms, descuentoGlobal: this.descuentoGlobal, items: this.items, fecha: this.hoy
                    }));
                } catch (e) { /* sin almacenamiento local: se sigue sin borrador */ }
            },
            restaurarBorrador() {
                try {
                    const raw = localStorage.getItem(this.claveBorrador);
                    if (!raw) return;
                    const b = JSON.parse(raw);
                    // Un borrador de otro día se descarta: precios, existencias y fecha pueden haber cambiado
                    if (!b || !Array.isArray(b.items) || !b.items.length || b.fecha !== this.hoy) { localStorage.removeItem(this.claveBorrador); return; }
                    this.tipoDocumento = b.tipo || this.tipoDocumento;
                    this.estado = this.tipoDocumento === 'venta' ? 'pagada' : 'pendiente';
                    this.clienteId = b.clienteId || '';
                    this.clienteNombre = b.clienteNombre || 'Consumidor Final';
                    this.clienteRuc = b.clienteRuc || 'N/A';
                    this.clienteTelefono = b.clienteTelefono || 'N/A';
                    this.clienteDireccion = b.clienteDireccion || 'Consumidor Final';
                    const pc = this.clientes.find(c => c.id === String(b.clienteId || ''));
                    if (pc) { this.clienteSaldo = pc.saldo; this.clienteLimite = pc.limite; }
                    if (b.formaPago) this.formaPago = b.formaPago;
                    if (b.depositoId) this.depositoId = String(b.depositoId);
                    this.observaciones = b.observaciones || '';
                    this.conItbms = b.conItbms !== false;
                    this.descuentoGlobal = Number(b.descuentoGlobal) || 0;
                    this.items = b.items;
                    this.calcularTotales();
                    this.refrescarStock();
                    this.notify('Se recuperó la venta que estabas haciendo', 'success');
                } catch (e) { try { localStorage.removeItem(this.claveBorrador); } catch (x) {} }
            },
            borrarBorrador() { try { localStorage.removeItem(this.claveBorrador); } catch (e) {} },

            notify(message, type = 'success') {
                const id = Date.now();
                this.toasts.push({ id, message, type, show: true });
                setTimeout(() => this.removeToast(id), 5000);
            },

            removeToast(id) {
                const index = this.toasts.findIndex(t => t.id === id);
                if (index !== -1) {
                    this.toasts[index].show = false;
                    setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 500);
                }
            },

            seleccionarCliente(id, nombre, ruc, tel, dir) {
                this.clienteId = id;
                this.clienteNombre = nombre;
                this.clienteRuc = ruc || 'N/A';
                this.clienteTelefono = tel || 'N/A';
                this.clienteDireccion = dir || 'Consumidor Final';
            },

            // Búsqueda sin acentos por nombre, código, RUC o teléfono (máx. 40 resultados)
            normalizar(t) { return String(t || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); },
            clientesFiltrados() {
                const q = this.normalizar(this.searchCliente.trim());
                const lista = q
                    ? this.clientes.filter(c => this.normalizar([c.nombre, c.codigo, c.ruc, c.telefono].join(' ')).includes(q))
                    : this.clientes;
                return lista.slice(0, 40);
            },
            elegirCliente(c) {
                this.seleccionarCliente(c.id, c.nombre, c.ruc, c.telefono, c.direccion);
                this.clienteSaldo = c.saldo;
                this.clienteLimite = c.limite;
                this.abiertoCliente = false;
                this.searchCliente = '';
            },
            quitarCliente() {
                this.seleccionarCliente('', 'Consumidor Final', 'N/A', 'N/A', 'Consumidor Final');
                this.clienteSaldo = 0;
                this.clienteLimite = 0;
                this.$nextTick(() => { this.$refs.buscarCliente?.focus(); this.abiertoCliente = true; });
            },
            abrirNuevoCliente() {
                this.nuevoCliente = { abierto: true, nombre: this.searchCliente.trim(), telefono: '', ruc: '', limite: '', guardando: false, error: '' };
                this.abiertoCliente = false;
            },
            async crearCliente() {
                const n = this.nuevoCliente;
                n.error = '';
                if (!n.nombre.trim()) { n.error = 'Escribe el nombre.'; return; }
                const existe = this.clientes.find(c => this.normalizar(c.nombre) === this.normalizar(n.nombre.trim()));
                if (existe && !confirm('Ya existe un cliente llamado "' + existe.nombre + '". ¿Crear otro igual?')) return;
                n.guardando = true;
                try {
                    const r = await fetch('/ventas/clientes/rapido', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' },
                        body: JSON.stringify({ nombre: n.nombre.trim(), telefono: n.telefono.trim(), ruc: n.ruc.trim(), limite_credito: n.limite === '' ? 0 : n.limite })
                    });
                    const d = await r.json();
                    if (!r.ok || !d.success) throw new Error(d.error || 'No se pudo crear el cliente.');
                    const c = { id: String(d.cliente_id), codigo: d.codigo || '', nombre: d.nombre, ruc: n.ruc.trim(), telefono: n.telefono.trim(), direccion: '', saldo: 0, limite: parseFloat(d.limite_credito) || 0 };
                    this.clientes.unshift(c);
                    this.elegirCliente(c);
                    n.abierto = false;
                    this.notify('Cliente creado: ' + c.nombre, 'success');
                } catch (e) {
                    n.error = e.message;
                }
                n.guardando = false;
            },
            // Si es a crédito: ¿le alcanza el límite al cliente para este total?
            avisoCredito() {
                if (!this.clienteId) return { nivel: 'mal', texto: 'Elige el cliente al que se le fía.', subirA: 0 };
                const disp = Math.max(0, this.clienteLimite - this.clienteSaldo);
                const total = Math.round(this.total * 100) / 100;
                if (this.sinLimite) return { nivel: 'ok', texto: 'Queda debiendo $' + (this.clienteSaldo + total).toFixed(2) + ' (a crédito, sin límite).', subirA: 0 };
                if (total <= disp + 0.005) return { nivel: 'ok', texto: 'Queda debiendo $' + (this.clienteSaldo + total).toFixed(2) + ' (límite $' + this.clienteLimite.toFixed(2) + ').', subirA: 0 };
                const necesita = Math.ceil((this.clienteSaldo + total) * 100) / 100;
                return { nivel: 'mal', texto: 'El límite de crédito no alcanza: disponible $' + disp.toFixed(2) + ', esta venta $' + total.toFixed(2) + '.', subirA: total > 0 ? necesita : 0 };
            },
            async subirLimite(monto) {
                if (!confirm('¿Subir el límite de crédito de ' + this.clienteNombre + ' a $' + monto.toFixed(2) + '?')) return;
                try {
                    const r = await fetch('/ventas/clientes/' + this.clienteId + '/limite', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' },
                        body: JSON.stringify({ limite_credito: monto })
                    });
                    const d = await r.json();
                    if (!r.ok || !d.success) throw new Error(d.error || 'No se pudo cambiar el límite.');
                    this.clienteLimite = parseFloat(d.limite_credito);
                    const c = this.clientes.find(x => x.id === String(this.clienteId));
                    if (c) c.limite = this.clienteLimite;
                    this.notify('Límite de crédito actualizado', 'success');
                } catch (e) {
                    this.notify(e.message, 'error');
                }
            },

            // Como el sistema anterior: si lo escrito (o escaneado) es el código exacto de un producto,
            // se agrega solo a la orden; si no, se muestra la lista para escoger. Enter busca de inmediato.
            buscarProductos(enter = false) {
                const q = this.query.trim();
                if (q.length < 1) { this.resultados = []; return; }
                fetch(`/api/productos/buscar?q=${encodeURIComponent(q)}&deposito_id=${encodeURIComponent(this.depositoId || '')}`)
                    .then(r => r.json())
                    .then(data => {
                        if (q !== this.query.trim()) return;   // ya escribió otra cosa: se ignora la respuesta vieja
                        const lista = data.productos || [];
                        if (lista.length === 1 && (data.exacto || enter)) { this.agregarAutomatico(lista[0]); return; }
                        this.resultados = lista;
                        if (lista.length === 0) this.notify('No se encontraron productos', 'warning');
                    });
            },

            agregarAutomatico(p) {
                this.agregarItem(p, this.cantidadBusqueda || 1, p.unidad_escaneada || null);
                this.productoSeleccionado = null;
                this.resultados = [];
                this.query = '';
                this.cantidadBusqueda = 1;
                this.$nextTick(() => this.$refs.buscarProducto && this.$refs.buscarProducto.focus());
            },

            seleccionarProducto(p) {
                this.productoSeleccionado = p;
                this.query = p.nombre;
                this.resultados = [];
            },

            agregarProductoSiSeleccionado() {
                if (!this.productoSeleccionado) { this.notify('Selecciona un producto de la lista', 'warning'); return; }
                this.agregarItem(this.productoSeleccionado, this.cantidadBusqueda);
                this.productoSeleccionado = null;
                this.query = '';
                this.cantidadBusqueda = 1;
            },

            // Ventana con los datos del producto, su existencia y ubicación en cada depósito
            async verFicha(item) {
                this.ficha = { abierta: true, cargando: true, error: '', datos: null, item: item, traslado: { desde: '', hacia: '', cantidad: 1, enviando: false, error: '', ok: '' } };
                try {
                    const r = await fetch('/api/productos/' + encodeURIComponent(item.id) + '/ficha', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const d = await r.json();
                    if (!r.ok || d.error) throw new Error(d.error || 'No se pudo cargar el producto');
                    this.ficha.datos = d;
                    this.preparaTraslado();
                } catch (e) { this.ficha.error = e.message; }
                this.ficha.cargando = false;
            },

            // Traslado propuesto: hacia el depósito de esta venta, desde el otro con más existencia, por lo que falta
            preparaTraslado() {
                const t = this.ficha.traslado, deps = this.ficha.datos.depositos;
                const hacia = deps.find(d => String(d.deposito_id) === String(this.depositoId)) || deps[0];
                const origenes = deps.filter(d => d.deposito_id !== hacia.deposito_id && Number(d.existencia) > 0)
                                     .sort((a, b) => Number(b.existencia) - Number(a.existencia));
                t.hacia = String(hacia.deposito_id);
                t.desde = origenes.length ? String(origenes[0].deposito_id) : '';
                const falta = Math.max(1, Number(this.ficha.item ? this.ficha.item.cantidad : 1) - Math.max(0, Number(hacia.existencia)));
                t.cantidad = origenes.length ? Math.min(falta, Number(origenes[0].existencia)) : 1;
            },
            existenciaDeposito(id) {
                const d = (this.ficha.datos ? this.ficha.datos.depositos : []).find(x => String(x.deposito_id) === String(id));
                return d ? Number(d.existencia) : 0;
            },
            async hacerTraslado() {
                const t = this.ficha.traslado;
                t.error = ''; t.ok = '';
                if (!t.desde || !t.hacia || t.desde === t.hacia) { t.error = 'Elige un depósito de origen y otro de destino.'; return; }
                const cant = Number(t.cantidad);
                if (!(cant > 0)) { t.error = 'Escribe la cantidad a trasladar.'; return; }
                if (cant > this.existenciaDeposito(t.desde)) { t.error = 'El depósito de origen solo tiene ' + this.existenciaDeposito(t.desde) + '.'; return; }
                t.enviando = true;
                try {
                    const r = await fetch('/inventario/traslados/guardar', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' },
                        body: JSON.stringify({ origen_id: Number(t.desde), destino_id: Number(t.hacia), completado: true,
                                               notas: 'Traslado hecho desde Nueva venta', items: [{ producto_id: this.ficha.item.id, cantidad: cant }] })
                    });
                    const d = await r.json().catch(() => ({}));
                    if (!r.ok || !d.success) throw new Error(d.error || 'No se pudo hacer el traslado.');
                    const msg = 'Traslado #' + d.traslado_id + ' hecho: ' + cant + ' unidad(es).';
                    await this.verFicha(this.ficha.item);
                    this.ficha.traslado.ok = msg;
                    this.refrescarStock();
                } catch (e) { t.error = e.message; }
                t.enviando = false;
            },

            // Color de la fila según la existencia en el depósito elegido:
            // sin stock o stock menor a lo pedido → rosado · alcanza → blanco
            filaClase(item) {
                if (item.stock == null || isNaN(item.stock)) return 'hover:bg-gray-50/60';
                if (item.stock <= 0 || item.stock < Number(item.cantidad || 0)) return 'bg-rose-100';
                return 'hover:bg-gray-50/60';
            },
            avisoStock(item) {
                if (item.stock == null || isNaN(item.stock)) return '';
                if (item.stock <= 0) return 'Sin existencia en este depósito';
                if (item.stock < Number(item.cantidad || 0)) return 'Solo hay ' + item.stock + ' en este depósito';
                return '';
            },
            // Vuelve a leer la existencia de cada línea (al cambiar de depósito o al cargar una cotización)
            async refrescarStock() {
                for (const it of this.items) {
                    try {
                        const r = await fetch(`/api/productos/buscar?q=${encodeURIComponent(it.codigo)}&deposito_id=${encodeURIComponent(this.depositoId || '')}`);
                        const d = await r.json();
                        const f = (d.productos || []).find(x => String(x.producto_id) === String(it.id));
                        it.stock = f ? parseFloat(f.stock) : 0;
                    } catch (e) { /* se deja como estaba */ }
                }
            },

            agregarItem(p, cant, unidadId = null) {
                const index = this.items.findIndex(i => i.id === p.producto_id);
                if (index !== -1) {
                    this.items[index].cantidad += cant;
                    // El último producto que se toca va arriba
                    if (index > 0) this.items.unshift(this.items.splice(index, 1)[0]);
                } else {
                    // El último producto que entra queda de primero en la lista
                    this.items.unshift({
                        id: p.producto_id,
                        codigo: p.codigo,
                        nombre: p.nombre,
                        cantidad: cant,
                        unidad_id: '',
                        unidades_adicionales: p.unidades || [],
                        precio_base_a: parseFloat(p.precio_a) || 0,
                        precio_base_b: parseFloat(p.precio_b) || 0,
                        precio_base_c: parseFloat(p.precio_c) || 0,
                        precio_ref: parseFloat(p.precio_a) || 0,
                        precio: this.precioConPorcentaje(parseFloat(p.precio_a) || 0),
                        descuento: 0,
                        stock: p.stock != null ? parseFloat(p.stock) : null,
                        applica_itbms: p.itbms == 1
                    });
                    // Se escaneó el código de una presentación (caja, paca…): se vende esa presentación
                    if (unidadId) {
                        const nuevo = this.items[0];
                        nuevo.unidad_id = String(unidadId);
                        this.cambiarUnidad(nuevo);
                    }
                }
                this.calcularTotales();
                this.notify(`Producto añadido: ${p.nombre}`, 'success');
            },

            // ── Porcentaje a todos los productos (ventas al por mayor) ─────────────────────────
            // precio_ref = precio elegido (A/B/C o escrito) sin porcentaje; precio = precio_ref × (1 + % / 100).
            // Como siempre se parte de precio_ref, aplicar el mismo % dos veces no se acumula.
            factorPorcentaje() {
                const p = Math.trunc(parseFloat(this.porcentaje));
                return (isNaN(p) || p <= -100) ? 1 : 1 + p / 100;
            },
            precioConPorcentaje(ref) {
                return Math.round((parseFloat(ref) || 0) * this.factorPorcentaje() * 100) / 100;
            },
            aplicarPorcentaje() {
                const p = parseFloat(this.porcentaje);
                if (this.porcentaje !== '' && this.porcentaje !== null && p <= -100) {
                    this.notify('El porcentaje debe ser mayor que -100.', 'warning');
                    return;
                }
                this.items.forEach(i => { i.precio = this.precioConPorcentaje(i.precio_ref != null ? i.precio_ref : i.precio); });
                this.calcularTotales();
            },

            cambiarUnidad(item) {
                let ref = item.precio_ref != null ? item.precio_ref : item.precio;
                if (!item.unidad_id) {
                    ref = item.precio_base_a;
                } else {
                    const unit = item.unidades_adicionales.find(u => u.unidad_id == item.unidad_id);
                    if (unit) ref = parseFloat(unit.precio_a) || 0;
                }
                item.precio_ref = ref;
                item.precio = this.precioConPorcentaje(ref);
                this.calcularTotales();
            },

            eliminarItem(index) {
                this.items.splice(index, 1);
                this.calcularTotales();
            },

            calcularTotalLinea(item) {
                return (item.cantidad * item.precio) - (item.descuento || 0);
            },

            calcularTotales() {
                this.subtotal = this.items.reduce((sum, item) => sum + this.calcularTotalLinea(item), 0);
                // El servidor cobra ITBMS sobre toda la venta cuando la casilla está marcada
                this.itbms = this.conItbms ? Math.max(0, this.subtotal * <?= json_encode(tasa_itbms()) ?>) : 0;
                this.total = this.subtotal - this.descuentoGlobal + this.itbms;
                this.guardarBorrador();
            },

            async procesarDocumento() {
                if (this.items.length === 0) { this.notify('Agrega al menos un producto.', 'error'); return; }
                const anterior = this.tipoDocumento === 'venta' && this.fechaVenta && this.fechaVenta !== this.hoy;
                if (anterior) {
                    if (this.formaPago !== 'credito') { this.notify('Una venta de un día anterior solo puede ser a crédito.', 'error'); return; }
                    if (!this.clienteId) { this.notify('Elige el cliente del crédito.', 'error'); return; }
                    if (!confirm('¿Registrar esta venta a crédito con fecha ' + this.fechaVenta.split('-').reverse().join('/') + '?')) return;
                }
                this.processing = true;
                const payload = {
                    tipo: this.tipoDocumento,
                    cliente_id: this.clienteId,
                    deposito_id: this.depositoId,
                    forma_pago: this.formaPago,
                    estado: this.estado,
                    descuento: this.descuentoGlobal,
                    aplicar_itbms: this.conItbms,
                    notas: this.observaciones,
                    cotizacion_origen_id: this.cotizacionOrigenId,
                    fecha: anterior ? this.fechaVenta : null,
                    items: this.items.map(i => ({
                        producto_id: i.id,
                        cantidad: i.cantidad,
                        unidad_id: i.unidad_id,
                        precio: i.precio,
                        descuento: i.descuento,
                        deposito_id: this.depositoId || null
                    }))
                };
                try {
                    const endpoint = this.tipoDocumento === 'venta' ? '/ventas/procesar' : '/ventas/cotizaciones/guardar';
                    const res = await fetch(endpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' },
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    if (!res.ok) throw new Error(data.error || 'Error al procesar');
                    this.notify('Guardado con éxito', 'success');
                    this.borrarBorrador();
                    setTimeout(() => {
                        window.location.href = this.tipoDocumento === 'venta'
                            ? `/ventas/venta/${data.venta_id}`
                            : `/ventas/cotizaciones/${data.cotizacion_id}`;
                    }, 1200);
                } catch (e) {
                    this.notify(e.message, 'error');
                } finally {
                    this.processing = false;
                }
            }
        }
    }
</script>

<?php // Quien puede cargar ventas de días anteriores no necesita caja abierta para eso (las de hoy la siguen pidiendo en el servidor) ?>
<?= view('cajas/_aviso_abrir', ['caja_actual' => $caja_actual ?? null, 'exigir_caja' => ($exigir_caja ?? false) && !$puede_fecha_anterior, 'current_uri' => '/ventas/nueva']) ?>

<?php $this->endSection(); ?>
