<?php

declare(strict_types=1);

namespace App\Filters;

use App\Libraries\Auth;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * API de la tienda en línea (/api/tienda/*): autenticación servidor a servidor con un token
 * fijo (TIENDA_TOKEN en .env), distinto de los tokens de usuario. Sin TIENDA_TOKEN la API queda cerrada.
 * Actúa con el contexto TIENDA_EMPRESA_ID / TIENDA_SUCURSAL_ID / TIENDA_VENDEDOR_ID.
 */
class TiendaTokenFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $esperado = (string) env('TIENDA_TOKEN', '');
        $dado     = preg_match('/Bearer\s+(\S+)/i', $request->getHeaderLine('Authorization'), $m) ? $m[1] : '';

        if (strlen($esperado) < 32 || $dado === '' || !hash_equals($esperado, $dado)) {
            return service('response')->setStatusCode(401)->setJSON(['status' => 'error', 'message' => 'Token de la tienda inválido.']);
        }

        $empresa  = (int) env('TIENDA_EMPRESA_ID', 1);
        $sucursal = (int) env('TIENDA_SUCURSAL_ID', 1);
        Auth::usarUsuarioApi([
            'id'            => (int) env('TIENDA_VENDEDOR_ID', 1),
            'username'      => 'tienda-web',
            'user_fullname' => 'Tienda en línea',
            'rol'           => 'cajero',
            'is_superuser'  => false,
            'is_staff'      => false,
            'empresa_id'    => $empresa,
            'sucursal_id'   => $sucursal,
            'deposito_id'   => 0,
        ]);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
