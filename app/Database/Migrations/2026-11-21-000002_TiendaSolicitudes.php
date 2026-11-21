<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Solicitudes de acceso que llegan desde la tienda en línea (se aprueban en el POS: Clientes > Solicitudes web). */
class TiendaSolicitudes extends Migration
{
    public function up(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS tienda_solicitudes (
            solicitud_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            empresa_id INT NOT NULL,
            email VARCHAR(150) NOT NULL,
            nombre VARCHAR(200) NOT NULL,
            telefono VARCHAR(50) NULL,
            ruc VARCHAR(50) NULL,
            direccion TEXT NULL,
            estado ENUM('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
            tipo_precio ENUM('a','b','c') NOT NULL DEFAULT 'c',
            cliente_id INT NULL,
            motivo VARCHAR(255) NULL,
            creado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            resuelto_por INT NULL,
            resuelto_en DATETIME NULL,
            UNIQUE KEY uq_email (empresa_id, email),
            KEY (estado)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down(): void
    {
        $this->db->query('DROP TABLE IF EXISTS tienda_solicitudes');
    }
}
