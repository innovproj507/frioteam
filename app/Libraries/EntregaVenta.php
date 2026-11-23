<?php

declare(strict_types=1);

namespace App\Libraries;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Entregas (despachos) de una venta, como el "Agregar entrega" del sistema anterior:
 * un documento DESP-00005368 con la lista de productos para que los verifiquen al despachar,
 * con las firmas de preparado, entregado y recibido. Se guarda, se ve, se baja en PDF o se
 * imprime directo en la Ricoh por CUPS.
 */
final class EntregaVenta
{
    /** Datos para llenar el formulario "Agregar entrega" de una venta. */
    public static function sugerencia(int $ventaId, int $empresaId, int $sucursalId): ?array
    {
        $v = Db::row(
            "SELECT v.venta_id, v.numero_factura, c.nombre, c.direccion, c.telefono, c.email
             FROM ventas v LEFT JOIN clientes c ON c.cliente_id = v.cliente_id
             WHERE v.venta_id = ? AND v.sucursal_id = ?",
            [$ventaId, $sucursalId]
        );
        if (!$v) {
            return null;
        }
        // Dirección + teléfono y correo, como el sistema anterior
        $contacto = trim(implode(' ', array_filter([
            !empty($v['telefono']) ? 'Tel: ' . $v['telefono'] : '',
            !empty($v['email']) ? 'Email: ' . $v['email'] : '',
        ])));
        $direccion = trim(implode("\n", array_filter([trim((string) ($v['direccion'] ?? '')), $contacto])));

        return [
            'venta_id'       => (int) $v['venta_id'],
            'referencia_venta' => (string) $v['numero_factura'],
            'cliente'        => (string) ($v['nombre'] ?: 'Consumidor Final'),
            'direccion'      => $direccion,
            'fecha'          => date('Y-m-d\TH:i'),
            'numero_sugerido' => NumeracionService::siguiente('entrega', $empresaId, $sucursalId),
        ];
    }

    /**
     * Crea la entrega y devuelve su id. Si no se indica número, se toma el siguiente DESP-########.
     * @param array{fecha?: string, numero?: string, cliente?: string, direccion?: string, nota?: string} $campos
     */
    public static function crear(int $ventaId, int $empresaId, int $sucursalId, ?int $usuarioId, array $campos): int
    {
        $base = self::sugerencia($ventaId, $empresaId, $sucursalId);
        if (!$base) {
            throw new \RuntimeException('Venta no encontrada.');
        }

        $ts    = strtotime(str_replace('T', ' ', trim((string) ($campos['fecha'] ?? ''))));
        $fecha = date('Y-m-d H:i:s', $ts !== false ? $ts : time());
        $cliente   = trim((string) ($campos['cliente'] ?? '')) ?: $base['cliente'];
        $direccion = trim((string) ($campos['direccion'] ?? ''));
        $nota      = trim((string) ($campos['nota'] ?? ''));
        $numero    = trim((string) ($campos['numero'] ?? ''));

        if ($numero !== '' && Db::value("SELECT 1 FROM entregas WHERE numero = ?", [$numero])) {
            throw new \RuntimeException("Ya existe una entrega con el número {$numero}.");
        }

        for ($intento = 0; $intento < 3; $intento++) {
            $num = $numero !== '' ? $numero : NumeracionService::siguiente('entrega', $empresaId, $sucursalId);
            try {
                return (int) Db::insert('entregas', [
                    'empresa_id'  => $empresaId,
                    'sucursal_id' => $sucursalId,
                    'venta_id'    => $ventaId,
                    'numero'      => $num,
                    'fecha'       => $fecha,
                    'cliente'     => mb_substr($cliente, 0, 200),
                    'direccion'   => $direccion,
                    'nota'        => $nota,
                    'usuario_id'  => $usuarioId,
                ]);
            } catch (\Throwable $e) {
                // Dos entregas a la vez pueden pedir el mismo número: se vuelve a calcular
                if ($numero !== '' || $intento === 2) {
                    throw $e;
                }
            }
        }
        throw new \RuntimeException('No se pudo generar el número de la entrega.');
    }

    /** Entregas de una venta (más recientes primero). */
    public static function porVenta(int $ventaId, int $sucursalId): array
    {
        return Db::all(
            "SELECT entrega_id, numero, fecha, cliente FROM entregas WHERE venta_id = ? AND sucursal_id = ? ORDER BY entrega_id DESC",
            [$ventaId, $sucursalId]
        );
    }

