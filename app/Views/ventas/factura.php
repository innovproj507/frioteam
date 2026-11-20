<?php
/**
 * Factura en hoja — mismo orden y textos que la del sistema anterior.
 * Layout con tablas y CSS simple: se ve igual en el navegador y en Dompdf.
 *
 * @var array  $venta $cliente $items $empresa $sucursal
 * @var string $modo  'web' | 'pdf'
 */
$modo ??= 'web';
$dinero = static fn ($n): string => number_format((float) $n, 2);

$nombreEmpresa = (string) (($empresa['razon_social'] ?? '') ?: ($empresa['nombre_comercial'] ?? ''));
$ruc = trim((string) ($empresa['ruc'] ?? ''));
if ($ruc !== '' && !empty($empresa['dv'])) $ruc .= ' DV ' . $empresa['dv'];
$direccion = (string) (($sucursal['direccion'] ?? '') ?: ($empresa['direccion'] ?? ''));
$telefono  = (string) (($sucursal['telefono'] ?? '') ?: ($empresa['telefono'] ?? ''));
$correo    = (string) (($sucursal['email'] ?? '') ?: ($empresa['email'] ?? ''));

// "442-8168 CEL. 6552-4288" -> Tel / Cel. / Whatsapp (el WhatsApp es el número del celular)
$tel = $telefono; $cel = '';
if (preg_match('/^(.*?)\s*cel\.?\s*(.+)$/iu', $telefono, $mTel)) {
    $tel = trim($mTel[1], " /,-");
    $cel = trim($mTel[2]);
}

$rucCliente = trim((string) ($cliente['ruc'] ?? ''));
if ($rucCliente !== '' && !empty($cliente['dv'])) $rucCliente .= ' DV ' . $cliente['dv'];

$logo = FCPATH . 'assets/img/logo.png';
$logoSrc = is_file($logo) ? 'data:image/png;base64,' . base64_encode((string) file_get_contents($logo)) : '';

// Lema y avisos de pie, como en la factura impresa de FrioTeam
$lema = 'Repuestos de Refrigeración';
$condiciones = [
    'NO SE ACEPTAN DEVOLUCIÓN EN PARTES ELÉCTRICAS.',
    'NO NOS HACEMOS RESPONSABLES POR FALTANTES DE MERCANCÍA, POR FAVOR VERIFIQUE TODO ANTES DE SALIR DEL LOCAL.',
];
$web = trim((string) ($empresa['sitio_web'] ?? ''));

