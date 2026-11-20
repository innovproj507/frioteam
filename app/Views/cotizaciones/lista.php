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

<!-- Filtros + acciones -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-4">
    <form method="get" id="cotizaciones-search-form" class="flex flex-wrap items-end gap-3">
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
                <option value="pendiente" <?= $estado_actual == 'pendiente' ? 'selected' : '' ?>>Pendientes</option>
                <option value="aprobada" <?= $estado_actual == 'aprobada' ? 'selected' : '' ?>>Aprobadas</option>
                <option value="rechazada" <?= $estado_actual == 'rechazada' ? 'selected' : '' ?>>Rechazadas</option>
                <option value="convertida" <?= $estado_actual == 'convertida' ? 'selected' : '' ?>>Convertidas</option>
            </select>
        </div>
        <div class="flex-1 min-w-40">
            <label class="block text-xs font-semibold text-gray-500 mb-1">N° de cotización</label>
            <div class="relative">
                <i class="fas fa-hashtag absolute left-0 top-2 text-gray-400 text-xs"></i>
                <input type="text" name="buscar" value="<?= esc($buscar ?? '') ?>" placeholder="Ej: COT-00002547" autocomplete="off"
                       class="w-full pl-5 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
            </div>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-sky-500 text-white rounded-lg text-sm font-semibold hover:bg-sky-600 transition shadow-sm">
                <i class="fas fa-filter"></i> Filtrar
            </button>
            <a href="/ventas/cotizaciones/nueva" class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-50 transition">
                <i class="fas fa-plus"></i> Nueva
            </a>
        </div>
    </form>
</div>

<!-- Tabla -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-gray-100">
        <thead>
            <tr>
                <th class="px-5 py-3.5 bg-gray-50/80 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Número</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Fecha</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Vence</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Cliente</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Total</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-50">
            <?php foreach (($cotizaciones ?? []) as $cotizacion): ?>
            <tr class="hover:bg-sky-50/40 transition-colors">
                <td class="px-5 py-3.5 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-blue-500 text-white flex items-center justify-center text-xs shrink-0 shadow-sm shadow-blue-500/30"><i class="fas fa-file-invoice"></i></span>
                        <span class="text-sm font-bold text-gray-800"><?= esc($cotizacion['numero']) ?></span>
                    </div>
                </td>
                <td class="px-5 py-3.5 whitespace-nowrap text-sm text-gray-600"><?= date('d/m/Y H:i', strtotime($cotizacion['fecha'])) ?></td>
                <td class="px-5 py-3.5 whitespace-nowrap text-sm">
                    <?php
                    $fecha_validez = strtotime($cotizacion['fecha_vencimiento'] ?? '');
                    $vencida = $fecha_validez && $fecha_validez < time() && $cotizacion['estado'] === 'pendiente';
                    ?>
                    <span class="<?= $vencida ? 'text-red-600 font-bold' : 'text-gray-600' ?>">
                        <?= $fecha_validez ? date('d/m/Y', $fecha_validez) : '—' ?>
                        <?php if ($vencida): ?><i class="fas fa-exclamation-circle ml-1 text-xs"></i><?php endif; ?>
                    </span>
                </td>
                <td class="px-5 py-3.5 whitespace-nowrap text-sm text-gray-900"><?= esc($cotizacion['cliente_nombre'] ?? 'Consumidor Final') ?></td>
                <td class="px-5 py-3.5 whitespace-nowrap text-sm font-bold text-gray-900">$<?= number_format((float)$cotizacion['total'], 2) ?></td>
                <td class="px-5 py-3.5 whitespace-nowrap">
                    <?php
                        $badgeClasses = match($cotizacion['estado']) {
                            'aprobada', 'convertida' => 'bg-emerald-500 text-white',
                            'rechazada' => 'bg-red-500 text-white',
                            default     => 'bg-amber-500 text-white',
                        };
                    ?>
                    <span class="px-2.5 py-1 inline-flex text-xs font-bold rounded-full <?= $badgeClasses ?>">
                        <?= ucfirst(esc($cotizacion['estado'])) ?>
                    </span>
                </td>
                <td class="px-5 py-3.5 whitespace-nowrap text-right text-sm">
                    <div class="flex justify-end gap-1.5">
                        <a href="/ventas/cotizaciones/<?= $cotizacion['cotizacion_id'] ?>" title="Ver"
                           class="w-8 h-8 rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition-colors flex items-center justify-center shadow-sm shadow-blue-500/30"><i class="fas fa-eye text-xs"></i></a>
                        <a href="/ventas/cotizaciones/<?= $cotizacion['cotizacion_id'] ?>/pdf" title="Descargar PDF"
                           class="w-8 h-8 rounded-lg bg-rose-500 text-white hover:bg-rose-600 transition-colors flex items-center justify-center shadow-sm shadow-rose-500/30"><i class="fas fa-file-pdf text-xs"></i></a>
                        <button onclick="imprimirCotizacion(<?= $cotizacion['cotizacion_id'] ?>)" title="Imprimir"
                           class="w-8 h-8 rounded-lg bg-emerald-500 text-white hover:bg-emerald-600 transition-colors flex items-center justify-center shadow-sm shadow-emerald-500/30"><i class="fas fa-print text-xs"></i></button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($cotizaciones)): ?>
            <tr>
                <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-400">
                    <i class="fas fa-file-invoice text-3xl mb-2 block text-gray-200"></i>
                    No se encontraron cotizaciones
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="px-5 py-3.5 border-t border-gray-100 bg-gray-50/80">
        <?php echo view('partials/pagination', ['pagination' => $pagination ?? []]); ?>
    </div>
</div>

<?php $this->endSection(); ?>

<?php $this->section('extra_js'); ?>
<script>
    function cotizClienteFilter() {
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
                document.getElementById('cotizaciones-search-form').submit();
            },
            clear: function() {
                this.search = '';
                this.selectedId = '';
                this.open = false;
                document.getElementById('cotizaciones-search-form').submit();
            }
        };
    }

    function imprimirCotizacion(id) { window.open(`/ventas/cotizaciones/${id}/?print=true`, '_blank'); }
</script>
<?php $this->endSection(); ?>
