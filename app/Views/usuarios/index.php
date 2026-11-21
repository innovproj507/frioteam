<?php use App\Libraries\Auth; $this->extend('layouts/app'); ?>
<?php $this->section('content'); ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
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
        <?php if (Auth::can('usuarios.gestionar')): ?>
        <a href="/usuarios/nuevo"
           class="inline-flex items-center gap-2 px-4 py-2 bg-sky-500 hover:bg-sky-600 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
            <i class="fas fa-user-plus"></i> Nuevo Usuario
        </a>
        <?php endif; ?>
        </div>
    </div>

    <?php
    $rolColors = [
        'superadmin' => 'bg-red-500 text-white',
        'gerente'    => 'bg-blue-500 text-white',
        'supervisor' => 'bg-emerald-500 text-white',
        'auditor'    => 'bg-amber-500 text-white',
        'cajero'     => 'bg-slate-500 text-white',
        'vendedor'   => 'bg-teal-500 text-white',
        'encargado'  => 'bg-violet-500 text-white',
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
    ?>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-bold text-slate-500 uppercase">Usuario</th>
                    <th class="px-5 py-3 text-left text-xs font-bold text-slate-500 uppercase">Email</th>
                    <th class="px-5 py-3 text-center text-xs font-bold text-slate-500 uppercase">Rol actual</th>
                    <th class="px-5 py-3 text-center text-xs font-bold text-slate-500 uppercase">Estado</th>
                    <?php if (Auth::can('usuarios.gestionar')): ?>
                    <th class="px-5 py-3 text-center text-xs font-bold text-slate-500 uppercase">Cambiar rol</th>
                    <th class="px-5 py-3 text-center text-xs font-bold text-slate-500 uppercase">Depósitos</th>
                    <th class="px-5 py-3 text-center text-xs font-bold text-slate-500 uppercase">Acceso</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                <?php foreach ($usuarios as $u): ?>
                <tr class="hover:bg-slate-50/50 <?= !$u['is_active'] ? 'opacity-50' : '' ?>">
                    <td class="px-5 py-3">
                        <div class="font-medium text-slate-800"><?= esc($u['username']) ?></div>
                        <div class="text-xs text-slate-400"><?= esc(trim($u['first_name'] . ' ' . $u['last_name'])) ?></div>
                    </td>
                    <td class="px-5 py-3 text-slate-600"><?= esc($u['email'] ?? '—') ?></td>
                    <td class="px-5 py-3 text-center">
                        <span class="px-3 py-1 rounded-full text-xs font-bold <?= $rolColors[$u['rol']] ?? 'bg-slate-500 text-white' ?>">
                            <?= $rolLabels[$u['rol']] ?? ucfirst($u['rol']) ?>
                        </span>
                    </td>
                    <td class="px-5 py-3 text-center">
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold text-white <?= $u['is_active'] ? 'bg-emerald-500' : 'bg-red-500' ?>">
                            <?= $u['is_active'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <?php if (Auth::can('usuarios.gestionar')): ?>
                    <td class="px-5 py-3 text-center">
                        <?php if ($u['id'] != Auth::id()): ?>
                        <form method="POST" action="/usuarios/cambiar-rol" class="flex items-center justify-center gap-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <select name="rol" class="text-xs border rounded-lg px-2 py-1 bg-slate-50">
                                <?php foreach ($roles as $r): ?>
                                <?php if ($r === 'superadmin' && Auth::rol() !== 'superadmin') continue; ?>
                                <option value="<?= $r ?>" <?= $u['rol'] === $r ? 'selected' : '' ?>>
                                    <?= $rolLabels[$r] ?? ucfirst($r) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="text-xs px-2 py-1 bg-blue-500 text-white rounded-lg hover:bg-blue-600 font-semibold">
                                Aplicar
                            </button>
                        </form>
                        <?php else: ?>
                        <span class="text-xs text-slate-400 italic">— tú mismo —</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-3 text-center">
                        <div class="flex flex-col items-center gap-1">
                            <a href="/usuarios/<?= $u['id'] ?>/permisos"
                               class="inline-flex items-center gap-1 text-xs px-3 py-1 bg-teal-500 text-white rounded-lg hover:bg-teal-600 font-semibold">
                                <i class="fas fa-warehouse"></i> Depósitos
                            </a>
                            <?php if (!empty($u['totp_habilitado']) && $u['id'] != Auth::id()): ?>
                            <form method="POST" action="/auth/2fa/desactivar"
                                  onsubmit="return confirm('¿Desactivar 2FA de este usuario?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="text-xs px-3 py-1 bg-amber-500 text-white hover:bg-amber-600 rounded-lg font-semibold">
                                    <i class="fas fa-shield-slash"></i> Reset 2FA
                                </button>
                            </form>
                            <?php elseif (empty($u['totp_habilitado'])): ?>
                            <span class="text-xs text-slate-300 italic">2FA inactivo</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <a href="/usuarios/<?= $u['id'] ?>/editar"
                               class="text-xs px-3 py-1 rounded-lg font-semibold bg-amber-500 text-white hover:bg-amber-600">
                                <i class="fas fa-pen mr-1"></i>Editar
                            </a>
                            <?php if ($u['id'] != Auth::id()): ?>
                            <form method="POST" action="/usuarios/toggle-activo" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit"
                                        class="text-xs px-3 py-1 rounded-lg font-semibold text-white <?= $u['is_active'] ? 'bg-red-500 hover:bg-red-600' : 'bg-emerald-500 hover:bg-emerald-600' ?>"
                                        onclick="return confirm('¿Confirmar cambio de estado?')">
                                    <?= $u['is_active'] ? 'Desactivar' : 'Activar' ?>
                                </button>
                            </form>
                            <form method="POST" action="/usuarios/<?= $u['id'] ?>/eliminar" class="inline"
                                  onsubmit="return confirm('¿Eliminar usuario <?= esc($u['username']) ?>? Esta acción no se puede deshacer.')">
                                <?= csrf_field() ?>
                                <button type="submit" class="w-7 h-7 rounded-lg font-semibold bg-slate-400 text-white hover:bg-red-500 flex items-center justify-center">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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
