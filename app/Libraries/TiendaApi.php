<?php

declare(strict_types=1);

namespace App\Libraries;

/** Error de la lógica de la tienda con su código HTTP (422 datos inválidos, 404 no existe, 500 falla interna). */
class TiendaApiError extends \RuntimeException
{
}

/**
 * Lógica de negocio de la tienda web, compartida por la API (/api/tienda, la tienda llama al POS)
 * y por el comando tienda:puente (el POS se conecta a la tienda). Aquí no hay nada de HTTP.
 */
class TiendaApi
{
    public const POR_PAGINA = 500;

    public static function empresa(): int  { return (int) env('TIENDA_EMPRESA_ID', 1); }
    public static function sucursal(): int { return (int) env('TIENDA_SUCURSAL_ID', 1); }

    public static function categorias(): array
    {
        return Db::all(
            'SELECT categoria_id, nombre, padre_id, nivel, imagen FROM categorias_productos WHERE empresa_id = ? ORDER BY nivel, nombre',
            [self::empresa()]
        );
    }

    /**
     * Una página del catálogo. $conPrecios=false => sin precios (lo que se manda a la tienda independiente).
     * @return array{productos:array, pagina:int, hay_mas:bool}
     */
    public static function catalogo(int $pagina, string $desde = '', bool $conPrecios = true): array
    {
        $s      = self::sucursal();
        $pagina = max(1, $pagina);
        $off    = ($pagina - 1) * self::POR_PAGINA;
        $where  = 'p.empresa_id = ?';
        $params = $conPrecios ? [$s, $s, $s, $s] : [$s];
        $params[] = self::empresa();
        if ($desde !== '' && strtotime($desde)) {
            $where   .= ' AND p.fecha_modificacion >= ?';
            $params[] = date('Y-m-d H:i:s', strtotime($desde));
        }
        $precios = $conPrecios
            ? "(SELECT pp.precio FROM precios_productos pp WHERE pp.producto_id = p.producto_id AND pp.tipo_precio = 'a' AND pp.sucursal_id = ? LIMIT 1) AS precio_a,
               (SELECT pp.precio FROM precios_productos pp WHERE pp.producto_id = p.producto_id AND pp.tipo_precio = 'b' AND pp.sucursal_id = ? LIMIT 1) AS precio_b,
               (SELECT pp.precio FROM precios_productos pp WHERE pp.producto_id = p.producto_id AND pp.tipo_precio = 'c' AND pp.sucursal_id = ? LIMIT 1) AS precio_c,"
            : '';

        $filas = Db::all(
            "SELECT p.producto_id, p.codigo, p.nombre, p.descripcion, p.marca, p.categoria_id, p.unidad_medida,
                    p.codigo_barras, p.itbms, p.estado, p.imagen_principal, p.fecha_modificacion,
                    {$precios}
                    COALESCE((SELECT SUM(i.existencia) FROM inventario i JOIN depositos d ON d.deposito_id = i.deposito_id
                               WHERE i.producto_id = p.producto_id AND d.sucursal_id = ?), 0) AS stock
             FROM productos p
             WHERE {$where}
             ORDER BY p.producto_id
             LIMIT " . self::POR_PAGINA . " OFFSET {$off}",
            $params
        );
        foreach ($filas as &$f) {
            $f['stock'] = (float) $f['stock'];
        }
        unset($f);

        return ['productos' => $filas, 'pagina' => $pagina, 'hay_mas' => count($filas) === self::POR_PAGINA];
    }

    /** Lo que más se vende (últimos 12 meses). Top 300. */
    public static function ranking(): array
    {
        return Db::all(
            'SELECT vd.producto_id, ROUND(SUM(vd.cantidad - vd.cantidad_devuelta), 2) AS vendidos, COUNT(DISTINCT vd.venta_id) AS ventas
             FROM ventas_detalle vd JOIN ventas v ON v.venta_id = vd.venta_id
             WHERE v.empresa_id = ? AND v.sucursal_id = ? AND v.fecha >= DATE_SUB(NOW(), INTERVAL 365 DAY)
             GROUP BY vd.producto_id HAVING vendidos > 0 ORDER BY vendidos DESC LIMIT 300',
            [self::empresa(), self::sucursal()]
        );
    }

    public static function stock(): array
    {
        $filas = Db::all(
            'SELECT i.producto_id, SUM(i.existencia) AS stock
             FROM inventario i JOIN depositos d ON d.deposito_id = i.deposito_id
             WHERE d.sucursal_id = ? GROUP BY i.producto_id',
            [self::sucursal()]
        );
        foreach ($filas as &$f) {
            $f['stock'] = (float) $f['stock'];
        }
        return $filas;
    }

