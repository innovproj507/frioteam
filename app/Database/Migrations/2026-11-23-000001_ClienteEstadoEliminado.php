<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * "Eliminar" un cliente con historial lo OCULTA (estado "eliminado") en vez de borrarlo: desaparece de las listas
 * y de los buscadores, pero sus facturas, cotizaciones y saldo pendiente quedan intactos.
 */
class ClienteEstadoEliminado extends Migration
{
    public function up(): void
    {
        $this->db->query("ALTER TABLE clientes MODIFY estado ENUM('activo','inactivo','eliminado') NOT NULL DEFAULT 'activo'");
    }

    public function down(): void
    {
        $this->db->query("UPDATE clientes SET estado = 'inactivo' WHERE estado = 'eliminado'");
        $this->db->query("ALTER TABLE clientes MODIFY estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo'");
    }
}
