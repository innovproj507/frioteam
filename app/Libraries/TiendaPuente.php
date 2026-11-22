<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * El POS se conecta a la tienda (que vive aparte y siempre está encendida) y:
 *   1. recoge los pedidos y las cuentas nuevas que los clientes dejaron mientras el POS estaba apagado,
 *   2. empuja las decisiones del administrador (precios de las cotizaciones aprobadas, cuentas aprobadas),
 *   3. empuja stock, y de vez en cuando categorías, catálogo (SIN precios), ranking y fotos.
 * Siempre es el POS quien llama: no hace falta IP fija ni abrir puertos en la oficina.
 */
class TiendaPuente
{
    /** @var string[] */
    public array $mensajes = [];

    private string $url;
    private string $token;

    public function __construct()
    {
        $this->url   = rtrim((string) env('TIENDA_PUENTE_URL'), '/');
        $this->token = (string) env('TIENDA_PUENTE_TOKEN');
        if ($this->url === '' || $this->token === '') {
            throw new \RuntimeException('Falta TIENDA_PUENTE_URL o TIENDA_PUENTE_TOKEN en el .env del POS.');
        }
    }

    /** @param bool $catalogo incluye categorías, productos modificados, ranking y fotos; $completo = todo el catálogo desde cero */
    public function ejecutar(bool $catalogo = false, bool $completo = false): void
    {
        $inicio = date('c');
        $pend   = $this->get('pendientes');

        // 1) Lo que los clientes dejaron en la tienda
        $conf = [];
        $ok = $fallo = 0;
        foreach ($pend['pedidos'] ?? [] as $p) {
            try {
                $r = TiendaApi::registrarPedido($p);
                $conf[$p['pedido_numero']] = ['cotizacion_id' => $r['cotizacion_id'], 'numero' => $r['numero']];
                $ok++;
            } catch (\Throwable $e) {
                $fallo++;
                $this->mensajes[] = "Pedido {$p['pedido_numero']}: " . $e->getMessage();
            }
        }
        $correos = [];
        foreach ($pend['solicitudes'] ?? [] as $s) {
            try {
                TiendaApi::registrarSolicitud($s);
                $correos[] = $s['email'];
            } catch (\Throwable $e) {
                $fallo++;
                $this->mensajes[] = "Solicitud {$s['email']}: " . $e->getMessage();
            }
        }
        if ($conf || $correos) {
            $this->post('confirmar', ['pedidos' => $conf, 'solicitudes' => $correos]);
        }
        $this->mensajes[] = "Recogidos: {$ok} pedidos, " . count($correos) . ' cuentas nuevas' . ($fallo ? " ({$fallo} con error)" : '') . '.';

        // 2) Decisiones del administrador
        $est = $this->post('estados', ['pedidos' => TiendaApi::pedidosRecientes(), 'solicitudes' => TiendaApi::solicitudesTodas()]);
        $this->mensajes[] = "Estados enviados: {$est['pedidos']} cotizaciones, {$est['solicitudes']} cuentas.";

        // 3) Stock (siempre) y catálogo (cuando se pide)
        $stock = TiendaApi::stock();
        foreach (array_chunk($stock, 2500) as $trozo) {
            $this->post('catalogo', ['stock' => $trozo]);
        }
        $this->mensajes[] = 'Stock: ' . count($stock) . ' productos.';

        if ($catalogo) {
            $this->catalogo($completo ? '' : (string) ($pend['ultimo_sync'] ?? ''), $inicio);
        }
    }

    private function catalogo(string $desde, string $inicio): void
    {
        $this->post('catalogo', ['categorias' => TiendaApi::categorias()]);
        $np = $ni = 0;
        $pagina = 1;
        do {
            $r = TiendaApi::catalogo($pagina, $desde, false);
            if ($r['productos']) {
                $this->post('catalogo', ['productos' => $r['productos']]);
                $np += count($r['productos']);
                $ni += $this->imagenes($r['productos']);
            }
            $pagina++;
        } while ($r['hay_mas']);
        $this->post('catalogo', ['ranking' => TiendaApi::ranking(), 'ultimo_sync' => $inicio]);
        $this->mensajes[] = "Catálogo: {$np} productos, {$ni} fotos nuevas.";
    }

    /** Sube las fotos que la tienda aún no tiene. */
    private function imagenes(array $productos): int
    {
        $archivos = [];
        foreach ($productos as $p) {
            if (!empty($p['imagen_principal'])) {
                $archivos[basename($p['imagen_principal'])] = $p['imagen_principal'];
            }
        }
        if (!$archivos) {
            return 0;
        }
        $faltan = $this->post('imagenes/faltan', ['archivos' => array_keys($archivos)])['faltan'] ?? [];
        $n = 0;
        foreach ($faltan as $nombre) {
            $ruta = FCPATH . 'assets/uploads/' . ltrim((string) $archivos[$nombre], '/');
            if (!is_file($ruta)) {
                continue;
            }
            $this->llamar('POST', 'imagenes', null, ['archivo' => new \CURLFile($ruta), 'nombre' => $nombre]);
            $n++;
        }
        return $n;
    }

    private function get(string $ruta): array
    {
        return $this->llamar('GET', $ruta);
    }

    private function post(string $ruta, array $json): array
    {
        return $this->llamar('POST', $ruta, $json);
    }

    private function llamar(string $metodo, string $ruta, ?array $json = null, ?array $formulario = null): array
    {
        $ch  = curl_init($this->url . '/' . $ruta);
        $cab = ['Authorization: Bearer ' . $this->token, 'Accept: application/json'];
        $opt = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 180, CURLOPT_CUSTOMREQUEST => $metodo, CURLOPT_SSL_VERIFYPEER => true];
        if ($json !== null) {
            $cab[] = 'Content-Type: application/json';
            $opt[CURLOPT_POSTFIELDS] = json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        } elseif ($formulario !== null) {
            $opt[CURLOPT_POSTFIELDS] = $formulario;
        }
        $opt[CURLOPT_HTTPHEADER] = $cab;
        curl_setopt_array($ch, $opt);
        $cuerpo = curl_exec($ch);
        $http   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($cuerpo === false) {
            throw new \RuntimeException("La tienda no responde: {$err}");
        }
        $data = json_decode((string) $cuerpo, true);
        if ($http >= 400 || !is_array($data) || ($data['status'] ?? '') !== 'success') {
            throw new \RuntimeException("La tienda respondió {$http}: " . ($data['message'] ?? substr((string) $cuerpo, 0, 120)));
        }
        return $data['data'] ?? [];
    }
}
