<?php

declare(strict_types=1);

namespace App\Commands;

use App\Libraries\TiendaPuente as Puente;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * php spark tienda:puente                rápido: recoge pedidos/cuentas, envía decisiones y stock (cada 2-5 min)
 * php spark tienda:puente --catalogo 1   además categorías, productos modificados, ranking y fotos (cada hora)
 * php spark tienda:puente --completo 1   todo el catálogo desde cero
 * La tienda es independiente: si el POS está apagado ella sigue atendiendo y guarda lo que llegue.
 */
class TiendaPuente extends BaseCommand
{
    protected $group       = 'Tienda';
    protected $name        = 'tienda:puente';
    protected $description = 'Sincroniza el POS con la tienda web (el POS llama a la tienda).';

    public function run(array $params)
    {
        $completo = (bool) CLI::getOption('completo');
        $catalogo = $completo || (bool) CLI::getOption('catalogo');

        // Una sola ejecución a la vez (el cron puede solaparse)
        $lock = fopen(WRITEPATH . 'puente.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            CLI::write('Ya hay una sincronización en curso.', 'yellow');
            return;
        }

        try {
            $p = new Puente();
            $p->ejecutar($catalogo, $completo);
            foreach ($p->mensajes as $m) {
                CLI::write($m, 'green');
            }
        } catch (\Throwable $e) {
            CLI::error('Sincronización fallida: ' . $e->getMessage());
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
