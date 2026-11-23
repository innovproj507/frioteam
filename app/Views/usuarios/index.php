<?php use App\Libraries\Auth; $this->extend('layouts/app'); ?>
<?php $this->section('content'); ?>

<?php
$puedeGestionar = Auth::can('usuarios.gestionar');
$rolColors = [
    'superadmin' => 'bg-red-500 text-white',
    'gerente'    => 'bg-blue-500 text-white',
    'supervisor' => 'bg-emerald-500 text-white',
    'auditor'    => 'bg-amber-500 text-white',
    'cajero'     => 'bg-slate-500 text-white',
    'vendedor'   => 'bg-teal-500 text-white',
    'encargado'  => 'bg-violet-500 text-white',
];
// Colores suaves para el selector de rol (el color ya dice el rol de un vistazo)
$rolSoft = [
    'superadmin' => 'bg-red-50 text-red-700 border-red-200',
    'gerente'    => 'bg-blue-50 text-blue-700 border-blue-200',
    'supervisor' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
    'auditor'    => 'bg-amber-50 text-amber-700 border-amber-200',
    'cajero'     => 'bg-slate-100 text-slate-700 border-slate-200',
    'vendedor'   => 'bg-teal-50 text-teal-700 border-teal-200',
    'encargado'  => 'bg-violet-50 text-violet-700 border-violet-200',
];
$rolLabels = [
    'superadmin' => 'Super Admin',
    'gerente'    => 'Gerente',
    'supervisor' => 'Supervisor',
    'auditor'    => 'Auditor',
    'cajero'     => 'Cajero',
    'vendedor'   => 'Vendedor',
    'encargado'  => 'Encargado de tienda',
];
$total     = count($usuarios);
$activos   = count(array_filter($usuarios, fn ($u) => !empty($u['is_active'])));
$inactivos = $total - $activos;
$btn = 'inline-flex w-8 h-8 items-center justify-center rounded-lg text-sm transition-colors';
$filasFiltro = [];
foreach ($usuarios as $u) {
    $filasFiltro[] = [
        'a' => !empty($u['is_active']) ? 1 : 0,
        'b' => mb_strtolower($u['username'] . ' ' . trim($u['first_name'] . ' ' . $u['last_name']) . ' ' . ($u['email'] ?? '') . ' ' . ($rolLabels[$u['rol']] ?? ucfirst($u['rol']))),
    ];
}
?>

