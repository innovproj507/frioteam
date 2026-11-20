<?php
/**
 * Entrega (despacho) — misma distribución que la del sistema anterior:
 * cabecera centrada, datos de la entrega, lista de productos y firmas de preparado / entregado / recibido.
 * Tablas y CSS simple: se ve igual en el navegador y en Dompdf.
 *
 * @var array  $entrega $items $empresa $depositos
 * @var string $preparado
 * @var string $modo  'web' | 'pdf'
 */
$modo ??= 'web';
$papel ??= 'letter';

$nombre = (string) (($empresa['razon_social'] ?? '') ?: ($empresa['nombre_comercial'] ?? ''));
$lema   = 'REFRIGERACIÓN Y AIRE ACONDICIONADO';
$ruc    = trim((string) ($empresa['ruc'] ?? ''));
if ($ruc !== '' && !empty($empresa['dv'])) $ruc .= ' DV' . $empresa['dv'];
$web    = strtoupper((string) preg_replace('#^https?://#i', '', trim((string) ($empresa['sitio_web'] ?? ''))));

$nota = trim((string) ($entrega['nota'] ?? ''));
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Entrega <?= esc($entrega['numero']) ?></title>
    <style>
        @page { size: <?= $papel === 'a4' ? 'A4' : 'letter' ?> portrait; margin: 14mm 12mm; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #222; margin: 0; <?= $modo === 'web' ? 'background:#e5e7eb;' : '' ?> }
        .hoja { <?= $modo === 'web' ? 'max-width: 800px; margin: 16px auto; background:#fff; padding: 28px 34px; box-shadow: 0 1px 6px rgba(0,0,0,.15);' : '' ?> }
        .centro { text-align: center; }
        .centro h1 { margin: 0; font-size: 22px; font-weight: normal; letter-spacing: .5px; }
        .centro p { margin: 1px 0; }
        table { border-collapse: collapse; width: 100%; }
        .campos { margin-top: 14px; }
        .campos td { border: 1px solid #d4d4d4; padding: 6px 8px; vertical-align: top; }
        .campos td.etq { width: 28%; }
        .items { margin-top: 16px; }
        .items th { border: 1px solid #d4d4d4; padding: 7px 6px; font-size: 12px; text-align: center; }
        .items td { border: 1px solid #d4d4d4; padding: 6px 7px; vertical-align: middle; }
        .items td.cen { text-align: center; }
        .firmas { margin-top: 26px; }
        .firmas td { width: 33%; vertical-align: top; padding-right: 22px; }
        .linea { border-bottom: 1px solid #555; height: 46px; }
        tr { page-break-inside: avoid; }
        thead { display: table-header-group; }
        .barra { text-align: right; padding: 10px 14px; background: #1f2937; }
        .barra a, .barra button { background:#fff; color:#111; border:0; border-radius:4px; padding:6px 12px; font-size:13px; text-decoration:none; cursor:pointer; margin-left:6px; }
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
    <a href="/entregas/<?= (int) $entrega['entrega_id'] ?>/pdf">Descargar PDF</a>
    <a href="/ventas/venta/<?= (int) $entrega['venta_id'] ?>">Volver a la venta</a>
</div>
<?php endif; ?>
<div class="hoja">

    <div class="centro">
        <h1><?= esc($nombre) ?></h1>
        <p style="font-size: 10px;"><?= esc($lema) ?></p>
        <?php if ($ruc !== ''): ?><p style="font-size: 12px;">RUC: <?= esc($ruc) ?></p><?php endif; ?>
        <?php if ($web !== ''): ?><p style="font-size: 8px;"><?= esc($web) ?></p><?php endif; ?>
    </div>

    <table class="campos">
        <tr><td class="etq">Fecha</td><td><?= date('d/m/Y H:i', strtotime((string) $entrega['fecha'])) ?></td></tr>
        <tr><td class="etq">Entrega de Referencia</td><td><?= esc($entrega['numero']) ?></td></tr>
        <tr><td class="etq">Referencia Venta</td><td><?= esc($entrega['numero_factura']) ?></td></tr>
        <tr><td class="etq">Cliente</td><td><?= esc($entrega['cliente']) ?></td></tr>
        <tr><td class="etq">Dirección</td><td><?= nl2br(esc((string) $entrega['direccion'])) ?></td></tr>
        <?php if ($nota !== ''): ?><tr><td class="etq">Nota</td><td><?= nl2br(esc($nota)) ?></td></tr><?php endif; ?>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 32px;">No</th>
                <th>Descripción</th>
                <?php foreach ($depositos as $d): ?>
                    <th style="width: 78px;"><?= esc(mb_strtolower((string) $d['nombre'])) ?></th>
                <?php endforeach; ?>
                <th style="width: 64px;">Cantidad</th>
            </tr>
        </thead>
        <tbody>
        <?php $n = 1; foreach ($items as $it): ?>
            <tr>
                <td class="cen"><?= $n++ ?></td>
                <td><?= esc($it['nombre']) ?><?= $it['codigo'] !== null && $it['codigo'] !== '' ? ' (' . esc($it['codigo']) . ')' : '' ?></td>
                <?php foreach ($depositos as $d): ?><td></td><?php endforeach; ?>
                <td class="cen"><?= esc(number_format((float) $it['cantidad'], 2, '.', '')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <table class="firmas">
        <tr>
            <td>
                <p>Preparado por: <?= esc($preparado) ?></p>
                <div class="linea"></div>
                <p style="margin-top: 4px;">Firma</p>
            </td>
            <td>
                <p>Entregado por:</p>
                <div class="linea"></div>
                <p style="margin-top: 4px;">Firma</p>
            </td>
            <td>
                <p>Recibido por:</p>
                <div class="linea"></div>
                <p style="margin-top: 4px;">Firma</p>
            </td>
        </tr>
    </table>

</div>
</body>
</html>
