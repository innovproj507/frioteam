<?php $this->extend('layouts/app'); ?>
<?php
$this->section('content');
$estado_actual       ??= '';
$cliente_actual      ??= '';
$fecha_inicio_actual ??= '';
$fecha_fin_actual    ??= '';

// JSON para el autocomplete de clientes
$clienteNombreActual = '';
$clientes_json = json_encode(array_map(function($c) use ($cliente_actual, &$clienteNombreActual) {
    $id     = is_object($c) ? $c->cliente_id : ($c['cliente_id'] ?? '');
    $nombre = is_object($c) ? $c->nombre     : ($c['nombre']     ?? '');
    if ((string)$id === (string)$cliente_actual) $clienteNombreActual = $nombre;
    return ['id' => (string)$id, 'nombre' => $nombre];
}, $clientes ?? []));
?>

<?php
// Pestañas: historial del POS separado del de ventas normales
$origen_actual ??= '';
$conteo_origen ??= ['pos' => 0, 'normal' => 0];
$filtrosTab = array_diff_key($_GET ?? [], ['origen' => 1, 'page' => 1]);
$urlTab = fn (string $o) => '/ventas' . (($q = http_build_query($o !== '' ? $filtrosTab + ['origen' => $o] : $filtrosTab)) !== '' ? '?' . $q : '');
$tabs = [
    ''       => ['Todas', 'fa-layer-group',   (int) $conteo_origen['pos'] + (int) $conteo_origen['normal']],
    'pos'    => ['Ventas del POS', 'fa-cash-register', (int) $conteo_origen['pos']],
    'normal' => ['Ventas normales', 'fa-file-invoice-dollar', (int) $conteo_origen['normal']],
];
?>
<?php if (empty($solo_pos)): ?>
<div class="flex flex-wrap gap-2 mb-4">
    <?php foreach ($tabs as $clave => [$etiqueta, $icono, $n]): $activa = ($origen_actual === $clave); ?>
        <a href="<?= esc($urlTab($clave)) ?>" title="<?= esc($etiqueta) ?>" aria-label="<?= esc($etiqueta) ?>"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold border transition-colors <?= $activa ? 'bg-sky-600 text-white border-sky-600 shadow-sm' : 'bg-white text-gray-600 border-gray-200 hover:bg-sky-50' ?>">
            <i class="fas <?= $icono ?>"></i><?= esc($etiqueta) ?>
            <span class="text-xs px-1.5 py-0.5 rounded-full <?= $activa ? 'bg-white/25' : 'bg-gray-100 text-gray-500' ?>"><?= number_format($n) ?></span>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Filtros + acciones -->
