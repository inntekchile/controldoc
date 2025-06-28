<div>
    {{-- El slot name="header" ya no es necesario aquí, se maneja en PanelOperacion --}}

    <div class="py-0"> {{-- Padding reducido, el PanelOperacion puede manejar el padding general --}}
        <div class="max-w-full mx-auto"> {{-- Sin sm:px-6 lg:px-8 --}}
            <div class="bg-transparent dark:bg-transparent overflow-hidden"> {{-- Sin shadow-xl sm:rounded-lg p-6, ni color de fondo --}}

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

                {{-- VISTA: LISTADO DE TRABAJADORES --}}
                @if ($vistaActual === 'listado_trabajadores')
                    @if ($unidadOrganizacionalId)
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
                                        <th wire:click="sortBy('trabajadores.id')" class="table-header cursor-pointer">ID <x-sort-icon field="trabajadores.id" :sortField="$sortByTrabajador" :sortDirection="$sortDirectionTrabajador" /></th>
                                        <th wire:click="sortBy('trabajadores.rut')" class="table-header cursor-pointer">RUT <x-sort-icon field="trabajadores.rut" :sortField="$sortByTrabajador" :sortDirection="$sortDirectionTrabajador" /></th>
                                        <th wire:click="sortBy('trabajadores.apellido_paterno')" class="table-header cursor-pointer">Trabajador <x-sort-icon field="trabajadores.apellido_paterno" :sortField="$sortByTrabajador" :sortDirection="$sortDirectionTrabajador" /></th>
                                        <th class="table-header">Cargo Actual (En UO Sel.)</th>
                                        <th class="table-header text-center">% Cumplimiento</th>
                                        <th class="table-header text-center">Acceso Habilitado</th>
                                        <th wire:click="sortBy('trabajadores.is_active')" class="table-header text-center cursor-pointer">Estado Ficha <x-sort-icon field="trabajadores.is_active" :sortField="$sortByTrabajador" :sortDirection="$sortDirectionTrabajador" /></th>
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
                                                    $vinculacionEnUOSeleccionada = $trab->vinculaciones->first();
                                                @endphp
                                                {{ $vinculacionEnUOSeleccionada && $vinculacionEnUOSeleccionada->cargoMandante ? $vinculacionEnUOSeleccionada->cargoMandante->nombre_cargo : 'N/A' }}
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
                                                
                                                <button 
                                                    wire:click="eliminarTrabajador({{ $trab->id }})"
                                                    wire:confirm="¿Estás seguro de eliminar este trabajador?\n\nEsta acción eliminará PERMANENTEMENTE la ficha del trabajador y TODAS sus vinculaciones asociadas. No se puede deshacer."
                                                    class="action-button-delete" title="Eliminar Trabajador">
                                                    <x-icons.trash class="inline-block"/>
                                                </button>
                                                
                                                <button wire:click="seleccionarTrabajadorParaVinculaciones({{ $trab->id }})" class="action-button-link" title="Ver/Gestionar Vinculaciones"><x-icons.link class="inline-block"/></button>
                                                <button wire:click="abrirModalDocumentosTrabajador({{ $trab->id }})" class="action-button-neutral" title="Ver/Gestionar Documentos">
                                                    <x-icons.clipboard-list class="inline-block"/>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="table-cell text-center">
                                                 @if($unidadOrganizacionalId)
                                                     No se encontraron trabajadores vinculados a esta Unidad Organizacional. Puede agregar uno nuevo.
                                                 @else
                                                     Error: El contexto de operación (Mandante - UO) no ha sido establecido.
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
                             Error: El contexto de operación (Mandante - Unidad Organizacional) no está definido. Por favor, regrese al Panel de Operación y seleccione una vinculación.
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
                            <x-icons.arrow-left class="w-5 h-5 mr-1 inline-block"/> Volver a Listado Trabajadores @if($nombreVinculacionSeleccionada) ({{ $nombreVinculacionSeleccionada }}) @endif
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
                                                 @if($unidadOrganizacionalId && $nombreVinculacionSeleccionada)
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
                                                         <label for="direccion_calle_trab" class="label-form">Calle</label>
                                                         <input type="text" wire:model.lazy="direccion_calle" id="direccion_calle_trab" class="input-field w-full">
                                                         @error('direccion_calle') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                     <div>
                                                         <label for="direccion_numero_trab" class="label-form">Número</label>
                                                         <input type="text" wire:model.lazy="direccion_numero" id="direccion_numero_trab" class="input-field w-full">
                                                         @error('direccion_numero') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                     <div>
                                                         <label for="direccion_departamento_trab" class="label-form">Depto / Casa</label>
                                                         <input type="text" wire:model.lazy="direccion_departamento" id="direccion_departamento_trab" class="input-field w-full">
                                                         @error('direccion_departamento') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                     <div> </div> 
                                                     <div>
                                                         <label for="trabajador_region_id_modal" class="label-form">Región</label>
                                                         <select wire:model.live="trabajador_region_id" id="trabajador_region_id_modal" class="input-field w-full">
                                                             <option value="">Seleccione Región...</option>
                                                             @foreach($regiones as $region) <option value="{{ $region->id }}">{{ $region->nombre }}</option> @endforeach
                                                         </select>
                                                         @error('trabajador_region_id') <span class="error-message">{{ $message }}</span> @enderror
                                                     </div>
                                                     <div>
                                                         <label for="trabajador_comuna_id_modal" class="label-form">Comuna</label>
                                                         <select wire:model="trabajador_comuna_id" id="trabajador_comuna_id_modal" class="input-field w-full" @if(empty($comunasDisponiblesTrabajador)) disabled @endif>
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
                                                         <label for="trabajador_is_active_modal" class="label-form flex items-center mt-2">
                                                             <input type="checkbox" wire:model="trabajador_is_active" id="trabajador_is_active_modal" class="form-checkbox rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-indigo-400">
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
                                                 @if($unidadOrganizacionalId && $nombreVinculacionSeleccionada && $vistaActual === 'listado_vinculaciones')
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
                
                {{-- MODAL PARA GESTIONAR DOCUMENTOS TRABAJADOR --}}
                @if ($showModalDocumentosTrabajador && $trabajadorParaDocumentos)
                    <div class="fixed z-30 inset-0 overflow-y-auto" aria-labelledby="modal-title-documentos" role="dialog" aria-modal="true" x-data="{ openInfo: null }">
                        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75" aria-hidden="true" wire:click="cerrarModalDocumentosTrabajador"></div>
                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>
                            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-7xl sm:w-full">
                                <form wire:submit.prevent="cargarDocumentos">
                                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                                        <div class="sm:flex sm:items-start">
                                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                                <h3 class="text-xl leading-6 font-medium text-gray-900 dark:text-gray-100 section-title mb-1" id="modal-title-documentos">
                                                    Documentos Requeridos para: <span class="font-semibold">{{ $nombreTrabajadorParaDocumentosModal }}</span>
                                                </h3>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">RUT: {{ $trabajadorParaDocumentos->rut }}</p>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    Contexto: {{ $nombreVinculacionSeleccionada }}
                                                    @if($vinculacionActivaEnUOContexto)
                                                        (Cargo: {{ $vinculacionActivaEnUOContexto->cargoMandante?->nombre_cargo ?? 'N/A' }})
                                                    @else
                                                        (Sin vinculación activa en esta UO)
                                                    @endif
                                                </p>
                                                
                                                @if (session()->has('info_modal_documentos'))
                                                    <div class="alert-info mt-4">
                                                        {{ session('info_modal_documentos') }}
                                                    </div>
                                                @endif
                                                @if (session()->has('error_modal_documentos'))
                                                    <div class="alert-danger mt-4">
                                                        {{ session('error_modal_documentos') }}
                                                    </div>
                                                @endif

                                                <div class="mt-6 overflow-x-auto shadow-md sm:rounded-lg">
                                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                                        <thead class="bg-gray-50 dark:bg-gray-700">
                                                            <tr>
                                                                <th class="table-header-sm px-2">N°</th>
                                                                <th class="table-header-sm">Documento</th>
                                                                <th class="table-header-sm text-center">Afecta % / Restr. Acc.</th>
                                                                <th class="table-header-sm">Estado Actual</th>
                                                                <th class="table-header-sm">F. Emisión</th>
                                                                <th class="table-header-sm">F. Vencimiento</th>
                                                                <th class="table-header-sm">Período</th>
                                                                <th class="table-header-sm">Cargar Nuevo Archivo</th>
                                                                <th class="table-header-sm text-center">Opc.</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                                            @forelse ($documentosRequeridosParaTrabajador as $index => $doc)
                                                                @php
                                                                    $reglaId = $doc['regla_documental_id_origen'];
                                                                    $estado = $doc['estado_actual_documento'];
                                                                    $colorClass = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
                                                                    if ($estado === 'No Cargado') { $colorClass = 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100'; }
                                                                    if ($estado === 'Vigente' || $estado === 'Aprobado') { $colorClass = 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'; }
                                                                    if ($estado === 'Vencido' || $estado === 'Rechazado') { $colorClass = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100'; }
                                                                    if ($estado === 'Pendiente Validación' || $estado === 'En Revisión') { $colorClass = 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100'; }
                                                                @endphp
                                                                <tr class="table-row-hover text-sm">
                                                                    <td class="table-cell-sm px-2">{{ $loop->iteration }}</td>
                                                                    <td class="table-cell-sm font-medium">
                                                                        {{ $doc['nombre_documento_texto'] }}
                                                                        @if($doc['observacion_documento_texto'])
                                                                            <span class="block text-xs text-gray-500 dark:text-gray-400 italic">{{ Str::limit($doc['observacion_documento_texto'], 70) }}</span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="table-cell-sm text-center">
                                                                        <div class="flex justify-center items-center space-x-1">
                                                                            <span title="Afecta % Cumplimiento">
                                                                                @if($doc['afecta_cumplimiento']) <x-icons.check-circle class="text-green-500 w-4 h-4"/> @else <x-icons.x-circle class="text-red-500 w-4 h-4"/> @endif
                                                                            </span>
                                                                            <span title="Restringe Acceso">
                                                                                @if($doc['restringe_acceso']) <x-icons.ban class="text-orange-500 w-4 h-4"/> @else <x-icons.check-circle class="text-green-500 w-4 h-4"/> @endif
                                                                            </span>
                                                                        </div>
                                                                    </td>
                                                                    <td class="table-cell-sm">
                                                                        <div class="flex items-center space-x-2">
                                                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $colorClass }}">
                                                                                {{ $doc['estado_actual_documento'] }}
                                                                            </span>
                                                                            {{-- BOTÓN ELIMINAR RESTAURADO --}}
                                                                            @if($doc['archivo_cargado'] && $doc['estado_actual_documento'] === 'Pendiente Validación')
                                                                                <button
                                                                                    type="button"
                                                                                    wire:click="eliminarDocumentoCargado({{ $doc['archivo_cargado']->id }})"
                                                                                    wire:confirm="¿Está seguro de eliminar este documento pendiente? Esta acción es irreversible."
                                                                                    class="text-red-500 hover:text-red-700"
                                                                                    title="Eliminar documento pendiente">
                                                                                    <x-icons.trash class="w-4 h-4" />
                                                                                </button>
                                                                            @endif
                                                                        </div>
                                                                        @if($doc['archivo_cargado'])
                                                                            <a href="{{ Storage::url($doc['archivo_cargado']->ruta_archivo) }}" target="_blank" class="text-xs text-blue-500 hover:text-blue-700 block mt-1" title="{{ $doc['archivo_cargado']->nombre_original_archivo }}">
                                                                                Ver Archivo Actual
                                                                            </a>
                                                                        @endif
                                                                    </td>
                                                                    <td class="table-cell-sm">
                                                                        @if ($doc['archivo_cargado'] && $doc['archivo_cargado']->fecha_emision)
                                                                            <span class="text-gray-900 dark:text-gray-200 p-1 block">{{ $doc['archivo_cargado']->fecha_emision->format('d-m-Y') }}</span>
                                                                        @elseif ($doc['valida_emision'] || $doc['tipo_vencimiento_nombre'] === 'DESDE EMISION')
                                                                            <input type="date" class="input-field-sm w-full py-1" wire:model.defer="documentosParaCargar.{{ $reglaId }}.fecha_emision_input">
                                                                        @else
                                                                            <span class="text-gray-400">N/A</span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="table-cell-sm">
                                                                        @if ($doc['archivo_cargado'] && $doc['archivo_cargado']->fecha_vencimiento)
                                                                             <span class="text-gray-900 dark:text-gray-200 p-1 block">{{ $doc['archivo_cargado']->fecha_vencimiento->format('d-m-Y') }}</span>
                                                                        @elseif ($doc['valida_vencimiento'] || $doc['tipo_vencimiento_nombre'] === 'FIJO')
                                                                            <input type="date" class="input-field-sm w-full py-1" wire:model.defer="documentosParaCargar.{{ $reglaId }}.fecha_vencimiento_input">
                                                                        @elseif ($doc['tipo_vencimiento_nombre'] === 'INDEFINIDO')
                                                                            <span class="text-gray-400">Indefinido</span>
                                                                        @else
                                                                            <span class="text-gray-400">N/A</span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="table-cell-sm">
                                                                        @if ($doc['archivo_cargado'] && $doc['archivo_cargado']->periodo)
                                                                            <span class="text-gray-900 dark:text-gray-200 p-1 block">{{ \Carbon\Carbon::createFromFormat('Y-m', $doc['archivo_cargado']->periodo)->translatedFormat('F Y') }}</span>
                                                                        @elseif ($doc['tipo_vencimiento_nombre'] === 'PERIODO')
                                                                            <input type="month" class="input-field-sm w-full py-1" wire:model.defer="documentosParaCargar.{{ $reglaId }}.periodo_input" placeholder="YYYY-MM">
                                                                        @else
                                                                            <span class="text-gray-400">N/A</span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="table-cell-sm">
                                                                        <input type="file" class="input-file-sm w-full text-xs" wire:model="documentosParaCargar.{{ $reglaId }}.archivo_input">
                                                                        <div wire:loading wire:target="documentosParaCargar.{{ $reglaId }}.archivo_input" class="text-xs text-blue-500 mt-1">Cargando...</div>
                                                                    </td>
                                                                    <td class="table-cell-sm text-center">
                                                                        <div class="relative inline-block text-left">
                                                                            <button @click="openInfo === {{ $index }} ? openInfo = null : openInfo = {{ $index }}" type="button" class="action-button-info p-1" title="Más Información">
                                                                                <x-icons.information-circle class="w-5 h-5"/>
                                                                            </button>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                                @if(isset($uploadSuccess[$reglaId]) || $errors->has('documentosParaCargar.' . $reglaId . '.archivo_input'))
                                                                    <tr class="text-xs">
                                                                        <td colspan="9" class="p-1 px-4">
                                                                            @if(isset($uploadSuccess[$reglaId]))
                                                                                <span class="text-green-600 dark:text-green-400 font-semibold flex items-center">
                                                                                    <x-icons.check-circle-solid class="w-4 h-4 mr-1"/> {{ $uploadSuccess[$reglaId] }}
                                                                                </span>
                                                                            @endif
                                                                            @error('documentosParaCargar.' . $reglaId . '.archivo_input')
                                                                                <span class="text-red-600 dark:text-red-400 font-semibold flex items-center">
                                                                                    <x-icons.x-circle-solid class="w-4 h-4 mr-1"/> {{ $message }}
                                                                                </span>
                                                                            @enderror
                                                                        </td>
                                                                    </tr>
                                                                @endif

                                                                <tr x-show="openInfo === {{ $index }}" x-transition>
                                                                    <td colspan="9" class="p-3 bg-gray-100 dark:bg-gray-700 text-xs">
                                                                        <h4 class="font-semibold mb-1">Criterios de Evaluación para: {{ $doc['nombre_documento_texto'] }}</h4>
                                                                        @if (!empty($doc['criterios_evaluacion']))
                                                                            <ul class="list-disc ml-5 space-y-1">
                                                                                @foreach($doc['criterios_evaluacion'] as $criterioItem)
                                                                                    <li>
                                                                                        <strong>Criterio:</strong> {{ $criterioItem['criterio'] ?? 'N/A' }}
                                                                                        @if($criterioItem['sub_criterio']) | <strong>Sub-Criterio:</strong> {{ $criterioItem['sub_criterio'] }} @endif
                                                                                        @if($criterioItem['texto_rechazo']) <br><span class="text-red-600 dark:text-red-400">Posible Rechazo: {{ $criterioItem['texto_rechazo'] }}</span> @endif
                                                                                        @if($criterioItem['aclaracion']) <br><span class="text-blue-600 dark:text-blue-400">Aclaración: {{ $criterioItem['aclaracion'] }}</span> @endif
                                                                                    </li>
                                                                                @endforeach
                                                                            </ul>
                                                                        @else
                                                                            <p>No hay criterios de evaluación específicos definidos para este documento.</p>
                                                                        @endif
                                                                         @if($doc['observacion_documento_texto'])
                                                                            <h4 class="font-semibold mt-2 mb-1">Observación General del Documento:</h4>
                                                                            <p>{{ $doc['observacion_documento_texto'] }}</p>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @empty
                                                                <tr>
                                                                    <td colspan="9" class="table-cell text-center p-4">
                                                                        No se encontraron documentos requeridos para este trabajador bajo los filtros actuales, o no tiene una vinculación activa que permita determinar los documentos.
                                                                    </td>
                                                                </tr>
                                                            @endforelse
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                        <button type="submit" class="btn-primary sm:ms-3" wire:loading.attr="disabled" wire:target="cargarDocumentos">
                                            <span wire:loading.remove wire:target="cargarDocumentos">
                                                <x-icons.upload class="w-5 h-5 mr-1 inline-block"/> Cargar Documentos Seleccionados
                                            </span>
                                            <span wire:loading wire:target="cargarDocumentos">
                                                <x-icons.spinner class="w-5 h-5 mr-1 inline-block"/> Procesando...
                                            </span>
                                        </button>
                                        <button type="button" wire:click="cerrarModalDocumentosTrabajador" class="btn-secondary">
                                            Cerrar
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