$nota = trim((string) ($venta['notas'] ?? ''));
if (trim($nota, " -\t") === '') $nota = '';
$emitidoPor = trim((string) ($venta['vendedor_nombre'] ?? '')) ?: (string) ($venta['vendedor'] ?? '');
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Factura <?= esc($venta['numero_factura']) ?></title>
    <style>
        @page { size: <?= ($papel ?? 'letter') === 'a4' ? 'A4' : 'letter' ?> portrait; margin: 14mm 12mm; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #111; margin: 0; <?= $modo === 'web' ? 'background:#e5e7eb;' : '' ?> }
        .hoja { <?= $modo === 'web' ? 'max-width: 800px; margin: 16px auto; background:#fff; padding: 28px 34px; box-shadow: 0 1px 6px rgba(0,0,0,.15);' : '' ?> }
        h3 { margin: 0 0 4px 0; font-size: 15px; }
        p  { margin: 0 0 3px 0; }
        table { border-collapse: collapse; width: 100%; }
        .sin-borde td { border: 0; vertical-align: top; padding: 0; }
        .items th { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 5px 6px; font-size: 10.5px; text-align: center; }
        .items td { border: 1px solid #cbd5e1; padding: 5px 6px; vertical-align: middle; }
        .der { text-align: right; } .cen { text-align: center; }
        .tot td { border: 1px solid #cbd5e1; padding: 5px 8px; }
        .tot td.etq { text-align: right; }
        .tot tr.final td { font-weight: bold; font-size: 12px; background: #f8fafc; }
        .firma { border-bottom: 1px solid #555; height: 34px; }
        .barra { text-align: right; padding: 10px 14px; background: #1f2937; }
        .barra a, .barra button { background:#fff; color:#111; border:0; border-radius:4px; padding:6px 12px; font-size:13px; text-decoration:none; cursor:pointer; margin-left:6px; }
        tr, .firma, .sin-borde td { page-break-inside: avoid; }
        thead { display: table-header-group; }
        @media print {
            html, body { width: auto; height: auto; margin: 0; padding: 0; background: #fff !important; }
            .barra { display: none !important; }
            .hoja { box-shadow: none; margin: 0; max-width: none; width: 100%; padding: 0; }
        }
    </style>
</head>
<body>
<?php if ($modo === 'web'): ?>
<div class="barra">
    <button onclick="window.print()">Imprimir</button>
    <a href="/ventas/venta/<?= (int) $venta['venta_id'] ?>/factura/pdf">Descargar PDF</a>
    <a href="/ventas/venta/<?= (int) $venta['venta_id'] ?>">Volver a la venta</a>
</div>
<?php endif; ?>
<div class="hoja">

    <?php if ($logoSrc): ?><img src="<?= $logoSrc ?>" alt="<?= esc($empresa['nombre_comercial'] ?? '') ?>" style="height: 64px; margin-bottom: 8px;"><?php endif; ?>

    <table class="sin-borde">
        <tr>
            <td style="width: 50%; padding-right: 14px;">
                <h3><?= esc($nombreEmpresa) ?></h3>
                <?php if ($lema !== ''): ?><p style="font-style: italic; color: #475569;"><?= esc($lema) ?></p><?php endif; ?>
                <?php if ($direccion !== ''): ?><p><?= esc($direccion) ?></p><?php endif; ?>
                <p style="margin-top: 8px;">
                    <?php if ($tel !== ''): ?>Tel: <?= esc($tel) ?><br><?php endif; ?>
                    <?php if ($cel !== ''): ?>Cel. <?= esc($cel) ?><br>Whatsapp: <?= esc($cel) ?><br><?php endif; ?>
                    <?php if ($correo !== ''): ?>Email: <?= esc($correo) ?><br><?php endif; ?>
                    <?php if ($web !== ''): ?>Web: <?= esc($web) ?><br><?php endif; ?>
                    <?php if ($ruc !== ''): ?><b>RUC: <?= esc($ruc) ?></b><?php endif; ?>
                </p>
            </td>
            <td style="width: 50%;">
                <p>Facturado a:</p>
                <h3><?= esc($cliente['nombre']) ?></h3>
                <p>
                    <?php if (!empty($cliente['direccion'])): ?>Dirección: <?= esc($cliente['direccion']) ?><br><?php endif; ?>
                    <?php if (!empty($cliente['telefono'])): ?>Tel: <?= esc($cliente['telefono']) ?><br><?php endif; ?>
                    <?php if (!empty($cliente['email'])): ?>Email: <?= esc($cliente['email']) ?><br><?php endif; ?>
                    <?php if ($rucCliente !== ''): ?>RUC: <?= esc($rucCliente) ?><?php endif; ?>
                </p>
            </td>
        </tr>
    </table>

    <table class="sin-borde" style="margin: 18px 0 10px 0;">
        <tr>
            <td style="width: 50%;"><h3 style="font-size: 16px;">Factura No. <?= esc($venta['numero_factura']) ?></h3></td>
            <td style="width: 50%;">
                <p style="font-weight: bold;">Fecha: <?= date('d/m/Y', strtotime((string) $venta['fecha'])) ?></p>
                <p style="font-weight: bold;">Hora: <?= date('H:i:s', strtotime((string) $venta['fecha'])) ?></p>
                <?php if (($venta['estado'] ?? '') === 'anulada'): ?><p style="font-weight: bold; color: #b91c1c;">FACTURA ANULADA</p><?php endif; ?>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 92px;">Ubicación</th>
                <th style="width: 70px;">Código</th>
                <th>Descripción</th>
                <th style="width: 62px;">Cantidad</th>
                <th style="width: 82px;">Precio unitario</th>
                <th style="width: 82px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
        <?php $n = 1; foreach ($items as $it): ?>
            <tr>
                <td class="cen"><?= $n++ ?></td>
                <td class="cen">
                    <?php if (!empty($it['ubicacion'])): ?><b><?= esc($it['ubicacion']) ?></b><?php else: ?><span style="color:#94a3b8;">—</span><?php endif; ?>
                    <?php if (!empty($it['deposito_nombre'])): ?><br><span style="font-size: 9px; color: #64748b;"><?= esc($it['deposito_nombre']) ?></span><?php endif; ?>
                </td>
                <td class="cen" style="font-size: 10px;"><?= esc((string) $it['codigo']) ?></td>
                <td><?= esc($it['nombre']) ?></td>
                <td class="cen"><?= esc(rtrim(rtrim(number_format((float) $it['cantidad'], 2, '.', ''), '0'), '.')) ?><?= !empty($it['unidad_nombre']) ? ' ' . esc($it['unidad_nombre']) : '' ?></td>
                <td class="der"><?= $dinero($it['precio']) ?></td>
                <td class="der"><?= $dinero($it['subtotal']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <table class="tot" style="margin-top: 0;">
        <tr><td class="etq">Total (USD)</td><td class="der" style="width: 100px;"><?= $dinero($venta['subtotal']) ?></td></tr>
        <?php if ((float) $venta['descuento'] > 0.004): ?>
            <tr><td class="etq">Descuento (USD)</td><td class="der">-<?= $dinero($venta['descuento']) ?></td></tr>
        <?php endif; ?>
        <tr><td class="etq">ITBMS (USD)</td><td class="der"><?= $dinero($venta['itbms']) ?></td></tr>
        <tr class="final"><td class="etq">Total a pagar (USD)</td><td class="der"><?= $dinero($venta['total']) ?></td></tr>
    </table>

    <?php if ($nota !== ''): ?>
        <p style="margin-top: 16px; font-weight: bold; font-size: 12px;">Nota:</p>
        <p><?= nl2br(esc($nota)) ?></p>
    <?php endif; ?>

    <div style="margin-top: 22px; text-align: center; font-size: 10px; color: #334155;">
        <p style="font-weight: bold; font-size: 11px;">*** ESTIMADOS CLIENTES ***</p>
        <?php foreach ($condiciones as $c): ?><p>* <?= esc($c) ?></p><?php endforeach; ?>
    </div>

    <table class="sin-borde" style="margin-top: 30px;">
        <tr>
            <td style="width: 44%;">
                <p>Comprador: <?= esc($cliente['nombre']) ?></p>
                <div class="firma"></div>
                <p style="margin-top: 3px;">Firma y sello</p>
            </td>
            <td style="width: 12%;"></td>
            <td style="width: 44%;">
                <p>Emitido por: <?= esc($emitidoPor) ?></p>
                <div class="firma"></div>
                <p style="margin-top: 3px;">Firma y sello</p>
            </td>
        </tr>
    </table>

</div>
<?php if ($modo === 'web'):
    $auto   = (bool) service('request')->getGet('auto');
    $volver = service('request')->getGet('volver') === 'pos';
?>
<script>
(function () {
    <?php if ($volver): ?>window.addEventListener('afterprint', function () { location.href = '/ventas/pos'; });<?php endif; ?>
    <?php if ($auto): ?>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 500); });<?php endif; ?>
})();
</script>
<?php endif; ?>
</body>
</html>
