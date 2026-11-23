<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Foto o logo opcional del cliente (ruta relativa dentro de public/assets/uploads). */
class ClienteFoto extends Migration
{
    public function up(): void
    {
        $this->db->query("ALTER TABLE clientes ADD COLUMN foto VARCHAR(255) NULL DEFAULT NULL");
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE clientes DROP COLUMN foto");
    }
}
