<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Nomenclatura de las facturas de FrioTeam: las dos series del sistema anterior.
 *   POS ............... FACT20261119-00127824  (FACT + fecha AAAAMMDD + "-" + contador de 8 dígitos, continuo)
 *   Nueva venta ....... FACT-00004691          (FACT + "-" + contador de 8 dígitos)
 * Cada serie sigue contando desde el último número usado en esa serie.
 *   php spark migrate
 */
class NumeracionFacturaFrioteam extends Migration
{
    public function up(): void
    {
        // Factura normal (pantalla "Nueva venta"): FACT-00004691, sin letra de sucursal
        $this->db->query("UPDATE numeraciones SET prefijo = 'FACT', separador = '-', digitos = 8, reinicio = 'nunca', con_sucursal = 0 WHERE tipo = 'factura'");

        // Factura del POS: FACT20261119-00127824 (modo "continuo")
        $this->db->query("UPDATE numeraciones SET prefijo = 'FACT', separador = '-', digitos = 8, reinicio = 'continuo', con_sucursal = 0 WHERE tipo = 'factura_pos'");
        $this->db->query("INSERT INTO numeraciones (empresa_id, tipo, prefijo, separador, digitos, reinicio, con_sucursal, posicion_sucursal)
            SELECT c.empresa_id, 'factura_pos', 'FACT', '-', 8, 'continuo', 0, 'antes' FROM companies c
            WHERE NOT EXISTS (SELECT 1 FROM numeraciones n WHERE n.empresa_id = c.empresa_id AND n.tipo = 'factura_pos')");
    }

    public function down(): void
    {
        $this->db->query("DELETE FROM numeraciones WHERE tipo = 'factura_pos'");
        $this->db->query("UPDATE numeraciones SET prefijo = 'FAC', separador = '-', digitos = 6, reinicio = 'nunca', con_sucursal = 1 WHERE tipo = 'factura'");
    }
}
