<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Impresión directa desde el servidor con CUPS (`lp`), igual que el sistema anterior:
 * se genera el PDF de la factura y se manda a la cola de la Ricoh, sin navegador ni diálogos.
 *
 * Se configura en .env:
 *   impresora.cups  = RICOH-IM430   (cola CUPS; vacío = impresión por CUPS desactivada)
 *   impresora.papel = Letter        (Letter | A4)
 *   impresora.auto  = true          (imprime sola al cobrar en el POS)
 *   impresora.pos   = cups          (el botón IMPRIMIR del POS usa CUPS)
 */
final class ImpresionCups
{
    public static function cola(): string
    {
        return trim((string) env('impresora.cups', ''));
    }

    public static function configurada(): bool
    {
        return self::cola() !== '';
    }

    public static function automatica(): bool
    {
        return filter_var(env('impresora.auto', false), FILTER_VALIDATE_BOOLEAN);
    }

    /** 'letter' | 'a4' */
    public static function papel(): string
    {
        return strtolower(trim((string) env('impresora.papel', 'letter'))) === 'a4' ? 'a4' : 'letter';
    }

    /** Genera la factura de la venta en PDF y la imprime. Devuelve el id del trabajo CUPS. */
    public static function imprimirFactura(int $ventaId, int $sucursalId): string
    {
        $datos = FacturaVenta::datos($ventaId, $sucursalId);
        if (!$datos) {
            throw new \RuntimeException('Venta no encontrada.');
        }
        $papel = self::papel();
        $pdf   = FacturaVenta::pdf($datos, $papel);

        return self::imprimirPdf($pdf, 'Factura ' . $datos['venta']['numero_factura'], self::cola(), $papel === 'a4' ? 'A4' : 'Letter');
    }

    /**
     * Manda un PDF a una cola CUPS con `lp`. Lanza \RuntimeException con un mensaje claro si falla.
     * @param list<string> $extra opciones -o adicionales (p. ej. "job-hold-until=indefinite" para pruebas)
     */
    public static function imprimirPdf(string $pdf, string $titulo, string $cola, string $pageSize = 'Letter', array $extra = []): string
    {
        if ($cola === '') {
            throw new \RuntimeException('No hay impresora CUPS configurada (impresora.cups en .env).');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'fact_');
        if ($tmp === false) {
            throw new \RuntimeException('No se pudo crear el archivo temporal para imprimir.');
        }
        $archivo = $tmp . '.pdf';
        rename($tmp, $archivo);
        file_put_contents($archivo, $pdf);

        $titulo = substr((string) preg_replace('/[^\w .\-]/u', '', $titulo), 0, 60) ?: 'Factura';
        $cmd = ['lp', '-d', $cola, '-t', $titulo, '-o', 'PageSize=' . $pageSize, '-o', 'sides=one-sided'];
        foreach ($extra as $opcion) {
            $cmd[] = '-o';
            $cmd[] = $opcion;
        }
        $cmd[] = $archivo;

        try {
            // Arreglo en vez de cadena: no pasa por el shell, así ningún dato puede inyectar comandos
            $proc = @proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            if (!is_resource($proc)) {
                throw new \RuntimeException('No se pudo ejecutar "lp": CUPS no está disponible en este servidor.');
            }
            $salida = trim((string) stream_get_contents($pipes[1]));
            $error  = trim((string) stream_get_contents($pipes[2]));
            fclose($pipes[1]);
            fclose($pipes[2]);
            $codigo = proc_close($proc);
        } finally {
            @unlink($archivo);
        }

        if ($codigo !== 0) {
            throw new \RuntimeException('CUPS rechazó el trabajo: ' . ($error !== '' ? $error : "código {$codigo}"));
        }
        // "request id is RICOH-IM430-12 (1 file(s))"
        return preg_match('/request id is (\S+)/', $salida, $m) ? $m[1] : $salida;
    }
}
