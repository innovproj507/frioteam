<?php $this->extend('layouts/app'); ?>
<?php $this->section('content'); ?>

<div x-data="{ tab: 'credito' }">

    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-gray-400 mb-3">
        <a href="/clientes" class="hover:text-gray-600 transition-colors">Clientes</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <a href="/clientes/<?= $cliente->cliente_id ?>" class="hover:text-gray-600 transition-colors"><?= esc($cliente->nombre) ?></a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-700 font-medium">Editar</span>
    </div>

    <form action="/clientes/<?= $cliente->cliente_id ?>/editar" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <!-- Action bar -->
        <div class="flex items-center justify-between bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-3 mb-4">
            <div class="flex gap-2">
                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-sky-500 text-white rounded-lg text-sm font-semibold hover:bg-sky-600 transition shadow-sm">
                    <i class="fas fa-save"></i> Guardar
                </button>
                <a href="/clientes/<?= $cliente->cliente_id ?>" class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                    Cancelar
                </a>
            </div>
            <span class="px-3 py-1.5 rounded-full text-xs font-bold text-white <?= $cliente->estado === 'activo' ? 'bg-emerald-500' : 'bg-slate-400' ?>">
                <?= ucfirst($cliente->estado) ?>
            </span>
        </div>

        <!-- Document card -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-3xl font-bold text-gray-900 mb-6"><?= esc($cliente->nombre) ?></h2>

                <!-- Foto / logo -->
                <div class="flex items-center gap-4 mb-5">
                    <div id="foto-prev" class="w-20 h-20 rounded-2xl bg-sky-50 border border-sky-100 text-sky-300 flex items-center justify-center text-3xl overflow-hidden shrink-0">
                        <?php if (!empty($cliente->foto)): ?><img src="/assets/uploads/<?= esc($cliente->foto, 'attr') ?>" alt="" class="w-full h-full object-cover"><?php else: ?><i class="fas fa-user"></i><?php endif; ?>
                    </div>
                    <div class="text-sm">
                        <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-sky-50 text-sky-600 font-semibold cursor-pointer hover:bg-sky-100 transition">
                            <i class="fas fa-camera"></i> <span id="foto-lbl">Foto</span>
                            <input type="file" name="foto" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" onchange="fotoElegida(this)">
                        </label>
                        <?php if (!empty($cliente->foto)): ?>
                        <label class="inline-flex items-center gap-1 ml-3 text-xs text-gray-400 cursor-pointer"><input type="checkbox" name="quitar_foto" value="1"> Quitar foto</label>
                        <?php endif; ?>
                        <p id="foto-nombre" class="text-xs font-semibold text-sky-600 mt-1 hidden"></p>
                        <p class="text-xs text-gray-400 mt-1"><span id="foto-ayuda">Foto de la persona</span> · JPG, PNG o WEBP, hasta 3 MB. Es opcional.</p>
                    </div>
                </div>

                <script>
                function fotoElegida(inp) {
                    var f = inp.files && inp.files[0];
                    var n = document.getElementById('foto-nombre');
                    if (!f) { n.classList.add('hidden'); return; }
                    n.textContent = f.name;
                    n.classList.remove('hidden');
                    var lector = new FileReader();
                    lector.onload = function (e) {
                        var p = document.getElementById('foto-prev');
                        p.innerHTML = '';
                        var i = document.createElement('img');
                        i.className = 'w-full h-full object-cover';
                        i.src = e.target.result;
                        p.appendChild(i);
                    };
                    lector.readAsDataURL(f);
                }
                document.addEventListener('DOMContentLoaded', function () {
                    var sel = document.querySelector('select[name="tipo"]');
                    function ajustar() {
                        var emp = sel && sel.value === 'juridico';
                        document.getElementById('foto-lbl').textContent = emp ? 'Logo' : 'Foto';
                        document.getElementById('foto-ayuda').textContent = emp ? 'Logo de la empresa' : 'Foto de la persona';
                    }
                    if (sel) { sel.addEventListener('change', ajustar); ajustar(); }
                });
                </script>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-16">
                    <!-- Columna izquierda -->
                    <div>
                        <div class="flex items-baseline gap-4 py-2">
                            <label class="text-sm font-semibold text-gray-600 w-40 shrink-0">Código *</label>
                            <input type="text" name="codigo" value="<?= esc($cliente->codigo ?: ($nuevo_codigo ?? '')) ?>" placeholder="Se genera automático" required
                                   class="flex-1 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
                        </div>
                        <div class="flex items-baseline gap-4 py-2">
                            <label class="text-sm font-semibold text-gray-600 w-40 shrink-0">Nombre *</label>
                            <input type="text" name="nombre" value="<?= esc($cliente->nombre) ?>" required
                                   class="flex-1 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
                        </div>
                        <div class="flex items-baseline gap-4 py-2">
                            <label class="text-sm font-semibold text-gray-600 w-40 shrink-0">Tipo</label>
                            <select name="tipo" class="flex-1 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none appearance-none">
                                <option value="natural" <?= $cliente->tipo === 'natural' ? 'selected' : '' ?>>Natural</option>
                                <option value="juridico" <?= $cliente->tipo === 'juridico' ? 'selected' : '' ?>>Jurídico</option>
                            </select>
                        </div>
                        <div class="flex items-baseline gap-4 py-2">
                            <label class="text-sm font-semibold text-gray-600 w-40 shrink-0">RUC / Cédula</label>
                            <input type="text" name="ruc" value="<?= esc($cliente->ruc) ?>"
                                   class="flex-1 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
                        </div>
                        <div class="flex items-baseline gap-4 py-2">
                            <label class="text-sm font-semibold text-gray-600 w-40 shrink-0">DV</label>
                            <input type="text" name="dv" value="<?= esc($cliente->dv) ?>"
                                   class="flex-1 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
                        </div>
                    </div>

                    <!-- Columna derecha -->
                    <div>
                        <div class="flex items-baseline gap-4 py-2">
                            <label class="text-sm font-semibold text-gray-600 w-40 shrink-0">Teléfono</label>
                            <input type="text" name="telefono" value="<?= esc($cliente->telefono) ?>"
                                   class="flex-1 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
                        </div>
                        <div class="flex items-baseline gap-4 py-2">
                            <label class="text-sm font-semibold text-gray-600 w-40 shrink-0">Email</label>
                            <input type="email" name="email" value="<?= esc($cliente->email) ?>"
                                   class="flex-1 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
                        </div>
                        <div class="flex items-baseline gap-4 py-2">
                            <label class="text-sm font-semibold text-gray-600 w-40 shrink-0">Aplica ITBMS</label>
                            <select name="itbms" class="flex-1 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none appearance-none">
                                <option value="SI" <?= ($cliente->itbms > 0) ? 'selected' : '' ?>>Sí</option>
                                <option value="NO" <?= ($cliente->itbms <= 0) ? 'selected' : '' ?>>No (Exento)</option>
                            </select>
                        </div>
                        <div class="flex items-baseline gap-4 py-2">
                            <label class="text-sm font-semibold text-gray-600 w-40 shrink-0">Estado</label>
                            <select name="estado" class="flex-1 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none appearance-none">
                                <option value="activo" <?= $cliente->estado === 'activo' ? 'selected' : '' ?>>Activo</option>
                                <option value="inactivo" <?= $cliente->estado === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <!-- Dirección — full width -->
                    <div class="lg:col-span-2 flex items-baseline gap-4 py-2">
                        <label class="text-sm font-semibold text-gray-600 w-40 shrink-0">Dirección</label>
                        <textarea name="direccion" rows="2"
                                  class="flex-1 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none resize-none"><?= esc($cliente->direccion) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-100 px-6 flex gap-6">
                <button type="button" @click="tab = 'credito'"
                    :class="tab === 'credito' ? 'border-b-2 border-sky-500 text-sky-600' : 'text-gray-400 hover:text-gray-600'"
                    class="py-3 text-sm font-semibold -mb-px transition-colors">
                    Crédito
                </button>
            </div>

            <!-- Tab: Crédito -->
            <div x-show="tab === 'credito'" class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-16">
                    <div>
                        <div class="flex items-baseline gap-4 py-2">
                            <label class="text-sm font-semibold text-gray-600 w-40 shrink-0">Límite de Crédito ($)</label>
                            <input type="number" name="limite_credito" value="<?= $cliente->limite_credito ?>" step="0.01"
                                   class="flex-1 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
                        </div>
                        <div class="flex items-baseline gap-4 py-2">
                            <label class="text-sm font-semibold text-gray-600 w-40 shrink-0">Días de Crédito</label>
                            <input type="number" name="dias_credito" value="<?= $cliente->dias_credito ?>" min="0"
                                   class="flex-1 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php $this->endSection(); ?>