<?php
$chipV = 'inline-flex items-center justify-center gap-1 h-8 min-w-8 px-2.5 rounded-lg text-xs font-semibold transition-colors';
$hayFiltros = ($fecha_inicio_actual !== '' || $fecha_fin_actual !== '' || $estado_actual !== '' || !empty($buscar));
$urlEstado = fn (string $e) => '/ventas?' . http_build_query(array_filter(array_merge($_GET ?? [], ['estado' => $e, 'page' => null]), fn ($v) => $v !== '' && $v !== null));
?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-4">
    <form method="get" id="ventas-search-form" class="flex flex-wrap items-end gap-3">
        <?php if ($origen_actual !== ''): ?><input type="hidden" name="origen" value="<?= esc($origen_actual) ?>"><?php endif; ?>
        <input type="hidden" name="estado" value="<?= esc($estado_actual) ?>">

        <div class="min-w-36">
            <p class="text-xs font-semibold text-gray-500 mb-1"><i class="fas fa-calendar-day mr-1"></i>Desde</p>
            <input type="date" name="fecha_inicio" value="<?= esc($fecha_inicio_actual) ?>" title="Desde"
                   class="w-full py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
        </div>
        <div class="min-w-36">
            <p class="text-xs font-semibold text-gray-500 mb-1"><i class="fas fa-calendar-check mr-1"></i>Hasta</p>
            <input type="date" name="fecha_fin" value="<?= esc($fecha_fin_actual) ?>" title="Hasta"
                   class="w-full py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
        </div>

        <div>
            <p class="text-xs font-semibold text-gray-500 mb-1"><i class="fas fa-toggle-on mr-1"></i>Estado</p>
            <div class="inline-flex bg-gray-50 border border-gray-100 rounded-xl p-0.5 gap-0.5">
                <a href="<?= esc($urlEstado('')) ?>" data-filtro="estado" data-valor="" title="Todas" class="<?= $chipV ?> <?= $estado_actual === '' ? 'bg-slate-800 text-white' : 'text-gray-500 hover:text-slate-800' ?>">Todas</a>
                <a href="<?= esc($urlEstado('pagada')) ?>" data-filtro="estado" data-valor="pagada" title="Pagadas" aria-label="Pagadas" class="<?= $chipV ?> <?= $estado_actual === 'pagada' ? 'bg-emerald-500 text-white' : 'text-gray-500 hover:text-emerald-600' ?>"><i class="fas fa-circle-check"></i> Pagadas</a>
                <a href="<?= esc($urlEstado('pendiente')) ?>" data-filtro="estado" data-valor="pendiente" title="Pendientes de pago" aria-label="Pendientes de pago" class="<?= $chipV ?> <?= $estado_actual === 'pendiente' ? 'bg-amber-500 text-white' : 'text-gray-500 hover:text-amber-600' ?>"><i class="fas fa-clock"></i> Pendientes</a>
                <a href="<?= esc($urlEstado('anulada')) ?>" data-filtro="estado" data-valor="anulada" title="Anuladas" aria-label="Anuladas" class="<?= $chipV ?> <?= $estado_actual === 'anulada' ? 'bg-red-500 text-white' : 'text-gray-500 hover:text-red-500' ?>"><i class="fas fa-ban"></i> Anuladas</a>
            </div>
        </div>

        <div class="flex-1 min-w-40">
            <p class="text-xs font-semibold text-gray-500 mb-1"><i class="fas fa-hashtag mr-1"></i>Factura o cliente</p>
            <div class="relative">
                <i class="fas fa-magnifying-glass absolute left-0 top-2 text-gray-400 text-xs"></i>
                <input type="text" name="buscar" value="<?= esc($buscar ?? '') ?>" placeholder="N° de factura o nombre del cliente" autocomplete="off"
                       class="w-full pl-5 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="submit" title="Aplicar filtros" aria-label="Aplicar filtros"
                    class="inline-flex items-center justify-center gap-1.5 px-4 h-10 bg-sky-500 text-white rounded-lg text-sm font-semibold hover:bg-sky-600 transition shadow-sm"><i class="fas fa-filter"></i> Filtrar</button>
            <?php if ($hayFiltros): ?>
            <a href="/ventas<?= $origen_actual !== '' ? '?origen=' . esc($origen_actual) : '' ?>" title="Quitar los filtros" aria-label="Quitar los filtros"
               class="inline-flex items-center justify-center gap-1.5 px-3 h-10 border border-gray-200 text-gray-500 rounded-lg text-sm hover:bg-gray-50 transition"><i class="fas fa-xmark"></i> Limpiar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Tabla -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-gray-100">
        <thead>
            <tr>
                <th class="px-5 py-3.5 bg-gray-50/80 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Factura</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Fecha</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Cliente</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"><i class="fas fa-user-tag mr-1"></i>Facturador</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-left text-xs font-bold text-gray-500 uppercase tracking-wider" ><i class="fas fa-wallet mr-1"></i>Forma de pago</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Total</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-center text-xs font-bold text-gray-500 uppercase tracking-wider" ><i class="fas fa-toggle-on mr-1"></i>Estado</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-50">
            <?php foreach (($ventas ?? []) as $venta): $anulada = ($venta['estado'] === 'anulada'); ?>
            <tr class="hover:bg-sky-50/40 transition-colors">
                <td class="px-5 py-3 whitespace-nowrap <?= $anulada ? 'opacity-60' : '' ?>">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs shrink-0"><i class="fas fa-receipt"></i></span>
                        <span class="text-sm font-bold text-gray-800 <?= $anulada ? 'line-through' : '' ?>"><?= esc($venta['numero_factura']) ?></span>
                    </div>
                </td>
                <td class="px-5 py-3 whitespace-nowrap <?= $anulada ? 'opacity-60' : '' ?>">
                    <?php $ts = strtotime($venta['fecha']); ?>
                    <div class="text-sm text-gray-700 leading-tight"><?= date('d/m/Y', $ts) ?></div>
                    <div class="text-xs text-gray-400"><?= date('H:i', $ts) ?></div>
                </td>
                <td class="px-5 py-3 text-sm text-gray-900 <?= $anulada ? 'opacity-60' : '' ?>"><?= esc($venta['cliente_nombre'] ?: "Consumidor Final") ?></td>
                <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-700 <?= $anulada ? 'opacity-60' : '' ?>"><?= ($venta['origen'] ?? '') === 'pos' && !empty($venta['vendedor_nombre']) ? esc($venta['vendedor_nombre']) : '<span class="text-gray-300">—</span>' ?></td>
                <td class="px-5 py-3 whitespace-nowrap">
                    <div class="flex items-center gap-1">
                    <?php
                    // Formas de pago de la venta (ventas_pagos); las viejas sin pagos usan ventas.forma_pago
                    $formasPago = array_filter(explode(',', (string) ($venta['formas_pago'] ?: $venta['forma_pago'])));
                    // Factura a crédito ya cobrada: en vez de "Crédito" se muestra con qué se pagó (los abonos)
                    if (in_array('CREDITO', array_map('strtoupper', array_map('trim', $formasPago)), true)
                        && ($venta['estado'] ?? '') !== 'pendiente' && !$anulada && !empty($venta['formas_cobro'])) {
                        $formasPago = array_filter(explode(',', (string) $venta['formas_cobro']));
                    }
                    $iconoPago = [
                        'EFECTIVO'      => ['fa-money-bill-wave',       'bg-emerald-50 text-emerald-600', 'Efectivo'],
                        'YAPPY'         => ['fa-mobile-screen-button',  'bg-sky-50 text-sky-600',         'Yappy'],
                        'TRANSFERENCIA' => ['fa-building-columns',      'bg-indigo-50 text-indigo-600',   'Transferencia'],
                        'TARJETA'       => ['fa-credit-card',           'bg-violet-50 text-violet-600',   'Tarjeta'],
                        'CREDITO'       => ['fa-hand-holding-dollar',   'bg-amber-50 text-amber-600',     'Crédito'],
                        'CHEQUE'        => ['fa-money-check-dollar',    'bg-gray-100 text-gray-600',      'Cheque'],
                        'MIXTO'         => ['fa-shuffle',               'bg-pink-50 text-pink-600',       'Pago mixto'],
                    ];
                    foreach ($formasPago as $fp): $fp = strtoupper(trim($fp)); [$ico, $col, $nom] = $iconoPago[$fp] ?? ['fa-circle-dollar-to-slot', 'bg-gray-100 text-gray-600', ucfirst(strtolower($fp))]; ?>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap <?= $col ?>" title="<?= esc($nom) ?>"><i class="fas <?= $ico ?> text-[10px]"></i> <?= esc($nom) ?></span>
                    <?php endforeach; ?>
                    <?php if (!empty($venta['comprobante'])): ?>
                        <a href="/m/api/venta/<?= (int) $venta['venta_id'] ?>/comprobante?v=<?= md5((string) $venta['comprobante']) ?>" target="_blank" title="Ver comprobante de pago" aria-label="Ver comprobante de pago"
                           class="inline-flex w-8 h-8 rounded-lg items-center justify-center bg-sky-50 text-sky-600 hover:bg-sky-500 hover:text-white transition-colors"><i class="fas fa-paperclip text-sm"></i></a>
                    <?php endif; ?>
                    </div>
                </td>
                <td class="px-5 py-3 whitespace-nowrap text-right text-sm font-bold <?= $anulada ? 'text-gray-400 line-through' : 'text-gray-900' ?>">$<?= number_format((float)$venta['total'], 2) ?></td>
                <td class="px-5 py-3 whitespace-nowrap text-center">
                    <?php if ($venta['estado'] === 'pagada'): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap bg-emerald-100 text-emerald-600" title="Pagada"><i class="fas fa-check text-[10px]"></i> Pagada</span>
                    <?php elseif ($venta['estado'] === 'pendiente'): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap bg-amber-100 text-amber-600" title="Pendiente de pago"><i class="fas fa-clock text-[10px]"></i> Pendiente</span>
                    <?php else: ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap bg-red-100 text-red-500" title="Anulada"><i class="fas fa-ban text-[10px]"></i> Anulada</span>
                    <?php endif; ?>
                </td>
                <td class="px-5 py-3 whitespace-nowrap text-right text-sm">
                    <div class="flex justify-end gap-1.5">
                        <a href="/ventas/venta/<?= (int) $venta['venta_id'] ?>" title="Ver detalle de la venta" aria-label="Ver detalle de la venta"
                           class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-500 hover:text-white transition-colors flex items-center justify-center"><i class="fas fa-eye text-xs"></i></a>
                        <?php if ($venta['estado'] == 'pagada'): ?>
                        <button type="button" onclick="imprimirFactura(<?= (int) $venta['venta_id'] ?>)" title="Imprimir factura" aria-label="Imprimir factura"
                           class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white transition-colors flex items-center justify-center"><i class="fas fa-print text-xs"></i></button>
                        <?php endif; ?>
                        <?php if ($venta['estado'] != 'anulada'): ?>
                        <button type="button" onclick="abrirModalAnular(<?= (int) $venta['venta_id'] ?>, <?= esc(json_encode((string) $venta['numero_factura']), 'attr') ?>)" title="Anular venta" aria-label="Anular venta"
                           class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-colors flex items-center justify-center"><i class="fas fa-ban text-xs"></i></button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($ventas)): ?>
            <tr>
                <td colspan="8" class="px-4 py-10 text-center text-sm text-gray-400">
                    <i class="fas fa-receipt text-3xl mb-2 block text-gray-200"></i>
                    No se encontraron ventas
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="px-5 py-3.5 border-t border-gray-100 bg-gray-50/80">
        <?php echo view('partials/pagination', ['pagination' => $pagination ?? []]); ?>
    </div>
