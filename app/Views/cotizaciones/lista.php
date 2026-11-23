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
<?php
$chipC = 'inline-flex items-center justify-center gap-1 h-8 min-w-8 px-2.5 rounded-lg text-xs font-semibold transition-colors';
$hayFiltros = ($fecha_inicio_actual !== '' || $fecha_fin_actual !== '' || $estado_actual !== '' || !empty($buscar));
$urlEstado = fn (string $e) => '/ventas/cotizaciones?' . http_build_query(array_filter(array_merge($_GET ?? [], ['estado' => $e, 'page' => null]), fn ($v) => $v !== '' && $v !== null));
$estadosFiltro = [
    ''           => ['Todas',       'bg-slate-800 text-white',   'text-gray-500 hover:text-slate-800',   null,                'Todas las cotizaciones'],
    'pendiente'  => ['Pendientes',  'bg-amber-500 text-white',   'text-gray-500 hover:text-amber-600',   'fa-clock',          'Pendientes'],
    'aprobada'   => ['Aprobadas',   'bg-emerald-500 text-white', 'text-gray-500 hover:text-emerald-600', 'fa-circle-check',   'Aprobadas'],
    'convertida' => ['Convertidas', 'bg-sky-500 text-white',     'text-gray-500 hover:text-sky-600',     'fa-receipt',        'Convertidas en venta'],
    'rechazada'  => ['Rechazadas',  'bg-red-500 text-white',     'text-gray-500 hover:text-red-500',     'fa-circle-xmark',   'Rechazadas'],
];
?>
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-4">
    <form method="get" id="cotizaciones-search-form" class="flex flex-wrap items-end gap-3">
        <input type="hidden" name="origen" value="<?= esc($origen ?? 'pos') ?>">
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
            <div class="inline-flex flex-wrap bg-gray-50 border border-gray-100 rounded-xl p-0.5 gap-0.5">
                <?php foreach ($estadosFiltro as $valor => [$txt, $on, $off, $ico, $tt]): ?>
                <a href="<?= esc($urlEstado($valor)) ?>" data-filtro="estado" data-valor="<?= $valor ?>" title="<?= esc($tt) ?>"
                   class="<?= $chipC ?> <?= $estado_actual === $valor ? $on : $off ?>"><?php if ($ico): ?><i class="fas <?= $ico ?>"></i> <?php endif; ?><?= $txt ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="flex-1 min-w-40">
            <p class="text-xs font-semibold text-gray-500 mb-1"><i class="fas fa-hashtag mr-1"></i>Cotización o cliente</p>
            <div class="relative">
                <i class="fas fa-magnifying-glass absolute left-0 top-2 text-gray-400 text-xs"></i>
                <input type="text" name="buscar" value="<?= esc($buscar ?? '') ?>" placeholder="N° de cotización o nombre del cliente" autocomplete="off"
                       class="w-full pl-5 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" class="inline-flex items-center gap-1.5 px-4 h-10 bg-sky-500 text-white rounded-lg text-sm font-semibold hover:bg-sky-600 transition shadow-sm">
                <i class="fas fa-filter"></i> Filtrar
            </button>
            <?php if ($hayFiltros): ?>
            <a href="/ventas/cotizaciones?origen=<?= esc($origen ?? 'pos') ?>" class="inline-flex items-center gap-1.5 px-3 h-10 border border-gray-200 text-gray-500 rounded-lg text-sm hover:bg-gray-50 transition">
                <i class="fas fa-xmark"></i> Limpiar
            </a>
            <?php endif; ?>
            <a href="/ventas/cotizaciones/nueva" class="inline-flex items-center gap-1.5 px-4 h-10 bg-white border border-gray-200 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-50 transition">
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
                <th class="px-5 py-3.5 bg-gray-50/80 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Total</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-center text-xs font-bold text-gray-500 uppercase tracking-wider"><i class="fas fa-toggle-on mr-1"></i>Estado</th>
                <th class="px-5 py-3.5 bg-gray-50/80 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-50">
            <?php foreach (($cotizaciones ?? []) as $cotizacion):
                $fecha_validez = strtotime($cotizacion['fecha_vencimiento'] ?? '');
                $vencida  = $fecha_validez && $fecha_validez < time() && $cotizacion['estado'] === 'pendiente';
                $ts       = strtotime($cotizacion['fecha']);
                $rechazada = ($cotizacion['estado'] === 'rechazada');
                $id       = (int) $cotizacion['cotizacion_id'];
            ?>
            <tr class="hover:bg-sky-50/40 transition-colors">
                <td class="px-5 py-3 whitespace-nowrap <?= $rechazada ? 'opacity-60' : '' ?>">
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs shrink-0"><i class="fas fa-file-invoice"></i></span>
                        <span class="text-sm font-bold text-gray-800 <?= $rechazada ? 'line-through' : '' ?>"><?= esc($cotizacion['numero']) ?></span>
                    </div>
                </td>
                <td class="px-5 py-3 whitespace-nowrap <?= $rechazada ? 'opacity-60' : '' ?>">
                    <div class="text-sm text-gray-700 leading-tight"><?= date('d/m/Y', $ts) ?></div>
                    <div class="text-xs text-gray-400"><?= date('H:i', $ts) ?></div>
                </td>
                <td class="px-5 py-3 whitespace-nowrap text-sm">
                    <?php if ($vencida): ?>
                    <span class="inline-flex items-center gap-1 text-red-600 font-semibold" title="Cotización vencida" aria-label="Cotización vencida"><i class="fas fa-hourglass-end text-xs"></i><?= date('d/m/Y', $fecha_validez) ?></span>
                    <?php else: ?>
                    <span class="text-gray-600 <?= $rechazada ? 'opacity-60' : '' ?>"><?= $fecha_validez ? date('d/m/Y', $fecha_validez) : '—' ?></span>
                    <?php endif; ?>
                </td>
                <td class="px-5 py-3 text-sm text-gray-900 <?= $rechazada ? 'opacity-60' : '' ?>"><?= esc($cotizacion['cliente_nombre'] ?? 'Consumidor Final') ?></td>
                <td class="px-5 py-3 whitespace-nowrap text-right text-sm font-bold <?= $rechazada ? 'text-gray-400 line-through' : 'text-gray-900' ?>">$<?= number_format((float)$cotizacion['total'], 2) ?></td>
                <td class="px-5 py-3 whitespace-nowrap text-center">
                    <?php [$eIco, $eCol, $eTxt] = match ($cotizacion['estado']) {
                        'aprobada'   => ['fa-check',   'bg-emerald-100 text-emerald-600', 'Aprobada'],
                        'convertida' => ['fa-receipt', 'bg-sky-100 text-sky-600',         'Convertida'],
                        'rechazada'  => ['fa-xmark',   'bg-red-100 text-red-500',         'Rechazada'],
                        default      => ['fa-clock',   'bg-amber-100 text-amber-600',     'Pendiente'],
                    }; ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap <?= $eCol ?>" title="<?= esc($eTxt) ?>"><i class="fas <?= $eIco ?> text-[10px]"></i> <?= esc($eTxt) ?></span>
                </td>
                <td class="px-5 py-3 whitespace-nowrap text-right text-sm">
                    <div class="flex justify-end gap-1.5">
                        <?php if (($origen ?? '') === 'web' && ($cotizacion['estado'] ?? '') !== 'convertida'):
                            $pend = ($cotizacion['estado'] ?? '') === 'pendiente'; $tPrecio = $pend ? 'Poner precios' : 'Cambiar precios'; ?>
                        <a href="/ventas/cotizaciones/<?= $id ?>/precios" title="<?= $tPrecio ?>" aria-label="<?= $tPrecio ?>"
                           class="w-8 h-8 rounded-lg transition-colors flex items-center justify-center <?= $pend ? 'bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white' ?>"><i class="fas fa-tags text-xs"></i></a>
                        <?php endif; ?>
                        <a href="/ventas/cotizaciones/<?= $id ?>" title="Ver cotización" aria-label="Ver cotización"
                           class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-500 hover:text-white transition-colors flex items-center justify-center"><i class="fas fa-eye text-xs"></i></a>
                        <a href="/ventas/cotizaciones/<?= $id ?>/pdf" title="Descargar PDF" aria-label="Descargar PDF"
                           class="w-8 h-8 rounded-lg bg-rose-50 text-rose-500 hover:bg-rose-500 hover:text-white transition-colors flex items-center justify-center"><i class="fas fa-file-pdf text-xs"></i></a>
                        <button type="button" onclick="imprimirCotizacion(<?= $id ?>)" title="Imprimir" aria-label="Imprimir"
                           class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white transition-colors flex items-center justify-center"><i class="fas fa-print text-xs"></i></button>
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
    // El filtro de estado conserva las fechas y la búsqueda que ya están en el formulario
    document.querySelectorAll('#cotizaciones-search-form [data-filtro]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
            var f = document.getElementById('cotizaciones-search-form');
            f.elements[a.dataset.filtro].value = a.dataset.valor;
            f.submit();
        });
    });
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
