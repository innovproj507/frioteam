<?php $this->extend('layouts/app'); ?>
<?php $this->section('content'); ?>
<?php
$puedeGuardar = \App\Libraries\Auth::can('usuarios.gestionar');
$activosSet   = array_flip($activos ?? []);
?>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6" x-data="rolesForm()">

    <!-- Roles -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 h-fit">
        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 px-1">Roles</h3>
        <div class="space-y-1">
            <?php foreach ($roles as $clave => $nombre): ?>
            <a href="/configuracion/roles?rol=<?= esc($clave) ?>"
               class="flex items-center justify-between px-3 py-2 rounded-lg text-sm font-semibold transition <?= $clave === $rol ? 'bg-sky-500 text-white shadow-sm' : 'text-gray-700 hover:bg-gray-100' ?>">
                <span><?= esc($nombre) ?></span>
                <?php if (\App\Libraries\RolesService::personalizado($clave)): ?>
                    <span class="text-[10px] px-1.5 py-0.5 rounded-full <?= $clave === $rol ? 'bg-white/25' : 'bg-amber-100 text-amber-700' ?>" title="Tiene permisos cambiados">editado</span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
            <div class="flex items-center justify-between px-3 py-2 rounded-lg text-sm text-gray-400" title="El administrador siempre tiene todos los permisos">
                <span>Administrador</span><i class="fas fa-lock text-xs"></i>
            </div>
        </div>
    </div>

    <!-- Permisos del rol -->
    <div class="lg:col-span-3">
        <form method="post" action="/configuracion/roles/guardar" id="form-permisos">
            <?= csrf_field() ?>
            <input type="hidden" name="rol" value="<?= esc($rol) ?>">

            <div class="flex flex-wrap items-center justify-between gap-3 bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-3 mb-4">
                <div>
                    <div class="text-lg font-bold text-gray-900">Permisos del rol <?= esc($roles[$rol]) ?></div>
                    <div class="text-xs text-gray-500"><?= $usuarios_rol ?> usuario(s) activo(s) con este rol
                        · <?= $personalizado ? 'permisos cambiados por ti' : 'permisos originales del sistema' ?></div>
                </div>
                <?php if ($puedeGuardar): ?>
                <div class="flex items-center gap-2">
                    <button type="button" @click="todos(true)" class="px-3 py-1.5 text-xs font-semibold text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Marcar todo</button>
                    <button type="button" @click="todos(false)" class="px-3 py-1.5 text-xs font-semibold text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Quitar todo</button>
                    <?php if ($personalizado): ?>
                    <button type="submit" formaction="/configuracion/roles/restaurar" onclick="return confirm('¿Volver a los permisos originales de este rol?')"
                            class="px-3 py-1.5 text-xs font-semibold text-amber-700 border border-amber-200 rounded-lg hover:bg-amber-50">Restaurar originales</button>
                    <?php endif; ?>
                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-sky-500 text-white rounded-lg text-sm font-semibold hover:bg-sky-600 transition shadow-sm">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($catalogo as $grupo => $permisos): ?>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                    <h4 class="text-sm font-bold text-gray-800 mb-2"><?= esc($grupo) ?></h4>
                    <div class="space-y-1.5">
                        <?php foreach ($permisos as $codigo => $etiqueta): ?>
                        <label class="flex items-start gap-2.5 text-sm text-gray-700 cursor-pointer hover:bg-gray-50 rounded-md px-1.5 py-1">
                            <input type="checkbox" name="permisos[]" value="<?= esc($codigo) ?>"
                                   <?= isset($activosSet[$codigo]) ? 'checked' : '' ?> <?= $puedeGuardar ? '' : 'disabled' ?>
                                   class="mt-0.5 rounded border-gray-300 text-sky-500 focus:ring-sky-400">
                            <span><?= esc($etiqueta) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <p class="text-xs text-gray-400 mt-4">Los cambios aplican la próxima vez que cada usuario abra una página. Cada usuario sigue limitado a los depósitos que tenga asignados.</p>
        </form>
    </div>
</div>

<script>
    function rolesForm() {
        return {
            todos(v) { document.querySelectorAll('#form-permisos input[type=checkbox]').forEach(c => { c.checked = v; }); }
        };
    }
</script>

<?php $this->endSection(); ?>
