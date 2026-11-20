<?php

declare(strict_types=1);

namespace App\Libraries;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Factura de venta en hoja (carta), con el formato de la factura del sistema anterior
 * (Stock Manager Advance): logo y datos de la empresa, "Facturado a", tabla de ítems,
 * totales, notas y firmas de comprador / emisor. HTML para ver o imprimir y PDF (Dompdf).
 */
final class FacturaVenta
{
    /** Datos completos de la factura, o null si la venta no existe en esa sucursal. */
    public static function datos(int $ventaId, int $sucursalId): ?array
    {
        $v = Db::row(
            "SELECT v.venta_id, v.empresa_id, v.sucursal_id, v.numero_factura, v.subtotal, v.itbms, v.descuento, v.total,
                    v.estado, v.forma_pago, v.fecha, v.notas, v.cliente_id, u.username AS vendedor,
                    TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))) AS vendedor_nombre
             FROM ventas v LEFT JOIN users u ON u.id = v.vendedor_id
             WHERE v.venta_id = ? AND v.sucursal_id = ?",
            [$ventaId, $sucursalId]
        );
        if (!$v) {
            return null;
        }

        $cliente = $v['cliente_id']
            ? Db::row("SELECT nombre, ruc, dv, direccion, telefono, email FROM clientes WHERE cliente_id = ?", [$v['cliente_id']])
            : null;

        $items = Db::all(
            "SELECT p.nombre, p.codigo, d.cantidad, d.unidad_nombre, d.precio, d.descuento, d.itbms,
                    dp.nombre AS deposito_nombre, inv.ubicacion
             FROM ventas_detalle d
             JOIN productos p ON p.producto_id = d.producto_id
             LEFT JOIN depositos dp ON dp.deposito_id = d.deposito_id
             LEFT JOIN inventario inv ON inv.producto_id = d.producto_id AND inv.deposito_id = d.deposito_id
             WHERE d.venta_id = ? ORDER BY d.detalle_id",
            [$ventaId]
        );
        // Subtotal de línea = neto (cantidad × precio − descuento); el ITBMS se muestra solo en los totales
        foreach ($items as &$it) {
            $neto           = round((float) $it['cantidad'] * (float) $it['precio'] - (float) $it['descuento'], 2);
            $it['neto']     = $neto;
            $it['subtotal'] = $neto;
        }
        unset($it);

        return [
            'venta'    => $v,
            'cliente'  => $cliente ?: ['nombre' => 'Consumidor Final', 'ruc' => '', 'dv' => '', 'direccion' => '', 'telefono' => '', 'email' => ''],
            'items'    => $items,
            'empresa'  => Db::row("SELECT razon_social, nombre_comercial, ruc, dv, direccion, telefono, email, sitio_web FROM companies WHERE empresa_id = ?", [$v['empresa_id']]) ?? [],
            'sucursal' => Db::row("SELECT nombre, direccion, telefono, email FROM branches WHERE sucursal_id = ?", [$v['sucursal_id']]) ?? [],
        ];
    }

    public static function pdf(array $datos, string $papel = 'letter'): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->setPaper($papel === 'a4' ? 'a4' : 'letter', 'portrait');
        $dompdf->loadHtml(view('ventas/factura', $datos + ['modo' => 'pdf', 'papel' => $papel]));
        $dompdf->render();

        return $dompdf->output();
    }

    public static function nombreArchivo(array $datos): string
    {
        $numero = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($datos['venta']['numero_factura'] ?? 'factura')) ?: 'factura';
        return 'Factura_' . $numero . '.pdf';
    }
}
