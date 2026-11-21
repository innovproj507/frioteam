<?php

namespace App\Controllers;

use App\Libraries\AuditService;
use App\Libraries\Auth;
use App\Libraries\Db;
use App\Libraries\NumeracionService;

/** Clientes > Solicitudes web: quien pidió acceso a la tienda en línea se aprueba (con su tipo de precio) o se rechaza aquí. */
class TiendaSolicitudController extends BaseController
{
    public function index(): string
    {
        return $this->render('clientes.solicitudes_web', [
            'page_title'    => 'Solicitudes de la tienda web',
            'page_subtitle' => 'Clientes que piden acceso para ver precios y cotizar',
            'pendientes'    => Db::all("SELECT * FROM tienda_solicitudes WHERE empresa_id = ? AND estado = 'pendiente' ORDER BY solicitud_id", [$this->empresaId()]),
            'resueltas'     => Db::all("SELECT * FROM tienda_solicitudes WHERE empresa_id = ? AND estado <> 'pendiente' ORDER BY resuelto_en DESC LIMIT 30", [$this->empresaId()]),
        ]);
    }

    public function aprobar(int $id)
    {
        $s = Db::row("SELECT * FROM tienda_solicitudes WHERE solicitud_id = ? AND empresa_id = ? AND estado = 'pendiente'", [$id, $this->empresaId()]);
        if (!$s) {
            $this->error('La solicitud ya no está pendiente.');
            return redirect()->to('/clientes/solicitudes-web');
        }
        $tipo = in_array($this->request->getPost('tipo_precio'), ['a', 'b', 'c'], true) ? $this->request->getPost('tipo_precio') : 'c';

        // Si ya es cliente (mismo correo o RUC) se vincula; si no, se crea
        $clienteId = Db::value('SELECT cliente_id FROM clientes WHERE empresa_id = ? AND email = ? LIMIT 1', [$this->empresaId(), $s['email']]);
        if (!$clienteId && !empty($s['ruc'])) {
            $clienteId = Db::value('SELECT cliente_id FROM clientes WHERE empresa_id = ? AND ruc = ? LIMIT 1', [$this->empresaId(), $s['ruc']]);
        }
        if (!$clienteId) {
            $clienteId = Db::insert('clientes', [
                'empresa_id'     => $this->empresaId(),
                'codigo'         => NumeracionService::siguiente('cliente', $this->empresaId()),
                'nombre'         => $s['nombre'],
                'tipo'           => 'natural',
                'ruc'            => (string) $s['ruc'],
                'email'          => $s['email'],
                'telefono'       => (string) $s['telefono'],
                'direccion'      => (string) $s['direccion'],
                'estado'         => 'activo',
                'saldo'          => 0,
                'vendedor_id'    => Auth::id(),
                'fecha_registro' => date('Y-m-d H:i:s'),
            ]);
        }
        Db::update('tienda_solicitudes', [
            'estado' => 'aprobada', 'tipo_precio' => $tipo, 'cliente_id' => (int) $clienteId,
            'resuelto_por' => Auth::id(), 'resuelto_en' => date('Y-m-d H:i:s'),
        ], ['solicitud_id' => $id]);
        AuditService::log('tienda.aprobar', "Acceso web aprobado: {$s['nombre']} ({$s['email']}), precio " . strtoupper($tipo), (int) $clienteId, 'cliente', [], $this->empresaId());

        $this->success("Acceso aprobado: {$s['nombre']} ya puede entrar a la tienda y ver los precios " . strtoupper($tipo) . '.');
        return redirect()->to('/clientes/solicitudes-web');
    }

    public function rechazar(int $id)
    {
        $s = Db::row("SELECT * FROM tienda_solicitudes WHERE solicitud_id = ? AND empresa_id = ? AND estado = 'pendiente'", [$id, $this->empresaId()]);
        if ($s) {
            Db::update('tienda_solicitudes', [
                'estado' => 'rechazada', 'motivo' => mb_substr(trim((string) $this->request->getPost('motivo')), 0, 255),
                'resuelto_por' => Auth::id(), 'resuelto_en' => date('Y-m-d H:i:s'),
            ], ['solicitud_id' => $id]);
            AuditService::log('tienda.rechazar', "Acceso web rechazado: {$s['nombre']} ({$s['email']})", $id, 'cliente', [], $this->empresaId());
            $this->success('Solicitud rechazada.');
        }
        return redirect()->to('/clientes/solicitudes-web');
    }
}
