<div class="p-6 bg-white dark:bg-gray-800 shadow-md rounded-lg">
    <h2 class="text-2xl font-semibold text-gray-800 dark:text-white mb-6">Gestión de Empresas Contratistas</h2>

    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             class="mb-4 px-4 py-3 bg-green-100 border border-green-400 text-green-700 rounded-md dark:bg-green-700 dark:text-green-100 dark:border-green-600">
            {{ session('message') }}
        </div>
    @endif
     @if (session()->has('admin_password_generated'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 10000)"
             class="mb-4 px-4 py-3 bg-blue-100 border border-blue-400 text-blue-700 rounded-md dark:bg-blue-700 dark:text-blue-100 dark:border-blue-600">
            <span class="font-bold">Información:</span> {{ session('admin_password_generated') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            class="mb-4 px-4 py-3 bg-red-100 border border-red-400 text-red-700 rounded-md dark:bg-red-700 dark:text-red-100 dark:border-red-600">
            {{ session('error') }}
        </div>
    @endif
    @if (session()->has('error_uos'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            class="mb-4 px-4 py-3 bg-red-100 border border-red-400 text-red-700 rounded-md dark:bg-red-700 dark:text-red-100 dark:border-red-600">
            {{ session('error_uos') }}
        </div>
    @endif

    @if ($errors->any() && $isOpen) {{-- Errores para el modal de Contratista --}}
        <div class="alert alert-danger bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">¡Hay errores de validación! Por favor, revise los campos.</strong>
            <ul class="mt-2 list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    @if (!Str::startsWith($error, 'selectedUnidadesConCondicion.')) {{-- No mostrar errores de UOs aquí --}}
                        <li>{{ $error }}</li>
                    @endif
                @endforeach
            </ul>
        </div>
    @endif
    
    @if ($errors->has('selectedUnidadesConCondicion.*') && $showModalAsignarUOs) {{-- Errores para el modal de UOs --}}
        <div class="alert alert-danger bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">¡Error en la asignación de condiciones!</strong>
            <ul class="mt-2 list-disc list-inside text-sm">
                @foreach ($errors->get('selectedUnidadesConCondicion.*') as $message)
                    <li>{{ $message[0] }}</li> {{-- Muestra el primer mensaje de error para esa UO/condición --}}
                @endforeach
            </ul>
            <p class="text-xs mt-1">Asegúrese que las condiciones seleccionadas sean válidas.</p>
        </div>
    @endif


    <div class="flex flex-col sm:flex-row justify-between items-center mb-4 space-y-2 sm:space-y-0">
        <div class="w-full sm:w-2/5">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar por Razón Social, RUT, Fantasía o Admin..."
                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-gray-200">
        </div>
        <button wire:click="create()"
                class="btn-primary">
            <x-icons.plus class="h-5 w-5 inline-block mr-1"/>
            Nueva Empresa Contratista
        </button>
    </div>

    <div class="overflow-x-auto shadow-md sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th scope="col" wire:click="sortBy('contratistas.id')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer">ID <x-sort-icon field="contratistas.id" :sortField="$sortField" :sortDirection="$sortDirection"/></th>
                    <th scope="col" wire:click="sortBy('contratistas.razon_social')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer">Razón Social <x-sort-icon field="contratistas.razon_social" :sortField="$sortField" :sortDirection="$sortDirection"/></th>
                    <th scope="col" wire:click="sortBy('contratistas.rut')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer">RUT <x-sort-icon field="contratistas.rut" :sortField="$sortField" :sortDirection="$sortDirection"/></th>
                    <th scope="col" wire:click="sortBy('admin_user_name')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer">Admin. Plataforma <x-sort-icon field="admin_user_name" :sortField="$sortField" :sortDirection="$sortDirection"/></th>
                    <th scope="col" wire:click="sortBy('contratistas.is_active')" class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer">Estado <x-sort-icon field="contratistas.is_active" :sortField="$sortField" :sortDirection="$sortDirection"/></th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($contratistas as $contratista_item)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $contratista_item->id }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $contratista_item->razon_social }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $contratista_item->rut }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                            {{ $contratista_item->admin_user_name ?? ($contratista_item->adminUser->name ?? 'No asignado') }}
                            @if($contratista_item->adminUser) <br><small class="text-gray-500 dark:text-gray-400">{{ $contratista_item->adminUser->email }}</small> @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-center text-sm">
                            <span wire:click="toggleActive({{ $contratista_item->id }})"
                                  wire:confirm="¿Está seguro de {{ $contratista_item->is_active ? 'desactivar' : 'activar' }} a {{ $contratista_item->razon_social }}?"
                                  class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full cursor-pointer {{ $contratista_item->is_active ? 'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100' : 'bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100' }}">
                                {{ $contratista_item->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium">
                            <button wire:click="edit({{ $contratista_item->id }})" class="action-button-edit" title="Editar Contratista">
                                <x-icons.edit class="inline-block"/>
                            </button>
                            <button wire:click="abrirModalAsignarUOs({{ $contratista_item->id }})" class="action-button-link" title="Asignar Unidades Organizacionales">
                                <x-icons.link class="inline-block"/>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">No se encontraron empresas contratistas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $contratistas->links() }}</div>

    {{-- MODAL PARA CREAR/EDITAR CONTRATISTA --}}
    @if ($isOpen)
        <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title-contratista" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75" aria-hidden="true" wire:click="closeModal()"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <form wire:submit.prevent="store">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                            <div class="sm:flex sm:items-start mb-4">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 dark:bg-indigo-900 sm:mx-0 sm:h-10 sm:w-10">
                                    <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18A2.25 2.25 0 004.5 21h3.75V7.5h3v13.5h3.75v-13.5h3V21h3.75a2.25 2.25 0 002.25-2.25V3" />
                                    </svg>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title-contratista">
                                        {{ $contratistaId ? 'Editar' : 'Crear Nueva' }} Empresa Contratista
                                    </h3>
                                </div>
                            </div>

                            <div class="space-y-6">
                                {{-- Fieldsets para Datos de la Empresa, Dirección, Detalles Adicionales, Representante Legal --}}
                                {{-- (Estos fieldsets no cambian y se mantienen como estaban) --}}
                                <fieldset class="border border-gray-300 dark:border-gray-600 p-4 rounded-md">
                                    <legend class="text-md font-semibold text-gray-700 dark:text-gray-300 px-2">Datos de la Empresa</legend>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                                        <div>
                                            <label for="razon_social" class="label-form">Razón Social <span class="text-red-500">*</span></label>
                                            <input type="text" wire:model.lazy="razon_social" id="razon_social" class="input-field @error('razon_social') input-error @enderror">
                                            @error('razon_social') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="nombre_fantasia" class="label-form">Nombre de Fantasía</label>
                                            <input type="text" wire:model.lazy="nombre_fantasia" id="nombre_fantasia" class="input-field @error('nombre_fantasia') input-error @enderror">
                                            @error('nombre_fantasia') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="rut_contratista" class="label-form">RUT Empresa <span class="text-red-500">*</span></label>
                                            <input type="text" wire:model.lazy="rut_contratista" id="rut_contratista" placeholder="Ej: 12345678-9" class="input-field @error('rut_contratista') input-error @enderror">
                                            @error('rut_contratista') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="email_empresa" class="label-form">Email Empresa <span class="text-red-500">*</span></label>
                                            <input type="email" wire:model.lazy="email_empresa" id="email_empresa" class="input-field @error('email_empresa') input-error @enderror">
                                            @error('email_empresa') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="telefono_empresa" class="label-form">Teléfono Empresa</label>
                                            <input type="tel" wire:model.lazy="telefono_empresa" id="telefono_empresa" class="input-field @error('telefono_empresa') input-error @enderror">
                                            @error('telefono_empresa') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                         <div>
                                            <label for="tipo_inscripcion" class="label-form">Tipo Inscripción <span class="text-red-500">*</span></label>
                                            <select wire:model="tipo_inscripcion" id="tipo_inscripcion" class="input-field @error('tipo_inscripcion') input-error @enderror">
                                                <option value="Contratista">Contratista</option>
                                                <option value="Subcontratista">Subcontratista</option>
                                            </select>
                                            @error('tipo_inscripcion') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </fieldset>

                                <fieldset class="border border-gray-300 dark:border-gray-600 p-4 rounded-md">
                                    <legend class="text-md font-semibold text-gray-700 dark:text-gray-300 px-2">Dirección</legend>
                                     <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-2">
                                        <div>
                                            <label for="direccion_calle" class="label-form">Calle <span class="text-red-500">*</span></label>
                                            <input type="text" wire:model.lazy="direccion_calle" id="direccion_calle" class="input-field @error('direccion_calle') input-error @enderror">
                                            @error('direccion_calle') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="direccion_numero" class="label-form">Número</label>
                                            <input type="text" wire:model.lazy="direccion_numero" id="direccion_numero" class="input-field @error('direccion_numero') input-error @enderror">
                                            @error('direccion_numero') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                         <div></div>
                                        <div>
                                            <label for="selected_region_id_contratista" class="label-form">Región <span class="text-red-500">*</span></label>
                                            <select wire:model.live="selected_region_id_contratista" id="selected_region_id_contratista" class="input-field @error('selected_region_id_contratista') input-error @enderror">
                                                <option value="">Seleccione Región...</option>
                                                @foreach ($regiones as $region)
                                                    <option value="{{ $region->id }}">{{ $region->nombre }}</option>
                                                @endforeach
                                            </select>
                                            @error('selected_region_id_contratista') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="comuna_id" class="label-form">Comuna <span class="text-red-500">*</span></label>
                                            <select wire:model="comuna_id" id="comuna_id" class="input-field @error('comuna_id') input-error @enderror" @if(empty($selected_region_id_contratista) || $comunasDisponiblesContratista->isEmpty()) disabled @endif>
                                                <option value="">Seleccione Comuna...</option>
                                                @foreach ($comunasDisponiblesContratista as $comuna)
                                                    <option value="{{ $comuna->id }}">{{ $comuna->nombre }}</option>
                                                @endforeach
                                            </select>
                                            @error('comuna_id') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </fieldset>

                                <fieldset class="border border-gray-300 dark:border-gray-600 p-4 rounded-md">
                                    <legend class="text-md font-semibold text-gray-700 dark:text-gray-300 px-2">Detalles Adicionales</legend>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-2">
                                        <div>
                                            <label for="tipo_empresa_legal_id" class="label-form">Tipo Empresa Legal <span class="text-red-500">*</span></label>
                                            <select wire:model="tipo_empresa_legal_id" id="tipo_empresa_legal_id" class="input-field @error('tipo_empresa_legal_id') input-error @enderror">
                                                <option value="">Seleccione...</option>
                                                @foreach ($tiposEmpresaLegal as $tipo)
                                                    <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                                                @endforeach
                                            </select>
                                            @error('tipo_empresa_legal_id') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="rubro_id" class="label-form">Rubro <span class="text-red-500">*</span></label>
                                            <select wire:model="rubro_id" id="rubro_id" class="input-field @error('rubro_id') input-error @enderror">
                                                <option value="">Seleccione...</option>
                                                @foreach ($rubros as $rubro)
                                                    <option value="{{ $rubro->id }}">{{ $rubro->nombre }}</option>
                                                @endforeach
                                            </select>
                                            @error('rubro_id') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="rango_cantidad_trabajadores_id" class="label-form">Rango Trabajadores</label>
                                            <select wire:model="rango_cantidad_trabajadores_id" id="rango_cantidad_trabajadores_id" class="input-field @error('rango_cantidad_trabajadores_id') input-error @enderror">
                                                <option value="">Seleccione...</option>
                                                @foreach ($rangosCantidad as $rango)
                                                    <option value="{{ $rango->id }}">{{ $rango->nombre }}</option>
                                                @endforeach
                                            </select>
                                            @error('rango_cantidad_trabajadores_id') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="mutualidad_id" class="label-form">Mutualidad</label>
                                            <select wire:model="mutualidad_id" id="mutualidad_id" class="input-field @error('mutualidad_id') input-error @enderror">
                                                <option value="">Seleccione...</option>
                                                @foreach ($mutualidades as $mutual)
                                                    <option value="{{ $mutual->id }}">{{ $mutual->nombre }}</option>
                                                @endforeach
                                            </select>
                                            @error('mutualidad_id') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </fieldset>

                                <fieldset class="border border-gray-300 dark:border-gray-600 p-4 rounded-md">
                                    <legend class="text-md font-semibold text-gray-700 dark:text-gray-300 px-2">Representante Legal</legend>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-2">
                                        <div>
                                            <label for="rep_legal_nombres" class="label-form">Nombres Rep. Legal <span class="text-red-500">*</span></label>
                                            <input type="text" wire:model.lazy="rep_legal_nombres" id="rep_legal_nombres" class="input-field @error('rep_legal_nombres') input-error @enderror">
                                            @error('rep_legal_nombres') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="rep_legal_apellido_paterno" class="label-form">Apellido Paterno Rep. Legal <span class="text-red-500">*</span></label>
                                            <input type="text" wire:model.lazy="rep_legal_apellido_paterno" id="rep_legal_apellido_paterno" class="input-field @error('rep_legal_apellido_paterno') input-error @enderror">
                                            @error('rep_legal_apellido_paterno') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="rep_legal_apellido_materno" class="label-form">Apellido Materno Rep. Legal</label>
                                            <input type="text" wire:model.lazy="rep_legal_apellido_materno" id="rep_legal_apellido_materno" class="input-field @error('rep_legal_apellido_materno') input-error @enderror">
                                            @error('rep_legal_apellido_materno') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="rep_legal_rut" class="label-form">RUT Rep. Legal</label>
                                            <input type="text" wire:model.lazy="rep_legal_rut" id="rep_legal_rut" placeholder="Ej: 12345678-9" class="input-field @error('rep_legal_rut') input-error @enderror">
                                            @error('rep_legal_rut') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="rep_legal_email" class="label-form">Email Rep. Legal</label>
                                            <input type="email" wire:model.lazy="rep_legal_email" id="rep_legal_email" class="input-field @error('rep_legal_email') input-error @enderror">
                                            @error('rep_legal_email') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="rep_legal_telefono" class="label-form">Teléfono Rep. Legal</label>
                                            <input type="tel" wire:model.lazy="rep_legal_telefono" id="rep_legal_telefono" class="input-field @error('rep_legal_telefono') input-error @enderror">
                                            @error('rep_legal_telefono') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </fieldset>

                                <fieldset class="border border-gray-300 dark:border-gray-600 p-4 rounded-md">
                                    <legend class="text-md font-semibold text-gray-700 dark:text-gray-300 px-2">Administrador de Plataforma (Usuario)</legend>
                                    
                                    @if ($admin_user_id && !$crear_nuevo_admin)
                                        <div class="mb-3 p-3 bg-yellow-50 dark:bg-yellow-700 dark:text-yellow-100 border border-yellow-300 dark:border-yellow-600 rounded-md">
                                            <p class="text-sm">Editando datos del administrador existente: <strong>{{ $admin_name }}</strong> ({{ $admin_email }})</p>
                                            <button type="button" wire:click="$set('crear_nuevo_admin', true)" class="mt-1 text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Reemplazar y Crear un nuevo administrador</button>
                                        </div>
                                    @else
                                        <div class="mb-2">
                                            <input type="checkbox" wire:model.live="crear_nuevo_admin" id="crear_nuevo_admin_chk" class="form-checkbox" 
                                                   @if(!$admin_user_id) checked disabled @endif
                                            >
                                            <label for="crear_nuevo_admin_chk" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                                {{ ($admin_user_id && $crear_nuevo_admin) ? 'Reemplazar y Crear Nuevo Administrador' : ((!$admin_user_id) ? 'Crear Nuevo Usuario Administrador (obligatorio)' : 'Editar Administrador Actual') }}
                                                @if($admin_user_id && !$crear_nuevo_admin) (desmarque para editar el actual) @endif
                                            </label>
                                        </div>
                                    @endif

                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-2">
                                        <div class="lg:col-span-2">
                                            <label for="admin_name" class="label-form">Nombre Completo Admin. <span class="text-red-500">*</span></label>
                                            <input type="text" wire:model.lazy="admin_name" id="admin_name" class="input-field @error('admin_name') input-error @enderror" placeholder="Ej: Juan Alberto Pérez González">
                                            @error('admin_name') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div> </div>
                                        <div>
                                            <label for="admin_rut_usuario" class="label-form">RUT Admin. <span class="text-red-500">*</span></label>
                                            <input type="text" wire:model.lazy="admin_rut_usuario" id="admin_rut_usuario" placeholder="Ej: 12345678-9" class="input-field @error('admin_rut_usuario') input-error @enderror">
                                            @error('admin_rut_usuario') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="admin_email" class="label-form">Email Admin. <span class="text-red-500">*</span></label>
                                            <input type="email" wire:model.lazy="admin_email" id="admin_email" class="input-field @error('admin_email') input-error @enderror">
                                            @error('admin_email') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        
                                        <div class="col-span-1 md:col-span-2 lg:col-span-3">
                                            <input type="checkbox" wire:model.live="generar_password_auto" id="generar_password_auto_input_chk" class="form-checkbox">
                                            <label for="generar_password_auto_input_chk" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                                {{ ($crear_nuevo_admin || !$admin_user_id) ? 'Generar contraseña automáticamente y notificar' : 'Generar nueva contraseña automáticamente (reemplazará la actual)'}}
                                            </label>
                                        </div>

                                        @if (!$generar_password_auto)
                                            <div>
                                                <label for="admin_password" class="label-form">
                                                    {{ ($crear_nuevo_admin || !$admin_user_id) ? 'Contraseña' : 'Nueva Contraseña (opcional)'}}
                                                    @if($crear_nuevo_admin || !$admin_user_id) <span class="text-red-500">*</span> @endif
                                                </label>
                                                <input type="password" wire:model.lazy="admin_password" id="admin_password" class="input-field @error('admin_password') input-error @enderror" placeholder="{{ ($admin_user_id && !$crear_nuevo_admin) ? 'Dejar en blanco para no cambiar' : '' }}">
                                                @error('admin_password') <span class="error-message">{{ $message }}</span> @enderror
                                            </div>
                                            <div>
                                                <label for="admin_password_confirmation" class="label-form">
                                                    Confirmar {{ ($crear_nuevo_admin || !$admin_user_id) ? 'Contraseña' : 'Nueva Contraseña'}}
                                                    @if($crear_nuevo_admin || !$admin_user_id) <span class="text-red-500">*</span> @endif
                                                </label>
                                                <input type="password" wire:model.lazy="admin_password_confirmation" id="admin_password_confirmation" class="input-field">
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <div class="mt-4">
                                        <label for="admin_is_active" class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                            <input type="checkbox" wire:model="admin_is_active" id="admin_is_active" class="form-checkbox">
                                            <span class="ml-2">Usuario Administrador Activo</span>
                                        </label>
                                        @error('admin_is_active') <span class="error-message">{{ $message }}</span> @enderror
                                    </div>
                                </fieldset>

                                <fieldset class="border border-gray-300 dark:border-gray-600 p-4 rounded-md">
                                     <legend class="text-md font-semibold text-gray-700 dark:text-gray-300 px-2">Estado del Contratista</legend>
                                     <div class="mt-2">
                                        <label for="is_active_contratista_modal" class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300">
                                            <input type="checkbox" wire:model="is_active" id="is_active_contratista_modal" class="form-checkbox">
                                            <span class="ml-2">Empresa Contratista Activa en Plataforma</span>
                                        </label>
                                        @error('is_active') <span class="error-message">{{ $message }}</span> @enderror
                                    </div>
                                </fieldset>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="btn-primary w-full sm:w-auto sm:ml-3">
                                Guardar Contratista
                            </button>
                            <button type="button" wire:click="closeModal()" class="btn-secondary w-full mt-3 sm:mt-0 sm:w-auto">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL PARA ASIGNAR UOs AL CONTRATISTA --}}
    @if ($showModalAsignarUOs)
        <div class="fixed z-20 inset-0 overflow-y-auto" aria-labelledby="modal-title-uos" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75" aria-hidden="true" wire:click="cerrarModalAsignarUOs()"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full"> {{-- Incrementado a max-w-3xl --}}
                    <form wire:submit.prevent="guardarAsignacionUOs">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                            <div class="sm:flex sm:items-start mb-4">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 sm:mx-0 sm:h-10 sm:w-10">
                                    <x-icons.link class="h-6 w-6 text-blue-600 dark:text-blue-300"/>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title-uos">
                                        Asignar Unidades Organizacionales y Condición
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        A: {{ $nombreContratistaParaAsignarUOs }}
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label for="selectedMandanteIdParaAsignarUOs" class="label-form">Seleccione Mandante para ver sus UOs:</label>
                                    <select wire:model.live="selectedMandanteIdParaAsignarUOs" id="selectedMandanteIdParaAsignarUOs" class="input-field">
                                        <option value="">-- Seleccione un Mandante --</option>
                                        @foreach($mandantesParaAsignarUOs as $mandante)
                                            <option value="{{ $mandante->id }}">{{ $mandante->razon_social }}</option>
                                        @endforeach
                                    </select>
                                    @error('selectedMandanteIdParaAsignarUOs') <span class="error-message">{{ $message }}</span> @enderror
                                </div>

                                @if(!empty($selectedMandanteIdParaAsignarUOs))
                                    @if($unidadesOrganizacionalesJerarquicas->count() > 0)
                                        <div class="mt-4 border border-gray-300 dark:border-gray-600 rounded-md p-3 max-h-96 overflow-y-auto"> {{-- Incrementado max-h-96 --}}
                                            <div class="grid grid-cols-6 gap-x-2 mb-2 sticky top-0 bg-gray-50 dark:bg-gray-700 py-2 px-1 rounded-t-md z-10">
                                                <div class="col-span-4 font-semibold text-xs text-gray-600 dark:text-gray-300">Unidad Organizacional</div>
                                                <div class="col-span-2 font-semibold text-xs text-gray-600 dark:text-gray-300">Condición Contratista</div>
                                            </div>
                                            <div class="space-y-1">
                                                {{-- MODIFICACIÓN CLAVE AQUÍ --}}
                                                @foreach($unidadesOrganizacionalesJerarquicas as $uoRaiz)
                                                    <x-jerarquia-uo-item
                                                        :uo="$uoRaiz"
                                                        :level="0"
                                                        :selectedUOsConCondicion="$selectedUnidadesConCondicion"
                                                        :tiposCondicionDisponibles="$tiposCondicionDisponibles" {{-- Pasar la lista de condiciones --}}
                                                        wire:key="jerarquia-{{ $uoRaiz->id }}"
                                                    />
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-4">Este mandante no tiene Unidades Organizacionales activas registradas.</p>
                                    @endif
                                @else
                                     <p class="text-sm text-gray-500 dark:text-gray-400 mt-4">Seleccione un mandante para listar sus Unidades Organizacionales.</p>
                                @endif
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Las UOs seleccionadas (y su condición) de todos los mandantes revisados se guardarán al presionar "Guardar Asignaciones".</p>

                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="btn-primary w-full sm:w-auto sm:ml-3">
                                Guardar Asignaciones
                            </button>
                            <button type="button" wire:click="cerrarModalAsignarUOs()" class="btn-secondary w-full mt-3 sm:mt-0 sm:w-auto">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif


    @push('styles')
    <style>
        /* Las clases CSS personalizadas no cambian, se mantienen como estaban */
        .label-form {
            @apply block text-sm font-medium text-gray-700 dark:text-gray-300;
        }
        .input-field {
            @apply mt-1 block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-gray-200;
        }
        .input-error {
            @apply border-red-500 dark:border-red-500;
        }
        .error-message {
            @apply text-red-500 text-xs mt-1;
        }
        .form-checkbox {
            @apply h-4 w-4 text-indigo-600 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500 dark:bg-gray-700 dark:focus:ring-indigo-600 dark:ring-offset-gray-800;
        }
        .btn-primary {
            @apply px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150;
        }
        .btn-secondary {
            @apply px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150;
        }
        .action-button-edit {
            @apply text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-200 mr-2;
        }
        .action-button-link { 
            @apply text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-200;
        }

    </style>
    @endpush
</div>