<?php $this->extend('layouts/app'); ?>
<?php $this->section('content'); ?>

<!-- Filtros + acciones -->
<?php
$currentCat    = $_GET['categoria'] ?? '';
$initCatNombre = '';
foreach (($categorias ?? []) as $_c) {
    $_id = is_array($_c) ? ($_c['categoria_id'] ?? '') : ($_c->categoria_id ?? '');
    if ((string)$_id === (string)$currentCat && $currentCat !== '') {
        $initCatNombre = is_array($_c) ? ($_c['nombre'] ?? '') : ($_c->nombre ?? '');
        break;
    }
}
$catsJson = json_encode(array_values(array_map(fn($c) => [
    'id'     => (string)(is_array($c) ? ($c['categoria_id'] ?? '') : ($c->categoria_id ?? '')),
    'nombre' => is_array($c) ? ($c['nombre'] ?? '') : ($c->nombre ?? ''),
], $categorias ?? [])));
?>

<!-- Alpine data como función para evitar problemas con > en atributos HTML -->
<script>
function posCatFilter() {
    return {
        open: false,
        search: <?= json_encode($initCatNombre) ?>,
        selectedId: <?= json_encode((string)$currentCat) ?>,
        cats: <?= $catsJson ?? '[]' ?>,
        get filtered() {
            var q = this.search.trim().toLowerCase();
            if (!q) return this.cats;
            return this.cats.filter(function(c) {
                return c.nombre.toLowerCase().indexOf(q) !== -1;
            });
        },
        select: function(cat) {
            this.search    = cat.nombre;
            this.selectedId = cat.id;
            this.open      = false;
            this.$nextTick(function() {
                document.getElementById('search-form').submit();
            });
        },
        clear: function() {
            this.search    = '';
            this.selectedId = '';
            this.open      = false;
            this.$nextTick(function() {
                document.getElementById('search-form').submit();
            });
        }
    };
}
</script>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-3 mb-4">
    <form method="get" id="search-form" class="flex flex-wrap items-end gap-3">

        <!-- Buscador texto -->
        <div class="flex-1 min-w-48">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Buscar</label>
            <div class="relative">
                <i class="fas fa-search absolute left-0 top-2 text-gray-400 text-xs"></i>
                <input type="text" name="buscar" value="<?= esc($_GET['buscar'] ?? '') ?>"
                    placeholder="Buscar producto..." id="search-input"
                    class="w-full pl-5 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
                <div id="search-spinner" class="absolute right-0 top-2 hidden">
                    <i class="fas fa-spinner fa-spin text-sky-500 text-xs"></i>
                </div>
            </div>
        </div>

        <!-- Autocomplete de categoría -->
        <div class="min-w-52 relative" x-data="posCatFilter()" @click.outside="open = false">

            <label class="block text-xs font-semibold text-gray-500 mb-1">Categoría</label>
            <input type="hidden" name="categoria" :value="selectedId">

            <div class="relative">
                <i class="fas fa-tags absolute left-0 top-2 text-gray-400 text-xs"></i>
                <input type="text"
                    x-model="search"
                    @focus="open = true"
                    @input="open = true"
                    @keydown.escape="open = false"
                    placeholder="Todas las categorías"
                    autocomplete="off"
                    class="w-full pl-5 pr-5 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
                <button type="button" x-show="search" @click="clear()"
                    class="absolute right-0 top-1.5 text-gray-300 hover:text-gray-500 text-xs leading-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Dropdown -->
            <div x-show="open"
                class="absolute z-50 top-full left-0 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-xl max-h-52 overflow-y-auto"
                style="display:none;">

                <button type="button" @click="clear()"
                    class="w-full text-left px-3 py-2 text-xs text-gray-400 hover:bg-gray-50 border-b border-gray-100 transition-colors">
                    <i class="fas fa-times-circle mr-1 text-gray-300"></i> Todas las categorías
                </button>

                <template x-for="cat in filtered" :key="cat.id">
                    <button type="button" @click="select(cat)"
                        :class="cat.id === selectedId ? 'bg-sky-50 text-sky-700 font-semibold' : 'text-gray-700 hover:bg-gray-50'"
                        class="w-full text-left px-3 py-2 text-xs transition-colors">
                        <span x-text="cat.nombre"></span>
                    </button>
                </template>

                <p x-show="filtered.length === 0" class="px-3 py-4 text-xs text-gray-400 text-center">
                    Sin resultados
                </p>
            </div>
        </div>

        <!-- Estado -->
        <div class="min-w-32">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Estado</label>
            <select name="estado" onchange="this.form.submit()"
                class="w-full py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none appearance-none">
                <option value="">Todos</option>
                <option value="activo"   <?= (($_GET['estado'] ?? '') === 'activo')   ? 'selected' : '' ?>>Activo</option>
                <option value="inactivo" <?= (($_GET['estado'] ?? '') === 'inactivo') ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>

        <!-- Depósito: ver lo que hay en cada uno -->
        <div class="min-w-36">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Depósito</label>
            <select name="deposito" onchange="this.form.submit()"
                class="w-full py-1.5 px-0 text-sm font-semibold bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none appearance-none">
                <?php foreach (($depositos_lista ?? []) as $d): ?>
                <option value="<?= (int) $d['deposito_id'] ?>" <?= (int) ($deposito_filtro ?? 0) === (int) $d['deposito_id'] ? 'selected' : '' ?>><?= esc($d['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex gap-2">
            <?php if (!empty($_GET['buscar']) || !empty($currentCat) || !empty($_GET['estado'])): ?>
            <a href="/inventario"
                class="inline-flex items-center gap-1 px-3 py-2 border border-gray-200 text-gray-400 rounded-lg text-sm hover:bg-gray-50 transition"
                title="Limpiar filtros">
                <i class="fas fa-times"></i>
            </a>
            <?php endif; ?>
            <a href="/inventario/exportar-excel"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700 transition shadow-sm"
                title="Exportar a Excel (.xlsx)">
                <i class="fas fa-file-excel"></i> .xlsx
            </a>
            <?php if (\App\Libraries\Auth::can('productos.editar')): ?>
            <a href="/inventario/codigos"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-sky-600 text-sky-700 rounded-lg text-sm font-semibold hover:bg-sky-50 transition shadow-sm"
                title="Escanear o generar el código de barras y la referencia del proveedor de cada producto; etiquetas">
                <i class="fas fa-barcode"></i> Códigos de barras
            </a>
            <?php endif; ?>
            <a href="/inventario/nuevo"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-sky-500 text-white rounded-lg text-sm font-semibold hover:bg-sky-600 transition shadow-sm">
                <i class="fas fa-plus"></i> Nuevo
            </a>
        </div>

    </form>
</div>

<!-- Tabla -->
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-100">
        <thead>
            <tr>
                <th class="px-3 py-3 bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Producto</th>
                <th class="px-3 py-3 bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Código</th>
                <th class="px-3 py-3 bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Ubicación</th>
                <?php if (ver_costos()): ?><th class="px-3 py-3 bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Costo</th><?php endif; ?>
                <th class="px-3 py-3 bg-gray-50 text-left text-xs font-semibold text-sky-500 uppercase tracking-wider">Precio A</th>
                <th class="px-3 py-3 bg-gray-50 text-left text-xs font-semibold text-emerald-500 uppercase tracking-wider">Precio B</th>
                <th class="px-3 py-3 bg-gray-50 text-left text-xs font-semibold text-pink-500 uppercase tracking-wider">Precio C</th>
                <th class="px-3 py-3 bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Inventario</th>
                <th class="px-3 py-3 bg-gray-50 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
            <?php foreach (($productos ?? []) as $producto): ?>
            <?php
            $pNombre = is_array($producto) ? ($producto['nombre'] ?? '') : ($producto->nombre ?? '');
            $pCodigo = is_array($producto) ? ($producto['codigo'] ?? '') : ($producto->codigo ?? '');
            $pId = is_array($producto) ? ($producto['producto_id'] ?? '') : ($producto->producto_id ?? '');
            $stock = is_array($producto) ? ($producto['stock_total'] ?? 0) : ($producto->stock_total ?? 0);
            $stockMin = is_array($producto) ? ($producto['stock_minimo'] ?? 0) : ($producto->stock_minimo ?? 0);
            $costo = is_array($producto) ? ($producto['costo'] ?? $producto['costo_promedio'] ?? 0) : ($producto->costo ?? $producto->costo_promedio ?? 0);
            if ($stock <= 0) {
                $bgClass    = 'bg-red-100';
                $hoverClass = 'hover:bg-red-200/70';
            } elseif ($stockMin > 0 && $stock <= $stockMin) {
                $bgClass    = 'bg-amber-50';
                $hoverClass = 'hover:bg-amber-100';
            } else {
                $bgClass    = '';
                $hoverClass = 'hover:bg-gray-50';
            }
            ?>
            <tr class="<?= $bgClass ?> <?= $hoverClass ?> transition-colors">
                <td class="px-3 py-3 max-w-[260px]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 bg-gray-100 rounded-lg flex items-center justify-center overflow-hidden shrink-0">
                            <?php $img = is_array($producto) ? ($producto['imagen_principal'] ?? '') : ($producto->imagen_principal ?? ''); ?>
                            <?php if ($img): ?>
                            <img src="/assets/uploads/<?= esc($img) ?>" class="h-9 w-9 object-cover" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                            <i class="fas fa-box text-gray-400 text-sm hidden"></i>
                            <?php else: ?>
                            <i class="fas fa-box text-gray-400 text-sm"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <a href="/inventario/<?= $pId ?>" class="text-sm font-semibold text-gray-900 hover:text-sky-500 transition-colors leading-tight"><?= esc($pNombre) ?></a>
                            <?php $vence = \App\Libraries\InventarioService::estadoVencimiento(is_array($producto) ? ($producto['fecha_vencimiento'] ?? null) : ($producto->fecha_vencimiento ?? null)); ?>
                            <?php if ($vence): ?>
                            <div class="text-[11px] font-semibold <?= $vence['nivel'] === 'vencido' ? 'text-red-600' : ($vence['nivel'] === 'pronto' ? 'text-orange-600' : 'text-gray-400') ?>"><i class="fas fa-hourglass-half mr-1"></i><?= esc($vence['texto']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td class="px-3 py-3 whitespace-nowrap text-base font-bold font-mono text-gray-900"><?= esc($pCodigo) ?></td>
                <td class="px-3 py-3 whitespace-nowrap text-sm font-semibold text-gray-700"><?php $pUbic = is_array($producto) ? ($producto['ubicacion'] ?? '') : ($producto->ubicacion ?? ''); ?><?= trim((string) $pUbic) !== '' ? esc($pUbic) : '<span class="text-gray-300">—</span>' ?></td>
                <?php if (ver_costos()): ?><td class="px-3 py-3 whitespace-nowrap text-sm text-gray-900">$<?= number_format((float)$costo, 2) ?></td><?php endif; ?>
                <td class="px-3 py-3 whitespace-nowrap text-sm font-semibold text-sky-600">
                    <?php $precioA = is_array($producto) ? ($producto['precio_a'] ?? 0) : ($producto->precio_a ?? 0); ?>
                    <?= $precioA ? '$' . number_format((float)$precioA, 2) : '<span class="text-gray-300">—</span>' ?>
                </td>
                <td class="px-3 py-3 whitespace-nowrap text-sm font-semibold text-emerald-600">
                    <?php $precioB = is_array($producto) ? ($producto['precio_b'] ?? 0) : ($producto->precio_b ?? 0); ?>
                    <?= $precioB ? '$' . number_format((float)$precioB, 2) : '<span class="text-gray-300">—</span>' ?>
                </td>
                <td class="px-3 py-3 whitespace-nowrap text-sm font-semibold text-pink-600">
                    <?php $precioC = is_array($producto) ? ($producto['precio_c'] ?? 0) : ($producto->precio_c ?? 0); ?>
                    <?= $precioC ? '$' . number_format((float)$precioC, 2) : '<span class="text-gray-300">—</span>' ?>
                </td>
                <td class="px-3 py-3 whitespace-nowrap">
                    <?php
                        $cantDepositos = is_array($producto) ? ($producto['cantidad_depositos'] ?? 1) : ($producto->cantidad_depositos ?? 1);
                        $depNombre     = is_array($producto) ? ($producto['deposito_principal'] ?? 'Principal') : ($producto->deposito_principal ?? 'Principal');
                        $stockColor    = $stock <= 0 ? 'text-red-600 font-black' : ($stockMin > 0 && $stock <= $stockMin ? 'text-amber-600 font-bold' : 'text-gray-900 font-semibold');
                    ?>
                    <?php $umProd = is_array($producto) ? ($producto['unidad_medida'] ?? 'und') : ($producto->unidad_medida ?? 'und'); ?>
                    <?php
                        if ($stock <= 0)                                { $dispIc = ['fa-times-circle', 'text-red-500', 'Agotado']; }
                        elseif ($stockMin > 0 && $stock <= $stockMin)   { $dispIc = ['fa-exclamation-circle', 'text-amber-500', 'Stock bajo']; }
                        else                                            { $dispIc = ['fa-check-circle', 'text-emerald-500', 'En stock']; }
                    ?>
                    <div class="text-sm <?= $stockColor ?> flex items-center gap-1.5"><span><?= rtrim(rtrim(number_format((float) $stock, 2), '0'), '.') ?> <?= esc(\App\Libraries\ProductoService::abreviatura($umProd)) ?></span><i class="fas <?= $dispIc[0] ?> <?= $dispIc[1] ?> text-xs" title="<?= $dispIc[2] ?>"></i></div>
                    <?php if ($dStock = \App\Libraries\ProductoService::desglose((float) $stock, (array) (is_array($producto) ? ($producto['presentaciones'] ?? []) : ($producto->presentaciones ?? [])), $umProd)): ?>
                    <div class="text-xs font-medium text-sky-700"><?= esc($dStock) ?></div>
                    <?php endif; ?>
                    <div class="text-xs text-gray-400 mt-0.5">
                        <?php if ($cantDepositos == 1): ?>
                            <?= esc($depNombre) ?>
                        <?php elseif ($cantDepositos > 1): ?>
                            <span class="text-sky-500 font-medium"><?= $cantDepositos ?> depósitos</span>
                        <?php else: ?>
                            Sin inventario
                        <?php endif; ?>
                    </div>
                </td>
                <td class="px-3 py-3 whitespace-nowrap text-right text-sm">
                    <div class="flex justify-end gap-1.5">
                        <a href="/inventario/<?= $pId ?>" class="w-8 h-8 rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition-colors flex items-center justify-center shadow-sm shadow-blue-500/30" title="Ver"><i class="fas fa-eye text-xs"></i></a>
                        <a href="/inventario/<?= $pId ?>/editar" class="w-8 h-8 rounded-lg bg-amber-500 text-white hover:bg-amber-600 transition-colors flex items-center justify-center shadow-sm shadow-amber-500/30" title="Editar"><i class="fas fa-edit text-xs"></i></a>
                        <form method="post" action="/inventario/<?= $pId ?>/eliminar" class="inline" onsubmit="return confirm('¿Eliminar este producto?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="w-8 h-8 rounded-lg bg-red-500 text-white hover:bg-red-600 transition-colors flex items-center justify-center shadow-sm shadow-red-500/30" title="Eliminar"><i class="fas fa-trash text-xs"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($productos)): ?>
            <tr>
                <td colspan="9" class="px-4 py-10 text-center text-sm text-gray-400">
                    <i class="fas fa-box-open text-3xl mb-2 block text-gray-200"></i>
                    No se encontraron productos.
                    <a href="/inventario/nuevo" class="block mt-1 text-sky-500 hover:underline font-medium">Crear uno nuevo</a>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if (!empty($productos)): ?>
<div class="mt-4">
    <?php echo view('partials/pagination', ['pagination' => $pagination ?? []]); ?>
</div>
<?php endif; ?>

<?php $this->endSection(); ?>

<?php $this->section('extra_js'); ?>
<script>
    let searchTimeout;
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const spinner = document.getElementById('search-spinner');
            if (e.target.value.length >= 2 || e.target.value.length === 0) {
                spinner.classList.remove('hidden');
                searchTimeout = setTimeout(() => { buscarSinRecargar(document.getElementById('search-form')); }, 500);
            }
        });
    }
</script>
<?php $this->endSection(); ?>
