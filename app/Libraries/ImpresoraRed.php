<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Impresión directa de tickets en una impresora térmica de red (ESC/POS, puerto 9100).
 * Pensada para la BIXOLON SRP-350II (80 mm, 42 columnas) que usaba el sistema anterior.
 *
 * El navegador no puede abrir un socket TCP, así que el SERVIDOR envía el ticket a la
 * impresora (debe alcanzarla por red: mismo LAN). Se configura en .env:
 *   impresora.ip = 192.168.0.100
 *   impresora.puerto = 9100
 *   impresora.columnas = 42
 *   impresora.auto = false        (true = imprime sola al cobrar en el POS)
 */
class ImpresoraRed
{
    private const ESC = "\x1B";
    private const GS  = "\x1D";

    public static function configurada(): bool
    {
        return trim((string) env('impresora.ip', '')) !== '';
    }

    public static function automatica(): bool
    {
        return filter_var(env('impresora.auto', false), FILTER_VALIDATE_BOOLEAN);
    }

    private static function columnas(): int
    {
        $c = (int) env('impresora.columnas', 42);
        return $c >= 24 && $c <= 64 ? $c : 42;
    }

    /** Imprime el ticket de una venta. Lanza \RuntimeException con un mensaje claro si falla. */
    public static function imprimirVenta(int $ventaId, int $sucursalId): int
    {
        $v = self::datosVenta($ventaId, $sucursalId);
        if (!$v) {
            throw new \RuntimeException('Venta no encontrada.');
        }
        return self::enviar(self::ticketVenta($v));
    }

    /** Envía bytes crudos a la impresora; devuelve cuántos bytes se enviaron. */
    public static function enviar(string $bytes): int
    {
        $ip     = trim((string) env('impresora.ip', ''));
        $puerto = (int) env('impresora.puerto', 9100);
        if ($ip === '') {
            throw new \RuntimeException('No hay impresora de red configurada (impresora.ip en .env).');
        }

        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client("tcp://{$ip}:{$puerto}", $errno, $errstr, 4);
        if (!$socket) {
            throw new \RuntimeException("No se pudo conectar con la impresora {$ip}:{$puerto} ({$errstr}). Revisa que esté encendida y en red.");
        }
        stream_set_timeout($socket, 5);
        $escrito = 0;
        $total   = strlen($bytes);
        while ($escrito < $total) {
            $n = @fwrite($socket, substr($bytes, $escrito, 4096));
            if ($n === false || $n === 0) {
                fclose($socket);
                throw new \RuntimeException('La impresora dejó de responder mientras se enviaba el ticket.');
            }
            $escrito += $n;
        }
        fflush($socket);
        usleep(200000); // deja vaciar el búfer antes de cerrar la conexión
        fclose($socket);
        return $total;
    }

    /** Datos de la venta para el ticket. */
    public static function datosVenta(int $ventaId, int $sucursalId): ?array
    {
        $v = Db::row(
            "SELECT v.venta_id, v.empresa_id, v.sucursal_id, v.numero_factura, v.subtotal, v.itbms, v.descuento, v.total,
                    v.estado, v.forma_pago, v.fecha, v.notas, v.caja_id,
                    c.nombre AS cliente_nombre, c.ruc AS cliente_ruc, u.username AS vendedor
             FROM ventas v
             LEFT JOIN clientes c ON c.cliente_id = v.cliente_id
             LEFT JOIN users u ON u.id = v.vendedor_id
             WHERE v.venta_id = ? AND v.sucursal_id = ?",
            [$ventaId, $sucursalId]
        );
        if (!$v) {
            return null;
        }
        $v['items'] = Db::all(
            "SELECT p.nombre, p.codigo, d.cantidad, d.unidad_nombre, d.precio, d.descuento, d.total_linea
             FROM ventas_detalle d JOIN productos p ON p.producto_id = d.producto_id
             WHERE d.venta_id = ? ORDER BY d.detalle_id",
            [$ventaId]
        );
        $v['pagos']  = Db::all("SELECT forma_pago, monto, referencia FROM ventas_pagos WHERE venta_id = ? ORDER BY pago_id", [$ventaId]);
        $v['vuelto'] = VentaService::vueltoEfectivo((string) $v['numero_factura'], $v['caja_id'] ? (int) $v['caja_id'] : null);

        $v['empresa']  = Db::row("SELECT razon_social, nombre_comercial, ruc, dv FROM companies WHERE empresa_id = ?", [$v['empresa_id']]) ?? [];
        $v['sucursal'] = Db::row("SELECT nombre, direccion, telefono FROM branches WHERE sucursal_id = ?", [$v['sucursal_id']]) ?? [];
        return $v;
    }

