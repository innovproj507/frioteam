<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\AuditService;
use App\Libraries\Auth;
use App\Libraries\Db;
use App\Libraries\Fila;
use App\Libraries\NumeracionService;

/** Clientes, direcciones y cuentas por cobrar. Permisos por ruta: clientes.ver / crear / editar / eliminar. */
class ClienteController extends BaseController
{
    public function index(): string
    {
        $empresaId = $this->empresaId();
        $page      = max(1, (int) ($this->request->getGet('page') ?? 1));
        $buscar    = (string) ($this->request->getGet('buscar') ?? '');
        $estado    = (string) ($this->request->getGet('estado') ?? '');
        $tipo      = (string) ($this->request->getGet('tipo') ?? '');
        if ($estado === 'eliminado' && !$this->veEliminados()) {
            $estado = '';
        }
        $porPagina = in_array((int) $this->request->getGet('por_pagina'), [10, 25, 50, 100], true) ? (int) $this->request->getGet('por_pagina') : (int) env('PAGINATION_PER_PAGE', 25);

        $where  = 'empresa_id = ?';
        $params = [$empresaId];
        if ($buscar !== '') {
            $where .= ' AND (nombre LIKE ? OR codigo LIKE ? OR ruc LIKE ?)';
            array_push($params, "%{$buscar}%", "%{$buscar}%", "%{$buscar}%");
        }
        if ($estado !== '') {
            $where .= ' AND estado = ?';
            $params[] = $estado;
        }
        if ($tipo !== '') {
            $where .= ' AND tipo = ?';
            $params[] = $tipo;
        }
        if ($estado !== 'eliminado') {
            $where .= " AND estado <> 'eliminado'"; // los eliminados no salen en la lista
        }

        $total      = (int) Db::value("SELECT COUNT(*) FROM clientes WHERE {$where}", $params);
        $offset     = ($page - 1) * $porPagina;
        $pagination = paginacion($total, $page, $porPagina) + ['per_page' => $porPagina];

        return $this->render('clientes.lista', [
            'page_title'    => 'Clientes',
            'page_subtitle' => 'Gestión de clientes',
            'clientes'      => Fila::lista(Db::all("SELECT * FROM clientes WHERE {$where} ORDER BY nombre ASC LIMIT {$porPagina} OFFSET {$offset}", $params)),
            'pagination'    => $pagination,
            'stats'         => Db::row(
                "SELECT SUM(CASE WHEN estado <> 'eliminado' THEN 1 ELSE 0 END) AS total,
                        SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) AS activos,
                        SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) AS inactivos,
                        SUM(CASE WHEN estado = 'eliminado' THEN 1 ELSE 0 END) AS eliminados,
                        SUM(saldo_pendiente) AS saldo_total
                 FROM clientes WHERE empresa_id = ?",
                [$empresaId]
            ),
            've_eliminados' => $this->veEliminados(),
            'buscar'        => $buscar,
            'estado_filtro' => $estado,
            'tipo_filtro'   => $tipo,
            'por_pagina'    => $porPagina,
        ]);
    }

    public function crear(): string
    {
        return $this->render('clientes.crear', [
            'page_title'    => 'Nuevo Cliente',
            'page_subtitle' => 'Registrar cliente',
            'nuevo_codigo'  => NumeracionService::siguiente('cliente', $this->empresaId()),
        ]);
    }

    public function guardar()
    {
        $datos = $this->datos();
        if ($datos['nombre'] === '') {
            $this->error('El nombre es requerido.');
            return redirect()->to('/clientes/nuevo');
        }
        if ($datos['ruc'] === '') {
            $this->error('La cédula o RUC es obligatoria.');
            return redirect()->to('/clientes/nuevo')->withInput();
        }
        if ($datos['codigo'] === '') {
            $datos['codigo'] = NumeracionService::siguiente('cliente', $this->empresaId());
        }
        if ($foto = $this->subirFoto()) {
            $datos['foto'] = $foto;
        }

        $id = Db::insert('clientes', $datos + [
            'empresa_id'     => $this->empresaId(),
            'estado'         => 'activo',
            'saldo'          => 0,
            'vendedor_id'    => Auth::id(),
            'fecha_registro' => date('Y-m-d H:i:s'),
        ]);
        AuditService::log('clientes.crear', "Cliente creado: {$datos['nombre']}", $id, 'cliente', [], $this->empresaId());

        $this->success('Cliente creado exitosamente.');
        return redirect()->to('/clientes');
    }

    public function detalle(int $clienteId)
    {
        $cliente = $this->buscar($clienteId);
        if (!$cliente) {
            $this->error('No autorizado.');
            return redirect()->to('/clientes');
        }

        return $this->render('clientes.detalle', [
            'page_title'    => $cliente['nombre'],
            'page_subtitle' => 'Detalle del cliente',
            'cliente'       => new Fila($cliente),
            'direcciones'   => Fila::lista(Db::all("SELECT * FROM direcciones_cliente WHERE cliente_id = ?", [$clienteId])),
            'ventas'        => Db::all("SELECT * FROM ventas WHERE cliente_id = ? AND sucursal_id = ? ORDER BY fecha DESC LIMIT 20", [$clienteId, $this->sucursalId()]),
            'saldosAnteriores' => \App\Libraries\SaldoAnteriorService::delCliente($clienteId, (int) $this->empresaId()),
        ]);
    }

    public function editar(int $clienteId)
    {
        $cliente = $this->buscar($clienteId);
        if (!$cliente) {
            $this->error('No autorizado.');
            return redirect()->to('/clientes');
        }

        return $this->render('clientes.editar', [
            'page_title'    => 'Editar Cliente',
            'page_subtitle' => $cliente['nombre'],
            'cliente'       => new Fila($cliente),
            'nuevo_codigo'  => trim((string) $cliente['codigo']) === '' ? NumeracionService::siguiente('cliente', $this->empresaId()) : '',
        ]);
    }

    public function actualizar(int $clienteId)
    {
        if (!$this->buscar($clienteId)) {
            $this->error('No autorizado.');
            return redirect()->to('/clientes');
        }

        $datos  = $this->datos();
        if ($datos['codigo'] === '') {
            $datos['codigo'] = NumeracionService::siguiente('cliente', $this->empresaId());
        }
        // El estado del formulario de edición (antes se ignoraba y no había forma de reactivar un cliente)
        $estado = (string) ($this->request->getPost('estado') ?? '');
        if (in_array($estado, ['activo', 'inactivo'], true)) {
            $datos['estado'] = $estado;
        }
        $anterior = $this->buscar($clienteId)['foto'] ?? null;
        if ($foto = $this->subirFoto()) {
            $datos['foto'] = $foto;
        } elseif ($this->request->getPost('quitar_foto')) {
            $datos['foto'] = null;
        }
        Db::update('clientes', $datos, ['cliente_id' => $clienteId]);
        if ($anterior && array_key_exists('foto', $datos) && $datos['foto'] !== $anterior) {
            $this->borrarArchivoFoto($anterior);
        }
        $this->success('Cliente actualizado.');
        return redirect()->to("/clientes/{$clienteId}");
    }

    public function cambiarEstado(int $clienteId)
    {
        $cliente = $this->buscar($clienteId);
        if (!$cliente) {
            $this->error('No autorizado.');
            return redirect()->to('/clientes');
        }

        if ($cliente['estado'] === 'eliminado') {
            return redirect()->to('/clientes');
        }
        $nuevo = $cliente['estado'] === 'activo' ? 'inactivo' : 'activo';
        Db::update('clientes', ['estado' => $nuevo], ['cliente_id' => $clienteId]);
        $this->success("Cliente marcado como {$nuevo}.");
        return redirect()->to('/clientes');
    }

    public function eliminar(int $clienteId)
    {
        $cliente = $this->buscar($clienteId);
        if (!$cliente) {
            $this->error('No autorizado.');
            return redirect()->to('/clientes');
        }

        // Con ventas, cotizaciones o saldo NO se borra la fila (se perdería el historial y la deuda): se OCULTA.
        // Para quien lo elimina el cliente desaparece de las listas y los buscadores; las facturas y el saldo siguen igual.
        $usos = (int) Db::value("SELECT COUNT(*) FROM ventas WHERE cliente_id = ?", [$clienteId])
              + (int) Db::value("SELECT COUNT(*) FROM cotizaciones WHERE cliente_id = ?", [$clienteId]);
        if ($usos > 0 || (float) $cliente['saldo_pendiente'] > 0) {
            Db::update('clientes', ['estado' => 'eliminado'], ['cliente_id' => $clienteId]);
            AuditService::log('clientes.eliminar', "Cliente eliminado (oculto; conserva historial y saldo): {$cliente['nombre']}", $clienteId, 'cliente', [], $this->empresaId());
            $this->success('Cliente eliminado.');
            return redirect()->to('/clientes');
        }

        Db::begin();
        Db::query("DELETE FROM direcciones_cliente WHERE cliente_id = ?", [$clienteId]);
        Db::query("DELETE FROM clientes WHERE cliente_id = ?", [$clienteId]);
        Db::commit();
        AuditService::log('clientes.eliminar', "Cliente eliminado: {$cliente['nombre']}", $clienteId, 'cliente', [], $this->empresaId());

        $this->success('Cliente eliminado.');
        return redirect()->to('/clientes');
    }

    /** Foto/logo del cliente: imagen real (JPG/PNG/WEBP/GIF) de hasta 3 MB. */
    private function subirFoto(): ?string
    {
        $file = $this->request->getFile('foto');
        if ($file === null || !$file->isValid() || $file->hasMoved()) {
            return null;
        }
        if ($file->getSize() > 3 * 1024 * 1024) {
            $this->warning('La foto pesa más de 3 MB: no se guardó.');
            return null;
        }
        return $this->subirImagen('foto', 'clientes');
    }

    private function borrarArchivoFoto(string $ruta): void
    {
        if (preg_match('~^clientes/[a-f0-9]+\.(jpg|png|webp|gif)$~', $ruta)) {
            @unlink(FCPATH . 'assets/uploads/' . $ruta);
        }
    }

    /** Solo el administrador principal ve los clientes eliminados y puede devolverlos a la lista. */
    private function veEliminados(): bool
    {
        return Auth::rol() === 'superadmin';
    }

    public function restaurar(int $clienteId)
    {
        $cliente = $this->veEliminados() ? Db::row("SELECT * FROM clientes WHERE cliente_id = ? AND empresa_id = ? AND estado = 'eliminado'", [$clienteId, $this->empresaId()]) : null;
        if (!$cliente) {
            $this->error('No autorizado.');
            return redirect()->to('/clientes');
        }
        Db::update('clientes', ['estado' => 'activo'], ['cliente_id' => $clienteId]);
        AuditService::log('clientes.restaurar', "Cliente restaurado: {$cliente['nombre']}", $clienteId, 'cliente', [], $this->empresaId());
        $this->success('Cliente restaurado.');
        return redirect()->to('/clientes?estado=eliminado');
    }

    public function agregarDireccion(int $clienteId)
    {
        if (!$this->buscar($clienteId)) {
            $this->error('No autorizado.');
            return redirect()->to('/clientes');
        }

        $direccion = trim((string) ($this->request->getPost('direccion') ?? ''));
        if ($direccion === '') {
            $this->error('La dirección es requerida.');
            return redirect()->to("/clientes/{$clienteId}");
        }

        // Columnas reales de direcciones_cliente: tipo, direccion, ciudad, provincia, pais, estado
        Db::insert('direcciones_cliente', [
            'cliente_id' => $clienteId,
            'direccion'  => $direccion,
            'ciudad'     => trim((string) ($this->request->getPost('ciudad') ?? '')) ?: null,
        ]);

        $this->success('Dirección agregada.');
        return redirect()->to("/clientes/{$clienteId}");
    }

    public function eliminarDireccion(int $direccionId)
    {
        $clienteId = Db::value(
            "SELECT d.cliente_id FROM direcciones_cliente d JOIN clientes c ON c.cliente_id = d.cliente_id
             WHERE d.direccion_id = ? AND c.empresa_id = ?",
            [$direccionId, $this->empresaId()]
        );
        if (!$clienteId) {
            $this->error('Dirección no encontrada.');
            return redirect()->to('/clientes');
        }

        Db::query("DELETE FROM direcciones_cliente WHERE direccion_id = ?", [$direccionId]);
        $this->success('Dirección eliminada.');
        return redirect()->to("/clientes/{$clienteId}");
    }

    // ── JSON ─────────────────────────────────────────────────────────────────

    public function crearRapido()
    {
        $data   = $this->jsonInput();
        $nombre = trim((string) ($data['nombre'] ?? ''));
        if ($nombre === '') {
            return $this->json(['error' => 'El nombre es requerido'], 422);
        }

        $cliente = [
            'empresa_id'     => $this->empresaId(),
            'codigo'         => trim((string) ($data['codigo'] ?? '')) ?: NumeracionService::siguiente('cliente', $this->empresaId()),
            'nombre'         => $nombre,
            'tipo'           => $data['tipo'] ?? 'natural',
            'telefono'       => (string) ($data['telefono'] ?? ''),
            'estado'         => 'activo',
            'saldo'          => 0,
            'vendedor_id'    => Auth::id(),
            'fecha_registro' => date('Y-m-d H:i:s'),
        ];
        $cliente['cliente_id'] = Db::insert('clientes', $cliente);

        return $this->json(['success' => true, 'cliente' => $cliente]);
    }

    public function buscarAjax()
    {
        $q = (string) ($this->request->getGet('q') ?? '');
        return $this->json(['clientes' => Db::all(
            "SELECT cliente_id, codigo, nombre, ruc, telefono FROM clientes
             WHERE estado = 'activo' AND empresa_id = ? AND (nombre LIKE ? OR codigo LIKE ? OR ruc LIKE ?)
             LIMIT 20",
            [$this->empresaId(), "%{$q}%", "%{$q}%", "%{$q}%"]
        )]);
    }

    public function cuentasPorCobrar(): string
    {
        return $this->render('clientes.cuentas_cobrar', [
            'page_title'    => 'Cuentas por Cobrar',
            'page_subtitle' => 'Gestión de cartera y créditos',
            'cuentas'       => Db::all(
                "SELECT c.cliente_id, c.nombre, c.ruc, c.telefono, c.limite_credito AS cupo_credito, c.saldo_pendiente, c.estado,
                        (SELECT MAX(fecha) FROM ventas WHERE cliente_id = c.cliente_id AND forma_pago = 'CREDITO') AS ultima_venta_credito
                 FROM clientes c
                 WHERE c.saldo_pendiente > 0 AND c.empresa_id = ?
                 ORDER BY c.saldo_pendiente DESC",
                [$this->empresaId()]
            ),
            'stats' => Db::row(
                "SELECT SUM(CASE WHEN saldo_pendiente > 0 THEN 1 ELSE 0 END) AS total_deudores,
                        SUM(saldo_pendiente) AS total_deuda, SUM(limite_credito) AS total_cupo
                 FROM clientes WHERE empresa_id = ?",
                [$this->empresaId()]
            ),
            // Lo cobrado hoy en esta tienda, por forma de pago
            'cobrosHoy'   => Db::all(
                "SELECT forma_pago, COUNT(*) AS recibos, SUM(monto) AS total FROM cobros
                 WHERE sucursal_id = ? AND DATE(fecha) = CURDATE() GROUP BY forma_pago ORDER BY total DESC",
                [$this->sucursalId()]
            ),
            'puedeCobrar' => \App\Libraries\Auth::can('ventas.crear') || \App\Libraries\Auth::can('pos.usar'),
            'ver'         => (string) ($this->request->getGet('ver') ?? ''),
        ]);
    }

    private function buscar(int $clienteId): ?array
    {
        $fila = Db::row("SELECT * FROM clientes WHERE cliente_id = ? AND empresa_id = ?", [$clienteId, $this->empresaId()]);
        return ($fila && $fila['estado'] === 'eliminado' && !$this->veEliminados()) ? null : $fila;
    }

    private function datos(): array
    {
        $post = fn (string $k, string $def = '') => trim((string) ($this->request->getPost($k) ?? $def));
        return [
            'codigo'         => $post('codigo'),
            'nombre'         => $post('nombre'),
            'tipo'           => $post('tipo', 'natural'),
            'ruc'            => $post('ruc'),
            'dv'             => $post('dv'),
            'direccion'      => $post('direccion'),
            'telefono'       => $post('telefono'),
            'email'          => $post('email'),
            'limite_credito' => (float) $post('limite_credito', '0'),
            'dias_credito'   => (int) $post('dias_credito', '0'),
            'itbms'          => $post('itbms', 'SI') === 'NO' ? 0.00 : 7.00,
        ];
    }
}