<div class="space-y-5">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Usuarios</h2>
            <p class="text-slate-500 text-sm">Gestión de roles y accesos al sistema</p>
        </div>
        <div class="flex items-center gap-2">
        <?php if (Auth::can('usuarios.ver')): ?>
        <a href="/configuracion/roles" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-sky-200 text-sky-700 text-sm font-semibold rounded-xl hover:bg-sky-50 transition-colors">
            <i class="fas fa-user-shield"></i> Roles y Permisos
        </a>
        <?php endif; ?>
        <?php if ($puedeGestionar): ?>
        <a href="/usuarios/nuevo"
           class="inline-flex items-center gap-2 px-4 py-2 bg-sky-500 hover:bg-sky-600 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
            <i class="fas fa-user-plus"></i> Nuevo Usuario
        </a>
        <?php endif; ?>
        </div>
    </div>

    <!-- Buscador y filtro (se filtra en el momento, sin recargar) -->
    <div class="space-y-3"
         x-data="{ q: '', f: 'todos', filas: <?= esc(json_encode($filasFiltro, JSON_UNESCAPED_UNICODE), 'attr') ?>,
                   coincide(r) { return (this.f === 'todos' || (this.f === 'activos' && r.a === 1) || (this.f === 'inactivos' && r.a === 0)) && r.b.includes(this.q.trim().toLowerCase()); },
                   get n() { return this.filas.filter(r => this.coincide(r)).length; } }">
        <div class="flex items-center gap-3 flex-wrap">
            <div class="relative w-full sm:w-80">
                <i class="fas fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="search" x-model="q" placeholder="Buscar usuario, nombre, correo o rol…"
                       class="w-full pl-9 pr-3 py-2 text-sm bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-transparent">
            </div>
            <div class="inline-flex bg-white border border-slate-200 rounded-xl p-0.5 text-xs font-semibold">
                <button type="button" @click="f = 'todos'" :class="f === 'todos' ? 'bg-slate-800 text-white' : 'text-slate-500 hover:text-slate-800'" class="px-3 py-1.5 rounded-lg transition-colors">
                    Todos <span class="opacity-70"><?= $total ?></span></button>
                <button type="button" @click="f = 'activos'" :class="f === 'activos' ? 'bg-emerald-500 text-white' : 'text-slate-500 hover:text-emerald-600'" class="px-3 py-1.5 rounded-lg transition-colors">
                    <i class="fas fa-circle-check"></i> Activos <span class="opacity-80"><?= $activos ?></span></button>
                <button type="button" @click="f = 'inactivos'" :class="f === 'inactivos' ? 'bg-red-500 text-white' : 'text-slate-500 hover:text-red-500'" class="px-3 py-1.5 rounded-lg transition-colors">
                    <i class="fas fa-circle-xmark"></i> Inactivos <span class="opacity-80"><?= $inactivos ?></span></button>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Usuario</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase">Rol</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-slate-500 uppercase"><i class="fas fa-toggle-on mr-1"></i>Estado</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-slate-500 uppercase" title="Verificación en dos pasos"><i class="fas fa-shield-halved mr-1"></i>2FA</th>
                        <?php if ($puedeGestionar): ?>
                        <th class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach ($usuarios as $i => $u):
                        $esYo     = ($u['id'] == Auth::id());
                        $activo   = !empty($u['is_active']);
                        $nombre   = trim($u['first_name'] . ' ' . $u['last_name']);
                        $inicial  = mb_strtoupper(mb_substr($u['username'], 0, 1));
                        $rolTxt   = $rolLabels[$u['rol']] ?? ucfirst($u['rol']);
                    ?>
                    <tr class="hover:bg-slate-50/60" x-show="coincide(filas[<?= (int) $i ?>])">
                        <td class="px-4 py-2.5 <?= !$activo ? 'opacity-50' : '' ?>">
                            <div class="flex items-center gap-3">
                                <span class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold shrink-0 <?= $rolColors[$u['rol']] ?? 'bg-slate-500 text-white' ?>"><?= esc($inicial) ?></span>
                                <div class="min-w-0">
                                    <div class="font-semibold text-slate-800 leading-tight flex items-center gap-1.5">
                                        <?= esc($u['username']) ?>
                                        <?php if ($esYo): ?><i class="fas fa-user text-sky-500 text-[10px]" title="Eres tú" aria-label="Eres tú"></i><?php endif; ?>
                                    </div>
                                    <div class="text-xs text-slate-400 truncate"><?= esc($nombre) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-2.5 text-slate-600 <?= !$activo ? 'opacity-50' : '' ?>"><?= esc($u['email'] ?? '—') ?></td>

                        <td class="px-4 py-2.5 <?= !$activo ? 'opacity-60' : '' ?>">
                            <?php if ($puedeGestionar && !$esYo): ?>
                            <form method="POST" action="/usuarios/cambiar-rol" class="flex items-center gap-1.5"
                                  x-data="{ orig: '<?= esc($u['rol']) ?>', val: '<?= esc($u['rol']) ?>' }">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                <select name="rol" x-model="val" title="Cambiar el rol"
                                        class="text-xs font-semibold border rounded-lg px-2 py-1.5 <?= $rolSoft[$u['rol']] ?? 'bg-slate-100 text-slate-700 border-slate-200' ?>">
                                    <?php foreach ($roles as $r): ?>
                                    <?php if ($r === 'superadmin' && Auth::rol() !== 'superadmin') continue; ?>
                                    <option value="<?= esc($r) ?>" <?= $u['rol'] === $r ? 'selected' : '' ?>><?= $rolLabels[$r] ?? ucfirst($r) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <!-- El visto bueno solo aparece cuando eliges otro rol -->
                                <button type="submit" x-show="val !== orig" x-cloak title="Aplicar el cambio de rol" aria-label="Aplicar el cambio de rol"
                                        class="<?= $btn ?> bg-blue-500 text-white hover:bg-blue-600 shadow-sm"><i class="fas fa-check"></i></button>
                            </form>
                            <?php else: ?>
                            <span class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-semibold border <?= $rolSoft[$u['rol']] ?? 'bg-slate-100 text-slate-700 border-slate-200' ?>"><?= esc($rolTxt) ?></span>
                            <?php endif; ?>
                        </td>

                        <td class="px-4 py-2.5 text-center">
                            <?php if ($activo): ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap bg-emerald-100 text-emerald-600" title="Activo"><i class="fas fa-check text-[10px]"></i> Activo</span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap bg-red-100 text-red-500" title="Inactivo"><i class="fas fa-xmark text-[10px]"></i> Inactivo</span>
                            <?php endif; ?>
                        </td>

                        <td class="px-4 py-2.5 text-center">
                            <?php if (!empty($u['totp_habilitado'])): ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap bg-emerald-100 text-emerald-700" title="Verificación en dos pasos activa"><i class="fas fa-shield-halved text-[10px]"></i> Activo</span>
                                <?php if ($puedeGestionar && !$esYo): ?>
                                <form method="POST" action="/auth/2fa/desactivar" class="inline align-middle ml-1"
                                      onsubmit="return confirm('¿Desactivar 2FA de este usuario?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                    <button type="submit" class="<?= $btn ?> bg-slate-100 text-slate-500 hover:bg-amber-500 hover:text-white"
                                            title="Resetear el 2FA de este usuario" aria-label="Resetear el 2FA de este usuario"><i class="fas fa-unlock-keyhole"></i></button>
                                </form>
                                <?php endif; ?>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold whitespace-nowrap bg-gray-100 text-gray-500" title="Sin verificación en dos pasos"><i class="fas fa-shield-halved text-[10px]"></i> Inactivo</span>
                            <?php endif; ?>
                        </td>

                        <?php if ($puedeGestionar): ?>
                        <td class="px-4 py-2.5">
                            <div class="flex items-center justify-end gap-1.5">
                                <a href="/usuarios/<?= (int) $u['id'] ?>/permisos" class="<?= $btn ?> bg-teal-50 text-teal-600 hover:bg-teal-500 hover:text-white"
                                   title="Depósitos y sucursales" aria-label="Depósitos y sucursales"><i class="fas fa-warehouse"></i></a>
                                <a href="/usuarios/<?= (int) $u['id'] ?>/editar" class="<?= $btn ?> bg-amber-50 text-amber-600 hover:bg-amber-500 hover:text-white"
                                   title="Editar usuario" aria-label="Editar usuario"><i class="fas fa-pen"></i></a>
                                <?php if (!$esYo): ?>
                                <form method="POST" action="/usuarios/toggle-activo" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                    <button type="submit" onclick="return confirm('¿Confirmar cambio de estado?')"
                                            class="<?= $btn ?> <?= $activo ? 'bg-red-50 text-red-500 hover:bg-red-500 hover:text-white' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white' ?>"
                                            title="<?= $activo ? 'Desactivar usuario' : 'Activar usuario' ?>" aria-label="<?= $activo ? 'Desactivar usuario' : 'Activar usuario' ?>">
                                        <i class="fas <?= $activo ? 'fa-user-slash' : 'fa-user-check' ?>"></i></button>
                                </form>
                                <form method="POST" action="/usuarios/<?= (int) $u['id'] ?>/eliminar" class="inline"
                                      onsubmit="return confirm('¿Eliminar usuario <?= esc($u['username'], 'js') ?>? Esta acción no se puede deshacer.')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="<?= $btn ?> bg-slate-100 text-slate-400 hover:bg-red-500 hover:text-white"
                                            title="Eliminar usuario" aria-label="Eliminar usuario"><i class="fas fa-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    <tr x-show="n === 0" x-cloak>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-400"><i class="fas fa-user-slash text-2xl mb-2 block"></i> Ningún usuario coincide con la búsqueda</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Leyenda de íconos -->
        <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 text-xs text-slate-400 px-1">
            <span><i class="fas fa-check text-emerald-500"></i> Activo</span>
            <span><i class="fas fa-xmark text-red-500"></i> Inactivo</span>
            <span><i class="fas fa-shield-halved text-emerald-500"></i> 2FA activo</span>
            <span><i class="fas fa-warehouse text-teal-500"></i> Depósitos</span>
            <span><i class="fas fa-pen text-amber-500"></i> Editar</span>
            <span><i class="fas fa-user-slash text-red-400"></i> Desactivar</span>
            <span><i class="fas fa-user-check text-emerald-500"></i> Activar</span>
            <span><i class="fas fa-trash text-slate-400"></i> Eliminar</span>
        </div>
    </div>

    <!-- Matriz de permisos por rol (lo que está vigente ahora, incluye lo que cambies en Roles y Permisos) -->
    <?php
    $matrizRoles = \App\Libraries\RolesService::matriz();
    $colRoles    = ['vendedor' => 'text-sky-600', 'cajero' => 'text-gray-500', 'encargado' => 'text-teal-600', 'supervisor' => 'text-green-600', 'gerente' => 'text-blue-600', 'auditor' => 'text-amber-600'];
    ?>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
        <div class="flex items-center justify-between mb-4 gap-3 flex-wrap">
            <h4 class="font-semibold text-slate-700">Matriz de permisos</h4>
            <?php if (Auth::can('usuarios.ver')): ?>
            <a href="/configuracion/roles" class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-sky-200 text-sky-700 text-xs font-semibold rounded-lg hover:bg-sky-50 transition-colors">
                <i class="fas fa-user-shield"></i> Cambiar permisos de los roles
            </a>
            <?php endif; ?>
        </div>
        <div class="overflow-x-auto">
        <table class="text-xs w-full">
            <thead>
                <tr class="border-b">
                    <th class="py-2 text-left font-bold text-slate-500 pr-6">Permiso</th>
                    <?php foreach ($colRoles as $r => $cls): ?>
                    <th class="py-2 text-center px-3 <?= $cls ?>"><?= esc(\App\Libraries\RolesService::ROLES[$r]) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach (\App\Libraries\RolesService::catalogo() as $grupo => $permisos): ?>
                <tr><td colspan="<?= count($colRoles) + 1 ?>" class="pt-3 pb-1 text-[11px] font-bold uppercase tracking-wider text-slate-400"><?= esc($grupo) ?></td></tr>
                <?php foreach ($permisos as $codigo => $etiqueta): ?>
                <tr>
                    <td class="py-1.5 pr-6 font-medium text-slate-700"><?= esc($etiqueta) ?></td>
                    <?php foreach ($colRoles as $r => $cls): ?>
                    <td class="py-1.5 text-center px-3">
                        <?= in_array($codigo, $matrizRoles[$r] ?? [], true) ? '<span class="text-green-600 font-bold">✓</span>' : '<span class="text-slate-200">—</span>' ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php $this->endSection(); ?>