</div>

<!-- Modal Anular -->
<div id="modal-anular" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-1">Anular Venta</h3>
        <p class="text-sm text-gray-500 mb-4">Se generará una <strong>nota de crédito</strong> por el monto total.</p>
        <form id="form-anular" method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Factura</label>
                <input type="text" id="modal-factura-num" readonly class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-600">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Motivo <span class="text-red-500">*</span></label>
                <select name="motivo_tipo" id="motivo_tipo" onchange="toggleMotivoCustom()" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm mb-2">
                    <option value="Producto defectuoso">Producto defectuoso</option>
                    <option value="Error en precio">Error en precio</option>
                    <option value="Error en cantidad">Error en cantidad</option>
                    <option value="Devolución del cliente">Devolución del cliente</option>
                    <option value="Duplicado">Factura duplicada</option>
                    <option value="otro">Otro motivo...</option>
                </select>
                <textarea name="motivo" id="motivo-custom" placeholder="Describe el motivo..."
                    class="hidden w-full px-3 py-2 border border-gray-200 rounded-lg text-sm resize-none" rows="3"></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="cerrarModalAnular()" class="px-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-semibold hover:bg-red-700"><i class="fas fa-ban mr-1"></i>Confirmar Anulación</button>
            </div>
        </form>
    </div>
