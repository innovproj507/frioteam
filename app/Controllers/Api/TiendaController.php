<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Libraries\CotizacionService;
use App\Libraries\Db;
use CodeIgniter\Controller;

/**
 * API para la tienda en línea (solo lectura de catálogo + recepción de pedidos).
 *   GET  /api/tienda/catalogo?desde=ISO&pagina=1   productos con precios A/B/C y stock, paginado
 *   GET  /api/tienda/categorias                     categorías
 *   GET  /api/tienda/stock?desde=ISO                solo existencias cambiadas (sincronización rápida)
 *   POST /api/tienda/pedidos                        un pedido web → cotización pendiente en el POS
 * La tienda nunca toca la base del POS: todo pasa por aquí.
 */
class TiendaController extends Controller
{
    private const POR_PAGINA = 500;

    private function empresa(): int  { return (int) env('TIENDA_EMPRESA_ID', 1); }
    private function sucursal(): int { return (int) env('TIENDA_SUCURSAL_ID', 1); }

    public function categorias()
    {
        return $this->response->setJSON(['status' => 'success', 'data' => Db::all(
            'SELECT categoria_id, nombre, padre_id, nivel, imagen FROM categorias_productos WHERE empresa_id = ? ORDER BY nivel, nombre',
            [$this->empresa()]
        )]);
    }

