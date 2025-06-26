<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            @if ($vistaActual === 'listado_trabajadores')
                {{ __('Gestión de Trabajadores') }}
                @if($selectedUnidadOrganizacionalId && $nombreVinculacionSeleccionada)
                    <span class="text-base font-normal text-gray-500 dark:text-gray-400">
                        (Operando en: {{ $nombreVinculacionSeleccionada }})
                    </span>
                @endif
            @elseif ($vistaActual === 'listado_vinculaciones' && $trabajadorSeleccionado)
                {{ __('Vinculaciones de: ') . $trabajadorSeleccionado->nombre_completo }}
                 @if($selectedUnidadOrganizacionalId && $nombreVinculacionSeleccionada)
                    <span class="text-base font-normal text-gray-500 dark:text-gray-400">
                        (Contexto: {{ $nombreVinculacionSeleccionada }})
                    </span>
                @endif
            @else
                {{ __('Gestión de Trabajadores') }}
            @endif
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

                {{-- Mensajes Flash Unificados --}}
                @if (session()->has('message_trabajador') || session()->has('success') || session()->has('message_vinculacion'))
                    <div class="alert-success mb-4">
                        {{ session('message_trabajador') ?? session('success') ?? session('message_vinculacion') }}
                    </div>
                @endif
                @if (session()->has('error_trabajador') || session()->has('error') || session()->has('error_vinculacion'))
                    <div class="alert-danger mb-4">
                        {{ session('error_trabajador') ?? session('error') ?? session('error_vinculacion') }}
                    </div>
                @endif

                {{-- NUEVO: Selector de Vinculación (Mandante - UO) --}}
                <div class="mb-6 p-4 border dark:border-gray-700 rounded-md bg-gray-50 dark:bg-gray-750">
                    <label for="selectedUnidadOrganizacionalId" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Seleccione la Vinculación (Mandante - Unidad Organizacional) para operar:
                    </label>
                    <select wire:model.live="selectedUnidadOrganizacionalId" id="selectedUnidadOrganizacionalId" 
                            class="input-field w-full sm:w-1/2 lg:w-1/3 @error('selectedUnidadOrganizacionalId') border-red-500 @enderror">
                        <option value="">-- Seleccione una opción --</option>
                        @if($unidadesHabilitadasContratista && $unidadesHabilitadasContratista->isNotEmpty())
                            @foreach ($unidadesHabilitadasContratista as $uo)
                                <option value="{{ $uo['id'] }}">{{ $uo['nombre_completo'] }}</option>
                            @endforeach
                        @else
                            <option value="" disabled>No tiene Unidades Organizacionales asignadas.</option>
                        @endif
                    </select>
                    @error('selectedUnidadOrganizacionalId') <span class="error-message">{{ $message }}</span> @enderror
                     @if($unidadesHabilitadasContratista && $unidadesHabilitadasContratista->isEmpty())
                         <p class="mt-2 text-sm text-yellow-600 dark:text-yellow-400">
                             No tiene Unidades Organizacionales asignadas por ASEM. Póngase en contacto con el administrador de la plataforma.
                         </p>
                     @endif
                </div>
                {{-- FIN NUEVO: Selector de Vinculación --}}

                {{-- VISTA: LISTADO DE TRABAJADORES --}}
                @if ($vistaActual === 'listado_trabajadores')
                    @if ($selectedUnidadOrganizacionalId) {{-- Solo mostrar si se ha seleccionado UO --}}
                        <div class="mb-4 flex flex-col sm:flex-row justify-between items-center">
                            <div class="w-full sm:w-2/3 mb-2 sm:mb-0">
                                <input wire:model.live.debounce.300ms="searchTrabajador" type="text"
                                       placeholder="Buscar por RUT o Nombre del Trabajador..."
                                       class="input-field w-full">
                            </div>
                            <button wire:click="abrirModalNuevoTrabajador" class="btn-primary">
                                <x-icons.plus class="w-5 h-5 mr-1 inline-block"/> Agregar Nuevo Trabajador
                            </button>
                        </div>

                        <div class="overflow-x-auto shadow-md sm:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th wire:click="sortBy('id')" class="table-header cursor-pointer">ID <x-sort-icon field="id" :sortField="$sortByTrabajador" :sortDirection="$sortDirectionTrabajador" /></th>
                                        <th wire:click="sortBy('rut')" class="table-header cursor-pointer">RUT <x-sort-icon field="rut" :sortField="$sortByTrabajador" :sortDirection="$sortDirectionTrabajador" /></th>
                                        <th wire:click="sortBy('apellido_paterno')" class="table-header cursor-pointer">Trabajador <x-sort-icon field="apellido_paterno" :sortField="$sortByTrabajador" :sortDirection="$sortDirectionTrabajador" /></th>
                                        <th class="table-header">Cargo Actual (Principal en UO)</th>
                                        <th class="table-header text-center">% Cumplimiento</th>
                                        <th class="table-header text-center">Acceso Habilitado</th>
                                        <th wire:click="sortBy('is_active')" class="table-header text-center cursor-pointer">Estado Ficha <x-sort-icon field="is_active" :sortField="$sortByTrabajador" :sortDirection="$sortDirectionTrabajador" /></th>
                                        <th class="table-header text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @forelse ($trabajadoresPaginados ?? [] as $trab)
                                        <tr class="table-row-hover">
                                            <td class="table-cell">{{ $trab->id }}</td>
                                            <td class="table-cell">{{ $trab->rut }}</td>
                                            <td class="table-cell">{{ $trab->apellido_paterno }} {{ $trab->apellido_materno }}, {{ $trab->nombres }}</td>
                                            <td class="table-cell">
                                                @php
                                                    // Cargo principal del trabajador DENTRO DE LA UO SELECCIONADA
                                                    $vinculacionEnUOSeleccionada = $trab->vinculaciones()
                                                        ->where('unidad_organizacional_mandante_id', $selectedUnidadOrganizacionalId)
                                                        ->where('is_active', true)
                                                        ->orderBy('fecha_ingreso_vinculacion', 'desc')
                                                        ->first();
                                                @endphp
                                                {{ $vinculacionEnUOSeleccionada && $vinculacionEnUOSeleccionada->cargoMandante ? $vinculacionEnUOSeleccionada->cargoMandante->nombre_cargo : 'Sin cargo en esta UO' }}
                                            </td>
                                            <td class="table-cell text-center text-sm">N/D</td>
                                            <td class="table-cell text-center text-sm">N/D</td>
                                            <td class="table-cell text-center">
                                                <span wire:click="toggleActivoTrabajador({{ $trab->id }})"
                                                      class="status-badge {{ $trab->is_active ? 'status-active' : 'status-inactive' }}">
                                                    {{ $trab->is_active ? 'Activo' : 'Inactivo' }}
                                                </span>
                                            </td>
                                            <td class="table-cell text-center whitespace-nowrap">
                                                <button wire:click="abrirModalEditarTrabajador({{ $trab->id }})" class="action-button-edit" title="Editar Ficha Trabajador"><x-icons.edit class="inline-block"/></button>
                                                <button wire:click="seleccionarTrabajadorParaVinculaciones({{ $trab->id }})" class="action-button-link" title="Ver/Gestionar Vinculaciones"><x-icons.link class="inline-block"/></button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="table-cell text-center">
                                                 @if($selectedUnidadOrganizacionalId)
                                                     No se encontraron trabajadores vinculados a esta Unidad Organizacional. Puede agregar uno nuevo.
                                                 @else
                                                     Seleccione una Vinculación (Mandante - UO) para ver los trabajadores.
                                                 @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($trabajadoresPaginados && $trabajadoresPaginados->hasPages())
                            <div class="mt-4">
                                {{ $trabajadoresPaginados->links() }}
                            </div>
                        @endif
                     @else
                         <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                             Por favor, seleccione una Vinculación (Mandante - Unidad Organizacional) en el desplegable de arriba para comenzar a gestionar los trabajadores.
                         </div>
                    @endif
                @endif

                {{-- VISTA: LISTADO DE VINCULACIONES --}}
                @if ($vistaActual === 'listado_vinculaciones' && $trabajadorSeleccionado)
                    <div class="mb-4 p-4 border dark:border-gray-700 rounded-md">
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                            Trabajador: <span class="font-normal">{{ $trabajadorSeleccionado->nombre_completo }} (RUT: {{ $trabajadorSeleccionado->rut }})</span>
                        </h3>
                         <button wire:click="abrirModalEditarTrabajador({{ $trabajadorSeleccionado->id }})" class="text-sm text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-200 mt-1 inline-flex items-center">
                            <x-icons.edit class="w-4 h-4 mr-1 inline-block"/> Editar Ficha de este Trabajador
                        </button>
                    </div>

                    <div class="mb-4 flex flex-col sm:flex-row justify-between items-center">
                        <button wire:click="irAListadoTrabajadores" class="btn-secondary mb-2 sm:mb-0"> 
                            <x-icons.arrow-left class="w-5 h-5 mr-1 inline-block"/> Volver a Listado Trabajadores ({{ $nombreVinculacionSeleccionada }})
                        </button>
                        <button wire:click="abrirModalNuevaVinculacion" class="btn-primary">
                            <x-icons.plus class="w-5 h-5 mr-1 inline-block"/> Agregar Vinculación / Contractual
                        </button>
                    </div>

                    <div class="overflow-x-auto shadow-md sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="table-header">Vinculación (Mandante / UO)</th>
                                    <th class="table-header">Cargo</th>
                                    <th class="table-header">Condición Personal</th>
                                    <th class="table-header text-center">F. Ingreso Vinc.</th>
                                    <th class="table-header text-center">Estado</th>
                                    <th class="table-header text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($vinculacionesPaginadas ?? [] as $vinc)
                                <tr class="table-row-hover">
                                    <td class="table-cell">
                                        {{ $vinc->unidadOrganizacionalMandante && $vinc->unidadOrganizacionalMandante->mandante ? $vinc->unidadOrganizacionalMandante->mandante->razon_social : 'N/A Mandante' }} / <br>
                                        {{ $vinc->unidadOrganizacionalMandante ? $vinc->unidadOrganizacionalMandante->nombre_unidad : 'N/A UO' }}
                                    </td>
                                    <td class="table-cell">{{ $vinc->cargoMandante ? $vinc->cargoMandante->nombre_cargo : 'N/A Cargo' }}</td>
                                    <td class="table-cell">{{ $vinc->tipoCondicionPersonal ? $vinc->tipoCondicionPersonal->nombre : 'Sin Condición' }}</td>
                                    <td class="table-cell text-center">{{ $vinc->fecha_ingreso_vinculacion ? $vinc->fecha_ingreso_vinculacion->format('d-m-Y') : 'N/A' }}</td>
                                    <td class="table-cell text-center">
                                        <span wire:click="toggleActivoVinculacion({{ $vinc->id }})"
                                              class="status-badge {{ $vinc->is_active ? 'status-active' : 'status-inactive' }}">
                                            {{ $vinc->is_active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                        @if(!$vinc->is_active && $vinc->fecha_desactivacion)
                                            <span class="text-xs block text-gray-500 dark:text-gray-400">Desact: {{ $vinc->fecha_desactivacion->format('d-m-Y') }}</span>
                                        @endif
                                    </td>
                                    <td class="table-cell text-center whitespace-nowrap">
                                        <button wire:click="abrirModalEditarVinculacion({{ $vinc->id }})" class="action-button-edit" title="Editar Vinculación"><x-icons.edit class="inline-block"/></button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="table-cell text-center">No se encontraron vinculaciones para este trabajador en las unidades organizacionales habilitadas.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($vinculacionesPaginadas && $vinculacionesPaginadas->hasPages())
                        <div class="mt-4">
                            {{ $vinculacionesPaginadas->links(data: ['scrollTo' => false]) }}
                        </div>
                    @endif
                @endif

                {{-- MODAL: FICHA DEL TRABAJADOR --}}
                @if ($showModalFichaTrabajador)
                    <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title-trabajador" role="dialog" aria-modal="true">
                        {{-- Contenido del modal como lo tenías, no hay cambios aquí --}}
                        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                         <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75" aria-hidden="true" wire:click="cerrarModalFichaTrabajador"></div>
                         <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>
                         <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                             <form wire:submit.prevent="guardarTrabajador" id="formFichaTrabajador">
                                 <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                     <div class="sm:flex sm:items-start w-full">
                                         <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                             <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 section-title" id="modal-title-trabajador">
                                                 {{ $trabajadorId ? 'Editar Ficha del Trabajador' : 'Agregar Nuevo Trabajador' }}
                                                 @if($trabajadorSeleccionado && $trabajadorId)
                                                     <span class="text-sm font-normal text-gray-500 dark:text-gray-400">- {{ $trabajadorSeleccionado->nombre_completo }}</span>
                                                 @endif
                                                 @if($selectedUnidadOrganizacionalId && $nombreVinculacionSeleccionada)
                                                     <p class="text-xs font-normal text-gray-500 dark:text-gray-400">
                                                         Contexto: {{ $nombreVinculacionSeleccionada }}
                                                     </p>
                                                 @endif
                                             </h3>
                                             <div class="mt-4 space-y-4">
                                                 <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                                     <div class="lg:col-span-4 section-title !mt-0 !border-none !pb-0">Datos Personales</div>
                                                     <div>
                                                         <label for="nombres" class="label-form">Nombres <span class="text-red-500">*</span></label>
                                                         <input type="text" wire:model.lazy="nombres" id="nombres" class="input-field w-full">
                                                         @error('nombres') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                     <div>
                                                         <label for="apellido_paterno" class="label-form">Apellido Paterno <span class="text-red-500">*</span></label>
                                                         <input type="text" wire:model.lazy="apellido_paterno" id="apellido_paterno" class="input-field w-full">
                                                         @error('apellido_paterno') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                     <div>
                                                         <label for="apellido_materno" class="label-form">Apellido Materno</label>
                                                         <input type="text" wire:model.lazy="apellido_materno" id="apellido_materno" class="input-field w-full">
                                                         @error('apellido_materno') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                     <div>
                                                         <label for="rut_trabajador" class="label-form">RUT <span class="text-red-500">*</span></label>
                                                         <input type="text" wire:model.lazy="rut_trabajador" id="rut_trabajador" class="input-field w-full" placeholder="Ej: 12345678-9">
                                                         @error('rut_trabajador') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                     <div>
                                                         <label for="nacionalidad_id" class="label-form">Nacionalidad <span class="text-red-500">*</span></label>
                                                         <select wire:model="nacionalidad_id" id="nacionalidad_id" class="input-field w-full">
                                                             <option value="">Seleccione...</option>
                                                             @foreach($nacionalidades as $nac) <option value="{{ $nac->id }}">{{ $nac->nombre }}</option> @endforeach
                                                         </select>
                                                         @error('nacionalidad_id') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                     <div>
                                                         <label for="fecha_nacimiento" class="label-form">Fecha de Nacimiento</label>
                                                         <input type="date" wire:model.lazy="fecha_nacimiento" id="fecha_nacimiento" class="input-field w-full">
                                                         @error('fecha_nacimiento') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                     <div>
                                                         <label for="sexo_id" class="label-form">Género</label>
                                                         <select wire:model="sexo_id" id="sexo_id" class="input-field w-full">
                                                             <option value="">Seleccione...</option>
                                                             @foreach($sexos as $sexo) <option value="{{ $sexo->id }}">{{ $sexo->nombre }}</option> @endforeach
                                                         </select>
                                                         @error('sexo_id') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                     <div>
                                                         <label for="email_trabajador" class="label-form">Email</label>
                                                         <input type="email" wire:model.lazy="email_trabajador" id="email_trabajador" class="input-field w-full">
                                                         @error('email_trabajador') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                     <div>
                                                         <label for="celular_trabajador" class="label-form">Celular</label>
                                                         <input type="text" wire:model.lazy="celular_trabajador" id="celular_trabajador" class="input-field w-full">
                                                         @error('celular_trabajador') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                     <div>
                                                         <label for="estado_civil_id" class="label-form">Estado Civil</label>
                                                         <select wire:model="estado_civil_id" id="estado_civil_id" class="input-field w-full">
                                                             <option value="">Seleccione...</option>
                                                             @foreach($estadosCiviles as $ec) <option value="{{ $ec->id }}">{{ $ec->nombre }}</option> @endforeach
                                                         </select>
                                                         @error('estado_civil_id') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                     <div>
                                                         <label for="nivel_educacional_id" class="label-form">Nivel Educacional</label>
                                                         <select wire:model="nivel_educacional_id" id="nivel_educacional_id" class="input-field w-full">
                                                             <option value="">Seleccione...</option>
                                                             @foreach($nivelesEducacionales as $ne) <option value="{{ $ne->id }}">{{ $ne->nombre }}</option> @endforeach
                                                         </select>
                                                         @error('nivel_educacional_id') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                     <div>
                                                         <label for="etnia_id" class="label-form">Etnia / Pueblo Originario</label>
                                                         <select wire:model="etnia_id" id="etnia_id" class="input-field w-full">
                                                             <option value="">Seleccione...</option>
                                                             @foreach($etnias as $etnia) <option value="{{ $etnia->id }}">{{ $etnia->nombre }}</option> @endforeach
                                                             <option value="">No pertenece / No informa</option>
                                                         </select>
                                                         @error('etnia_id') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                     <div class="lg:col-span-4 section-title !border-none !pb-0">Domicilio del Trabajador</div>
                                                     <div>
                                                         <label for="direccion_calle" class="label-form">Calle</label>
                                                         <input type="text" wire:model.lazy="direccion_calle" id="direccion_calle" class="input-field w-full">
                                                         @error('direccion_calle') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                     <div>
                                                         <label for="direccion_numero" class="label-form">Número</label>
                                                         <input type="text" wire:model.lazy="direccion_numero" id="direccion_numero" class="input-field w-full">
                                                         @error('direccion_numero') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                     <div>
                                                         <label for="direccion_departamento" class="label-form">Depto / Casa</label>
                                                         <input type="text" wire:model.lazy="direccion_departamento" id="direccion_departamento" class="input-field w-full">
                                                         @error('direccion_departamento') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                     <div> </div> 
                                                     <div>
                                                         <label for="trabajador_region_id" class="label-form">Región</label>
                                                         <select wire:model.live="trabajador_region_id" id="trabajador_region_id" class="input-field w-full">
                                                             <option value="">Seleccione Región...</option>
                                                             @foreach($regiones as $region) <option value="{{ $region->id }}">{{ $region->nombre }}</option> @endforeach
                                                         </select>
                                                         @error('trabajador_region_id') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                     <div>
                                                         <label for="trabajador_comuna_id" class="label-form">Comuna</label>
                                                         <select wire:model="trabajador_comuna_id" id="trabajador_comuna_id" class="input-field w-full" @if(empty($comunasDisponiblesTrabajador)) disabled @endif>
                                                             <option value="">Seleccione Comuna...</option>
                                                             @foreach($comunasDisponiblesTrabajador as $comuna) <option value="{{ $comuna->id }}">{{ $comuna->nombre }}</option> @endforeach
                                                         </select>
                                                         @error('trabajador_comuna_id') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                     <div class="lg:col-span-4 section-title !border-none !pb-0">Otros Datos</div>
                                                     <div>
                                                         <label for="fecha_ingreso_empresa" class="label-form">Fecha Ingreso (Contratista)</label>
                                                         <input type="date" wire:model.lazy="fecha_ingreso_empresa" id="fecha_ingreso_empresa" class="input-field w-full">
                                                         @error('fecha_ingreso_empresa') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                     <div class="lg:col-span-3">
                                                         <label for="trabajador_is_active" class="label-form flex items-center mt-2">
                                                             <input type="checkbox" wire:model="trabajador_is_active" id="trabajador_is_active" class="form-checkbox rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-indigo-400">
                                                             <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Trabajador Activo</span>
                                                         </label>
                                                     </div>
                                                 </div>
                                             </div>
                                         </div>
                                     </div>
                                 </div>
                                 <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                     <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ms-3 sm:w-auto sm:text-sm">
                                         {{ $trabajadorId ? 'Guardar Cambios' : 'Crear Trabajador' }}
                                     </button>
                                     <button type="button" wire:click="cerrarModalFichaTrabajador" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                                         Cancelar
                                     </button>
                                 </div>
                             </form>
                         </div>
                     </div>
                    </div>
                @endif

                {{-- MODAL: VINCULACIÓN --}}
                @if($showModalVinculacion && $trabajadorSeleccionado)
                    <div class="fixed z-20 inset-0 overflow-y-auto" aria-labelledby="modal-title-vinculacion" role="dialog" aria-modal="true">
                        {{-- Contenido del modal como lo tenías, no hay cambios aquí --}}
                        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                         <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75" aria-hidden="true" wire:click="cerrarModalVinculacion"></div>
                         <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>
                         <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                             <form wire:submit.prevent="guardarVinculacion" id="formVinculacion">
                                 <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                     <div class="sm:flex sm:items-start w-full">
                                         <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                             <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 section-title" id="modal-title-vinculacion">
                                                 {{ $vinculacionId ? 'Editar Vinculación Contractual' : 'Agregar Nueva Vinculación' }}
                                                 <p class="text-sm font-normal text-gray-500 dark:text-gray-400">
                                                     Para: {{ $trabajadorSeleccionado->nombre_completo }}
                                                 </p>
                                                  @if($selectedUnidadOrganizacionalId && $nombreVinculacionSeleccionada && $vistaActual === 'listado_vinculaciones')
                                                     <p class="text-xs font-normal text-gray-500 dark:text-gray-400">
                                                         Contexto Principal: {{ $nombreVinculacionSeleccionada }}
                                                     </p>
                                                 @endif
                                             </h3>
                                             <div class="mt-4 space-y-4">
                                                 <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                     <div>
                                                         <label for="v_mandante_id" class="label-form">Seleccione Mandante <span class="text-red-500">*</span></label>
                                                         <select wire:model.live="v_mandante_id" id="v_mandante_id" class="input-field w-full">
                                                             <option value="">Seleccione un Mandante...</option>
                                                             @foreach($mandantesDisponibles as $mandante)
                                                                 <option value="{{ $mandante->id }}">{{ $mandante->razon_social }}</option>
                                                             @endforeach
                                                         </select>
                                                         @error('v_mandante_id') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                     <div> <!-- Placeholder --> </div>

                                                     <div>
                                                         <label for="v_unidad_organizacional_mandante_id" class="label-form">Vinculación (Unidad Organizacional) <span class="text-red-500">*</span></label>
                                                         <select wire:model="v_unidad_organizacional_mandante_id" id="v_unidad_organizacional_mandante_id" class="input-field w-full" @if(empty($unidadesOrganizacionalesDisponibles)) disabled @endif>
                                                             <option value="">Seleccione U.O....</option>
                                                             @foreach($unidadesOrganizacionalesDisponibles as $uo)
                                                                 <option value="{{ $uo->id }}">{{ $uo->nombre_unidad }}</option>
                                                             @endforeach
                                                         </select>
                                                         @error('v_unidad_organizacional_mandante_id') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                     <div>
                                                         <label for="v_cargo_mandante_id" class="label-form">Cargo Trabajador <span class="text-red-500">*</span></label>
                                                         <select wire:model="v_cargo_mandante_id" id="v_cargo_mandante_id" class="input-field w-full" @if(empty($cargosMandanteDisponibles)) disabled @endif>
                                                             <option value="">Seleccione Cargo...</option>
                                                             @foreach($cargosMandanteDisponibles as $cargo)
                                                                 <option value="{{ $cargo->id }}">{{ $cargo->nombre_cargo }}</option>
                                                             @endforeach
                                                         </select>
                                                         @error('v_cargo_mandante_id') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                     <div>
                                                         <label for="v_tipo_condicion_personal_id" class="label-form">Condición del Trabajador (Opcional)</label>
                                                         <select wire:model="v_tipo_condicion_personal_id" id="v_tipo_condicion_personal_id" class="input-field w-full">
                                                             <option value="">Sin condición especial</option>
                                                             @foreach($tiposCondicionPersonal as $tcp)
                                                                 <option value="{{ $tcp->id }}">{{ $tcp->nombre }}</option>
                                                             @endforeach
                                                         </select>
                                                         @error('v_tipo_condicion_personal_id') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                      <div> <!-- Placeholder --> </div>

                                                     <div>
                                                         <label for="v_fecha_ingreso_vinculacion" class="label-form">Fecha Ingreso a Vinculación <span class="text-red-500">*</span></label>
                                                         <input type="date" wire:model.lazy="v_fecha_ingreso_vinculacion" id="v_fecha_ingreso_vinculacion" class="input-field w-full">
                                                         @error('v_fecha_ingreso_vinculacion') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                     <div>
                                                         <label for="v_fecha_contrato" class="label-form">Fecha Contrato (Opcional)</label>
                                                         <input type="date" wire:model.lazy="v_fecha_contrato" id="v_fecha_contrato" class="input-field w-full">
                                                         @error('v_fecha_contrato') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>

                                                     <div class="md:col-span-2">
                                                         <label for="v_is_active" class="label-form flex items-center mt-2">
                                                             <input type="checkbox" wire:model.live="v_is_active" id="v_is_active" class="form-checkbox rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-indigo-400">
                                                             <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Vinculación Activa</span>
                                                         </label>
                                                     </div>

                                                     @if(!$v_is_active)
                                                         <div class="md:col-span-2 section-title !border-none !pb-0">Desactivar Vinculación</div>
                                                         <div>
                                                             <label for="v_fecha_desactivacion" class="label-form">Fecha de Desactivación <span class="text-red-500">*</span></label>
                                                             <input type="date" wire:model.lazy="v_fecha_desactivacion" id="v_fecha_desactivacion" class="input-field w-full">
                                                             @error('v_fecha_desactivacion') <span class="error-message">{{ $message }}</span> @enderror
                                                         </div>
                                                         <div class="md:col-span-2"> 
                                                             <label for="v_motivo_desactivacion" class="label-form">Motivo de Desactivación <span class="text-red-500">*</span></label>
                                                             <textarea wire:model.lazy="v_motivo_desactivacion" id="v_motivo_desactivacion" rows="3" class="input-field w-full"></textarea>
                                                             @error('v_motivo_desactivacion') <span class="error-message">{{ $message }}</span> @enderror
                                                         </div>
                                                     @endif
                                                 </div>
                                             </div>
                                         </div>
                                     </div>
                                 </div>
                                 <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                     <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ms-3 sm:w-auto sm:text-sm">
                                         {{ $vinculacionId ? 'Guardar Cambios' : 'Crear Vinculación' }}
                                     </button>
                                     <button type="button" wire:click="cerrarModalVinculacion" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                                         Cancelar
                                     </button>
                                 </div>
                             </form>
                         </div>
                     </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>