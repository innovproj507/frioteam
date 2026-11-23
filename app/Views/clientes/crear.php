<?php $this->extend('layouts/app'); ?>
<?php $this->section('content'); ?>

<div x-data="{ tab: 'info' }">

    <!-- Breadcrumb -->
    <div class="flex items-center gap-2 text-sm text-gray-400 mb-3">
        <a href="/clientes" class="hover:text-gray-600 transition-colors">Clientes</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-700 font-medium">Nuevo Cliente</span>
    </div>

    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <!-- Action bar -->
        <div class="flex items-center justify-between bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-3 mb-4">
            <div class="flex gap-2">
                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-sky-500 text-white rounded-lg text-sm font-semibold hover:bg-sky-600 transition shadow-sm">
                    <i class="fas fa-save"></i> Guardar
                </button>
                <a href="/clientes" class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                    Cancelar
                </a>
            </div>
        </div>

        <!-- Document card -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-3xl font-bold text-gray-900 mb-6">Nuevo Cliente</h2>

                <!-- Foto / logo -->
                <div class="flex items-center gap-4 mb-5">
                    <div id="foto-prev" class="w-20 h-20 rounded-2xl bg-sky-50 border border-sky-100 text-sky-300 flex items-center justify-center text-3xl overflow-hidden shrink-0">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="text-sm">
                        <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-sky-50 text-sky-600 font-semibold cursor-pointer hover:bg-sky-100 transition">
                            <i class="fas fa-camera"></i> <span id="foto-lbl">Foto</span>
                            <input type="file" name="foto" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" onchange="fotoElegida(this)">
                        </label>
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
                            <label class="text-sm font-semibold text-gray-600 w-40 shrink-0">Código</label>
                            <input type="text" name="codigo" value="<?= esc($nuevo_codigo ?? '') ?>" placeholder="Dejar vacío para generar automático"
                                   class="flex-1 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
                        </div>
                        <div class="flex items-baseline gap-4 py-2">
                            <label class="text-sm font-semibold text-gray-600 w-40 shrink-0">Nombre / Razón Social *</label>
                            <input type="text" name="nombre" value="<?= esc($cliente->nombre ?? '') ?>" required placeholder="Nombre del cliente"
                                   class="flex-1 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
                        </div>
                        <div class="flex items-baseline gap-4 py-2">
                            <label class="text-sm font-semibold text-gray-600 w-40 shrink-0">Tipo *</label>
                            <select name="tipo" required class="flex-1 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none appearance-none">
                                <option value="natural" <?= ($cliente->tipo ?? '') == 'natural' ? 'selected' : '' ?>>Persona Natural</option>
                                <option value="juridico" <?= ($cliente->tipo ?? '') == 'juridico' ? 'selected' : '' ?>>Persona Jurídica</option>
                            </select>
                        </div>
                        <div class="flex items-baseline gap-4 py-2">
                            <label class="text-sm font-semibold text-gray-600 w-40 shrink-0">RUC / Cédula <span class="text-red-500">*</span></label>
                            <input type="text" name="ruc" required" value="<?= esc($cliente->ruc ?? '') ?>" placeholder="Ej: 8-000-000"
                                   class="flex-1 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
                        </div>
                        <div class="flex items-baseline gap-4 py-2">
                            <label class="text-sm font-semibold text-gray-600 w-40 shrink-0">DV</label>
                            <input type="text" name="dv" value="<?= esc($cliente->dv ?? '') ?>" placeholder="00"
                                   class="flex-1 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
                        </div>
                    </div>

                    <!-- Columna derecha -->
                    <div>
                        <div class="flex items-baseline gap-4 py-2">
                            <label class="text-sm font-semibold text-gray-600 w-40 shrink-0">Teléfono</label>
                            <input type="text" name="telefono" value="<?= esc($cliente->telefono ?? '') ?>" placeholder="+507 000-0000"
                                   class="flex-1 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
                        </div>
                        <div class="flex items-baseline gap-4 py-2">
                            <label class="text-sm font-semibold text-gray-600 w-40 shrink-0">Correo Electrónico</label>
                            <input type="email" name="email" value="<?= esc($cliente->email ?? '') ?>" placeholder="cliente@ejemplo.com"
                                   class="flex-1 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
                        </div>
                        <div class="flex items-baseline gap-4 py-2">
                            <label class="text-sm font-semibold text-gray-600 w-40 shrink-0">Aplica ITBMS</label>
                            <select name="itbms" class="flex-1 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none appearance-none">
                                <option value="SI" <?= (!isset($cliente->itbms) || $cliente->itbms > 0) ? 'selected' : '' ?>>Sí</option>
                                <option value="NO" <?= (isset($cliente->itbms) && $cliente->itbms <= 0) ? 'selected' : '' ?>>No (Exento)</option>
                            </select>
                        </div>
                        <div class="flex items-baseline gap-4 py-2">
                            <label class="text-sm font-semibold text-gray-600 w-40 shrink-0">Estado</label>
                            <select name="estado" class="flex-1 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none appearance-none">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <!-- Dirección — full width -->
                    <div class="lg:col-span-2 flex items-baseline gap-4 py-2">
                        <label class="text-sm font-semibold text-gray-600 w-40 shrink-0">Dirección</label>
                        <textarea name="direccion" rows="2" placeholder="Dirección completa..."
                                  class="flex-1 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none resize-none"><?= esc($cliente->direccion ?? '') ?></textarea>
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
                            <input type="number" name="limite_credito" value="<?= esc($cliente->limite_credito ?? '0.00') ?>" step="0.01"
                                   class="flex-1 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
                        </div>
                        <div class="flex items-baseline gap-4 py-2">
                            <label class="text-sm font-semibold text-gray-600 w-40 shrink-0">Días de Crédito</label>
                            <input type="number" name="dias_credito" value="<?= esc($cliente->dias_credito ?? '0') ?>" min="0"
                                   class="flex-1 py-1.5 px-0 text-sm bg-transparent border-0 border-b border-gray-200 focus:border-sky-400 focus:ring-0 outline-none">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php $this->endSection(); ?>
