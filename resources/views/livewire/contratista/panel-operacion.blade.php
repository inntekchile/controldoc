<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Panel de Operación Contratista
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    <div class="mb-6">
                        <label for="vinculacion_seleccionada" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Seleccione la Vinculación (Mandante - Unidad Organizacional) para operar:
                        </label>
                        <select wire:model.live="vinculacionSeleccionada" id="vinculacion_seleccionada" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">-- Seleccione una opción --</option>
                            @if($vinculacionesDisponibles)
                                @foreach ($vinculacionesDisponibles as $v)
                                    <option value="{{ $v['id_seleccion'] }}">{{ $v['texto_visible'] }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div>
                        <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
                            <nav class="-mb-px flex space-x-8 overflow-x-auto" aria-label="Tabs">
                                <button wire:click="seleccionarPestaña('mi_ficha')"
                                        class="{{ $pestañaActiva === 'mi_ficha' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-500' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                    Mi Ficha Empresa
                                </button>
                                @if ($vinculacionSeleccionada)
                                    @if (in_array('EMPRESA', $tiposEntidadPermitidosContextoActual))
                                    <button wire:click="seleccionarPestaña('documentos_empresa')"
                                            class="{{ $pestañaActiva === 'documentos_empresa' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-500' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                        Docs. Empresa
                                    </button>
                                    @endif
                                    @if (in_array('PERSONA', $tiposEntidadPermitidosContextoActual))
                                        <button wire:click="seleccionarPestaña('trabajadores')" class="{{ $pestañaActiva === 'trabajadores' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-500' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Trabajadores</button>
                                    @endif
                                    @if (in_array('VEHICULO', $tiposEntidadPermitidosContextoActual))
                                        <button wire:click="seleccionarPestaña('vehiculos')" class="{{ $pestañaActiva === 'vehiculos' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-500' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Vehículos</button>
                                    @endif
                                    @if (in_array('MAQUINARIA', $tiposEntidadPermitidosContextoActual))
                                        <button wire:click="seleccionarPestaña('maquinaria')" class="{{ $pestañaActiva === 'maquinaria' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-500' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Maquinaria</button>
                                    @endif
                                    @if (in_array('EMBARCACION', $tiposEntidadPermitidosContextoActual))
                                        <button wire:click="seleccionarPestaña('embarcaciones')" class="{{ $pestañaActiva === 'embarcaciones' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-500' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">Embarcaciones</button>
                                    @endif
                                @endif
                            </nav>
                        </div>

                        <div class="mt-4">
                            @if (session()->has('message_docs_empresa')) <div class="alert-success mb-4">{{ session('message_docs_empresa') }}</div> @endif
                            @if (session()->has('error_docs_empresa')) <div class="alert-danger mb-4">{{ session('error_docs_empresa') }}</div> @endif
                            @if (session()->has('info_docs_empresa')) <div class="alert-info mb-4">{{ session('info_docs_empresa') }}</div> @endif
                            
                            @if ($pestañaActiva === 'mi_ficha')
                                @livewire('ficha-contratista', ['contratistaId' => Auth::user()->contratista_id])
                            @elseif($vinculacionSeleccionada)
                                @if ($pestañaActiva === 'documentos_empresa')
                                    <div x-data="{ openInfo: null }">
                                        <form wire:submit.prevent="cargarDocumentos">
                                            <div class="overflow-x-auto shadow-md sm:rounded-lg">
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
                                                        @forelse ($documentosRequeridosEmpresa as $index => $doc)
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
                                                                    <h4 class="font-semibold mb-1">Observación General para: {{ $doc['nombre_documento_texto'] }}</h4>
                                                                    @if($doc['observacion_documento_texto']) <p>{{ $doc['observacion_documento_texto'] }}</p> @else <p>No hay observaciones definidas para este documento.</p> @endif
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr><td colspan="9" class="table-cell text-center p-4">No se encontraron documentos requeridos para la empresa en esta vinculación.</td></tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse mt-4 rounded-b-lg">
                                                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="cargarDocumentos, documentosParaCargar.*.archivo_input">
                                                    <span wire:loading.remove wire:target="cargarDocumentos"><x-icons.upload class="w-5 h-5 mr-1 inline-block"/> Cargar Documentos Seleccionados</span>
                                                    <span wire:loading wire:target="cargarDocumentos"><x-icons.spinner class="w-5 h-5 mr-1 inline-block"/> Procesando...</span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                @elseif ($pestañaActiva === 'trabajadores')
                                    @livewire('gestion-trabajadores-contratista', ['mandanteId' => $mandanteContextoId, 'unidadOrganizacionalId' => $unidadOrganizacionalContextoId], key('trabajadores-' . $unidadOrganizacionalContextoId))
                                @elseif ($pestañaActiva === 'vehiculos')
                                    @livewire('contratista.gestion-vehiculos', ['mandanteId' => $mandanteContextoId, 'unidadOrganizacionalId' => $unidadOrganizacionalContextoId], key('vehiculos-' . $unidadOrganizacionalContextoId))
                                @elseif ($pestañaActiva === 'maquinaria')
                                    @livewire('contratista.gestion-maquinaria', ['mandanteId' => $mandanteContextoId, 'unidadOrganizacionalId' => $unidadOrganizacionalContextoId], key('maquinaria-' . $unidadOrganizacionalContextoId))
                                @elseif ($pestañaActiva === 'embarcaciones')
                                    @livewire('contratista.gestion-embarcaciones', ['mandanteId' => $mandanteContextoId, 'unidadOrganizacionalId' => $unidadOrganizacionalContextoId], key('embarcaciones-' . $unidadOrganizacionalContextoId))
                                @endif
                            @elseif($pestañaActiva !== 'mi_ficha')
                                <p class="text-center text-gray-500 dark:text-gray-400 py-8">
                                    Por favor, seleccione una vinculación para gestionar esta sección.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>