    /** Arma el ticket ESC/POS (texto ASCII: la impresora no trae acentos en su página de códigos). */
    public static function ticketVenta(array $v): string
    {
        $cols = self::columnas();
        $e = $v['empresa'] ?? [];
        $s = $v['sucursal'] ?? [];

        $out  = self::ESC . '@';                       // inicializar
        $out .= self::ESC . 't' . "\x00";              // página de códigos 0 (ASCII)
        $out .= self::centro();
        $out .= self::negrita(true) . self::dobleAlto(true);
        $out .= self::lineas(self::ascii((string) (($e['nombre_comercial'] ?? '') ?: ($e['razon_social'] ?? ''))), $cols);
        $out .= self::dobleAlto(false) . self::negrita(false);
        if (!empty($e['razon_social']) && ($e['razon_social'] !== ($e['nombre_comercial'] ?? ''))) {
            $out .= self::lineas(self::ascii((string) $e['razon_social']), $cols);
        }
        $ruc = trim((string) ($e['ruc'] ?? ''));
        if ($ruc !== '' && !empty($e['dv'])) {
            $ruc .= ' DV ' . $e['dv'];
        }
        if ($ruc !== '') {
            $out .= self::lineas('RUC: ' . $ruc, $cols);
        }
        if (!empty($s['direccion'])) {
            $out .= self::lineas(self::ascii((string) $s['direccion']), $cols);
        }
        if (!empty($s['telefono'])) {
            $out .= self::lineas('Tel: ' . self::ascii((string) $s['telefono']), $cols);
        }

        $out .= self::izquierda() . self::separador($cols);
        $out .= self::lineas('Factura: ' . $v['numero_factura'], $cols);
        $out .= self::lineas('Fecha: ' . date('d/m/Y H:i', strtotime((string) $v['fecha'])), $cols);
        $out .= self::lineas('Cliente: ' . self::ascii((string) ($v['cliente_nombre'] ?: 'Consumidor Final')), $cols);
        if (!empty($v['cliente_ruc'])) {
            $out .= self::lineas('RUC cliente: ' . self::ascii((string) $v['cliente_ruc']), $cols);
        }
        if (!empty($v['vendedor'])) {
            $out .= self::lineas('Atendido por: ' . self::ascii((string) $v['vendedor']), $cols);
        }
        if (($v['estado'] ?? '') === 'anulada') {
            $out .= self::centro() . self::negrita(true) . "*** ANULADA ***\n" . self::negrita(false) . self::izquierda();
        }
        $out .= self::separador($cols);

        // Líneas: nombre, y debajo "cant x precio" a la izquierda y total a la derecha
        $n = 1;
        foreach ($v['items'] as $it) {
            $nombre = self::ascii((string) $it['nombre']);
            $out .= self::lineas('#' . $n . ' ' . $nombre, $cols);
            $cant = rtrim(rtrim(number_format((float) $it['cantidad'], 2, '.', ''), '0'), '.');
            $pres = !empty($it['unidad_nombre']) ? ' ' . self::ascii((string) $it['unidad_nombre']) : '';
            $izq  = $cant . $pres . ' x ' . number_format((float) $it['precio'], 2);
            $out .= self::dosColumnas($izq, number_format((float) $it['total_linea'], 2), $cols);
            if ((float) $it['descuento'] > 0.005) {
                $out .= self::dosColumnas('  Descuento', '-' . number_format((float) $it['descuento'], 2), $cols);
            }
            $n++;
        }
        $out .= self::separador($cols);

        $out .= self::dosColumnas('Subtotal', number_format((float) $v['subtotal'], 2), $cols);
        if ((float) $v['descuento'] > 0.005) {
            $out .= self::dosColumnas('Descuento', '-' . number_format((float) $v['descuento'], 2), $cols);
        }
        $out .= self::dosColumnas('ITBMS', number_format((float) $v['itbms'], 2), $cols);
        $out .= self::negrita(true) . self::dobleAlto(true);
        $out .= self::dosColumnas('TOTAL', number_format((float) $v['total'], 2), $cols);
        $out .= self::dobleAlto(false) . self::negrita(false);

        if (!empty($v['pagos'])) {
            $out .= self::separador($cols);
            foreach ($v['pagos'] as $p) {
                $out .= self::dosColumnas(self::ascii((string) $p['forma_pago']), number_format((float) $p['monto'], 2), $cols);
                if (!empty($p['referencia'])) {
                    $out .= self::lineas('  Ref: ' . self::ascii((string) $p['referencia']), $cols);
                }
            }
            if ((float) ($v['vuelto'] ?? 0) > 0.005) {
                $out .= self::dosColumnas('Vuelto', number_format((float) $v['vuelto'], 2), $cols);
            }
        }
        if (!empty($v['notas']) && trim((string) $v['notas'], " -\t") !== '') {
            $out .= self::separador($cols) . self::lineas('Nota: ' . self::ascii((string) $v['notas']), $cols);
        }

        $out .= self::centro() . "\n" . self::lineas('Gracias por su compra', $cols);
        $out .= "\n\n\n\n";
        $out .= self::GS . 'V' . "\x42" . "\x03";      // corte parcial con avance
        return $out;
    }

