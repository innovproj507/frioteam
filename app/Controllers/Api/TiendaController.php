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
