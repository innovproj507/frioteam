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
    ''       => ['Todas',            'fa-list',          (int) $conteo_origen['pos'] + (int) $conteo_origen['normal']],
    'pos'    => ['Ventas del POS',   'fa-cash-register', (int) $conteo_origen['pos']],
    'normal' => ['Ventas normales',  'fa-file-invoice',  (int) $conteo_origen['normal']],
];
?>
<?php if (empty($solo_pos)): ?>
<div class="flex flex-wrap gap-2 mb-4">
    <?php foreach ($tabs as $clave => [$etiqueta, $icono, $n]): $activa = ($origen_actual === $clave); ?>
        <a href="<?= esc($urlTab($clave)) ?>"
           class="px-4 py-2 rounded-xl text-sm font-semibold border transition-colors <?= $activa ? 'bg-sky-600 text-white border-sky-600 shadow-sm' : 'bg-white text-gray-600 border-gray-200 hover:bg-sky-50' ?>">
            <i class="fas <?= $icono ?> mr-1.5"></i><?= $etiqueta ?>
            <span class="ml-1.5 text-xs px-1.5 py-0.5 rounded-full <?= $activa ? 'bg-white/25' : 'bg-gray-100 text-gray-500' ?>"><?= number_format($n) ?></span>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Filtros + acciones -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-4">
    <form method="get" id="ventas-search-form" class="flex flex-wrap items-end gap-3">
        <?php if ($origen_actual !== ''): ?><input type="hidden" name="origen" value="<?= esc($origen_actual) ?>"><?php endif; ?>
        <div class="flex-1 min-w-36">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Fecha Inicio</label>
            <input type="date" name="fecha_inicio" value="<?= esc($fecha_inicio_actual) ?>"
                   class="w-full py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
        </div>
        <div class="flex-1 min-w-36">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Fecha Fin</label>
            <input type="date" name="fecha_fin" value="<?= esc($fecha_fin_actual) ?>"
                   class="w-full py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
        </div>
        <div class="flex-1 min-w-28">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Estado</label>
            <select name="estado" class="w-full py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none appearance-none">
                <option value="">Todos</option>
                <option value="pagada" <?= $estado_actual == 'pagada' ? 'selected' : '' ?>>Pagadas</option>
                <option value="pendiente" <?= $estado_actual == 'pendiente' ? 'selected' : '' ?>>Pendientes</option>
                <option value="anulada" <?= $estado_actual == 'anulada' ? 'selected' : '' ?>>Anuladas</option>
            </select>
        </div>
        <div class="flex-1 min-w-40">
            <label class="block text-xs font-semibold text-gray-500 mb-1">N° de factura</label>
            <div class="relative">
                <i class="fas fa-hashtag absolute left-0 top-2 text-gray-400 text-xs"></i>
                <input type="text" name="buscar" value="<?= esc($buscar ?? '') ?>" placeholder="Ej: FACT-00004694" autocomplete="off"
                       class="w-full pl-5 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
            </div>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-sky-500 text-white rounded-lg text-sm font-semibold hover:bg-sky-600 transition shadow-sm">
                <i class="fas fa-filter"></i> Filtrar
            </button>
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
                <th class="px-5 py-3.5 bg-gray-50/80 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Forma de pago</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Total</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-50">
            <?php foreach (($ventas ?? []) as $venta): ?>
            <tr class="hover:bg-sky-50/40 transition-colors">
                <td class="px-5 py-3.5 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-blue-500 text-white flex items-center justify-center text-xs shrink-0 shadow-sm shadow-blue-500/30"><i class="fas fa-receipt"></i></span>
                        <span class="text-sm font-bold text-gray-800"><?= esc($venta['numero_factura']) ?></span>
                    </div>
                </td>
                <td class="px-5 py-3.5 whitespace-nowrap text-sm text-gray-600"><?= date("d/m/Y H:i", strtotime($venta['fecha'])) ?></td>
                <td class="px-5 py-3.5 whitespace-nowrap text-sm text-gray-900"><?= esc($venta['cliente_nombre'] ?: "Consumidor Final") ?></td>
                <td class="px-5 py-3.5 whitespace-nowrap text-sm">
                    <?php
                    // Formas de pago de la venta (ventas_pagos); las viejas sin pagos usan ventas.forma_pago
                    $formasPago = array_filter(explode(',', (string) ($venta['formas_pago'] ?: $venta['forma_pago'])));
                    $estiloPago = ['EFECTIVO' => 'bg-emerald-50 text-emerald-700', 'YAPPY' => 'bg-sky-50 text-sky-700', 'TRANSFERENCIA' => 'bg-indigo-50 text-indigo-700',
                                   'TARJETA' => 'bg-violet-50 text-violet-700', 'CREDITO' => 'bg-amber-50 text-amber-700', 'CHEQUE' => 'bg-gray-100 text-gray-700'];
                    $nombrePago = ['EFECTIVO' => 'Efectivo', 'YAPPY' => 'Yappy', 'TRANSFERENCIA' => 'Transferencia', 'TARJETA' => 'Tarjeta', 'CREDITO' => 'Crédito', 'CHEQUE' => 'Cheque', 'MIXTO' => 'Mixto'];
                    foreach ($formasPago as $fp): $fp = strtoupper(trim($fp)); ?>
                        <span class="inline-block px-2 py-0.5 rounded-md text-xs font-semibold mr-0.5 <?= $estiloPago[$fp] ?? 'bg-gray-100 text-gray-700' ?>"><?= esc($nombrePago[$fp] ?? ucfirst(strtolower($fp))) ?></span>
                    <?php endforeach; ?>
                    <?php if (!empty($venta['comprobante'])): ?>
                        <a href="/m/api/venta/<?= (int) $venta['venta_id'] ?>/comprobante?v=<?= md5((string) $venta['comprobante']) ?>" target="_blank" title="Ver comprobante de pago" class="inline-block px-1.5 py-0.5 rounded-md text-xs bg-sky-100 text-sky-700 hover:bg-sky-200">📎</a>
                    <?php endif; ?>
                </td>
                <td class="px-5 py-3.5 whitespace-nowrap text-sm font-bold text-gray-900">$<?= number_format((float)$venta['total'], 2) ?></td>
                <td class="px-5 py-3.5 whitespace-nowrap">
                    <?php
                        $badgeClasses = 'bg-red-500 text-white';
                        if ($venta['estado'] === 'pagada') $badgeClasses = 'bg-emerald-500 text-white';
                        elseif ($venta['estado'] === 'pendiente') $badgeClasses = 'bg-amber-500 text-white';
                    ?>
                    <span class="px-2.5 py-1 inline-flex text-xs font-bold rounded-full <?= $badgeClasses ?>">
                        <?= ucfirst(esc($venta['estado'])) ?>
                    </span>
                </td>
                <td class="px-5 py-3.5 whitespace-nowrap text-right text-sm">
                    <div class="flex justify-end gap-1.5">
                        <a href="/ventas/venta/<?= $venta['venta_id'] ?>" title="Ver Detalles"
                           class="w-8 h-8 rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition-colors flex items-center justify-center shadow-sm shadow-blue-500/30"><i class="fas fa-eye text-xs"></i></a>
                        <?php if ($venta['estado'] == 'pagada'): ?>
                        <button onclick="imprimirFactura(<?= $venta['venta_id'] ?>)" title="Imprimir"
                           class="w-8 h-8 rounded-lg bg-emerald-500 text-white hover:bg-emerald-600 transition-colors flex items-center justify-center shadow-sm shadow-emerald-500/30"><i class="fas fa-print text-xs"></i></button>
                        <?php endif; ?>
                        <?php if ($venta['estado'] != 'anulada'): ?>
                        <button onclick="abrirModalAnular(<?= (int) $venta['venta_id'] ?>, <?= esc(json_encode((string) $venta['numero_factura']), 'attr') ?>)" title="Anular"
                           class="w-8 h-8 rounded-lg bg-red-500 text-white hover:bg-red-600 transition-colors flex items-center justify-center shadow-sm shadow-red-500/30"><i class="fas fa-ban text-xs"></i></button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($ventas)): ?>
            <tr>
                <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-400">
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
