<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * ventas.origen: de qué pantalla salió la venta.
 *   'pos'    = pantalla del POS (venta rápida de mostrador)
 *   'normal' = "Nueva venta" (factura), API y ventas anteriores sin dato
 * Permite tener el historial del POS separado del de ventas normales.
 *   php spark migrate
 */
class VentasOrigen extends Migration
{
    public function up(): void
    {
        $this->db->query("ALTER TABLE ventas
            ADD COLUMN origen ENUM('normal','pos') NOT NULL DEFAULT 'normal' AFTER estado,
            ADD KEY idx_ventas_origen (sucursal_id, origen, fecha)");
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE ventas DROP KEY idx_ventas_origen, DROP COLUMN origen");
    }
}