    /** Todo lo necesario para ver / imprimir una entrega, o null si no existe en esa sucursal. */
    public static function datos(int $entregaId, int $sucursalId, bool $bodega = false): ?array
    {
        $e = Db::row(
            "SELECT e.*, v.numero_factura, v.empresa_id AS venta_empresa_id,
                    TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))) AS preparado_nombre, u.username AS preparado_usuario
             FROM entregas e
             JOIN ventas v ON v.venta_id = e.venta_id
             LEFT JOIN users u ON u.id = e.usuario_id
             WHERE e.entrega_id = ? AND e.sucursal_id = ?",
            [$entregaId, $sucursalId]
        );
        if (!$e) {
            return null;
        }
        $items = Db::all(
            "SELECT p.producto_id, d.deposito_id, p.nombre, p.codigo, d.cantidad, d.unidad_nombre
             FROM ventas_detalle d JOIN productos p ON p.producto_id = d.producto_id
             WHERE d.venta_id = ? ORDER BY d.detalle_id",
            [$e['venta_id']]
        );

        $depositos = Db::all("SELECT deposito_id, nombre FROM depositos WHERE sucursal_id = ? AND estado = 'activo' ORDER BY deposito_id", [$sucursalId]);

        // Hoja para bodega: la ubicación del producto va en la columna del depósito que usó la venta; el otro queda en blanco
        if ($bodega && $items && $depositos) {
            $ids  = array_values(array_unique(array_map(fn ($i) => (int) $i['producto_id'], $items)));
            $dep  = array_map(fn ($d) => (int) $d['deposito_id'], $depositos);
            $ph   = implode(',', array_fill(0, count($ids), '?'));
            $ph2  = implode(',', array_fill(0, count($dep), '?'));
            $stock = [];
            foreach (Db::all("SELECT producto_id, deposito_id, SUM(existencia) AS existencia, MAX(ubicacion) AS ubicacion FROM inventario WHERE producto_id IN ($ph) AND deposito_id IN ($ph2) GROUP BY producto_id, deposito_id", array_merge($ids, $dep)) as $r) {
                $stock[(int) $r['producto_id']][(int) $r['deposito_id']] = ['existencia' => (float) $r['existencia'], 'ubicacion' => trim((string) ($r['ubicacion'] ?? ''))];
            }
            foreach ($items as &$it) {
                // Solo el depósito de esa línea de la venta. Con existencia suficiente sale la ubicación;
                // con existencia insuficiente la celda queda gris (sin ubicación); sin existencia (0 o negativa), en blanco.
                $dl  = (int) ($it['deposito_id'] ?? 0);
                $reg = $stock[(int) $it['producto_id']][$dl] ?? ['existencia' => 0.0, 'ubicacion' => ''];
                if ($reg['existencia'] <= 0) {
                    $estado = 'vacio';
                } elseif ($reg['existencia'] + 0.0001 < (float) $it['cantidad']) {
                    $estado = 'insuficiente';
                } else {
                    $estado = 'ok';
                }
                $it['celda_dep'] = [$dl => ['estado' => $estado, 'ubicacion' => $reg['ubicacion']]];
            }
            unset($it);
        }

        return [
            'bodega'     => $bodega,
            'entrega'    => $e,
            'items'      => $items,
            'empresa'    => Db::row("SELECT razon_social, nombre_comercial, ruc, dv, sitio_web FROM companies WHERE empresa_id = ?", [$e['empresa_id']]) ?? [],
            // Columnas en blanco, una por depósito, para que anoten de dónde sacaron cada producto
            'depositos'  => $depositos,
            'preparado'  => trim((string) ($e['preparado_nombre'] ?: ($e['preparado_usuario'] ?? ''))),
        ];
    }

    public static function pdf(array $datos, string $papel = 'letter'): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->setPaper($papel === 'a4' ? 'a4' : 'letter', 'portrait');
        $dompdf->loadHtml(view('ventas/entrega', $datos + ['modo' => 'pdf', 'papel' => $papel]));
        $dompdf->render();

        return $dompdf->output();
    }

    public static function nombreArchivo(array $datos): string
    {
        $numero = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($datos['entrega']['numero'] ?? 'entrega')) ?: 'entrega';
        return (!empty($datos['bodega']) ? 'Bodega_' : 'Entrega_') . $numero . '.pdf';
    }

    /** Imprime la entrega en la Ricoh por CUPS. Devuelve el id del trabajo. */
    public static function imprimir(int $entregaId, int $sucursalId, bool $bodega = false): string
    {
        $datos = self::datos($entregaId, $sucursalId, $bodega);
        if (!$datos) {
            throw new \RuntimeException('Entrega no encontrada.');
        }
        $papel = ImpresionCups::papel();
        return ImpresionCups::imprimirPdf(
            self::pdf($datos, $papel),
            ($bodega ? 'Bodega ' : 'Entrega ') . $datos['entrega']['numero'],
            ImpresionCups::cola(),
            $papel === 'a4' ? 'A4' : 'Letter'
        );
    }
}
