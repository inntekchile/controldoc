@php use Carbon\Carbon; @endphp
<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestión General de Documentos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    @if (session()->has('message'))<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">{{ session('message') }}</span></div>@endif
                    @if (session()->has('error'))<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert"><span class="block sm:inline">{{ session('error') }}</span></div>@endif
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <input wire:model.live.debounce.500ms="filtroContratista" type="text" placeholder="Filtrar por Contratista..." class="form-input rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300">
                        <select wire:model.live="filtroMandante" class="form-select rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300">
                            <option value="">-- Todos los Mandantes --</option>
                            @foreach($mandantes as $mandante)
                                <option value="{{ $mandante->id }}">{{ $mandante->razon_social }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="filtroEntidad" class="form-select rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300">
                            <option value="">-- Todas las Entidades --</option>
                            <option value="App\Models\Contratista">Empresa</option>
                            <option value="App\Models\Trabajador">Trabajador</option>
                            <option value="App\Models\Vehiculo">Vehículo</option>
                            <option value="App\Models\Maquinaria">Maquinaria</option>
                            <option value="App\Models\Embarcacion">Embarcación</option>
                        </select>
                        <input wire:model.live.debounce.500ms="filtroDocumento" type="text" placeholder="Filtrar por Nombre documento" class="form-input rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300">
                        
                        <input wire:model.live.debounce.500ms="filtroIdEntidad" type="text" placeholder="Filtrar por ID Entidad..." class="form-input rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300">
                        
                        <select wire:model.live="filtroEstado" class="form-select rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300">
                            <option value="">-- Estado de Validación --</option>
                            <option value="Sin Asignar">Sin Asignar</option>
                            <option value="Asignado">Asignado</option>
                            <option value="Revisado">Revisado</option>
                            <option value="Revalidar">Revalidar</option>
                            <option value="Devuelto">Devuelto</option>
                        </select>

                        <select wire:model.live="filtroResultado" class="form-select rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300">
                            <option value="">-- Resultado Validación --</option>
                            <option value="Aprobado">Aprobado</option>
                            <option value="Rechazado">Rechazado</option>
                        </select>
                        
                        <select wire:model.live="filtroVigencia" class="form-select rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300">
                            <option value="">-- Estado de Vigencia --</option>
                            <option value="Vigente">Vigente</option>
                            <option value="Vencido">Vencido</option>
                            <option value="Por Periodo">Por Periodo</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-between mb-4 bg-gray-100 dark:bg-gray-700 p-4 rounded-lg">
                        <div class="flex-grow mr-4">
                            <label for="validador" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Asignar a Validador:</label>
                            <select id="validador" wire:model.live="validadorSeleccionado" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-800 dark:text-gray-300">
                                <option value="">-- Seleccione un validador --</option>
                                @foreach ($validadores as $validador)<option value="{{ $validador->id }}">{{ $validador->name }}</option>@endforeach
                            </select>
                            @error('validadorSeleccionado') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <x-primary-button wire:click="asignarSeleccionados" wire:loading.attr="disabled" :disabled="!count($documentosSeleccionados) || !$validadorSeleccionado">
                                <span wire:loading.remove wire:target="asignarSeleccionados">
                                    Asignar ({{ count($documentosSeleccionados) }})
                                    <span class="ml-2 font-bold">[ Valor Nominal: {{ $totalValorNominal }} ]</span>
                                </span>
                                <span wire:loading wire:target="asignarSeleccionados">Asignando...</span>
                            </x-primary-button>
                             @error('documentosSeleccionados') <span class="block text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="p-4"><input type="checkbox" wire:model.live="seleccionarTodos"></th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nº</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"># ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Mandante</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Contratista</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Documento</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor Nominal</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Entidad Asociada</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID Entidad</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado Validación</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Resultado Validación</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha Vencimiento</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado Vigencia</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Validador</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha Carga</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        <button wire:click="sortBy('tiempo_en_cola')" class="uppercase tracking-wider font-medium text-xs">
                                            Horas en Cola
                                            @if ($sortField === 'tiempo_en_cola')
                                                <span>{{ $sortDirection === 'asc' ? '▲' : '▼' }}</span>
                                            @endif
                                        </button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                                @forelse ($documentosPendientes as $key => $documento)
                                    <tr wire:key="documento-{{ $documento->id }}">
                                        <td class="p-4"><input type="checkbox" wire:model.live="documentosSeleccionados" value="{{ $documento->id }}"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $documentosPendientes->firstItem() + $key }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $documento->id }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $documento->mandante->razon_social ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $documento->contratista->razon_social ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $documento->nombre_documento_snapshot }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-center">{{ $documento->valor_nominal_snapshot ?? 0 }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ class_basename($documento->entidad_type) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @if($documento->entidad)
                                                @if($documento->entidad instanceof \App\Models\Vehiculo) {{ $documento->entidad->patente_letras }} {{ $documento->entidad->patente_numeros }}
                                                @elseif($documento->entidad instanceof \App\Models\Trabajador) {{ $documento->entidad->rut }}
                                                @elseif($documento->entidad instanceof \App\Models\Maquinaria) {{ $documento->entidad->identificador_letras }} {{ $documento->entidad->identificador_numeros }}
                                                @elseif($documento->entidad instanceof \App\Models\Embarcacion) {{ $documento->entidad->matricula_letras }} {{ $documento->entidad->matricula_numeros }}
                                                @elseif($documento->entidad instanceof \App\Models\Contratista) {{ $documento->entidad->rut }}
                                                @else N/A @endif
                                            @else N/A @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $documento->estado_asignacion == 'Sin Asignar' ? 'bg-yellow-100 text-yellow-800' : '' }} {{ $documento->estado_asignacion == 'Asignado' ? 'bg-blue-100 text-blue-800' : '' }} {{ $documento->estado_asignacion == 'Revalidar' ? 'bg-purple-100 text-purple-800' : '' }} {{ $documento->estado_asignacion == 'Devuelto' ? 'bg-gray-200 text-gray-800' : '' }} {{ $documento->estado_asignacion == 'Revisado' ? 'bg-green-100 text-green-800' : '' }}">{{ $documento->estado_asignacion }}</span></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                {{ $documento->resultado_validacion == 'Aprobado' ? 'bg-green-100 text-green-800' : '' }}
                                                {{ $documento->resultado_validacion == 'Rechazado' ? 'bg-red-100 text-red-800' : '' }}">
                                                {{ $documento->resultado_validacion ?? '---' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            {{ $documento->fecha_vencimiento ? $documento->fecha_vencimiento->format('d-m-Y') : 'Por Periodo' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                {{ $documento->estado_vigencia == 'Vigente' ? 'bg-green-100 text-green-800' : '' }}
                                                {{ $documento->estado_vigencia == 'Vencido' ? 'bg-red-100 text-red-800' : '' }}
                                                {{ $documento->estado_vigencia == 'Por Periodo' ? 'bg-gray-200 text-gray-800' : '' }}">
                                                {{ $documento->estado_vigencia }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $documento->validador->name ?? '---' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $documento->created_at->format('d-m-Y H:i') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            @php
                                                $horas = $documento->fecha_validacion 
                                                            ? (int) abs(Carbon::parse($documento->fecha_validacion)->diffInHours($documento->created_at))
                                                            : (int) abs(now()->diffInHours($documento->created_at));
                                            @endphp
                                            {{ $horas }} horas
                                        </td>
                                    </tr>
                                @empty
                                    <tr ><td colspan="17" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No hay documentos que coincidan con los filtros aplicados.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">{{ $documentosPendientes->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>