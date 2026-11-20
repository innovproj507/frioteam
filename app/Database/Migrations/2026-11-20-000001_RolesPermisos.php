<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Permisos de los roles editables desde Configuración > Roles y Permisos.
 * Un rol sin filas aquí usa la matriz por defecto de app/Config/Permisos.php; un rol con filas usa
 * SOLO lo guardado (la fila permiso='*' marca "personalizado", así un rol puede quedar sin ningún permiso).
 *   php spark migrate
 */
class RolesPermisos extends Migration
{
    public function up(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS roles_permisos (
            rol     VARCHAR(30) NOT NULL,
            permiso VARCHAR(60) NOT NULL,
            PRIMARY KEY (rol, permiso)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down(): void
    {
        $this->db->query("DROP TABLE IF EXISTS roles_permisos");
    }
}
