<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Ficha de Mi Empresa') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">

                @if (session()->has('message'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                         class="mb-4 px-4 py-3 bg-green-100 border border-green-400 text-green-700 rounded-md dark:bg-green-700 dark:text-green-100 dark:border-green-600">
                        {{ session('message') }}
                    </div>
                @endif
                @if (session()->has('error'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                        class="mb-4 px-4 py-3 bg-red-100 border border-red-400 text-red-700 rounded-md dark:bg-red-700 dark:text-red-100 dark:border-red-600">
                        {{ session('error') }}
                    </div>
                @endif

                <form wire:submit.prevent="updateFicha">
                    <div class="space-y-8">

                        {{-- Sección Datos de la Empresa (Algunos informativos) --}}
                        <fieldset class="border border-gray-300 dark:border-gray-600 p-4 rounded-md">
                            <legend class="text-lg font-semibold text-gray-700 dark:text-gray-300 px-2">Datos de la Empresa</legend>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Razón Social</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 p-2 bg-gray-100 dark:bg-gray-700 rounded-md">{{ $razon_social_info }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">RUT Empresa</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 p-2 bg-gray-100 dark:bg-gray-700 rounded-md">{{ $rut_contratista_info }}</p>
                                </div>
                                <div>
                                    <label for="nombre_fantasia" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre de Fantasía</label>
                                    <input type="text" wire:model.lazy="nombre_fantasia" id="nombre_fantasia" class="input-field @error('nombre_fantasia') input-error @enderror">
                                    @error('nombre_fantasia') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="email_empresa_contratista" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email Empresa <span class="text-red-500">*</span></label>
                                    <input type="email" wire:model.lazy="email_empresa_contratista" id="email_empresa_contratista" class="input-field @error('email_empresa_contratista') input-error @enderror">
                                    @error('email_empresa_contratista') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="telefono_empresa" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Teléfono Empresa</label>
                                    <input type="tel" wire:model.lazy="telefono_empresa" id="telefono_empresa" class="input-field @error('telefono_empresa') input-error @enderror">
                                    @error('telefono_empresa') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                 <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo Inscripción</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 p-2 bg-gray-100 dark:bg-gray-700 rounded-md">{{ $tipo_inscripcion_info }}</p>
                                </div>
                            </div>
                        </fieldset>

                        {{-- Sección Dirección --}}
                        <fieldset class="border border-gray-300 dark:border-gray-600 p-4 rounded-md">
                            <legend class="text-lg font-semibold text-gray-700 dark:text-gray-300 px-2">Dirección</legend>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                                <div>
                                    <label for="direccion_calle" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Calle <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model.lazy="direccion_calle" id="direccion_calle" class="input-field @error('direccion_calle') input-error @enderror">
                                    @error('direccion_calle') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="direccion_numero" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Número</label>
                                    <input type="text" wire:model.lazy="direccion_numero" id="direccion_numero" class="input-field @error('direccion_numero') input-error @enderror">
                                    @error('direccion_numero') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="selected_region_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Región <span class="text-red-500">*</span></label>
                                    <select wire:model.live="selected_region_id" id="selected_region_id" class="input-field @error('selected_region_id') input-error @enderror">
                                        <option value="">Seleccione Región...</option>
                                        @foreach ($regiones as $region)
                                            <option value="{{ $region->id }}">{{ $region->nombre }}</option>
                                        @endforeach
                                    </select>
                                    @error('selected_region_id') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="comuna_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Comuna <span class="text-red-500">*</span></label>
                                    <select wire:model="comuna_id" id="comuna_id" class="input-field @error('comuna_id') input-error @enderror" @if(empty($selected_region_id)) disabled @endif>
                                        <option value="">Seleccione Comuna...</option>
                                        @foreach ($comunasDisponibles as $comuna)
                                            <option value="{{ $comuna->id }}">{{ $comuna->nombre }}</option>
                                        @endforeach
                                    </select>
                                    @error('comuna_id') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </fieldset>

                        {{-- Sección Detalles Adicionales (Informativos) --}}
                        <fieldset class="border border-gray-300 dark:border-gray-600 p-4 rounded-md">
                            <legend class="text-lg font-semibold text-gray-700 dark:text-gray-300 px-2">Detalles Adicionales (Gestionados por ASEM)</legend>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo Empresa Legal</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 p-2 bg-gray-100 dark:bg-gray-700 rounded-md">{{ $tipo_empresa_legal_info ?? 'No asignado' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rubro</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 p-2 bg-gray-100 dark:bg-gray-700 rounded-md">{{ $rubro_info ?? 'No asignado' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rango Trabajadores</label>
                                     <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 p-2 bg-gray-100 dark:bg-gray-700 rounded-md">{{ $rango_cantidad_info ?? 'No asignado' }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mutualidad</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 p-2 bg-gray-100 dark:bg-gray-700 rounded-md">{{ $mutualidad_info ?? 'No asignado' }}</p>
                                </div>
                            </div>
                        </fieldset>

                        {{-- Sección Representante Legal --}}
                        <fieldset class="border border-gray-300 dark:border-gray-600 p-4 rounded-md">
                            <legend class="text-lg font-semibold text-gray-700 dark:text-gray-300 px-2">Representante Legal</legend>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-4">
                                <div>
                                    <label for="rep_legal_nombres" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombres <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model.lazy="rep_legal_nombres" id="rep_legal_nombres" class="input-field @error('rep_legal_nombres') input-error @enderror">
                                    @error('rep_legal_nombres') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="rep_legal_apellido_paterno" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Apellido Paterno <span class="text-red-500">*</span></label>
                                    <input type="text" wire:model.lazy="rep_legal_apellido_paterno" id="rep_legal_apellido_paterno" class="input-field @error('rep_legal_apellido_paterno') input-error @enderror">
                                    @error('rep_legal_apellido_paterno') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="rep_legal_apellido_materno" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Apellido Materno</label>
                                    <input type="text" wire:model.lazy="rep_legal_apellido_materno" id="rep_legal_apellido_materno" class="input-field @error('rep_legal_apellido_materno') input-error @enderror">
                                    @error('rep_legal_apellido_materno') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="rep_legal_rut" class="block text-sm font-medium text-gray-700 dark:text-gray-300">RUT</label>
                                    <input type="text" wire:model.lazy="rep_legal_rut" id="rep_legal_rut" placeholder="Ej: 12345678-9" class="input-field @error('rep_legal_rut') input-error @enderror">
                                    @error('rep_legal_rut') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="rep_legal_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                                    <input type="email" wire:model.lazy="rep_legal_email" id="rep_legal_email" class="input-field @error('rep_legal_email') input-error @enderror">
                                    @error('rep_legal_email') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="rep_legal_telefono" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Teléfono</label>
                                    <input type="tel" wire:model.lazy="rep_legal_telefono" id="rep_legal_telefono" class="input-field @error('rep_legal_telefono') input-error @enderror">
                                    @error('rep_legal_telefono') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </fieldset>

                        {{-- Sección Datos de Usuario Administrador (Mi Perfil) --}}
                        <fieldset class="border border-gray-300 dark:border-gray-600 p-4 rounded-md">
                            <legend class="text-lg font-semibold text-gray-700 dark:text-gray-300 px-2">Mis Datos de Administrador</legend>
                             <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre Usuario</label>
                                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 p-2 bg-gray-100 dark:bg-gray-700 rounded-md">{{ $admin_user_name_actual }}</p>
                                    <small class="text-xs text-gray-500 dark:text-gray-400">Para cambiar su nombre, contacte a ASEM.</small>
                                </div>
                                <div>
                                    <label for="admin_email_actual" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mi Email de Acceso <span class="text-red-500">*</span></label>
                                    <input type="email" wire:model.lazy="admin_email_actual" id="admin_email_actual" class="input-field @error('admin_email_actual') input-error @enderror">
                                    @error('admin_email_actual') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div class="md:col-span-2"><hr class="my-3 dark:border-gray-600"></div>
                                <div>
                                    <label for="admin_current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contraseña Actual (para cambiar)</label>
                                    <input type="password" wire:model.lazy="admin_current_password" id="admin_current_password" class="input-field @error('admin_current_password') input-error @enderror" placeholder="Dejar en blanco si no cambia">
                                    @error('admin_current_password') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="admin_new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nueva Contraseña</label>
                                    <input type="password" wire:model.lazy="admin_new_password" id="admin_new_password" class="input-field @error('admin_new_password') input-error @enderror">
                                    @error('admin_new_password') <span class="error-message">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="admin_new_password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirmar Nueva Contraseña</label>
                                    <input type="password" wire:model.lazy="admin_new_password_confirmation" id="admin_new_password_confirmation" class="input-field">
                                </div>
                            </div>
                        </fieldset>
                        
                        <div class="flex items-center justify-end mt-8 space-x-4">
                            {{-- Mensaje de estado del formulario --}}
                            @if ($formStatusMessage)
                                <span class="text-sm font-medium
                                    @if ($formStatusType === 'success') text-green-600 dark:text-green-400 @endif
                                    @if ($formStatusType === 'error') text-red-600 dark:text-red-400 @endif">
                                    {{ $formStatusMessage }}
                                </span>
                            @endif

                            <button type="submit" 
                                    wire:loading.attr="disabled" 
                                    wire:target="updateFicha"
                                    class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-50">
                                <span wire:loading.remove wire:target="updateFicha">
                                    Guardar Cambios en Mi Ficha
                                </span>
                                <span wire:loading wire:target="updateFicha">
                                    Guardando...
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('styles')
    <style>
        .input-field {
            @apply mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200;
        }
        .input-error {
            @apply border-red-500 dark:border-red-500;
        }
        .error-message {
            @apply text-red-500 text-xs mt-1;
        }
    </style>
    @endpush
</div>