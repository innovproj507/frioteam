<?php $this->extend('layouts/app'); ?>
<?php $this->section('content'); ?>
<?php
$lineas = array_map(fn ($d) => [
    'id'       => (int) $d['detalle_id'],
    'codigo'   => (string) $d['producto_codigo'],
    'nombre'   => (string) $d['producto_nombre'],
    'cantidad' => (float) $d['cantidad'],
    'precio'   => (float) $d['precio'],
], $detalles);
?>
<div class="flex items-center gap-2 text-sm text-gray-400 mb-3">
    <a href="/ventas/cotizaciones?origen=web" class="hover:text-gray-600">Cotizaciones web</a>
    <i class="fas fa-chevron-right text-xs"></i>
    <a href="/ventas/cotizaciones/<?= (int) $cotizacion['cotizacion_id'] ?>" class="hover:text-gray-600">#<?= esc($cotizacion['numero']) ?></a>
    <i class="fas fa-chevron-right text-xs"></i>
    <span class="text-gray-700 font-medium">Precios</span>
</div>

<form method="post" action="/ventas/cotizaciones/<?= (int) $cotizacion['cotizacion_id'] ?>/precios" x-data="preciosWeb()" class="grid lg:grid-cols-3 gap-5 items-start">
    <?= csrf_field() ?>

    <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-bold text-gray-800"><i class="fas fa-tags text-emerald-500 mr-2"></i>Ponle precio a lo que pidió el cliente</h2>
            <p class="text-xs text-gray-500 mt-1">Cliente: <b><?= esc($cotizacion['cliente_nombre'] ?? 'Sin cliente') ?></b>. Los precios vienen propuestos según su tipo de precio. El cliente no ve nada hasta que pulses <b>Enviar precios al cliente</b>.</p>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                <tr><th class="text-left px-4 py-2.5">Producto</th><th class="text-right px-3 py-2.5 w-20">Cant.</th><th class="text-right px-3 py-2.5 w-36">Precio unit.</th><th class="text-right px-4 py-2.5 w-28">Total</th></tr>
            </thead>
            <tbody>
                <template x-for="l in lineas" :key="l.id">
                    <tr class="border-t border-gray-50">
                        <td class="px-4 py-2.5"><span class="font-mono font-bold text-gray-900 mr-2" x-text="l.codigo"></span><span class="text-gray-700" x-text="l.nombre"></span></td>
                        <td class="px-3 py-2.5 text-right" x-text="l.cantidad"></td>
                        <td class="px-3 py-2.5 text-right">
                            <input type="number" step="0.01" min="0" :name="'precio[' + l.id + ']'" x-model.number="l.precio"
                                   class="w-28 text-right border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                        </td>
                        <td class="px-4 py-2.5 text-right font-semibold" x-text="'$' + (l.cantidad * l.precio).toFixed(2)"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-3 text-sm lg:sticky lg:top-4">
        <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><b x-text="'$' + subtotal().toFixed(2)"></b></div>
        <div class="flex justify-between"><span class="text-gray-500">ITBMS <?= $exento ? '(cliente exento)' : '(' . rtrim(rtrim(number_format($tasa * 100, 2), '0'), '.') . '%)' ?></span><b x-text="'$' + itbms().toFixed(2)"></b></div>
        <div class="flex justify-between text-lg border-t pt-3"><span class="font-bold">Total</span><b class="text-emerald-600" x-text="'$' + (subtotal() + itbms()).toFixed(2)"></b></div>
        <button name="accion" value="enviar" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold"><i class="fas fa-paper-plane mr-1"></i> Enviar precios al cliente</button>
        <button name="accion" value="borrador" class="w-full py-2.5 border border-gray-200 text-gray-600 rounded-xl font-semibold hover:bg-gray-50">Guardar sin enviar</button>
        <?php if (($cotizacion['estado'] ?? '') === 'aprobada'): ?><p class="text-xs text-emerald-600"><i class="fas fa-check-circle"></i> Ya enviada: el cliente ve estos precios. Si los cambias y la envías, se actualizan.</p><?php endif; ?>
    </div>
</form>

<script>
function preciosWeb() {
    const tasa = <?= $exento ? '0' : json_encode($tasa) ?>;
    return {
        lineas: <?= json_encode($lineas, JSON_UNESCAPED_UNICODE) ?>,
        subtotal() { return this.lineas.reduce((s, l) => s + l.cantidad * (parseFloat(l.precio) || 0), 0); },
        itbms() { return this.subtotal() * tasa; },
    };
}
</script>
<?php $this->endSection(); ?>
