<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Permisos por rol: la matriz de app/Config/Permisos.php es el valor por defecto y lo que se guarda
 * desde Configuración > Roles y Permisos (tabla roles_permisos) lo reemplaza para ese rol.
 * superadmin siempre tiene todo y no se edita.
 */
class RolesService
{
    public const MARCA = '*';

    /** Roles que se pueden editar, con su nombre en pantalla. */
    public const ROLES = [
        'vendedor'   => 'Vendedor',
        'cajero'     => 'Cajero',
        'encargado'  => 'Encargado',
        'supervisor' => 'Supervisor',
        'gerente'    => 'Gerente',
        'auditor'    => 'Auditor',
    ];

    /** Grupo => [permiso => etiqueta] en el orden en que se muestran. */
    public static function catalogo(): array
    {
        return [
            'Ventas' => [
                'ventas.ver'           => 'Ver la lista de ventas',
                'ventas.ver_normales'  => 'Ver también las ventas normales / al por mayor (sin esto solo ve las del POS)',
                'ventas.crear'         => 'Crear ventas (nueva venta)',
                'ventas.anular'        => 'Anular, devolver o agregar a una venta',
                'ventas.fecha_anterior'=> 'Cargar ventas de días anteriores',
                'descuentos.aplicar'   => 'Aplicar descuentos',
                'pos.usar'             => 'Usar el Punto de Venta (POS)',
            ],
            'Cotizaciones' => [
                'cotizaciones.ver'     => 'Ver cotizaciones',
                'cotizaciones.crear'   => 'Crear y editar cotizaciones',
                'cotizaciones.aprobar' => 'Aprobar cotizaciones',
            ],
            'Cajas' => [
                'caja.usar'            => 'Abrir su caja y cobrar con ella',
                'caja.mi_caja'         => 'Ver y cerrar su caja (menú Mi Caja)',
                'cajas.ver'            => 'Ver todas las cajas y arqueos',
                'cajas.gestionar'      => 'Gestionar y cerrar cajas de otros',
            ],
            'Productos' => [
                'productos.ver'            => 'Ver productos',
                'productos.crear'          => 'Crear productos',
                'productos.editar'         => 'Modificar productos',
                'productos.editar_basico'  => 'Editar solo nombre, foto y ubicación',
                'productos.eliminar'       => 'Eliminar productos',
                'productos.ver_precio_a'   => 'Ver el Precio A',
                'costos.ver'               => 'Ver costos',
                'categorias.ver'           => 'Ver categorías',
                'categorias.crear'         => 'Crear categorías',
                'categorias.editar'        => 'Editar categorías',
                'categorias.eliminar'      => 'Eliminar categorías',
            ],
            'Inventario' => [
                'inventario.ver'       => 'Ver inventario y kardex',
                'inventario.ajustar'   => 'Ajustar existencias',
                'lotes.ver'            => 'Ver lotes y vencimientos',
                'lotes.gestionar'      => 'Gestionar lotes',
                'alertas.ver'          => 'Ver alertas',
                'alertas.resolver'     => 'Resolver alertas',
                'conteos.ver'          => 'Ver conteos físicos',
                'conteos.crear'        => 'Hacer conteos físicos',
                'conteos.reconciliar'  => 'Aplicar el ajuste de un conteo',
                'depositos.ver'        => 'Ver depósitos',
                'depositos.gestionar'  => 'Gestionar depósitos',
                'traslados.ver'        => 'Ver traslados',
                'traslados.crear'      => 'Crear traslados',
                'traslados.aprobar'    => 'Aprobar traslados',
                'traslados.recibir'    => 'Recibir traslados',
            ],
            'Compras' => [
                'compras.ver'          => 'Ver compras',
                'compras.crear'        => 'Crear compras',
                'compras.recibir'      => 'Recibir compras',
                'proveedores.ver'      => 'Ver proveedores',
                'proveedores.crear'    => 'Crear proveedores',
                'proveedores.editar'   => 'Editar proveedores',
                'sugerencias.ver'      => 'Ver órdenes de compra automáticas',
                'sugerencias.gestionar'=> 'Gestionar órdenes de compra automáticas',
            ],
            'Clientes' => [
                'clientes.ver'         => 'Ver clientes y cuentas por cobrar',
                'clientes.crear'       => 'Crear clientes',
                'clientes.editar'      => 'Editar clientes',
                'clientes.eliminar'    => 'Eliminar clientes',
            ],
            'Administración' => [
                'dashboard.ver'        => 'Ver el dashboard',
                'reportes.ver'         => 'Ver reportes',
                'reportes.avanzados'   => 'Reportes avanzados',
                'usuarios.ver'         => 'Ver usuarios y roles',
                'usuarios.gestionar'   => 'Gestionar usuarios, roles y configuración',
                'bitacora.ver'         => 'Ver la bitácora',
                'sucursales.ver'       => 'Ver sucursales',
                'sucursales.gestionar' => 'Gestionar sucursales',
            ],
        ];
    }

    /** Todos los códigos de permiso conocidos. */
    public static function todos(): array
    {
        $out = [];
        foreach (self::catalogo() as $grupo) {
            $out = array_merge($out, array_keys($grupo));
        }
        return $out;
    }

    /** Permisos por defecto de un rol (los del archivo de configuración). */
    public static function porDefecto(string $rol): array
    {
        return config('Permisos')->roles[$rol] ?? [];
    }

    /** ¿Este rol tiene permisos propios guardados? */
    public static function personalizado(string $rol): bool
    {
        try {
            return (bool) Db::value("SELECT 1 FROM roles_permisos WHERE rol = ? LIMIT 1", [$rol]);
        } catch (\Throwable) {
            return false;
        }
    }

    /** Permisos vigentes de todos los roles: lo guardado en la BD o, si no hay, lo por defecto. */
    public static function matriz(): array
    {
        $roles = config('Permisos')->roles;
        try {
            $guardados = [];
            foreach (Db::all("SELECT rol, permiso FROM roles_permisos") as $f) {
                $guardados[$f['rol']][] = $f['permiso'];
            }
            foreach ($guardados as $rol => $lista) {
                $roles[$rol] = array_values(array_diff($lista, [self::MARCA]));
            }
        } catch (\Throwable) {
            // la tabla aún no existe (falta php spark migrate): se usa lo por defecto
        }
        return $roles;
    }

    /** Guarda los permisos de un rol (solo códigos conocidos). */
    public static function guardar(string $rol, array $permisos): void
    {
        $validos = array_values(array_intersect(self::todos(), $permisos));
        Db::query("DELETE FROM roles_permisos WHERE rol = ?", [$rol]);
        Db::query("INSERT INTO roles_permisos (rol, permiso) VALUES (?, ?)", [$rol, self::MARCA]);
        foreach ($validos as $p) {
            Db::query("INSERT INTO roles_permisos (rol, permiso) VALUES (?, ?)", [$rol, $p]);
        }
    }

    /** Vuelve a los permisos por defecto del rol. */
    public static function restaurar(string $rol): void
    {
        Db::query("DELETE FROM roles_permisos WHERE rol = ?", [$rol]);
    }
}
