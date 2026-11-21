<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * El refrigerante que se vende por libra (3.5 lb) pasa a unidad de medida "Libra": es la unidad la que decide
 * si el POS admite decimales (el resto de productos se vende por unidades enteras).
 * 019322 = Libra Refrigerante R22.
 *   php spark migrate
 */
class RefrigerantePorLibra extends Migration
{
    private const CODIGOS = ['2012012', '019322', '019141', '2014364', '019325', '2014610'];

    public function up(): void
    {
        $in = implode(',', array_map(fn ($c) => $this->db->escape($c), self::CODIGOS));
        $this->db->query("UPDATE productos SET unidad_medida = 'lb' WHERE codigo IN ({$in}) AND unidad_medida = 'und'");
    }

    public function down(): void
    {
        $in = implode(',', array_map(fn ($c) => $this->db->escape($c), self::CODIGOS));
        $this->db->query("UPDATE productos SET unidad_medida = 'und' WHERE codigo IN ({$in}) AND unidad_medida = 'lb'");
    }
}