</div>

<?php $this->endSection(); ?>

<?php $this->section('extra_js'); ?>
<script>
    // El filtro de estado conserva las fechas y la búsqueda que ya están en el formulario
    document.querySelectorAll('#ventas-search-form [data-filtro]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            var f = document.getElementById('ventas-search-form');
            f.elements[a.dataset.filtro].value = a.dataset.valor;
            f.submit();
        });
    });
    function ventasClienteFilter() {
        return {
            open: false,
            search: <?= json_encode($clienteNombreActual) ?>,
            selectedId: <?= json_encode((string)$cliente_actual) ?>,
            clientes: <?= $clientes_json ?>,
            get filtered() {
                if (!this.search) return this.clientes.slice(0, 15);
                var q = this.search.toLowerCase();
                return this.clientes.filter(function(c) {
                    return c.nombre.toLowerCase().indexOf(q) !== -1;
                }).slice(0, 15);
            },
            select: function(c) {
                this.search = c.nombre;
                this.selectedId = c.id;
                this.open = false;
                document.getElementById('ventas-search-form').submit();
            },
            clear: function() {
                this.search = '';
                this.selectedId = '';
                this.open = false;
                document.getElementById('ventas-search-form').submit();
            }
        };
    }

    function imprimirFactura(ventaId) { window.open(`/ventas/venta/${ventaId}/?print=true`, '_blank'); }

    function abrirModalAnular(ventaId, numeroFactura) {
        document.getElementById('form-anular').action = `/ventas/venta/${ventaId}/anular`;
        document.getElementById('modal-factura-num').value = numeroFactura;
        document.getElementById('motivo_tipo').value = 'Producto defectuoso';
        document.getElementById('motivo-custom').classList.add('hidden');
        document.getElementById('modal-anular').classList.remove('hidden');
    }

    function cerrarModalAnular() { document.getElementById('modal-anular').classList.add('hidden'); }

    function toggleMotivoCustom() {
        const sel = document.getElementById('motivo_tipo');
        const txt = document.getElementById('motivo-custom');
        if (sel.value === 'otro') { txt.classList.remove('hidden'); txt.required = true; }
        else { txt.classList.add('hidden'); txt.required = false; }
    }

    document.getElementById('form-anular').addEventListener('submit', function(e) {
        const sel = document.getElementById('motivo_tipo');
        if (sel.value === 'otro') {
            const txt = document.getElementById('motivo-custom');
            if (!txt.value.trim()) { e.preventDefault(); txt.focus(); return; }
            sel.disabled = true;
        } else {
            document.getElementById('motivo-custom').disabled = true;
        }
    });
</script>
<?php $this->endSection(); ?>
