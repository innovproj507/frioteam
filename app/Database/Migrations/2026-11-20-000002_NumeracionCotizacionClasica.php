<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Nomenclatura de las cotizaciones del sistema anterior de FrioTeam:
 *   2026Z1113COT-00002547  =  AAAA + "Z" + MMDD + "COT" + "-" + contador de 8 dígitos (continuo)
 * Las cotizaciones migradas ya traen ese formato; las nuevas siguen contando desde la última.
 *   php spark migrate
 */
class NumeracionCotizacionClasica extends Migration
{
    public function up(): void
    {
        $this->db->query("UPDATE numeraciones SET prefijo = 'COT', separador = '-', digitos = 8, reinicio = 'clasico', con_sucursal = 0 WHERE tipo = 'cotizacion'");
    }

    public function down(): void
    {
        $this->db->query("UPDATE numeraciones SET prefijo = 'COT', separador = '-', digitos = 6, reinicio = 'nunca', con_sucursal = 1 WHERE tipo = 'cotizacion'");
    }
}
