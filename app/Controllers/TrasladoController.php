<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\AuditService;
use App\Libraries\Db;
use App\Libraries\Fila;
use App\Libraries\TrasladoService;

/**
 * Traslados entre depósitos: al enviar sale del origen (traslado_salida) y queda en
 * tránsito; al recibir entra al destino (traslado_entrada).
 * Permisos por ruta: traslados.ver / crear / aprobar (recibir).
 */
class TrasladoController extends BaseController
{
    public function index()
    {
        // Quien puede crear traslados entra directo a "Nuevo traslado"; el listado queda en ?lista=1
        // (tras enviar la mercancía, Cancelar, o al filtrar / paginar, que llevan parámetros).
        if (empty($this->request->getGet()) && \App\Libraries\Auth::can('traslados.crear')) {
            return redirect()->to('/inventario/traslados/nuevo');
        }

        $sucursalId = $this->sucursalId();
        $buscar     = trim((string) ($this->request->getGet('buscar') ?? ''));
        $porPagina  = in_array((int) $this->request->getGet('por_pagina'), [10, 25, 50, 100], true) ? (int) $this->request->getGet('por_pagina') : 25;
        $page       = max(1, (int) ($this->request->getGet('page') ?? 1));
        $offset     = ($page - 1) * $porPagina;

        $where  = '(do.sucursal_id = ? OR dd.sucursal_id = ?)';
        $params = [$sucursalId, $sucursalId];
        if ($buscar !== '') {
            $where .= " AND (CONCAT(ue.first_name, ' ', ue.last_name) LIKE ? OR ue.username LIKE ?)";
            array_push($params, "%{$buscar}%", "%{$buscar}%");
        }

        $from = "FROM traslados t
                 JOIN depositos do ON t.deposito_origen_id = do.deposito_id
                 JOIN depositos dd ON t.deposito_destino_id = dd.deposito_id
                 LEFT JOIN users ue ON t.usuario_envia_id = ue.id";

        $total = (int) Db::value("SELECT COUNT(*) {$from} WHERE {$where}", $params);

        return $this->render('traslados.index', [
            'page_title'    => 'Traslados entre Almacenes',
            'page_subtitle' => 'Mover mercancía de forma controlada',
            'traslados'     => Db::all(
                "SELECT t.*, do.nombre AS origen_nombre, dd.nombre AS destino_nombre,
                        CONCAT(ue.first_name, ' ', ue.last_name) AS usuario_envia_nombre
                 {$from} WHERE {$where}
                 ORDER BY t.traslado_id DESC
                 LIMIT {$porPagina} OFFSET {$offset}",
                $params
            ),
            'stats' => Db::row(
                "SELECT COUNT(*) AS total,
                        SUM(CASE WHEN t.estado = 'en_transito' THEN 1 ELSE 0 END) AS en_transito,
                        SUM(CASE WHEN t.estado = 'recibido' THEN 1 ELSE 0 END) AS recibidos,
                        SUM(CASE WHEN t.estado = 'cancelado' THEN 1 ELSE 0 END) AS cancelados
                 FROM traslados t
                 JOIN depositos do ON t.deposito_origen_id = do.deposito_id
                 JOIN depositos dd ON t.deposito_destino_id = dd.deposito_id
                 WHERE do.sucursal_id = ? OR dd.sucursal_id = ?",
                [$sucursalId, $sucursalId]
            ),
            'buscar'     => $buscar,
            'pagination' => paginacion($total, $page, $porPagina) + ['per_page' => $porPagina],
        ]);
    }

    public function crear(): string
    {
        return $this->render('traslados.crear', [
            'page_title'        => 'Nuevo Traslado',
            'page_subtitle'     => 'Selecciona origen y destino',
            'depositos_origen'  => Fila::lista(Db::all("SELECT * FROM depositos WHERE sucursal_id = ? AND estado = 'activo' ORDER BY nombre", [$this->sucursalId()])),
            // El destino puede ser de otra sucursal de la misma empresa
            'depositos_destino' => Fila::lista(Db::all(
                "SELECT d.* FROM depositos d JOIN branches b ON d.sucursal_id = b.sucursal_id
                 WHERE b.empresa_id = ? AND d.estado = 'activo' ORDER BY d.nombre",
                [$this->empresaId()]
            )),
        ]);
    }

