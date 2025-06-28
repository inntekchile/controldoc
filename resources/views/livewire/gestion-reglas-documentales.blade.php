<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestión de Reglas Documentales') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                
                @if (session()->has('success')) <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded dark:bg-green-700 dark:text-green-100 dark:border-green-600">{{ session('success') }}</div> @endif
                @if (session()->has('error')) <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded dark:bg-red-700 dark:text-red-100 dark:border-red-600">{{ session('error') }}</div> @endif

                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-700 dark:text-gray-300">Listado de Reglas</h3>
                    <button wire:click="create()" class="btn-primary">
                        <x-icons.plus class="w-5 h-5 mr-2"/>
                        {{ __('Agregar Nueva Regla') }}
                    </button>
                </div>

                {{-- Sección de Filtros --}}
                <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg shadow">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div>
                            <label for="filtroMandanteId" class="label-generic">Filtrar por Mandante:</label>
                            <select wire:model.live="filtroMandanteId" id="filtroMandanteId" class="input-field">
                                <option value="">Todos los Mandantes</option>
                                @if(isset($listaMandantes))
                                    @foreach ($listaMandantes as $mandante)
                                        <option value="{{ $mandante->id }}">{{ $mandante->razon_social }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div>
                            <label for="filtroTipoEntidadId" class="label-generic">Filtrar por Entidad:</label>
                            <select wire:model.live="filtroTipoEntidadId" id="filtroTipoEntidadId" class="input-field">
                                <option value="">Todas las Entidades</option>
                                 @if(isset($listaTiposEntidad))
                                    @foreach ($listaTiposEntidad as $tipo)
                                        <option value="{{ $tipo->id }}">{{ $tipo->nombre_entidad }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div>
                            <label for="filtroNombreDocumento" class="label-generic">Buscar por Nombre Documento:</label>
                            <input type="text" wire:model.live.debounce.300ms="filtroNombreDocumento" id="filtroNombreDocumento" class="input-field" placeholder="Escriba para buscar...">
                        </div>
                        <div>
                            <button wire:click="resetFilters" class="btn-secondary-outline w-full">
                                <x-icons.x-circle class="w-4 h-4 mr-1"/>
                                Limpiar Filtros
                            </button>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="table-header cursor-pointer" wire:click="sortBy('reglas_documentales.id')">ID <x-icons.sort-arrow :direction="$sortBy === 'reglas_documentales.id' ? $sortDirection : null" /></th>
                                <th scope="col" class="table-header cursor-pointer" wire:click="sortBy('mandantes.razon_social')">Mandante <x-icons.sort-arrow :direction="$sortBy === 'mandantes.razon_social' ? $sortDirection : null" /></th>
                                <th scope="col" class="table-header cursor-pointer" wire:click="sortBy('tipos_entidad_controlable.nombre_entidad')">Entidad <x-icons.sort-arrow :direction="$sortBy === 'tipos_entidad_controlable.nombre_entidad' ? $sortDirection : null" /></th>
                                <th scope="col" class="table-header cursor-pointer" wire:click="sortBy('nombre_documentos.nombre')">Documento <x-icons.sort-arrow :direction="$sortBy === 'nombre_documentos.nombre' ? $sortDirection : null" /></th>
                                <th scope="col" class="table-header">Aplicabilidad Específica</th>
                                <th scope="col" class="table-header">U. Org.</th>
                                <th scope="col" class="table-header">Activa</th>
                                <th scope="col" class="table-header text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($reglas as $regla)
                                <tr wire:key="regla-{{ $regla->id }}">
                                    <td class="table-cell">{{ $regla->id }}</td>
                                    <td class="table-cell">{{ $regla->mandante->razon_social ?? 'N/A' }}</td>
                                    <td class="table-cell">{{ $regla->tipoEntidadControlada->nombre_entidad ?? 'N/A' }}</td>
                                    <td class="table-cell">{{ $regla->nombreDocumento->nombre ?? 'N/A' }}</td>
                                    <td class="table-cell">
                                        @php $specifics = []; @endphp
                                        @if($regla->tipoEntidadControlada)
                                            @php $entidadNombre = strtoupper($regla->tipoEntidadControlada->nombre_entidad); @endphp
                                            @if($entidadNombre === 'PERSONA')
                                                @if($regla->cargosAplica->isNotEmpty())
                                                    @php $specifics[] = 'Cargos: ' . $regla->cargosAplica->pluck('nombre_cargo')->take(1)->join(', ') . ($regla->cargosAplica->count() > 1 ? '...' : ''); @endphp
                                                @endif
                                                @if($regla->nacionalidadesAplica->isNotEmpty())
                                                    @php $specifics[] = 'Nac.: ' . $regla->nacionalidadesAplica->pluck('nombre')->take(1)->join(', ') . ($regla->nacionalidadesAplica->count() > 1 ? '...' : ''); @endphp
                                                @endif
                                            @elseif($entidadNombre === 'VEHICULO')
                                                @if($regla->tiposVehiculoAplica->isNotEmpty())
                                                    @php $specifics[] = 'Tipos Veh.: ' . $regla->tiposVehiculoAplica->pluck('nombre')->take(1)->join(', ') . ($regla->tiposVehiculoAplica->count() > 1 ? '...' : ''); @endphp
                                                @endif
                                            @elseif($entidadNombre === 'MAQUINARIA')
                                                 @if($regla->tiposMaquinariaAplica->isNotEmpty())
                                                    @php $specifics[] = 'Tipos Maq.: ' . $regla->tiposMaquinariaAplica->pluck('nombre')->take(1)->join(', ') . ($regla->tiposMaquinariaAplica->count() > 1 ? '...' : ''); @endphp
                                                @endif
                                            @elseif($entidadNombre === 'EMBARCACION')
                                                @if($regla->tiposEmbarcacionAplica->isNotEmpty())
                                                    @php $specifics[] = 'Tipos Emb.: ' . $regla->tiposEmbarcacionAplica->pluck('nombre')->take(1)->join(', ') . ($regla->tiposEmbarcacionAplica->count() > 1 ? '...' : ''); @endphp
                                                @endif
                                            @endif
                                            @if(in_array($entidadNombre, ['VEHICULO', 'MAQUINARIA', 'EMBARCACION']))
                                                @if($regla->tenenciasAplica->isNotEmpty())
                                                    @php $specifics[] = 'Tenencias: ' . $regla->tenenciasAplica->pluck('nombre')->take(1)->join(', ') . ($regla->tenenciasAplica->count() > 1 ? '...' : ''); @endphp
                                                @endif
                                            @endif
                                        @endif
                                        
                                        @if(empty($specifics) && $regla->tipoEntidadControlada && strtoupper($regla->tipoEntidadControlada->nombre_entidad) !== 'EMPRESA')
                                            <span class="italic text-gray-500 dark:text-gray-400">Aplica a Todos</span>
                                        @elseif(!empty($specifics))
                                            {{ implode('; ', $specifics) }}
                                        @else
                                             <span class="italic text-gray-500 dark:text-gray-400">N/A (Empresa)</span>
                                        @endif
                                    </td>
                                    <td class="table-cell">
                                        @if($regla->unidadesOrganizacionales->isNotEmpty())
                                            {{ $regla->unidadesOrganizacionales->pluck('nombre_unidad')->take(2)->join(', ') }}
                                            @if($regla->unidadesOrganizacionales->count() > 2)
                                                ... (+{{ $regla->unidadesOrganizacionales->count() - 2 }})
                                            @endif
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td class="table-cell">
                                        @if ($regla->is_active)
                                            <span class="badge-active">Sí</span>
                                        @else
                                            <span class="badge-inactive">No</span>
                                        @endif
                                    </td>
                                    <td class="table-cell text-right">
                                        <button wire:click="toggleActivo({{ $regla->id }})" wire:loading.attr="disabled" wire:target="toggleActivo({{ $regla->id }})" class="btn-secondary-outline btn-sm mr-1 p-1 {{ $regla->is_active ? 'hover:bg-yellow-100 dark:hover:bg-yellow-700' : 'hover:bg-green-100 dark:hover:bg-green-700' }}" title="{{ $regla->is_active ? 'Desactivar Regla' : 'Activar Regla' }}"> <span wire:loading.remove wire:target="toggleActivo({{ $regla->id }})"> @if ($regla->is_active) <x-icons.eye-slash class="w-4 h-4 text-yellow-600 dark:text-yellow-400"/> @else <x-icons.eye class="w-4 h-4 text-green-600 dark:text-green-400"/> @endif </span> <span wire:loading wire:target="toggleActivo({{ $regla->id }})"> <svg class="animate-spin h-4 w-4 text-gray-600 dark:text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"> <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle> <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path> </svg> </span> </button>
                                        <button wire:click="edit({{ $regla->id }})" class="btn-secondary btn-sm mr-1 p-1" title="Editar Regla"> <x-icons.edit class="w-4 h-4"/> </button>
                                        <button wire:click="confirmarEliminacion({{ $regla->id }})" wire:loading.attr="disabled" wire:target="confirmarEliminacion({{ $regla->id }})" class="btn-danger btn-sm p-1" title="Eliminar Regla"> <span wire:loading.remove wire:target="confirmarEliminacion({{ $regla->id }})"> <x-icons.trash class="w-4 h-4"/> </span> <span wire:loading wire:target="confirmarEliminacion({{ $regla->id }})"> <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"> <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle> <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path> </svg> </span> </button>
                                    </td>
                                </tr>
                            @empty
                                <tr> <td colspan="8" class="table-cell text-center">No hay reglas documentales que coincidan con los filtros aplicados.</td> </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($reglas->hasPages()) <div class="mt-4"> {{ $reglas->links() }} </div> @endif
            </div>
        </div>
    </div>

    {{-- Modal de Creación/Edición de Regla --}}
    @if ($isOpen)
        @php
            $labelIdentificadorEspecifico = 'APLICA SOLO A LOS RUT';
            $labelIdentificadorExcluido = 'NO APLICA A LOS RUT';
            $placeholderIdentificador = 'RUTs separados por ;';

            if ($nombreEntidadSeleccionada === 'VEHICULO') {
                $labelIdentificadorEspecifico = 'APLICA SOLO A patentes';
                $labelIdentificadorExcluido = 'NO APLICA A patentes';
                $placeholderIdentificador = 'Patentes separadas por ;';
            } elseif ($nombreEntidadSeleccionada === 'MAQUINARIA') {
                $labelIdentificadorEspecifico = 'APLICA SOLO A patente/código';
                $labelIdentificadorExcluido = 'NO APLICA A patente/código';
                $placeholderIdentificador = 'Patentes/Códigos separados por ;';
            } elseif ($nombreEntidadSeleccionada === 'EMBARCACION') {
                $labelIdentificadorEspecifico = 'APLICA SOLO A matrícula';
                $labelIdentificadorExcluido = 'NO APLICA A matrícula';
                $placeholderIdentificador = 'Matrículas separadas por ;';
            }
        @endphp
        <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75" aria-hidden="true" wire:click="closeModal()"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full">
                    <form wire:submit.prevent="{{ $modoEdicion ? 'update' : 'store' }}">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start mb-4"> <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full"> <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title"> {{ $modoEdicion ? 'Editar Regla Documental' : 'Agregar Nueva Regla Documental' }} </h3> </div> </div>
                            <hr class="my-4 border-gray-300 dark:border-gray-600"/>
                            <div class="space-y-6 max-h-[70vh] overflow-y-auto pr-2">

                                {{-- FILA 1 --}}
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                    <div> <label for="mandante_id" class="label-generic">MANDANTE <span class="text-red-500">*</span></label> <select wire:model.live="mandante_id" id="mandante_id" class="input-field"> <option value="">Seleccione...</option> @if(isset($listaMandantes)) @foreach ($listaMandantes as $mandante) <option value="{{ $mandante->id }}">{{ $mandante->razon_social }}</option> @endforeach @endif </select> @error('mandante_id') <span class="error-message">{{ $message }}</span> @enderror </div>
                                    <div> <label for="tipo_entidad_controlada_id" class="label-generic">Entidad Controlada <span class="text-red-500">*</span></label> <select wire:model.live="tipo_entidad_controlada_id" id="tipo_entidad_controlada_id" class="input-field"> <option value="">Seleccione...</option> @if(isset($listaTiposEntidadControlable)) @foreach ($listaTiposEntidadControlable as $tipo) <option value="{{ $tipo->id }}">{{ $tipo->nombre_entidad }}</option> @endforeach @endif </select> @error('tipo_entidad_controlada_id') <span class="error-message">{{ $message }}</span> @enderror </div>
                                    <div> <label for="nombre_documento_id" class="label-generic">Documento <span class="text-red-500">*</span></label> <select wire:model="nombre_documento_id" id="nombre_documento_id" class="input-field"> <option value="">Seleccione...</option> @if(isset($listaNombresDocumento)) @foreach ($listaNombresDocumento as $doc) <option value="{{ $doc->id }}">{{ $doc->nombre }}</option> @endforeach @endif </select> @error('nombre_documento_id') <span class="error-message">{{ $message }}</span> @enderror </div>
                                    <div> <label for="valor_nominal_documento" class="label-generic">Valor Nominal DEL DOCUMENTO</label> <input type="number" wire:model="valor_nominal_documento" id="valor_nominal_documento" class="input-field" min="0"> <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Valor Defecto: 1</p> @error('valor_nominal_documento') <span class="error-message">{{ $message }}</span> @enderror </div>
                                </div>

                                {{-- FILA 2 --}}
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                    <div> <label for="aplica_empresa_condicion_id" class="label-generic">Condición EMPRESA</label> <select wire:model="aplica_empresa_condicion_id" id="aplica_empresa_condicion_id" class="input-field"> <option value="">Seleccione...</option> @if(isset($listaTiposCondicionEmpresa)) @foreach ($listaTiposCondicionEmpresa as $condicion) <option value="{{ $condicion->id }}">{{ $condicion->nombre }}</option> @endforeach @endif </select> @error('aplica_empresa_condicion_id') <span class="error-message">{{ $message }}</span> @enderror </div>
                                    
                                    @if ($nombreEntidadSeleccionada === 'PERSONA')
                                        <div> <label for="aplica_persona_condicion_id" class="label-generic">Condición Persona</label> <select wire:model="aplica_persona_condicion_id" id="aplica_persona_condicion_id" class="input-field"> <option value="">Seleccione...</option> @if(isset($listaTiposCondicionPersonal)) @foreach ($listaTiposCondicionPersonal as $condicion) <option value="{{ $condicion->id }}">{{ $condicion->nombre }}</option> @endforeach @endif </select> @error('aplica_persona_condicion_id') <span class="error-message">{{ $message }}</span> @enderror </div>
                                    @else
                                        <div class="md:col-span-1"></div>
                                    @endif

                                    <div> <label for="rut_especificos" class="label-generic">{{ $labelIdentificadorEspecifico }}</label> <textarea wire:model="rut_especificos" id="rut_especificos" rows="2" class="input-field" placeholder="{{ $placeholderIdentificador }}"></textarea> <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">(SEPARADOS CON ;)</p> @error('rut_especificos') <span class="error-message">{{ $message }}</span> @enderror </div>
                                    <div> <label for="rut_excluidos" class="label-generic">{{ $labelIdentificadorExcluido }}</label> <textarea wire:model="rut_excluidos" id="rut_excluidos" rows="2" class="input-field" placeholder="{{ $placeholderIdentificador }}"></textarea> <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">(SEPARADOS CON ;)</p> @error('rut_excluidos') <span class="error-message">{{ $message }}</span> @enderror </div>
                                </div>
                                
                                @if ($nombreEntidadSeleccionada === 'PERSONA')
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="md:col-span-1"> 
                                            <label for="cargosSeleccionados" class="label-generic">Cargos Específicos Aplicables (POR MANDANTE)</label>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Si no selecciona ninguno, la regla aplicará a todos los cargos del mandante.</p>
                                            <select wire:model="cargosSeleccionados" id="cargosSeleccionados" class="input-field h-32" multiple @if(empty($mandante_id) || empty($listaCargosMandante) || $listaCargosMandante->isEmpty()) disabled @endif> 
                                                @if(!empty($mandante_id) && isset($listaCargosMandante) && $listaCargosMandante->isNotEmpty())
                                                    @foreach ($listaCargosMandante as $cargo) <option value="{{ $cargo->id }}">{{ $cargo->nombre_cargo }}</option> @endforeach 
                                                @else
                                                    <option value="" disabled> @if(empty($mandante_id)) Seleccione un mandante primero... @else No hay cargos disponibles. @endif </option> 
                                                @endif
                                            </select> 
                                            @if(empty($mandante_id)) <p class="text-xs text-yellow-500 dark:text-yellow-400 mt-1">Seleccione un Mandante para ver y seleccionar cargos.</p> @endif
                                            @error('cargosSeleccionados') <span class="error-message">{{ $message }}</span> @enderror @error('cargosSeleccionados.*') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="md:col-span-1 flex flex-col justify-start space-y-2 pt-6">
                                            <button type="button" wire:click="quitarSeleccionDeCargos" class="btn-secondary-outline btn-sm w-full" @if(empty($cargosSeleccionados)) disabled @endif> <x-icons.x-circle class="w-4 h-4 mr-1"/> Quitar Selección (Aplica a Todos) </button>
                                            <button type="button" wire:click="seleccionarTodosLosCargos" class="btn-primary-outline btn-sm w-full" @if(empty($mandante_id) || empty($listaCargosMandante) || $listaCargosMandante->isEmpty()) disabled @endif> <x-icons.check-circle class="w-4 h-4 mr-1"/> Seleccionar Todos los Cargos </button>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Actualmente: {{ count($cargosSeleccionados) }} cargo(s) seleccionado(s).</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="md:col-span-1">
                                            <label for="nacionalidadesSeleccionadas" class="label-generic">Nacionalidades Específicas Aplicables</label>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Si no selecciona ninguna, la regla aplicará a todas las nacionalidades.</p>
                                            <select wire:model="nacionalidadesSeleccionadas" id="nacionalidadesSeleccionadas" class="input-field h-32" multiple> 
                                                @if(isset($listaNacionalidades) && $listaNacionalidades->isNotEmpty())
                                                    @foreach ($listaNacionalidades as $nacionalidad) <option value="{{ $nacionalidad->id }}">{{ $nacionalidad->nombre }}</option> @endforeach 
                                                @else
                                                    <option value="" disabled>No hay nacionalidades disponibles.</option> 
                                                @endif
                                            </select> 
                                            @error('nacionalidadesSeleccionadas') <span class="error-message">{{ $message }}</span> @enderror
                                            @error('nacionalidadesSeleccionadas.*') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="md:col-span-1 flex flex-col justify-start space-y-2 pt-6">
                                            <button type="button" wire:click="quitarSeleccionDeNacionalidades" class="btn-secondary-outline btn-sm w-full" @if(empty($nacionalidadesSeleccionadas)) disabled @endif> <x-icons.x-circle class="w-4 h-4 mr-1"/> Quitar Selección (Aplica a Todas) </button>
                                            <button type="button" wire:click="seleccionarTodasLasNacionalidades" class="btn-primary-outline btn-sm w-full" @if(!isset($listaNacionalidades) || $listaNacionalidades->isEmpty()) disabled @endif> <x-icons.check-circle class="w-4 h-4 mr-1"/> Seleccionar Todas las Nacionalidades </button>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Actualmente: {{ count($nacionalidadesSeleccionadas) }} nacionalidad(es) seleccionada(s).</p>
                                        </div>
                                    </div>
                                @endif

                                @if ($nombreEntidadSeleccionada === 'VEHICULO')
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="md:col-span-1">
                                            <label for="tiposVehiculoSeleccionados" class="label-generic">Tipos de Vehículo Específicos Aplicables</label>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Si no selecciona ninguno, la regla aplicará a todos los tipos de vehículo.</p>
                                            <select wire:model="tiposVehiculoSeleccionados" id="tiposVehiculoSeleccionados" class="input-field h-32" multiple>
                                                @if(isset($listaTiposVehiculo) && $listaTiposVehiculo->isNotEmpty())
                                                    @foreach ($listaTiposVehiculo as $tipo) <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option> @endforeach
                                                @else
                                                    <option value="" disabled>No hay tipos de vehículo disponibles.</option>
                                                @endif
                                            </select>
                                            @error('tiposVehiculoSeleccionados') <span class="error-message">{{ $message }}</span> @enderror
                                            @error('tiposVehiculoSeleccionados.*') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="md:col-span-1 flex flex-col justify-start space-y-2 pt-6">
                                            <button type="button" wire:click="quitarSeleccionDeTiposVehiculo" class="btn-secondary-outline btn-sm w-full" @if(empty($tiposVehiculoSeleccionados)) disabled @endif> <x-icons.x-circle class="w-4 h-4 mr-1"/> Quitar Selección (Aplica a Todos) </button>
                                            <button type="button" wire:click="seleccionarTodosLosTiposVehiculo" class="btn-primary-outline btn-sm w-full" @if(!isset($listaTiposVehiculo) || $listaTiposVehiculo->isEmpty()) disabled @endif> <x-icons.check-circle class="w-4 h-4 mr-1"/> Seleccionar Todos los Tipos </button>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Actualmente: {{ count($tiposVehiculoSeleccionados) }} tipo(s) seleccionado(s).</p>
                                        </div>
                                    </div>
                                @endif
                                
                                @if ($nombreEntidadSeleccionada === 'MAQUINARIA')
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="md:col-span-1">
                                            <label for="tiposMaquinariaSeleccionados" class="label-generic">Tipos de Maquinaria Específicos Aplicables</label>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Si no selecciona ninguno, la regla aplicará a todos los tipos de maquinaria.</p>
                                            <select wire:model="tiposMaquinariaSeleccionados" id="tiposMaquinariaSeleccionados" class="input-field h-32" multiple>
                                                @if(isset($listaTiposMaquinaria) && $listaTiposMaquinaria->isNotEmpty())
                                                    @foreach ($listaTiposMaquinaria as $tipo) <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option> @endforeach
                                                @else
                                                    <option value="" disabled>No hay tipos de maquinaria disponibles.</option>
                                                @endif
                                            </select>
                                            @error('tiposMaquinariaSeleccionados') <span class="error-message">{{ $message }}</span> @enderror
                                            @error('tiposMaquinariaSeleccionados.*') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="md:col-span-1 flex flex-col justify-start space-y-2 pt-6">
                                            <button type="button" wire:click="quitarSeleccionDeTiposMaquinaria" class="btn-secondary-outline btn-sm w-full" @if(empty($tiposMaquinariaSeleccionados)) disabled @endif> <x-icons.x-circle class="w-4 h-4 mr-1"/> Quitar Selección (Aplica a Todos) </button>
                                            <button type="button" wire:click="seleccionarTodosLosTiposMaquinaria" class="btn-primary-outline btn-sm w-full" @if(!isset($listaTiposMaquinaria) || $listaTiposMaquinaria->isEmpty()) disabled @endif> <x-icons.check-circle class="w-4 h-4 mr-1"/> Seleccionar Todos los Tipos </button>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Actualmente: {{ count($tiposMaquinariaSeleccionados) }} tipo(s) seleccionado(s).</p>
                                        </div>
                                    </div>
                                @endif

                                @if ($nombreEntidadSeleccionada === 'EMBARCACION')
                                     <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="md:col-span-1">
                                            <label for="tiposEmbarcacionSeleccionados" class="label-generic">Tipos de Embarcación Específicos Aplicables</label>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Si no selecciona ninguno, la regla aplicará a todos los tipos de embarcación.</p>
                                            <select wire:model="tiposEmbarcacionSeleccionados" id="tiposEmbarcacionSeleccionados" class="input-field h-32" multiple>
                                                @if(isset($listaTiposEmbarcacion) && $listaTiposEmbarcacion->isNotEmpty())
                                                    @foreach ($listaTiposEmbarcacion as $tipo) <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option> @endforeach
                                                @else
                                                    <option value="" disabled>No hay tipos de embarcación disponibles.</option>
                                                @endif
                                            </select>
                                            @error('tiposEmbarcacionSeleccionados') <span class="error-message">{{ $message }}</span> @enderror
                                            @error('tiposEmbarcacionSeleccionados.*') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="md:col-span-1 flex flex-col justify-start space-y-2 pt-6">
                                            <button type="button" wire:click="quitarSeleccionDeTiposEmbarcacion" class="btn-secondary-outline btn-sm w-full" @if(empty($tiposEmbarcacionSeleccionados)) disabled @endif> <x-icons.x-circle class="w-4 h-4 mr-1"/> Quitar Selección (Aplica a Todos) </button>
                                            <button type="button" wire:click="seleccionarTodosLosTiposEmbarcacion" class="btn-primary-outline btn-sm w-full" @if(!isset($listaTiposEmbarcacion) || $listaTiposEmbarcacion->isEmpty()) disabled @endif> <x-icons.check-circle class="w-4 h-4 mr-1"/> Seleccionar Todos los Tipos </button>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Actualmente: {{ count($tiposEmbarcacionSeleccionados) }} tipo(s) seleccionado(s).</p>
                                        </div>
                                    </div>
                                @endif
                                
                                @php
                                    $entidadesActivos = ['VEHICULO', 'MAQUINARIA', 'EMBARCACION'];
                                @endphp

                                @if (in_array($nombreEntidadSeleccionada, $entidadesActivos))
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-gray-200 dark:border-gray-600 pt-4">
                                        <div class="md:col-span-1">
                                            <label for="tenenciasSeleccionadas" class="label-generic">Tenencia Específica del Activo</label>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Si no selecciona ninguna, la regla aplicará a activos con cualquier tenencia.</p>
                                            <select wire:model="tenenciasSeleccionadas" id="tenenciasSeleccionadas" class="input-field h-32" multiple>
                                                @if(isset($listaTenenciasVehiculo) && $listaTenenciasVehiculo->isNotEmpty())
                                                    @foreach ($listaTenenciasVehiculo as $tenencia) <option value="{{ $tenencia->id }}">{{ $tenencia->nombre }}</option> @endforeach
                                                @else
                                                    <option value="" disabled>No hay tenencias disponibles.</option>
                                                @endif
                                            </select>
                                            @error('tenenciasSeleccionadas') <span class="error-message">{{ $message }}</span> @enderror
                                            @error('tenenciasSeleccionadas.*') <span class="error-message">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="md:col-span-1 flex flex-col justify-start space-y-2 pt-6">
                                            <button type="button" wire:click="quitarSeleccionDeTenencias" class="btn-secondary-outline btn-sm w-full" @if(empty($tenenciasSeleccionadas)) disabled @endif> <x-icons.x-circle class="w-4 h-4 mr-1"/> Quitar Selección (Aplica a Todas) </button>
                                            <button type="button" wire:click="seleccionarTodasLasTenencias" class="btn-primary-outline btn-sm w-full" @if(!isset($listaTenenciasVehiculo) || $listaTenenciasVehiculo->isEmpty()) disabled @endif> <x-icons.check-circle class="w-4 h-4 mr-1"/> Seleccionar Todas las Tenencias </button>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">Actualmente: {{ count($tenenciasSeleccionadas) }} tenencia(s) seleccionada(s).</p>
                                        </div>
                                    </div>
                                @endif

                                @if ($nombreEntidadSeleccionada === 'PERSONA')
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end border-t border-gray-200 dark:border-gray-600 pt-4">
                                        <div> <label for="condicion_fecha_ingreso_id" class="label-generic">Opción de Fechas Ingreso</label> <select wire:model.live="condicion_fecha_ingreso_id" id="condicion_fecha_ingreso_id" class="input-field"> <option value="">Todas las fechas</option> @if(isset($listaCondicionesFechaIngreso)) @foreach ($listaCondicionesFechaIngreso as $condicion) <option value="{{ $condicion->id }}">{{ $condicion->nombre }}</option> @endforeach @endif </select> @error('condicion_fecha_ingreso_id') <span class="error-message">{{ $message }}</span> @enderror </div>
                                        <div @if(empty($condicion_fecha_ingreso_id)) style="display: none;" @endif>  <label for="fecha_comparacion_ingreso" class="label-generic">Fecha de Comparación</label> <input type="date" wire:model="fecha_comparacion_ingreso" id="fecha_comparacion_ingreso" class="input-field"> @error('fecha_comparacion_ingreso') <span class="error-message">{{ $message }}</span> @enderror </div>
                                    </div>
                                @endif
                                
                                <div class="border-t border-gray-200 dark:border-gray-600 pt-4"> 
                                    <div class="flex justify-between items-center mb-2"> 
                                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">UNIDADES ORGANIZACIONALES A LAS QUE APLICA <span class="text-red-500">*</span></p> 
                                        <button type="button" wire:click="agregarUnidadSeleccionada" class="btn-success-outline btn-sm" @if(empty($mandante_id)) disabled title="Seleccione un mandante primero" @endif> <x-icons.plus class="w-4 h-4 mr-1"/> Añadir U.O. </button> 
                                    </div> 
                                    @if(is_array($unidadesSeleccionadas) && count($unidadesSeleccionadas) > 0) 
                                        @foreach ($unidadesSeleccionadas as $index => $unidadSet) 
                                        <div class="grid grid-cols-1 md:grid-cols-5 gap-x-6 gap-y-2 mb-3 p-3 border dark:border-gray-700 rounded-md items-end" wire:key="unidad-org-{{ $index }}"> 
                                            <div> <label for="uo_nivel1_id_{{ $index }}" class="label-generic text-xs">Nivel Principal</label> <select wire:model="unidadesSeleccionadas.{{ $index }}.uo_nivel1_id" wire:change="uoNivel1Changed({{ $index }}, $event.target.value)" id="uo_nivel1_id_{{ $index }}" class="input-field text-sm" @if(empty($mandante_id)) disabled @endif> <option value="">Seleccione...</option> @foreach($this->getNivel1Options($index) as $uo1) <option value="{{ $uo1['id'] }}">{{ $uo1['nombre_unidad'] }}</option> @endforeach </select> </div> 
                                            <div> <label for="uo_nivel2_id_{{ $index }}" class="label-generic text-xs">Nivel 2</label> <select wire:model="unidadesSeleccionadas.{{ $index }}.uo_nivel2_id" wire:change="uoNivel2Changed({{ $index }}, $event.target.value)" id="uo_nivel2_id_{{ $index }}" class="input-field text-sm" @if(empty($unidadesSeleccionadas[$index]['uo_nivel1_id'])) disabled @endif> <option value="">Seleccione...</option> @foreach($this->getNivel2Options($index) as $uo2) <option value="{{ $uo2['id'] }}">{{ $uo2['nombre_unidad'] }}</option> @endforeach </select> </div> 
                                            <div> <label for="uo_nivel3_id_{{ $index }}" class="label-generic text-xs">Nivel 3</label> <select wire:model="unidadesSeleccionadas.{{ $index }}.uo_nivel3_id" wire:change="uoNivel3Changed({{ $index }}, $event.target.value)" id="uo_nivel3_id_{{ $index }}" class="input-field text-sm" @if(empty($unidadesSeleccionadas[$index]['uo_nivel2_id'])) disabled @endif> <option value="">Seleccione...</option> @foreach($this->getNivel3Options($index) as $uo3) <option value="{{ $uo3['id'] }}">{{ $uo3['nombre_unidad'] }}</option> @endforeach </select> </div> 
                                            <div> <label for="uo_nivel4_id_{{ $index }}" class="label-generic text-xs">Nivel 4</label> <select wire:model="unidadesSeleccionadas.{{ $index }}.uo_nivel4_id" wire:change="uoNivel4Changed({{ $index }}, $event.target.value)" id="uo_nivel4_id_{{ $index }}" class="input-field text-sm" @if(empty($unidadesSeleccionadas[$index]['uo_nivel3_id'])) disabled @endif> <option value="">Seleccione...</option> @foreach($this->getNivel4Options($index) as $uo4) <option value="{{ $uo4['id'] }}">{{ $uo4['nombre_unidad'] }}</option> @endforeach </select> </div> 
                                            <div class="flex items-end"> @if(count($unidadesSeleccionadas) > 1) <button type="button" wire:click="eliminarUnidadSeleccionada({{ $index }})" class="btn-danger-outline btn-sm ml-auto"> <x-icons.trash class="w-4 h-4"/> </button> @endif </div> 
                                        </div> 
                                        @error('unidadesSeleccionadas.' . $index . '.final_uo_id') <span class="error-message mb-2 block">{{ $message }}</span> @enderror 
                                        @endforeach 
                                    @else 
                                        @if(!empty($mandante_id)) <p class="text-sm text-gray-500 dark:text-gray-400">Haga clic en "Añadir U.O." para seleccionar la primera unidad organizacional.</p> 
                                        @else <p class="text-sm text-gray-500 dark:text-gray-400">Seleccione un Mandante para habilitar la selección de Unidades Organizacionales.</p> @endif 
                                    @endif 
                                    @error('unidadesSeleccionadas') <span class="error-message mt-2 block">{{ $message }}</span> @enderror 
                                </div>

                                <div class="border-t border-gray-200 dark:border-gray-600 pt-4"> <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ADICIONALES DE AYUDA QUE VERÁ EL ANALISTA AL VALIDAR UN DOCUMENTO</p> <div class="grid grid-cols-1 md:grid-cols-2 gap-6"> <div> <label for="observacion_documento_id" class="label-generic">Observación Documento</label> <select wire:model="observacion_documento_id" id="observacion_documento_id" class="input-field"> <option value="">Seleccione una observación...</option> @if(isset($listaObservacionesDocumento)) @foreach ($listaObservacionesDocumento as $obs) <option value="{{ $obs->id }}">{{ $obs->titulo }}</option> @endforeach @endif </select> @error('observacion_documento_id') <span class="error-message">{{ $message }}</span> @enderror </div> <div class="flex items-end space-x-2"> <div class="flex-grow"> <label for="formato_documento_id" class="label-generic">Formato Documento</label> <select wire:model.lazy="formato_documento_id" id="formato_documento_id" class="input-field"> <option value="">Seleccione un formato...</option> @if(isset($listaFormatosDocumentoMuestra)) @foreach ($listaFormatosDocumentoMuestra as $formato) <option value="{{ $formato->id }}">{{ $formato->nombre }}</option> @endforeach @endif </select> @error('formato_documento_id') <span class="error-message">{{ $message }}</span> @enderror </div> @php $formatoSeleccionado = null; if ($formato_documento_id && isset($listaFormatosDocumentoMuestra) && $listaFormatosDocumentoMuestra instanceof \Illuminate\Support\Collection) { $formatoSeleccionado = $listaFormatosDocumentoMuestra->firstWhere('id', $formato_documento_id); } @endphp @if ($formatoSeleccionado && !empty($formatoSeleccionado->ruta_archivo)) <a href="{{ Storage::url($formatoSeleccionado->ruta_archivo) }}" target="_blank" class="btn-secondary inline-flex items-center px-3 py-2 text-sm"> Ver </a> @else <button type="button" class="btn-secondary-outline inline-flex items-center px-3 py-2 text-sm" disabled> Ver </button> @endif </div> </div> <div class="mt-6"> <label for="documento_relacionado_id" class="label-generic">Documento Relacionado (DOCUMENTO DEL MISMO TRABAJADOR QUE SIRVE DE APOYO AL ANALISTA)</label> <select wire:model="documento_relacionado_id" id="documento_relacionado_id" class="input-field"> <option value="">Seleccione un documento...</option> @if(isset($listaNombresDocumento)) @foreach ($listaNombresDocumento as $doc) @if($this->nombre_documento_id != $doc->id) <option value="{{ $doc->id }}">{{ $doc->nombre }}</option> @endif @endforeach @endif </select> @error('documento_relacionado_id') <span class="error-message">{{ $message }}</span> @enderror </div> </div>
                                
                                <div class="border-t border-gray-200 dark:border-gray-600 pt-4 space-y-6"> <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end"> <div> <label for="tipo_vencimiento_id" class="label-generic">TIPO DE VENCIMIENTO</label> <select wire:model.live="tipo_vencimiento_id" id="tipo_vencimiento_id" class="input-field"> <option value="">Seleccione...</option> @if(isset($listaTiposVencimiento)) @foreach ($listaTiposVencimiento as $tipo) <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option> @endforeach @endif </select> @error('tipo_vencimiento_id') <span class="error-message">{{ $message }}</span> @enderror </div> @php $tipoVencimientoSeleccionadoObj = null; if ($this->tipo_vencimiento_id && isset($listaTiposVencimiento) && $listaTiposVencimiento instanceof \Illuminate\Support\Collection) { $tipoVencimientoSeleccionadoObj = $listaTiposVencimiento->firstWhere('id', $this->tipo_vencimiento_id); } $nombresTiposVencimientoQueRequierenDias = ['DESDE CARGA', 'DESDE EMISION']; $mostrarDiasValidez = $tipoVencimientoSeleccionadoObj && in_array(strtoupper($tipoVencimientoSeleccionadoObj->nombre), $nombresTiposVencimientoQueRequierenDias); @endphp <div @if(!$mostrarDiasValidez) style="display: none;" @endif> <label for="dias_validez_documento" class="label-generic">Días Validez Documento</label> <input type="number" wire:model="dias_validez_documento" id="dias_validez_documento" class="input-field" min="0" placeholder="Ej: 30"> @error('dias_validez_documento') <span class="error-message">{{ $message }}</span> @enderror </div> <div> <label for="dias_aviso_vencimiento" class="label-generic">Días Aviso Vencimiento</label> <input type="number" wire:model="dias_aviso_vencimiento" id="dias_aviso_vencimiento" class="input-field" min="0" placeholder="Ej: 15"> @error('dias_aviso_vencimiento') <span class="error-message">{{ $message }}</span> @enderror </div> </div> <div class="grid grid-cols-1 md:grid-cols-2 gap-6"> <div class="flex items-center"> <input wire:model="valida_emision" id="valida_emision" type="checkbox" class="checkbox-generic"> <label for="valida_emision" class="ml-2 label-generic">Valida Emisión</label> </div> <div class="flex items-center"> <input wire:model="valida_vencimiento" id="valida_vencimiento" type="checkbox" class="checkbox-generic"> <label for="valida_vencimiento" class="ml-2 label-generic">Valida Vencimiento</label> </div> </div> <div> <label for="configuracion_validacion_id" class="label-generic">Quien Valida</label> <select wire:model="configuracion_validacion_id" id="configuracion_validacion_id" class="input-field"> <option value="">Seleccione...</option> @if(isset($listaConfiguracionesValidacion)) @foreach ($listaConfiguracionesValidacion as $config) <option value="{{ $config->id }}">{{ $config->nombre }}</option> @endforeach @endif </select> @error('configuracion_validacion_id') <span class="error-message">{{ $message }}</span> @enderror </div> <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4"> <div class="flex items-center"> <input wire:model="restringe_acceso" id="restringe_acceso" type="checkbox" class="checkbox-generic"> <label for="restringe_acceso" class="ml-2 label-generic">Restringe Acceso</label> </div> <div class="flex items-center"> <input wire:model="afecta_porcentaje_cumplimiento" id="afecta_porcentaje_cumplimiento" type="checkbox" class="checkbox-generic"> <label for="afecta_porcentaje_cumplimiento" class="ml-2 label-generic">Afecta % Cumplimiento</label> </div> <div class="flex items-center"> <input wire:model="documento_es_perseguidor" id="documento_es_perseguidor" type="checkbox" class="checkbox-generic"> <label for="documento_es_perseguidor" class="ml-2 label-generic">Doc. es Perseguidor</label> </div> <div class="flex items-center"> <input wire:model="mostrar_historico_documento" id="mostrar_historico_documento" type="checkbox" class="checkbox-generic"> <label for="mostrar_historico_documento" class="ml-2 label-generic">Mostrar Histórico</label> </div> </div> 
                                    @if($nombreEntidadSeleccionada === 'PERSONA')
                                        <div class="border-t border-gray-200 dark:border-gray-600 pt-4 mt-4">
                                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Opcionales Identidad Controlada Persona</p>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                                                <div class="flex items-center"> <input wire:model="permite_ver_nacionalidad_trabajador" id="permite_ver_nacionalidad_trabajador" type="checkbox" class="checkbox-generic"> <label for="permite_ver_nacionalidad_trabajador" class="ml-2 label-generic">Ver Nacionalidad</label> </div>
                                                <div class="flex items-center"> <input wire:model="permite_modificar_nacionalidad_trabajador" id="permite_modificar_nacionalidad_trabajador" type="checkbox" class="checkbox-generic"> <label for="permite_modificar_nacionalidad_trabajador" class="ml-2 label-generic">Modificar Nacionalidad</label> </div>
                                                <div class="flex items-center"> <input wire:model="permite_ver_fecha_nacimiento_trabajador" id="permite_ver_fecha_nacimiento_trabajador" type="checkbox" class="checkbox-generic"> <label for="permite_ver_fecha_nacimiento_trabajador" class="ml-2 label-generic">Ver Fecha de Nacimiento</label> </div>
                                                <div class="flex items-center"> <input wire:model="permite_modificar_fecha_nacimiento_trabajador" id="permite_modificar_fecha_nacimiento_trabajador" type="checkbox" class="checkbox-generic"> <label for="permite_modificar_fecha_nacimiento_trabajador" class="ml-2 label-generic">Modificar Fecha de Nacimiento</label> </div>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="flex items-center pt-4"> <input wire:model="is_active" id="is_active" type="checkbox" class="checkbox-generic"> <label for="is_active" class="ml-2 label-generic">Regla Activa</label> </div>
                                </div>

                                <div class="border-t border-gray-300 dark:border-gray-700 pt-4"> 
                                    <h4 class="text-md font-semibold mb-2 text-gray-800 dark:text-gray-200">Criterios de Evaluación</h4> 
                                    @if(is_array($criterios)) 
                                        @foreach ($criterios as $index => $criterio) 
                                            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-3 p-3 border dark:border-gray-600 rounded-md" wire:key="{{ $criterio['temp_id'] }}"> 
                                                <div> <label for="criterios_{{ $index }}_criterio_evaluacion_id" class="label-generic text-xs">Criterio<span class="text-red-500">*</span></label> <select wire:model.lazy="criterios.{{ $index }}.criterio_evaluacion_id" id="criterios_{{ $index }}_criterio_evaluacion_id" class="input-field text-sm"> <option value="">Seleccione...</option> @if(isset($listaCriteriosEvaluacion)) @foreach ($listaCriteriosEvaluacion as $item) <option value="{{ $item->id }}">{{ $item->nombre_criterio }}</option> @endforeach @endif </select> @error("criterios.$index.criterio_evaluacion_id") <span class="error-message">{{ $message }}</span> @enderror </div> 
                                                <div> <label for="criterios_{{ $index }}_sub_criterio_id" class="label-generic text-xs">Sub Criterio</label> <select wire:model.lazy="criterios.{{ $index }}.sub_criterio_id" id="criterios_{{ $index }}_sub_criterio_id" class="input-field text-sm"> <option value="">Seleccione...</option> @if(isset($listaSubCriterios)) @foreach ($listaSubCriterios as $item) <option value="{{ $item->id }}">{{ $item->nombre }}</option> @endforeach @endif </select> @error("criterios.$index.sub_criterio_id") <span class="error-message">{{ $message }}</span> @enderror </div> 
                                                <div> <label for="criterios_{{ $index }}_texto_rechazo_id" class="label-generic text-xs">Texto Rechazo</label> <select wire:model.lazy="criterios.{{ $index }}.texto_rechazo_id" id="criterios_{{ $index }}_texto_rechazo_id" class="input-field text-sm"> <option value="">Seleccione...</option> @if(isset($listaTextosRechazo)) @foreach ($listaTextosRechazo as $item) <option value="{{ $item->id }}">{{ $item->titulo }}</option> @endforeach @endif </select> @error("criterios.$index.texto_rechazo_id") <span class="error-message">{{ $message }}</span> @enderror </div> 
                                                <div> <label for="criterios_{{ $index }}_aclaracion_criterio_id" class="label-generic text-xs">Aclaración Criterio</label> <select wire:model.lazy="criterios.{{ $index }}.aclaracion_criterio_id" id="criterios_{{ $index }}_aclaracion_criterio_id" class="input-field text-sm"> <option value="">Seleccione...</option> @if(isset($listaAclaracionesCriterio)) @foreach ($listaAclaracionesCriterio as $item) <option value="{{ $item->id }}">{{ $item->titulo }}</option> @endforeach @endif </select> @error("criterios.$index.aclaracion_criterio_id") <span class="error-message">{{ $message }}</span> @enderror </div> 
                                                <div class="flex items-end"> @if (count($criterios) > 1) <button type="button" wire:click="eliminarCriterio('{{ $criterio['temp_id'] }}')" class="btn-danger-outline btn-sm"> <x-icons.trash class="w-4 h-4"/> </button> @endif </div> 
                                            </div> 
                                        @endforeach 
                                    @endif 
                                    <button type="button" wire:click="agregarCriterio" class="btn-secondary btn-sm mt-2"> <x-icons.plus class="w-4 h-4 mr-1"/> Agregar Criterio </button> 
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="btn-primary sm:ml-3 sm:w-auto" wire:loading.attr="disabled" wire:target="{{ $modoEdicion ? 'update' : 'store' }}"> <span wire:loading.remove wire:target="{{ $modoEdicion ? 'update' : 'store' }}"> {{ $modoEdicion ? 'Actualizar Regla' : 'Guardar Regla' }} </span> <span wire:loading wire:target="{{ $modoEdicion ? 'update' : 'store' }}"> <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"> <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle> <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path> </svg> {{ $modoEdicion ? 'Actualizando...' : 'Guardando...' }} </span> </button>
                            <button type="button" wire:click="closeModal()" class="btn-secondary-outline mt-3 sm:mt-0 sm:w-auto"> Cancelar </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($showConfirmDeleteModal) <div class="fixed z-20 inset-0 overflow-y-auto" aria-labelledby="modal-title-delete" role="dialog" aria-modal="true"> <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0"> <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-80" aria-hidden="true" wire:click="$set('showConfirmDeleteModal', false)"></div> <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span> <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"> <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4"> <div class="sm:flex sm:items-start"> <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-700 sm:mx-0 sm:h-10 sm:w-10"> <x-icons.warning class="h-6 w-6 text-red-600 dark:text-red-200"/> </div> <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left"> <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title-delete"> Confirmar Eliminación </h3> <div class="mt-2"> <p class="text-sm text-gray-500 dark:text-gray-300"> ¿Está seguro de que desea eliminar la regla: "<strong>{{ $nombreReglaParaEliminar }}</strong>"? </p> <p class="text-sm text-red-500 dark:text-red-400 mt-2"> Esta acción no se puede deshacer. Todos los criterios y asociaciones con unidades organizacionales, cargos y nacionalidades también serán eliminados. </p> </div> </div> </div> </div> <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse"> <button wire:click="deleteRegla()" type="button" class="btn-danger sm:ml-3 sm:w-auto" wire:loading.attr="disabled" wire:target="deleteRegla"> <span wire:loading.remove wire:target="deleteRegla"> Eliminar Definitivamente </span> <span wire:loading wire:target="deleteRegla"> <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"> <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle> <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path> </svg> Eliminando... </span> </button> <button wire:click="$set('showConfirmDeleteModal', false)" type="button" class="btn-secondary-outline mt-3 sm:mt-0 sm:w-auto"> Cancelar </button> </div> </div> </div> </div> @endif
</div>