    /**
     * Registra un pedido web como cotización pendiente. Idempotente por número de pedido.
     * @return array{cotizacion_id:int, numero:string, duplicado?:bool, total?:float}
     */
    public static function registrarPedido(array $in): array
    {
        $num   = trim((string) ($in['pedido_numero'] ?? ''));
        $items = $in['items'] ?? [];
        if ($num === '' || empty($items)) {
            throw new TiendaApiError('Pedido sin número o sin productos.', 422);
        }

        $marca = "[WEB {$num}]";
        $ya    = Db::row('SELECT cotizacion_id, numero FROM cotizaciones WHERE empresa_id = ? AND notas LIKE ? LIMIT 1', [self::empresa(), $marca . '%']);
        if ($ya) {
            return ['cotizacion_id' => (int) $ya['cotizacion_id'], 'numero' => $ya['numero'], 'duplicado' => true];
        }

        try {
            Db::begin();
            $cli       = $in['cliente'] ?? [];
            $clienteId = self::clienteDe($cli);
            // El cliente web no manda precios: el POS propone el de su tipo (A/B/C) y el administrador lo confirma
            $tipo = (string) Db::value('SELECT tipo_precio FROM tienda_solicitudes WHERE empresa_id = ? AND email = ?', [self::empresa(), strtolower(trim((string) ($cli['email'] ?? '')))]);
            $tipo = in_array($tipo, ['a', 'b', 'c'], true) ? $tipo : 'c';
            foreach ($items as &$it) {
                $it['precio'] = (float) Db::value(
                    'SELECT precio FROM precios_productos WHERE producto_id = ? AND tipo_precio = ? AND sucursal_id = ? LIMIT 1',
                    [(int) $it['producto_id'], $tipo, self::sucursal()]
                );
            }
            unset($it);
            $notas = $marca . ' ' . trim(
                ($cli['nombre'] ?? '') . ' · ' . ($cli['telefono'] ?? '') . ' · ' . ($cli['email'] ?? '')
                . (!empty($in['entrega']) ? ' · Entrega: ' . $in['entrega'] : '')
                . (!empty($in['notas']) ? ' · ' . $in['notas'] : '')
            );
            $res = CotizacionService::crear(
                ['items' => $items, 'cliente_id' => $clienteId, 'notas' => $notas, 'estado' => 'pendiente'],
                ['empresa_id' => self::empresa(), 'sucursal_id' => self::sucursal()]
            );
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            log_message('error', 'Pedido de la tienda {n}: {m}', ['n' => $num, 'm' => $e->getMessage()]);
            throw new TiendaApiError('No se pudo registrar el pedido en el POS.', 500);
        }

        return ['cotizacion_id' => $res['cotizacion_id'], 'numero' => $res['numero'], 'total' => $res['total']];
    }

    /**
     * Estado y precios de cotizaciones web por número. Los precios solo salen cuando el administrador
     * ya revisó (aprobada/convertida); mientras esté pendiente NO se devuelven.
     * @param string[] $numeros
     */
    public static function pedidosEstado(array $numeros): array
    {
        $salida = [];
        foreach (array_slice(array_values(array_filter(array_map('trim', $numeros))), 0, 300) as $num) {
            $c = Db::row('SELECT * FROM cotizaciones WHERE empresa_id = ? AND notas LIKE ? LIMIT 1', [self::empresa(), "[WEB {$num}]%"]);
            if ($c) {
                $salida[$num] = self::estadoCotizacion($c);
            }
        }
        return $salida;
    }

    /** Cotizaciones web de los últimos días (para empujarlas a la tienda): número => estado/precios. */
    public static function pedidosRecientes(int $dias = 120): array
    {
        $filas = Db::all(
            "SELECT * FROM cotizaciones WHERE empresa_id = ? AND notas LIKE '[WEB %' AND fecha >= DATE_SUB(NOW(), INTERVAL {$dias} DAY)",
            [self::empresa()]
        );
        $salida = [];
        foreach ($filas as $c) {
            if (preg_match('/^\[WEB ([^\]]+)\]/', (string) $c['notas'], $m)) {
                $salida[$m[1]] = self::estadoCotizacion($c);
            }
        }
        return $salida;
    }

