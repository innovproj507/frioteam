<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\AuditService;
use App\Libraries\Auth;
use App\Libraries\Db;
use App\Libraries\EntregaVenta;
use App\Libraries\ImpresionCups;

/**
 * Entregas (despachos) de ventas: "Agregar entrega" del sistema anterior.
 */
class EntregaController extends BaseController
{
    private const POR_PAGINA = 25;

    /** GET /entregas — listado con búsqueda por número, venta o cliente. */
    public function index(): string
    {
        $page   = max(1, (int) ($this->request->getGet('page') ?? 1));
        $buscar = trim((string) ($this->request->getGet('buscar') ?? ''));

        $where  = 'e.sucursal_id = ?';
        $params = [$this->sucursalId()];
        if ($buscar !== '') {
            $where   .= ' AND (e.numero LIKE ? OR e.cliente LIKE ? OR v.numero_factura LIKE ?)';
            $params[] = "%{$buscar}%";
            $params[] = "%{$buscar}%";
            $params[] = "%{$buscar}%";
        }

        $total   = (int) Db::value("SELECT COUNT(*) FROM entregas e JOIN ventas v ON v.venta_id = e.venta_id WHERE {$where}", $params);
        $offset  = ($page - 1) * self::POR_PAGINA;
        $entregas = Db::all(
            "SELECT e.entrega_id, e.numero, e.fecha, e.cliente, e.venta_id, v.numero_factura
             FROM entregas e JOIN ventas v ON v.venta_id = e.venta_id
             WHERE {$where} ORDER BY e.entrega_id DESC LIMIT " . self::POR_PAGINA . " OFFSET {$offset}",
            $params
        );

        return $this->render('ventas.entregas_lista', [
            'page_title'    => 'Envíos',
            'page_subtitle' => 'Todas las entregas (despachos) de ventas',
            'entregas'      => $entregas,
            'buscar'        => $buscar,
            'pagination'    => paginacion($total, $page, self::POR_PAGINA),
            'cups'          => ImpresionCups::configurada(),
        ]);
    }

    /** GET /ventas/venta/{id}/entrega/datos — valores por defecto del formulario "Agregar entrega". */
    public function datos(int $ventaId)
    {
        $s = EntregaVenta::sugerencia($ventaId, (int) $this->empresaId(), (int) $this->sucursalId());
        return $s ? $this->json($s) : $this->json(['error' => 'Venta no encontrada.'], 404);
    }

    /** GET /ventas/venta/{id}/entregas — entregas ya hechas de la venta. */
    public function porVenta(int $ventaId)
    {
        return $this->json(['entregas' => EntregaVenta::porVenta($ventaId, (int) $this->sucursalId()), 'cups' => ImpresionCups::configurada()]);
    }

    /** POST /ventas/venta/{id}/entrega — crea la entrega. */
    public function guardar(int $ventaId)
    {
        $data = $this->jsonInput();
        try {
            $id = EntregaVenta::crear($ventaId, (int) $this->empresaId(), (int) $this->sucursalId(), Auth::id() ? (int) Auth::id() : null, $data);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            log_message('error', 'Entrega de la venta {id}: {m}', ['id' => $ventaId, 'm' => $e->getMessage()]);
            return $this->json(['error' => 'No se pudo guardar la entrega.'], 500);
        }

        $numero = (string) Db::value("SELECT numero FROM entregas WHERE entrega_id = ?", [$id]);
        AuditService::log('ventas.entrega', "Entrega {$numero} creada para la venta #{$ventaId}", $ventaId, 'venta', ['entrega_id' => $id], $this->empresaId());

        return $this->json(['ok' => true, 'entrega_id' => $id, 'numero' => $numero, 'cups' => ImpresionCups::configurada()]);
    }

    /** GET /entregas/{id} — vista para ver o imprimir desde el navegador. */
    public function ver(int $id)
    {
        $datos = EntregaVenta::datos($id, (int) $this->sucursalId(), $this->esBodega());
        if (!$datos) {
            $this->error('Entrega no encontrada.');
            return redirect()->to('/entregas');
        }
        return view('ventas/entrega', $datos + ['modo' => 'web', 'papel' => ImpresionCups::papel()]);
    }

    /** GET /entregas/{id}/modal — detalle de la entrega como fragmento HTML para la ventana (modal). */
    public function modal(int $id)
    {
        $datos = EntregaVenta::datos($id, (int) $this->sucursalId(), $this->esBodega());
        if (!$datos) {
            return $this->response->setStatusCode(404)->setBody('Entrega no encontrada.');
        }
        return $this->response->setBody(view('ventas/entrega_modal', $datos + ['cups' => ImpresionCups::configurada()]));
    }

    /** GET /entregas/{id}/pdf */
    public function pdf(int $id)
    {
        $datos = EntregaVenta::datos($id, (int) $this->sucursalId(), $this->esBodega());
        if (!$datos) {
            $this->error('Entrega no encontrada.');
            return redirect()->to('/entregas');
        }
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . EntregaVenta::nombreArchivo($datos) . '"')
            ->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->setBody(EntregaVenta::pdf($datos, ImpresionCups::papel()));
    }

    /** POST /entregas/{id}/imprimir — imprime directo en la Ricoh por CUPS. */
    public function imprimir(int $id)
    {
        if (!ImpresionCups::configurada()) {
            return $this->json(['error' => 'No hay impresora CUPS configurada.'], 422);
        }
        ob_start();
        try {
            $trabajo = EntregaVenta::imprimir($id, (int) $this->sucursalId(), $this->esBodega());
        } catch (\Throwable $e) {
            ob_end_clean();
            log_message('error', 'Impresión CUPS de la entrega {id}: {m}', ['id' => $id, 'm' => $e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], 422);
        }
        ob_end_clean();
        log_message('info', 'Entrega {id} enviada a CUPS: trabajo {t}.', ['id' => $id, 't' => $trabajo]);
        return $this->json(['ok' => true, 'mensaje' => 'Entrega enviada a la impresora.', 'trabajo' => $trabajo]);
    }

    /** ?bodega=1 → hoja para bodega (con la existencia de cada depósito) en lugar de la copia para el cliente. */
    private function esBodega(): bool
    {
        return (string) ($this->request->getGet('bodega') ?? '') === '1';
    }
}
