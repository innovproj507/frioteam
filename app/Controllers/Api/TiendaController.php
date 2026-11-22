<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Libraries\TiendaApi;
use App\Libraries\TiendaApiError;
use CodeIgniter\Controller;

/**
 * API de la tienda en línea (la tienda llama al POS). Solo es un envoltorio HTTP de App\Libraries\TiendaApi,
 * que es lo mismo que usa el comando tienda:puente (el POS llama a la tienda).
 */
class TiendaController extends Controller
{
    private function ok(mixed $data, int $codigo = 200)
    {
        return $this->response->setStatusCode($codigo)->setJSON(['status' => 'success', 'data' => $data]);
    }

    private function error(TiendaApiError $e)
    {
        return $this->response->setStatusCode($e->getCode() ?: 500)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
    }

    public function categorias()
    {
        return $this->ok(TiendaApi::categorias());
    }

    public function catalogo()
    {
        $r    = TiendaApi::catalogo((int) ($this->request->getGet('pagina') ?? 1), trim((string) ($this->request->getGet('desde') ?? '')));
        $base = rtrim((string) env('TIENDA_URL_IMAGENES', ''), '/');
        foreach ($r['productos'] as &$f) {
            $f['imagen_url'] = ($f['imagen_principal'] && $base !== '') ? $base . '/' . ltrim($f['imagen_principal'], '/') : null;
        }
        unset($f);
        return $this->ok($r + ['generado' => date('c')]);
    }

    public function ranking()
    {
        return $this->ok(TiendaApi::ranking());
    }

    public function stock()
    {
        return $this->ok(['stock' => TiendaApi::stock(), 'generado' => date('c')]);
    }

    public function pedido()
    {
        try {
            $r = TiendaApi::registrarPedido($this->request->getJSON(true) ?: []);
        } catch (TiendaApiError $e) {
            return $this->error($e);
        }
        return $this->ok($r, empty($r['duplicado']) ? 201 : 200);
    }

    public function pedidosEstado()
    {
        return $this->ok(TiendaApi::pedidosEstado(explode(',', (string) $this->request->getGet('numeros'))));
    }

    public function solicitud()
    {
        try {
            return $this->ok(TiendaApi::registrarSolicitud($this->request->getJSON(true) ?: []));
        } catch (TiendaApiError $e) {
            return $this->error($e);
        }
    }

    public function solicitudEstado()
    {
        $r = TiendaApi::solicitudEstado((string) $this->request->getGet('email'));
        return $r ? $this->ok($r) : $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Sin solicitud.']);
    }
}