    private static function estadoCotizacion(array $c): array
    {
        $fila = ['estado' => $c['estado'], 'cotizacion' => $c['numero']];
        if (in_array($c['estado'], ['aprobada', 'convertida'], true)) {
            $fila['subtotal'] = (float) $c['subtotal'];
            $fila['itbms']    = (float) $c['itbms'];
            $fila['total']    = (float) $c['total'];
            $fila['items']    = array_map(fn ($d) => [
                'producto_id' => (int) $d['producto_id'], 'cantidad' => (float) $d['cantidad'],
                'precio' => (float) $d['precio'], 'total_linea' => (float) $d['total_linea'],
            ], Db::all('SELECT producto_id, cantidad, precio, total_linea FROM cotizaciones_detalle WHERE cotizacion_id = ?', [$c['cotizacion_id']]));
        }
        return $fila;
    }

    /** Registra una solicitud de acceso (queda pendiente hasta que se apruebe). Idempotente por correo. */
    public static function registrarSolicitud(array $in): array
    {
        $email = strtolower(trim((string) ($in['email'] ?? '')));
        if ($email === '' || trim((string) ($in['nombre'] ?? '')) === '') {
            throw new TiendaApiError('Falta el nombre o el correo.', 422);
        }
        $fila = Db::row('SELECT * FROM tienda_solicitudes WHERE empresa_id = ? AND email = ?', [self::empresa(), $email]);
        if (!$fila) {
            Db::insert('tienda_solicitudes', [
                'empresa_id' => self::empresa(),
                'email'      => $email,
                'nombre'     => mb_substr(trim((string) $in['nombre']), 0, 200),
                'telefono'   => mb_substr(trim((string) ($in['telefono'] ?? '')), 0, 50),
                'ruc'        => mb_substr(trim((string) ($in['ruc'] ?? '')), 0, 50),
                'direccion'  => trim((string) ($in['direccion'] ?? '')),
            ]);
            $fila = Db::row('SELECT * FROM tienda_solicitudes WHERE empresa_id = ? AND email = ?', [self::empresa(), $email]);
        }
        return self::estadoSolicitud($fila);
    }

    public static function solicitudEstado(string $email): ?array
    {
        $fila = Db::row('SELECT * FROM tienda_solicitudes WHERE empresa_id = ? AND email = ?', [self::empresa(), strtolower(trim($email))]);
        return $fila ? self::estadoSolicitud($fila) : null;
    }

    /** Estado de todas las solicitudes: correo => estado (lo que se empuja a la tienda). */
    public static function solicitudesTodas(): array
    {
        $salida = [];
        foreach (Db::all('SELECT * FROM tienda_solicitudes WHERE empresa_id = ?', [self::empresa()]) as $f) {
            $salida[$f['email']] = self::estadoSolicitud($f);
        }
        return $salida;
    }

    private static function estadoSolicitud(array $f): array
    {
        $exento = $f['cliente_id'] ? VentaService::clienteExento((int) $f['cliente_id']) : false;
        return ['estado' => $f['estado'], 'tipo_precio' => $f['tipo_precio'], 'motivo' => $f['motivo'], 'cliente_id' => $f['cliente_id'] ? (int) $f['cliente_id'] : null, 'exento' => $exento];
    }

    /** Cliente del POS que coincide por correo (o RUC); si no existe lo crea. */
    private static function clienteDe(array $c): ?int
    {
        $email  = trim((string) ($c['email'] ?? ''));
        $ruc    = trim((string) ($c['ruc'] ?? ''));
        $nombre = trim((string) ($c['nombre'] ?? ''));
        if ($nombre === '' && $email === '') {
            return null;
        }
        $id = null;
        if ($email !== '') {
            $id = Db::value('SELECT cliente_id FROM clientes WHERE empresa_id = ? AND email = ? LIMIT 1', [self::empresa(), $email]);
        }
        if (!$id && $ruc !== '') {
            $id = Db::value('SELECT cliente_id FROM clientes WHERE empresa_id = ? AND ruc = ? LIMIT 1', [self::empresa(), $ruc]);
        }
        if ($id) {
            return (int) $id;
        }
        return Db::insert('clientes', [
            'empresa_id' => self::empresa(),
            'codigo'     => 'WEB-' . substr(md5($email . $ruc . $nombre), 0, 8),
            'nombre'     => $nombre !== '' ? $nombre : $email,
            'tipo'       => 'natural',
            'ruc'        => $ruc,
            'email'      => $email,
            'telefono'   => (string) ($c['telefono'] ?? ''),
            'direccion'  => (string) ($c['direccion'] ?? ''),
            'estado'     => 'activo',
        ]);
    }
}