    /** GET /inventario/traslados/{id}/modal — detalle del traslado (productos, cantidades, ubicaciones) como fragmento para la ventana. */
    public function modal(int $id)
    {
        $s = (int) $this->sucursalId();
        $t = Db::row(
            "SELECT t.*, do.nombre AS origen_nombre, dd.nombre AS destino_nombre,
                    TRIM(CONCAT(COALESCE(ue.first_name, ''), ' ', COALESCE(ue.last_name, ''))) AS envia_nombre, ue.username AS envia_usuario,
                    TRIM(CONCAT(COALESCE(ur.first_name, ''), ' ', COALESCE(ur.last_name, ''))) AS recibe_nombre, ur.username AS recibe_usuario
             FROM traslados t
             JOIN depositos do ON t.deposito_origen_id = do.deposito_id
             JOIN depositos dd ON t.deposito_destino_id = dd.deposito_id
             LEFT JOIN users ue ON t.usuario_envia_id = ue.id
             LEFT JOIN users ur ON t.usuario_recibe_id = ur.id
             WHERE t.traslado_id = ? AND (do.sucursal_id = ? OR dd.sucursal_id = ?)",
            [$id, $s, $s]
        );
        if (!$t) {
            return $this->response->setStatusCode(404)->setBody('Traslado no encontrado.');
        }
        $items = Db::all(
            "SELECT d.cantidad, d.cantidad_recibida, p.producto_id, p.codigo, p.nombre,
                    COALESCE(io.ubicacion, '') AS ubicacion_origen, COALESCE(id_.ubicacion, '') AS ubicacion_destino
             FROM traslados_detalle d
             JOIN productos p ON p.producto_id = d.producto_id
             LEFT JOIN inventario io  ON io.producto_id = d.producto_id AND io.deposito_id = ?
             LEFT JOIN inventario id_ ON id_.producto_id = d.producto_id AND id_.deposito_id = ?
             WHERE d.traslado_id = ? ORDER BY d.detalle_id",
            [$t['deposito_origen_id'], $t['deposito_destino_id'], $id]
        );
        return $this->response->setBody(view('traslados/modal', ['t' => $t, 'items' => $items]));
    }

    /** POST JSON {origen_id, destino_id, notas, items[{producto_id, cantidad}]} */
    public function guardar()
    {
        $data = $this->jsonInput();

        // La referencia se guarda al inicio de las notas; "completado" recibe el traslado en el destino en el mismo paso
        $referencia = trim((string) ($data['referencia'] ?? ''));
        $notas      = trim((string) ($data['notas'] ?? ''));
        if ($referencia !== '') {
            $notas = trim('Ref: ' . $referencia . ($notas !== '' ? ' — ' . $notas : ''));
        }
        $completado = !empty($data['completado']);
        $ctx        = ['empresa_id' => $this->empresaId(), 'sucursal_id' => $this->sucursalId()];

        try {
            Db::begin();
            $trasladoId = TrasladoService::enviar(
                (int) ($data['origen_id'] ?? 0),
                (int) ($data['destino_id'] ?? 0),
                $data['items'] ?? [],
                $notas,
                $ctx
            );
            if ($completado) {
                TrasladoService::recibir($trasladoId, $ctx);
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return $this->json(['error' => $e instanceof \RuntimeException ? $e->getMessage() : 'No se pudo registrar el traslado.'], 422);
        }

        AuditService::log('inventario.traslado', "Traslado #{$trasladoId} enviado" . ($completado ? ' y recibido (completado)' : ''), $trasladoId, 'traslado', [], $this->empresaId());
        return $this->json(['success' => true, 'traslado_id' => $trasladoId]);
    }

    /** POST — recibe en el destino lo que salió del origen. */
    public function recibir(int $id)
    {
        try {
            Db::begin();
            TrasladoService::recibir($id, ['empresa_id' => $this->empresaId(), 'sucursal_id' => $this->sucursalId()]);
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return $this->json(['error' => $e instanceof \RuntimeException ? $e->getMessage() : 'No se pudo recibir el traslado.'], 422);
        }

        AuditService::log('inventario.traslado', "Traslado #{$id} recibido", $id, 'traslado', [], $this->empresaId());
        return $this->json(['success' => true]);
    }
}