    // ───────────── utilidades ESC/POS ─────────────

    private static function centro(): string { return self::ESC . 'a' . "\x01"; }
    private static function izquierda(): string { return self::ESC . 'a' . "\x00"; }
    private static function negrita(bool $on): string { return self::ESC . 'E' . ($on ? "\x01" : "\x00"); }
    private static function dobleAlto(bool $on): string { return self::ESC . '!' . ($on ? "\x10" : "\x00"); }
    private static function separador(int $cols): string { return str_repeat('-', $cols) . "\n"; }

    /** Texto envuelto a $cols columnas, una línea por salto. */
    private static function lineas(string $texto, int $cols): string
    {
        $texto = trim(preg_replace('/\s+/', ' ', $texto) ?? '');
        if ($texto === '') {
            return '';
        }
        return wordwrap($texto, $cols, "\n", true) . "\n";
    }

    /** "izquierda ........ derecha" en una sola línea (recorta la izquierda si hace falta). */
    private static function dosColumnas(string $izq, string $der, int $cols): string
    {
        $izq = self::ascii($izq);
        $max = $cols - strlen($der) - 1;
        if (strlen($izq) > $max) {
            $izq = substr($izq, 0, max(0, $max));
        }
        return $izq . str_repeat(' ', max(1, $cols - strlen($izq) - strlen($der))) . $der . "\n";
    }

    /** Quita acentos y deja solo ASCII imprimible (sin depender de iconv ni de la plataforma). */
    private static function ascii(string $s): string
    {
        $s = strtr($s, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
            '°' => 'o', '“' => '"', '”' => '"', '‘' => "'", '’' => "'", '–' => '-', '—' => '-', '…' => '...',
        ]);
        return preg_replace('/[^\x20-\x7E]/', '', $s) ?? '';
    }
}
