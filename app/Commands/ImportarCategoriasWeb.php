<?php

declare(strict_types=1);

namespace App\Commands;

use App\Libraries\Db;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * php spark categorias:importar-web <archivo.json> [--simular]
 *
 * Crea en el POS el árbol de categorías de la web original (Compresor › Toyota › Yaris › 06-12…) y coloca cada
 * producto en la suya. Archivo: {"cats":[{"id":20,"nombre":"Compresor","padre":null}…], "productos":{"<codigo>":<id_cat_web>}}
 * Es repetible: reutiliza las categorías que ya existen (mismo nombre y mismo padre).
 */
class ImportarCategoriasWeb extends BaseCommand
{
    protected $group       = 'Catálogo';
    protected $name        = 'categorias:importar-web';
    protected $description = 'Crea las categorías de la web original en el POS y asigna los productos.';
    protected $usage       = 'categorias:importar-web <archivo.json> [--simular]';

    public function run(array $params)
    {
        $archivo = $params[0] ?? '';
        if (!is_file($archivo)) {
            CLI::error('Falta el archivo JSON.');
            return;
        }
        $simular = CLI::getOption('simular') !== null;
        $d       = json_decode((string) file_get_contents($archivo), true);
        $cats    = [];
        foreach ($d['cats'] as $c) {
            $cats[(int) $c['id']] = $c;
        }
        $productos = $d['productos'];

        // Solo las categorías con productos (y sus ancestros)
        $usadas = [];
        foreach ($productos as $catWeb) {
            for ($i = (int) $catWeb; $i && isset($cats[$i]) && !isset($usadas[$i]); $i = (int) ($cats[$i]['padre'] ?? 0)) {
                $usadas[$i] = true;
            }
        }
        $empresa = (int) env('TIENDA_EMPRESA_ID', 1);
        CLI::write('Categorías a crear/usar: ' . count($usadas) . ' · productos a ubicar: ' . count($productos));
        if ($simular) {
            return;
        }

        Db::begin();
        $mapa = [];
        $creadas = 0;
        $crear = function (int $id) use (&$crear, &$mapa, &$creadas, $cats, $empresa): int {
            if (isset($mapa[$id])) {
                return $mapa[$id];
            }
            $c     = $cats[$id];
            $padre = !empty($c['padre']) ? $crear((int) $c['padre']) : null;
            $nombre = mb_substr(trim((string) $c['nombre']), 0, 100);
            $ya = Db::value(
                'SELECT categoria_id FROM categorias_productos WHERE empresa_id = ? AND nombre = ? AND ' . ($padre ? 'padre_id = ?' : 'padre_id IS NULL'),
                $padre ? [$empresa, $nombre, $padre] : [$empresa, $nombre]
            );
            if ($ya) {
                return $mapa[$id] = (int) $ya;
            }
            $nivel = $padre ? 1 + (int) Db::value('SELECT nivel FROM categorias_productos WHERE categoria_id = ?', [$padre]) : 1;
            $creadas++;
            return $mapa[$id] = (int) Db::insert('categorias_productos', ['empresa_id' => $empresa, 'nombre' => $nombre, 'padre_id' => $padre, 'nivel' => $nivel]);
        };
        foreach (array_keys($usadas) as $id) {
            $crear((int) $id);
        }

        $movidos = 0;
        $faltan  = [];
        foreach ($productos as $codigo => $catWeb) {
            $pid = Db::value('SELECT producto_id FROM productos WHERE empresa_id = ? AND codigo = ?', [$empresa, (string) $codigo]);
            if (!$pid || !isset($mapa[(int) $catWeb])) {
                $faltan[] = $codigo;
                continue;
            }
            Db::update('productos', ['categoria_id' => $mapa[(int) $catWeb]], ['producto_id' => $pid]);
            $movidos++;
        }
        // La categoría provisional "Category 1" desaparece cuando queda vacía
        $c1 = Db::value("SELECT categoria_id FROM categorias_productos WHERE empresa_id = ? AND nombre = 'Category 1' AND padre_id IS NULL", [$empresa]);
        if ($c1 && !(int) Db::value('SELECT COUNT(*) FROM productos WHERE categoria_id = ?', [$c1]) && !(int) Db::value('SELECT COUNT(*) FROM categorias_productos WHERE padre_id = ?', [$c1])) {
            Db::query('DELETE FROM categorias_productos WHERE categoria_id = ?', [$c1]);
            CLI::write('Se quitó la categoría provisional "Category 1".');
        } elseif ($c1) {
            // Lo que no está en la web original queda en "Otros productos" hasta que se clasifique
            Db::update('categorias_productos', ['nombre' => 'Otros productos'], ['categoria_id' => $c1]);
            CLI::write('"Category 1" pasó a llamarse "Otros productos" (productos sin categoría en la web).');
        }
        Db::commit();
        CLI::write("Categorías creadas: {$creadas} · productos ubicados: {$movidos} · sin ubicar: " . count($faltan));
    }
}