    public function catalogo()
    {
        $s      = $this->sucursal();
        $pagina = max(1, (int) ($this->request->getGet('pagina') ?? 1));
        $desde  = trim((string) ($this->request->getGet('desde') ?? ''));
        $off    = ($pagina - 1) * self::POR_PAGINA;

        $where  = 'p.empresa_id = ?';
        $params = [$s, $s, $this->empresa()];
        if ($desde !== '' && strtotime($desde)) {
            $where   .= ' AND p.fecha_modificacion >= ?';
            $params[] = date('Y-m-d H:i:s', strtotime($desde));
        }

        $filas = Db::all(
            "SELECT p.producto_id, p.codigo, p.nombre, p.descripcion, p.marca, p.categoria_id, p.unidad_medida,
                    p.codigo_barras, p.itbms, p.estado, p.imagen_principal, p.fecha_modificacion,
                    (SELECT pp.precio FROM precios_productos pp WHERE pp.producto_id = p.producto_id AND pp.tipo_precio = 'a' AND pp.sucursal_id = ? LIMIT 1) AS precio_a,
                    (SELECT pp.precio FROM precios_productos pp WHERE pp.producto_id = p.producto_id AND pp.tipo_precio = 'b' AND pp.sucursal_id = {$s} LIMIT 1) AS precio_b,
                    (SELECT pp.precio FROM precios_productos pp WHERE pp.producto_id = p.producto_id AND pp.tipo_precio = 'c' AND pp.sucursal_id = {$s} LIMIT 1) AS precio_c,
                    COALESCE((SELECT SUM(i.existencia) FROM inventario i JOIN depositos d ON d.deposito_id = i.deposito_id
                               WHERE i.producto_id = p.producto_id AND d.sucursal_id = ?), 0) AS stock
             FROM productos p
             WHERE {$where}
             ORDER BY p.producto_id
             LIMIT " . self::POR_PAGINA . " OFFSET {$off}",
            $params
        );

        $base = rtrim((string) env('TIENDA_URL_IMAGENES', ''), '/');
        foreach ($filas as &$f) {
            $f['imagen_url'] = ($f['imagen_principal'] && $base !== '') ? $base . '/' . ltrim($f['imagen_principal'], '/') : null;
            $f['stock']      = (float) $f['stock'];
        }
        unset($f);

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => ['productos' => $filas, 'pagina' => $pagina, 'hay_mas' => count($filas) === self::POR_PAGINA, 'generado' => date('c')],
        ]);
    }

    /** Lo que más se vende (últimos 12 meses): unidades y cantidad de ventas por producto. Top 300. */
    public function ranking()
    {
        $filas = Db::all(
            'SELECT vd.producto_id, ROUND(SUM(vd.cantidad - vd.cantidad_devuelta), 2) AS vendidos, COUNT(DISTINCT vd.venta_id) AS ventas
             FROM ventas_detalle vd JOIN ventas v ON v.venta_id = vd.venta_id
             WHERE v.empresa_id = ? AND v.sucursal_id = ? AND v.fecha >= DATE_SUB(NOW(), INTERVAL 365 DAY)
             GROUP BY vd.producto_id HAVING vendidos > 0 ORDER BY vendidos DESC LIMIT 300',
            [$this->empresa(), $this->sucursal()]
        );
        return $this->response->setJSON(['status' => 'success', 'data' => $filas]);
    }

    /** Existencias por producto (liviano, para sincronizar stock cada pocos minutos). */
    public function stock()
    {
        $filas = Db::all(
            'SELECT i.producto_id, SUM(i.existencia) AS stock
             FROM inventario i JOIN depositos d ON d.deposito_id = i.deposito_id
             WHERE d.sucursal_id = ? GROUP BY i.producto_id',
            [$this->sucursal()]
        );
        foreach ($filas as &$f) {
            $f['stock'] = (float) $f['stock'];
        }
        return $this->response->setJSON(['status' => 'success', 'data' => ['stock' => $filas, 'generado' => date('c')]]);
    }

    /**
     * Recibe un pedido web. Cuerpo JSON:
     * { pedido_numero, cliente:{nombre,email,telefono,ruc,direccion}, entrega, notas, items:[{producto_id,cantidad,precio}] }
     * Es idempotente: si ya existe una cotización con "pedido_numero" en las notas devuelve la misma.
     */
    public function pedido()
    {
        $in    = $this->request->getJSON(true) ?: [];
        $num   = trim((string) ($in['pedido_numero'] ?? ''));
        $items = $in['items'] ?? [];
        if ($num === '' || empty($items)) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 'error', 'message' => 'Pedido sin número o sin productos.']);
        }

        $marca = "[WEB {$num}]";
        $ya    = Db::row('SELECT cotizacion_id, numero FROM cotizaciones WHERE empresa_id = ? AND notas LIKE ? LIMIT 1', [$this->empresa(), $marca . '%']);
        if ($ya) {
            return $this->response->setJSON(['status' => 'success', 'data' => ['cotizacion_id' => (int) $ya['cotizacion_id'], 'numero' => $ya['numero'], 'duplicado' => true]]);
        }

        try {
            Db::begin();
            $cli       = $in['cliente'] ?? [];
            $clienteId = $this->clienteDe($cli);
            // El cliente web no manda precios: el POS propone el de su tipo (A/B/C) y el administrador lo confirma
            $tipo = (string) Db::value('SELECT tipo_precio FROM tienda_solicitudes WHERE empresa_id = ? AND email = ?', [$this->empresa(), strtolower(trim((string) ($cli['email'] ?? '')))]);
            $tipo = in_array($tipo, ['a', 'b', 'c'], true) ? $tipo : 'c';
            foreach ($items as &$it) {
                $it['precio'] = (float) Db::value(
                    'SELECT precio FROM precios_productos WHERE producto_id = ? AND tipo_precio = ? AND sucursal_id = ? LIMIT 1',
                    [(int) $it['producto_id'], $tipo, $this->sucursal()]
                );
            }
            unset($it);
            $notas     = $marca . ' ' . trim(
                ($cli['nombre'] ?? '') . ' · ' . ($cli['telefono'] ?? '') . ' · ' . ($cli['email'] ?? '')
                . (!empty($in['entrega']) ? ' · Entrega: ' . $in['entrega'] : '')
                . (!empty($in['notas']) ? ' · ' . $in['notas'] : '')
            );
            $res = CotizacionService::crear(
                ['items' => $items, 'cliente_id' => $clienteId, 'notas' => $notas, 'estado' => 'pendiente'],
                ['empresa_id' => $this->empresa(), 'sucursal_id' => $this->sucursal()]
            );
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            log_message('error', 'Pedido de la tienda {n}: {m}', ['n' => $num, 'm' => $e->getMessage()]);
            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'No se pudo registrar el pedido en el POS.']);
        }

        return $this->response->setStatusCode(201)->setJSON(['status' => 'success', 'data' => ['cotizacion_id' => $res['cotizacion_id'], 'numero' => $res['numero'], 'total' => $res['total']]]);
    }

    /**
     * Estado y precios de varias cotizaciones web. GET ?numeros=WEB-1,WEB-2
     * Devuelve, por número: estado de la cotización en el POS y, si el administrador ya la revisó (aprobada/convertida),
     * los precios finales por línea y los totales. Mientras esté pendiente NO se devuelven precios.
     */
    public function pedidosEstado()
    {
        $numeros = array_filter(array_map('trim', explode(',', (string) $this->request->getGet('numeros'))));
        $salida  = [];
        foreach (array_slice($numeros, 0, 100) as $num) {
            $c = Db::row('SELECT * FROM cotizaciones WHERE empresa_id = ? AND notas LIKE ? LIMIT 1', [$this->empresa(), "[WEB {$num}]%"]);
            if (!$c) {
                continue;
            }
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
            $salida[$num] = $fila;
        }
        return $this->response->setJSON(['status' => 'success', 'data' => $salida]);
    }

    /** La tienda avisa de una cuenta nueva: queda "pendiente" hasta que el POS la apruebe. Idempotente por correo. */
    public function solicitud()
    {
        $in    = $this->request->getJSON(true) ?: [];
        $email = strtolower(trim((string) ($in['email'] ?? '')));
        if ($email === '' || trim((string) ($in['nombre'] ?? '')) === '') {
            return $this->response->setStatusCode(422)->setJSON(['status' => 'error', 'message' => 'Falta el nombre o el correo.']);
        }
        $fila = Db::row('SELECT * FROM tienda_solicitudes WHERE empresa_id = ? AND email = ?', [$this->empresa(), $email]);
        if (!$fila) {
            Db::insert('tienda_solicitudes', [
                'empresa_id' => $this->empresa(),
                'email'      => $email,
                'nombre'     => mb_substr(trim((string) $in['nombre']), 0, 200),
                'telefono'   => mb_substr(trim((string) ($in['telefono'] ?? '')), 0, 50),
                'ruc'        => mb_substr(trim((string) ($in['ruc'] ?? '')), 0, 50),
                'direccion'  => trim((string) ($in['direccion'] ?? '')),
            ]);
            $fila = Db::row('SELECT * FROM tienda_solicitudes WHERE empresa_id = ? AND email = ?', [$this->empresa(), $email]);
        }
        return $this->response->setJSON(['status' => 'success', 'data' => $this->estadoDe($fila)]);
    }

    /** Estado de una solicitud (la tienda lo consulta al iniciar sesión). GET ?email= */
    public function solicitudEstado()
    {
        $email = strtolower(trim((string) $this->request->getGet('email')));
        $fila  = Db::row('SELECT * FROM tienda_solicitudes WHERE empresa_id = ? AND email = ?', [$this->empresa(), $email]);
        if (!$fila) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Sin solicitud.']);
        }
        return $this->response->setJSON(['status' => 'success', 'data' => $this->estadoDe($fila)]);
    }

    private function estadoDe(array $f): array
    {
        $exento = $f['cliente_id'] ? \App\Libraries\VentaService::clienteExento((int) $f['cliente_id']) : false;
        return ['estado' => $f['estado'], 'tipo_precio' => $f['tipo_precio'], 'motivo' => $f['motivo'], 'cliente_id' => $f['cliente_id'] ? (int) $f['cliente_id'] : null, 'exento' => $exento];
    }

    /** Cliente del POS que coincide por correo (o RUC); si no existe lo crea. */
    private function clienteDe(array $c): ?int
    {
        $email = trim((string) ($c['email'] ?? ''));
        $ruc   = trim((string) ($c['ruc'] ?? ''));
        $nombre = trim((string) ($c['nombre'] ?? ''));
        if ($nombre === '' && $email === '') {
            return null;
        }
        $id = null;
        if ($email !== '') {
            $id = Db::value("SELECT cliente_id FROM clientes WHERE empresa_id = ? AND email = ? LIMIT 1", [$this->empresa(), $email]);
        }
        if (!$id && $ruc !== '') {
            $id = Db::value("SELECT cliente_id FROM clientes WHERE empresa_id = ? AND ruc = ? LIMIT 1", [$this->empresa(), $ruc]);
        }
        if ($id) {
            return (int) $id;
        }
        return Db::insert('clientes', [
            'empresa_id' => $this->empresa(),
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
