<div>
    {{-- Mensajes Flash --}}
    @if (session()->has('message')) <div class="alert-success mb-4">{{ session('message') }}</div> @endif
    @if (session()->has('error')) <div class="alert-danger mb-4">{{ session('error') }}</div> @endif
    @if (session()->has('message_asignacion')) <div class="alert-success mb-4">{{ session('message_asignacion') }}</div> @endif

    {{-- VISTA: LISTADO DE VEHÍCULOS --}}
    @if ($vistaActual === 'listado_vehiculos')
        @if ($unidadOrganizacionalId)
            <div class="mb-4 flex flex-col sm:flex-row justify-between items-center">
                <div class="w-full sm:w-2/3 mb-2 sm:mb-0">
                    <input wire:model.live.debounce.300ms="searchVehiculo" type="text"
                           placeholder="Buscar por Patente, Marca o Tipo..." class="input-field w-full">
                </div>
                <button wire:click="abrirModalNuevoVehiculo" class="btn-primary">
                    <x-icons.plus class="w-5 h-5 mr-1 inline-block"/> Agregar Ficha Vehículo
                </button>
            </div>

            <div class="overflow-x-auto shadow-md sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="table-header">Patente</th>
                            <th class="table-header">Marca / Tipo</th>
                            <th class="table-header">Año Fab.</th>
                            <th class="table-header text-center">Estado Ficha</th>
                            <th class="table-header text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($vehiculosPaginados ?? [] as $vehiculo)
                            <tr class="table-row-hover">
                                <td class="table-cell font-mono">{{ $vehiculo->patente_completa }}</td>
                                <td class="table-cell">
                                    <span class="font-semibold">{{ $vehiculo->marcaVehiculo->nombre ?? 'N/A' }}</span>
                                    <span class="block text-xs text-gray-500">{{ $vehiculo->tipoVehiculo->nombre ?? 'N/A' }}</span>
                                </td>
                                <td class="table-cell">{{ $vehiculo->ano_fabricacion }}</td>
                                <td class="table-cell text-center"><span wire:click="toggleActivoVehiculo({{ $vehiculo->id }})" class="status-badge {{ $vehiculo->is_active ? 'status-active' : 'status-inactive' }}">{{ $vehiculo->is_active ? 'Activo' : 'Inactivo' }}</span></td>
                                <td class="table-cell text-center whitespace-nowrap">
                                    <button wire:click="abrirModalEditarVehiculo({{ $vehiculo->id }})" class="action-button-edit" title="Editar Ficha"><x-icons.edit/></button>
                                    <button wire:click="eliminarVehiculo({{ $vehiculo->id }})" wire:confirm="¿Estás seguro?\n\nSe eliminará la ficha del vehículo y TODAS sus asignaciones a Unidades Organizacionales." class="action-button-delete" title="Eliminar Ficha"><x-icons.trash/></button>
                                    <button wire:click="seleccionarVehiculoParaAsignaciones({{ $vehiculo->id }})" class="action-button-link" title="Ver/Gestionar Asignaciones"><x-icons.link/></button>
                                    <button wire:click="abrirModalDocumentos({{ $vehiculo->id }})" class="action-button-neutral" title="Gestionar Documentos"><x-icons.clipboard-list/></button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="table-cell text-center">No se encontraron vehículos asignados a esta Unidad Organizacional. Puede crear una ficha y luego asignarla.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($vehiculosPaginados && $vehiculosPaginados->hasPages())<div class="mt-4">{{ $vehiculosPaginados->links(data: ['pageName' => 'vehiculosPage']) }}</div>@endif
        @else
            <div class="p-4 text-center text-gray-500 dark:text-gray-400">Error: El contexto de operación (Mandante - UO) no está definido. Por favor, regrese al Panel de Operación y seleccione una vinculación.</div>
        @endif

    {{-- VISTA: LISTADO DE ASIGNACIONES --}}
    @elseif ($vistaActual === 'listado_asignaciones' && $vehiculoSeleccionado)
        <div class="mb-4 p-4 border dark:border-gray-700 rounded-md">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Vehículo: <span class="font-normal">{{ $vehiculoSeleccionado->patente_completa }} ({{ $vehiculoSeleccionado->marcaVehiculo->nombre ?? 'N/A' }})</span></h3>
            <button wire:click="abrirModalEditarVehiculo({{ $vehiculoSeleccionado->id }})" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">Editar Ficha de este Vehículo</button>
        </div>
        <div class="mb-4 flex flex-col sm:flex-row justify-between items-center">
            <button wire:click="irAListadoVehiculos" class="btn-secondary mb-2 sm:mb-0"><x-icons.arrow-left class="w-5 h-5 mr-1"/> Volver a Listado Vehículos</button>
            <button wire:click="abrirModalNuevaAsignacion" class="btn-primary"><x-icons.plus class="w-5 h-5 mr-1"/> Agregar Asignación</button>
        </div>
        <div class="overflow-x-auto shadow-md sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="table-header">Asignación (Mandante / UO)</th>
                        <th class="table-header text-center">Fecha Asignación</th>
                        <th class="table-header text-center">Estado</th>
                        <th class="table-header text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($asignacionesPaginadas ?? [] as $asignacion)
                    <tr class="table-row-hover">
                        <td class="table-cell">{{ $asignacion->mandante->razon_social ?? 'Mandante no encontrado' }} /<br>{{ $asignacion->nombre_unidad }}</td>
                        <td class="table-cell text-center">{{ \Carbon\Carbon::parse($asignacion->pivot->fecha_asignacion)->format('d-m-Y') }}</td>
                        <td class="table-cell text-center"><span class="status-badge {{ $asignacion->pivot->is_active ? 'status-active' : 'status-inactive' }}">{{ $asignacion->pivot->is_active ? 'Activa' : 'Inactiva' }}</span></td>
                        <td class="table-cell text-center">
                            <button wire:click="abrirModalEditarAsignacion({{ $asignacion->pivot->id }})" class="action-button-edit" title="Editar Asignación">
                                <x-icons.edit/>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="table-cell text-center">Este vehículo no tiene asignaciones a Unidades Organizacionales.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($asignacionesPaginadas && $asignacionesPaginadas->hasPages())<div class="mt-4">{{ $asignacionesPaginadas->links(data: ['pageName' => 'asignacionesPage']) }}</div>@endif
    
    @endif
    
    {{-- MODAL: FICHA DEL VEHÍCULO --}}
    @if ($showModalFicha)
         <div class="fixed z-20 inset-0 overflow-y-auto" aria-labelledby="modal-title-vehiculo" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
             <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75" aria-hidden="true" wire:click="cerrarModalFicha"></div>
             <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>
             <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                 <form wire:submit.prevent="guardarVehiculo" id="formFichaVehiculo">
                     <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                         <div class="sm:flex sm:items-start w-full">
                             <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                 <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 section-title" id="modal-title-vehiculo">{{ $vehiculoId ? 'Editar Ficha del Vehículo' : 'Agregar Nueva Ficha de Vehículo' }}</h3>
                                 <div class="mt-4 space-y-4">
                                     <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                         <div class="md:col-span-3 section-title !mt-0 !border-none !pb-0">Identificación</div>
                                         <div class="md:col-span-2">
                                             <label class="label-form">Patente <span class="text-red-500">*</span></label>
                                             <div class="flex items-center space-x-2">
                                                <input type="text" wire:model.lazy="patente_letras" id="patente_letras" class="input-field w-1/2 uppercase" placeholder="ABCD">
                                                <span class="text-gray-400">-</span>
                                                <input type="text" wire:model.lazy="patente_numeros" id="patente_numeros" class="input-field w-1/2 uppercase" placeholder="12">
                                             </div>
                                             @error('patente_letras') <span class="error-message">{{ $message }}</span> @enderror
                                             @error('patente_numeros') <span class="error-message">{{ $message }}</span> @enderror
                                         </div>
                                         <div>
                                             <label for="ano_fabricacion" class="label-form">Año de Fabricación <span class="text-red-500">*</span></label>
                                             <input type="number" wire:model.lazy="ano_fabricacion" id="ano_fabricacion" class="input-field w-full" placeholder="YYYY">
                                             @error('ano_fabricacion') <span class="error-message">{{ $message }}</span> @enderror
                                         </div>
                                         <div class="md:col-span-3 section-title !border-none !pb-0">Características</div>
                                         <div>
                                             <label for="marca_vehiculo_id" class="label-form">Marca <span class="text-red-500">*</span></label>
                                             <select wire:model="marca_vehiculo_id" id="marca_vehiculo_id" class="input-field w-full">
                                                 <option value="">Seleccione...</option>
                                                 @foreach($marcasVehiculo as $marca) <option value="{{ $marca->id }}">{{ $marca->nombre }}</option> @endforeach
                                             </select>
                                             @error('marca_vehiculo_id') <span class="error-message">{{ $message }}</span> @enderror
                                         </div>
                                         <div>
                                             <label for="color_vehiculo_id" class="label-form">Color <span class="text-red-500">*</span></label>
                                             <select wire:model="color_vehiculo_id" id="color_vehiculo_id" class="input-field w-full">
                                                 <option value="">Seleccione...</option>
                                                 @foreach($coloresVehiculo as $color) <option value="{{ $color->id }}">{{ $color->nombre }}</option> @endforeach
                                             </select>
                                             @error('color_vehiculo_id') <span class="error-message">{{ $message }}</span> @enderror
                                         </div>
                                         <div>
                                             <label for="tipo_vehiculo_id" class="label-form">Tipo de Vehículo <span class="text-red-500">*</span></label>
                                             <select wire:model="tipo_vehiculo_id" id="tipo_vehiculo_id" class="input-field w-full">
                                                 <option value="">Seleccione...</option>
                                                 @foreach($tiposVehiculo as $tipo) <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option> @endforeach
                                             </select>
                                             @error('tipo_vehiculo_id') <span class="error-message">{{ $message }}</span> @enderror
                                         </div>
                                         <div class="md:col-span-3">
                                             <label for="tenencia_vehiculo_id" class="label-form">Propiedad del Vehículo (Opcional)</label>
                                             <select wire:model="tenencia_vehiculo_id" id="tenencia_vehiculo_id" class="input-field w-full">
                                                 <option value="">Seleccione...</option>
                                                 @foreach($tenenciasVehiculo as $tenencia) <option value="{{ $tenencia->id }}">{{ $tenencia->nombre }}</option> @endforeach
                                             </select>
                                             @error('tenencia_vehiculo_id') <span class="error-message">{{ $message }}</span> @enderror
                                         </div>
                                         <div class="md:col-span-3">
                                             <label for="vehiculo_is_active_modal" class="label-form flex items-center mt-2">
                                                 <input type="checkbox" wire:model="vehiculo_is_active" id="vehiculo_is_active_modal" class="form-checkbox h-5 w-5 rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-indigo-400">
                                                 <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Ficha de Vehículo Activa</span>
                                             </label>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     </div>
                     <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                         <button type="submit" class="btn-primary w-full sm:w-auto sm:ml-3">{{ $vehiculoId ? 'Guardar Cambios' : 'Crear Ficha' }}</button>
                         <button type="button" wire:click="cerrarModalFicha" class="btn-secondary w-full mt-3 sm:mt-0 sm:w-auto">Cancelar</button>
                     </div>
                 </form>
             </div>
         </div>
        </div>
    @endif

    {{-- MODAL: NUEVA/EDITAR ASIGNACIÓN --}}
    @if ($showModalAsignacion)
        <div class="fixed z-30 inset-0 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="cerrarModalAsignacion"></div>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit.prevent="guardarAsignacion">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 section-title">{{ $asignacionId ? 'Editar Asignación' : 'Nueva Asignación' }} para Vehículo: {{ $vehiculoSeleccionado->patente_completa }}</h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="a_unidad_organizacional_id" class="label-form">Unidad Organizacional <span class="text-red-500">*</span></label>
                                    <select wire:model="a_unidad_organizacional_id" id="a_unidad_organizacional_id" class="input-field w-full">
                                        <option value="">Seleccione...</option>
                                        @foreach($unidadesOrganizacionalesDisponibles as $uo)
                                            <option value="{{ $uo->id }}">{{ $uo->mandante->razon_social }} - {{ $uo->nombre_unidad }}</option>
                                        @endforeach
                                    </select>
                                    @error('a_unidad_organizacional_id')<span class="error-message">{{$message}}</span>@enderror
                                </div>
                                <div>
                                    <label for="a_fecha_asignacion" class="label-form">Fecha Asignación <span class="text-red-500">*</span></label>
                                    <input type="date" wire:model.lazy="a_fecha_asignacion" id="a_fecha_asignacion" class="input-field w-full">
                                    @error('a_fecha_asignacion')<span class="error-message">{{$message}}</span>@enderror
                                </div>
                                <div>
                                    <label class="label-form flex items-center mt-2">
                                        <input type="checkbox" wire:model.live="a_is_active" class="form-checkbox">
                                        <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Asignación Activa</span>
                                    </label>
                                </div>
                                @if(!$a_is_active)
                                    <div class="space-y-4">
                                        <div>
                                            <label for="a_fecha_desasignacion" class="label-form">Fecha de Desactivación <span class="text-red-500">*</span></label>
                                            <input type="date" wire:model.lazy="a_fecha_desasignacion" id="a_fecha_desasignacion" class="input-field w-full">
                                            @error('a_fecha_desasignacion') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="a_motivo_desasignacion" class="label-form">Motivo de Desactivación <span class="text-red-500">*</span></label>
                                            <textarea wire:model.lazy="a_motivo_desasignacion" id="a_motivo_desasignacion" rows="3" class="input-field w-full"></textarea>
                                            @error('a_motivo_desasignacion') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="btn-primary w-full sm:w-auto sm:ml-3">{{ $asignacionId ? 'Guardar Cambios' : 'Crear Asignación' }}</button>
                            <button type="button" wire:click="cerrarModalAsignacion" class="btn-secondary w-full mt-3 sm:mt-0 sm:w-auto">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    
    {{-- MODAL PARA GESTIONAR DOCUMENTOS DE VEHÍCULOS --}}
    @if ($showDocumentosModal && $vehiculoParaDocumentos)
        <div class="fixed z-30 inset-0 overflow-y-auto" aria-labelledby="modal-title-documentos" role="dialog" aria-modal="true" x-data="{ openInfo: null }">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75" aria-hidden="true" wire:click="cerrarModalDocumentos"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-7xl sm:w-full">
                    <form wire:submit.prevent="cargarDocumentos">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-xl leading-6 font-medium text-gray-900 dark:text-gray-100 section-title mb-1" id="modal-title-documentos">
                                        Documentos Requeridos para: <span class="font-semibold">{{ $nombreVehiculoParaDocumentosModal }}</span>
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Vehículo: {{ $vehiculoParaDocumentos->marcaVehiculo?->nombre }} {{ $vehiculoParaDocumentos->tipoVehiculo?->nombre }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Contexto: {{ $nombreVinculacionSeleccionada }}</p>

                                    @if (session()->has('message_modal_documentos')) <div class="alert-success mt-4">{{ session('message_modal_documentos') }}</div> @endif
                                    @if (session()->has('error_modal_documentos')) <div class="alert-danger mt-4">{{ session('error_modal_documentos') }}</div> @endif
                                    @if (session()->has('info_modal_documentos')) <div class="alert-info mt-4">{{ session('info_modal_documentos') }}</div> @endif

                                    <div class="mt-6 overflow-x-auto shadow-md sm:rounded-lg">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                            <thead class="bg-gray-50 dark:bg-gray-700">
                                                <tr>
                                                    <th class="table-header-sm px-2">N°</th><th class="table-header-sm">Documento</th>
                                                    <th class="table-header-sm text-center">Afecta % / Restr. Acc.</th><th class="table-header-sm">Estado Actual</th>
                                                    <th class="table-header-sm">F. Emisión</th><th class="table-header-sm">F. Vencimiento</th>
                                                    <th class="table-header-sm">Período</th><th class="table-header-sm">Cargar Nuevo Archivo</th>
                                                    <th class="table-header-sm text-center">Opc.</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                                @forelse ($documentosRequeridos as $index => $doc)
                                                    @php
                                                        $reglaId = $doc['regla_documental_id_origen']; $estado = $doc['estado_actual_documento'];
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
                                                            @if($doc['observacion_documento_texto'])<span class="block text-xs text-gray-500 dark:text-gray-400 italic">{{ Str::limit($doc['observacion_documento_texto'], 70) }}</span>@endif
                                                        </td>
                                                        <td class="table-cell-sm text-center">
                                                            <div class="flex justify-center items-center space-x-1">
                                                                <span title="Afecta % Cumplimiento">@if($doc['afecta_cumplimiento']) <x-icons.check-circle class="text-green-500 w-4 h-4"/> @else <x-icons.x-circle class="text-red-500 w-4 h-4"/> @endif</span>
                                                                <span title="Restringe Acceso">@if($doc['restringe_acceso']) <x-icons.ban class="text-orange-500 w-4 h-4"/> @else <x-icons.check-circle class="text-green-500 w-4 h-4"/> @endif</span>
                                                            </div>
                                                        </td>
                                                        <td class="table-cell-sm">
                                                            <div class="flex items-center space-x-2">
                                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $colorClass }}">{{ $doc['estado_actual_documento'] }}</span>
                                                                @if($doc['archivo_cargado'] && $doc['estado_actual_documento'] === 'Pendiente Validación')
                                                                    <button type="button" wire:click="eliminarDocumentoCargado({{ $doc['archivo_cargado']->id }})" wire:confirm="¿Está seguro de eliminar este documento pendiente? Esta acción es irreversible." class="text-red-500 hover:text-red-700" title="Eliminar documento pendiente"><x-icons.trash class="w-4 h-4" /></button>
                                                                @endif
                                                            </div>
                                                            @if($doc['archivo_cargado'])<a href="{{ Storage::url($doc['archivo_cargado']->ruta_archivo) }}" target="_blank" class="text-xs text-blue-500 hover:text-blue-700 block mt-1" title="{{ $doc['archivo_cargado']->nombre_original_archivo }}">Ver Archivo Actual</a>@endif
                                                        </td>
                                                        <td class="table-cell-sm">
                                                            @if ($doc['archivo_cargado'] && $doc['archivo_cargado']->fecha_emision) <span class="text-gray-900 dark:text-gray-200 p-1 block">{{ $doc['archivo_cargado']->fecha_emision->format('d-m-Y') }}</span>
                                                            @elseif ($doc['valida_emision'] || $doc['tipo_vencimiento_nombre'] === 'DESDE EMISION') <input type="date" class="input-field-sm w-full py-1" wire:model.defer="documentosParaCargar.{{ $reglaId }}.fecha_emision_input">
                                                            @else <span class="text-gray-400">N/A</span> @endif
                                                        </td>
                                                        <td class="table-cell-sm">
                                                            @if ($doc['archivo_cargado'] && $doc['archivo_cargado']->fecha_vencimiento) <span class="text-gray-900 dark:text-gray-200 p-1 block">{{ $doc['archivo_cargado']->fecha_vencimiento->format('d-m-Y') }}</span>
                                                            @elseif ($doc['valida_vencimiento'] || $doc['tipo_vencimiento_nombre'] === 'FIJO') <input type="date" class="input-field-sm w-full py-1" wire:model.defer="documentosParaCargar.{{ $reglaId }}.fecha_vencimiento_input">
                                                            @elseif ($doc['tipo_vencimiento_nombre'] === 'INDEFINIDO') <span class="text-gray-400">Indefinido</span>
                                                            @else <span class="text-gray-400">N/A</span> @endif
                                                        </td>
                                                        <td class="table-cell-sm">
                                                            @if ($doc['archivo_cargado'] && $doc['archivo_cargado']->periodo) <span class="text-gray-900 dark:text-gray-200 p-1 block">{{ \Carbon\Carbon::createFromFormat('Y-m', $doc['archivo_cargado']->periodo)->translatedFormat('F Y') }}</span>
                                                            @elseif ($doc['tipo_vencimiento_nombre'] === 'PERIODO') <input type="month" class="input-field-sm w-full py-1" wire:model.defer="documentosParaCargar.{{ $reglaId }}.periodo_input">
                                                            @else <span class="text-gray-400">N/A</span> @endif
                                                        </td>
                                                        <td class="table-cell-sm">
                                                            <input type="file" class="input-file-sm w-full text-xs" wire:model.defer="documentosParaCargar.{{ $reglaId }}.archivo_input">
                                                            <div wire:loading wire:target="documentosParaCargar.{{ $reglaId }}.archivo_input" class="text-xs text-blue-500 mt-1">Cargando...</div>
                                                        </td>
                                                        <td class="table-cell-sm text-center">
                                                            <div class="relative inline-block text-left">
                                                                <button @click="openInfo === {{ $index }} ? openInfo = null : openInfo = {{ $index }}" type="button" class="action-button-info p-1" title="Más Información"><x-icons.information-circle class="w-5 h-5"/></button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    @if(isset($uploadSuccess[$reglaId]) || $errors->has('documentosParaCargar.' . $reglaId . '.archivo_input'))
                                                        <tr class="text-xs"><td colspan="9" class="p-1 px-4">
                                                            @if(isset($uploadSuccess[$reglaId]))<span class="text-green-600 dark:text-green-400 font-semibold flex items-center"><x-icons.check-circle-solid class="w-4 h-4 mr-1"/> {{ $uploadSuccess[$reglaId] }}</span>@endif
                                                            @error('documentosParaCargar.' . $reglaId . '.archivo_input')<span class="text-red-600 dark:text-red-400 font-semibold flex items-center"><x-icons.x-circle-solid class="w-4 h-4 mr-1"/> {{ $message }}</span>@enderror
                                                        </td></tr>
                                                    @endif
                                                    <tr x-show="openInfo === {{ $index }}" x-transition>
                                                        <td colspan="9" class="p-3 bg-gray-100 dark:bg-gray-700 text-xs">
                                                            <h4 class="font-semibold mb-1">Criterios de Evaluación para: {{ $doc['nombre_documento_texto'] }}</h4>
                                                            @if (!empty($doc['criterios_evaluacion'])) <ul class="list-disc ml-5 space-y-1"> @foreach($doc['criterios_evaluacion'] as $criterioItem) <li> <strong>Criterio:</strong> {{ $criterioItem['criterio'] ?? 'N/A' }} @if($criterioItem['sub_criterio']) | <strong>Sub-Criterio:</strong> {{ $criterioItem['sub_criterio'] }} @endif @if($criterioItem['texto_rechazo']) <br><span class="text-red-600 dark:text-red-400">Posible Rechazo: {{ $criterioItem['texto_rechazo'] }}</span> @endif @if($criterioItem['aclaracion']) <br><span class="text-blue-600 dark:text-blue-400">Aclaración: {{ $criterioItem['aclaracion'] }}</span> @endif </li> @endforeach </ul> @else <p>No hay criterios de evaluación específicos definidos para este documento.</p> @endif
                                                            @if($doc['observacion_documento_texto'])<h4 class="font-semibold mt-2 mb-1">Observación General del Documento:</h4> <p>{{ $doc['observacion_documento_texto'] }}</p>@endif
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="9" class="table-cell text-center p-4">No se encontraron documentos requeridos para este vehículo en esta Unidad Organizacional.</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="cargarDocumentos, documentosParaCargar.*.archivo_input">
                                <span wire:loading.remove wire:target="cargarDocumentos"><x-icons.upload class="w-5 h-5 mr-1 inline-block"/> Cargar Documentos Seleccionados</span>
                                <span wire:loading wire:target="cargarDocumentos"><x-icons.spinner class="w-5 h-5 mr-1 inline-block"/> Procesando...</span>
                            </button>
                            <button type="button" wire:click="cerrarModalDocumentos" class="btn-secondary">Cerrar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>