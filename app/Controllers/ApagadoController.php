<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\Auth;
use App\Libraries\AuditService;

/**
 * Apagar el servidor desde el POS (para quien no sabe usar la consola de Linux).
 *
 * El POS corre dentro de un contenedor y no puede apagar el equipo por sí solo: deja un archivo de
 * solicitud en writable/apagar/ (carpeta compartida con el servidor) y un vigilante del sistema
 * (herramientas/instalar_apagado.sh) lo ve, y apaga el equipo en 1 minuto. Solo el administrador principal.
 * writable/apagar/activo existe únicamente cuando ese vigilante está instalado.
 */
class ApagadoController extends BaseController
{
    public static function instalado(): bool
    {
        return is_file(WRITEPATH . 'apagar/activo');
    }

    public static function permitido(): bool
    {
        return Auth::isSuperuser() || Auth::rol() === 'superadmin';
    }

    /** POST /configuracion/apagar — pide el apagado; exige la confirmación explícita. */
    public function apagar()
    {
        if (!self::permitido()) {
            $this->error('Solo el administrador puede apagar el servidor.');
            return redirect()->to('/configuracion');
        }
        if ((string) $this->request->getPost('confirmado') !== '1') {
            $this->error('Falta confirmar el apagado.');
            return redirect()->to('/configuracion');
        }
        if (!self::instalado()) {
            $this->error('El apagado desde el sistema no está instalado en este servidor.');
            return redirect()->to('/configuracion');
        }

        $dir = WRITEPATH . 'apagar/';
        @unlink($dir . 'cancelar');
        if (@file_put_contents($dir . 'solicitud', date('c') . ' ' . Auth::name() . "\n") === false) {
            $this->error('No se pudo pedir el apagado (sin permiso de escritura).');
            return redirect()->to('/configuracion');
        }

        AuditService::log('core.apagar', 'Apagado del servidor solicitado desde el sistema', null, null, [], $this->empresaId());
        $this->success('El servidor se apagará en 1 minuto. Si fue un error, pulsa "Cancelar apagado" ahora mismo.');
        return redirect()->to('/configuracion');
    }

    /** POST /configuracion/apagar/cancelar — cancela un apagado pendiente (dentro del minuto de espera). */
    public function cancelar()
    {
        if (!self::permitido() || !self::instalado()) {
            $this->error('No autorizado.');
            return redirect()->to('/configuracion');
        }
        @unlink(WRITEPATH . 'apagar/solicitud');
        @file_put_contents(WRITEPATH . 'apagar/cancelar', date('c') . "\n");
        AuditService::log('core.apagar', 'Apagado del servidor cancelado desde el sistema', null, null, [], $this->empresaId());
        $this->success('Apagado cancelado.');
        return redirect()->to('/configuracion');
    }
}
