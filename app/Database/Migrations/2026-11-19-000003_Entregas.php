<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Entregas (despachos): el documento "Agregar entrega" del sistema anterior, DESP-00005368.
 * Lista los productos de una venta para que los verifiquen al despachar. La numeración
 * (DESP-########) es el tipo "entrega" de la tabla numeraciones.
 *   php spark migrate
 */
class Entregas extends Migration
{
    public function up(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS entregas (
            entrega_id  INT NOT NULL AUTO_INCREMENT,
            empresa_id  INT NOT NULL,
            sucursal_id INT NOT NULL,
            venta_id    INT NOT NULL,
            numero      VARCHAR(50) NOT NULL,
            fecha       DATETIME NOT NULL,
            cliente     VARCHAR(200) NOT NULL DEFAULT '',
            direccion   TEXT NULL,
            nota        TEXT NULL,
            usuario_id  INT NULL,
            creado_en   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (entrega_id),
            UNIQUE KEY uq_entrega_numero (numero),
            KEY idx_entrega_venta (venta_id),
            KEY idx_entrega_fecha (sucursal_id, fecha),
            CONSTRAINT fk_entrega_venta FOREIGN KEY (venta_id) REFERENCES ventas (venta_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("INSERT INTO numeraciones (empresa_id, tipo, prefijo, separador, digitos, reinicio, con_sucursal, posicion_sucursal)
            SELECT c.empresa_id, 'entrega', 'DESP', '-', 8, 'nunca', 0, 'antes' FROM companies c
            WHERE NOT EXISTS (SELECT 1 FROM numeraciones n WHERE n.empresa_id = c.empresa_id AND n.tipo = 'entrega')");
    }

    public function down(): void
    {
        $this->db->query("DELETE FROM numeraciones WHERE tipo = 'entrega'");
        $this->db->query("DROP TABLE IF EXISTS entregas");
    }
}
