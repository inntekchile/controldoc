<div>
    {{-- Manejo de mensajes de sesión --}}
    @if (session()->has('message')) <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert"><p>{{ session('message') }}</p></div> @endif
    @if (session()->has('info')) <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4" role="alert"><p>{{ session('info') }}</p></div> @endif
    @if (session()->has('error')) <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert"><p>{{ session('error') }}</p></div> @endif

    <div class="p-4 sm:p-6 lg:p-8">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-4">Revisar Documento</h2>

        @if ($documento)
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                
                <div class="lg:col-span-3 h-[calc(100vh-12rem)]">
                    <div class="bg-gray-200 dark:bg-gray-900 w-full h-full rounded-lg shadow">
                         @if (Str::endsWith(strtolower($documento->ruta_archivo ?? ''), '.pdf'))
                            @if ($pdfUrl) <iframe src="{{ $pdfUrl }}" width="100%" height="100%" frameborder="0"></iframe> @else <div class="flex items-center justify-center h-full text-gray-500">No se pudo cargar la vista previa del documento.</div> @endif
                        @else
                            <div class="flex flex-col items-center justify-center h-full text-center p-4">
                                <h3 class="text-xl font-bold text-gray-700 dark:text-gray-300">Vista Previa no Disponible</h3>
                                <p class="text-gray-500 mt-2">Este archivo no es un PDF ({{ $documento->mime_type }}). Por favor, descárguelo para revisarlo.</p>
                                <a href="{{ $pdfUrl }}" target="_blank" class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500">Descargar Archivo ({{ $documento->nombre_original_archivo }})</a>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="lg:col-span-1 space-y-6">
                    
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                        <h3 class="font-bold text-lg mb-2 text-gray-900 dark:text-gray-100 border-b pb-2">Principal</h3> <p class="text-sm text-gray-600 dark:text-gray-400">{{ $documento->mandante->razon_social ?? 'N/A' }}</p>
                        <h3 class="font-bold text-lg mt-3 mb-2 text-gray-900 dark:text-gray-100 border-b pb-2">Contratista</h3> <p class="text-sm text-gray-600 dark:text-gray-400">{{ $documento->contratista->razon_social ?? 'N/A' }} ({{ $documento->contratista->rut }})</p>
                        <h3 class="font-bold text-lg mt-3 mb-2 text-gray-900 dark:text-gray-100 border-b pb-2">Entidad</h3> <p class="text-sm text-gray-600 dark:text-gray-400"> <strong>{{ str_replace('App\\Models\\', '', $documento->entidad_type) }}:</strong> {{ $documento->entidad_identificador }} <br> <strong>Nombre:</strong> {{ $documento->entidad->nombre_completo ?? ($documento->entidad->patente ?? 'N/A') }} </p>
                        <h3 class="font-bold text-lg mt-3 mb-2 text-gray-900 dark:text-gray-100 border-b pb-2">Documento</h3> <p class="text-sm text-gray-600 dark:text-gray-400"> {{ $documento->nombre_documento_snapshot ?? 'N/A' }} <br> <strong class="font-semibold text-gray-700 dark:text-gray-300">Cargado:</strong> {{ $documento->created_at->format('d-m-Y H:i') }} </p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                        <h3 class="font-bold text-lg mb-2 text-gray-900 dark:text-gray-100 border-b pb-2">Guía de Regla</h3>
                        <div class="space-y-3 mt-3 text-sm">
                            @if ($documento->observacion_documento_snapshot)
                                <div><strong class="font-semibold text-gray-700 dark:text-gray-300">Observación:</strong><p class="text-gray-600 dark:text-gray-400 italic">{{ $documento->observacion_documento_snapshot }}</p></div>
                            @endif
                        
                            
                            @if ($documento->reglaDocumental?->formatoDocumento?->ruta_archivo)
                                <div><strong class="font-semibold text-gray-700 dark:text-gray-300">Formato:</strong><p><a href="{{ Storage::disk('public')->url($documento->reglaDocumental->formatoDocumento->ruta_archivo) }}" target="_blank" class="text-blue-500 hover:underline">{{ $documento->formato_documento_snapshot }}</a></p></div>
                            @elseif ($documento->formato_documento_snapshot)
                                <div><strong class="font-semibold text-gray-700 dark:text-gray-300">Formato:</strong><p>{{ $documento->formato_documento_snapshot }}</p></div>
                            @endif
                             @if ($documento->documento_relacionado_id_snapshot)
                                <div>
                                    <strong class="font-semibold text-gray-700 dark:text-gray-300">Doc. Relacionado:</strong>
                                    @if ($documentoRelacionado)
                                        <div class="mt-1 p-2 border rounded-md"><p class="font-medium">{{ $documentoRelacionado->nombre_documento_snapshot }}</p><p>Estado: <span @class(['font-bold', 'text-green-600' => $documentoRelacionado->resultado_validacion == 'Aprobado', 'text-red-600' => $documentoRelacionado->resultado_validacion == 'Rechazado', 'text-blue-600' => in_array($documentoRelacionado->estado_validacion, ['Pendiente', 'En Revisión'])])>{{ $documentoRelacionado->resultado_validacion ?? $documentoRelacionado->estado_validacion }}</span></p><a href="{{ Storage::disk('public')->url($documentoRelacionado->ruta_archivo) }}" target="_blank" class="text-blue-500 hover:underline">Ver Doc →</a></div>
                                    @else
                                        <p class="italic">No se encontró</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                        <h3 class="font-bold text-lg mb-2 text-gray-900 dark:text-gray-100 border-b pb-2">Checklist de Validación</h3>
                        <div class="space-y-4 mt-3">

                            {{-- ============================================= --}}
                            {{-- INICIO: NUEVO BLOQUE PARA FECHA DE EMISIÓN --}}
                            {{-- ============================================= --}}
                            @if($documento->valida_emision_snapshot)
                                <div class="space-y-2 p-2 border rounded-md @if($errors->has('fechaEmisionValidador') || $errors->has('confirmaFechaEmision')) border-red-400 @else border-gray-300 dark:border-gray-600 @endif">
                                    <label for="fechaEmision" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha Emisión:</label>
                                    <input type="date" id="fechaEmision" wire:model.live="fechaEmisionValidador" class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm text-sm" {{ $isReadOnly || $decision ? 'disabled' : '' }}>
                                    <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 mt-2">
                                        <input type="checkbox" wire:model.live="confirmaFechaEmision" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ $isReadOnly || $decision ? 'disabled' : '' }}>
                                        <span class="ml-2">Confirmo la fecha de emisión</span>
                                    </label>
                                    @error('fechaEmisionValidador') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    @error('confirmaFechaEmision') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                            @endif
                            {{-- ============================================= --}}
                            {{-- FIN: NUEVO BLOQUE --}}
                            {{-- ============================================= --}}

                            @if($documento->valida_vencimiento_snapshot)
                                <div class="space-y-2 p-2 border rounded-md @if($errors->has('fechaVencimientoValidador') || $errors->has('confirmaFechaVencimiento')) border-red-400 @else border-gray-300 dark:border-gray-600 @endif">
                                    <label for="fechaVencimiento" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha Vencimiento:</label>
                                    <input type="date" id="fechaVencimiento" wire:model.live="fechaVencimientoValidador" class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm text-sm" {{ $isReadOnly || $decision ? 'disabled' : '' }}>
                                    <label class="flex items-center text-sm text-gray-700 dark:text-gray-300 mt-2">
                                        <input type="checkbox" wire:model.live="confirmaFechaVencimiento" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ $isReadOnly || $decision ? 'disabled' : '' }}>
                                        <span class="ml-2">Confirmo la fecha de vencimiento</span>
                                    </label>
                                    @error('fechaVencimientoValidador') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    @error('confirmaFechaVencimiento') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                            @endif
                            
                            @forelse ($criterios as $index => $criterioData)
                                <div>
                                    <label class="flex items-start text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                        <input type="checkbox" wire:model.live="criteriosCumplidos.{{ $index }}" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 mt-0.5" {{ $isReadOnly || $decision ? 'disabled' : '' }}>
                                        <div class="ml-2">
                                            <span class="font-semibold">{{ $criterioData['criterio'] ?? 'Criterio no definido' }}</span>
                                             @if(!empty($criterioData['sub_criterio']) || !empty($criterioData['aclaracion']))
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                @if(!empty($criterioData['sub_criterio']))
                                                    <span class="font-medium">Detalle: {{ $criterioData['sub_criterio'] }}</span>
                                                    @if(!empty($criterioData['aclaracion'])) | @endif
                                                @endif
                                                @if(!empty($criterioData['aclaracion']))
                                                    {{ $criterioData['aclaracion'] }}
                                                @endif
                                            </p>
                                            @endif
                                        </div>
                                    </label>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400">No hay un checklist específico para este documento.</p>
                            @endforelse
                        </div>
                    </div>

                    @if(!$isReadOnly)
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow space-y-4">
                            @if(!$decision)
                                <h3 class="font-bold text-lg text-gray-900 dark:text-gray-100 border-b pb-2">Decisión</h3>
                                <div class="flex space-x-2">
                                    <button wire:click="seleccionarDecision('Aprobado')" @if(!$puedeAprobar) disabled title="Debe marcar todos los criterios y confirmar fechas para aprobar" @endif class="flex-1 justify-center inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 disabled:opacity-50 disabled:cursor-not-allowed">Aceptar</button>
                                    <button wire:click="seleccionarDecision('Rechazado')" @if(!$puedeRechazar) disabled title="No puede rechazar si todos los criterios están cumplidos" @endif class="flex-1 justify-center inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 disabled:opacity-50 disabled:cursor-not-allowed">Rechazar</button>
                                </div>
                            @endif
                            @if($decision)
                                <h3 class="font-bold text-lg text-gray-900 dark:text-gray-100 border-b pb-2">Confirmar Decisión: <span class="{{ $decision == 'Aprobado' ? 'text-green-500' : 'text-red-500' }}">{{ $decision }}</span></h3>
                                @if($decision == 'Rechazado')
                                    <div class="space-y-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Motivos de Rechazo (Automático):</label>
                                        <div class="p-3 border rounded-md border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 space-y-1 max-h-40 overflow-y-auto">
                                            @forelse($motivosRechazoCalculados as $motivo) <p class="text-sm text-gray-800 dark:text-gray-200">- {{ $motivo }}</p> @empty <p class="text-sm text-yellow-600 dark:text-yellow-400">No hay motivos de rechazo definidos para los criterios no cumplidos.</p> @endforelse
                                        </div>
                                    </div>
                                @endif
                                @error('decision') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                <div class="flex space-x-2">
                                    <button wire:click="procesarDecision" wire:loading.attr="disabled" class="flex-1 justify-center inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-500">
                                        <span wire:loading.remove>Confirmar</span> <span wire:loading>PROCESANDO...</span>
                                    </button>
                                    <button wire:click="resetDecision" wire:loading.attr="disabled" class="flex-1 justify-center inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500">Volver a Validar</button>
                                </div>
                            @endif
                            <hr class="dark:border-gray-600">
                            <div>
                                <label for="motivoDevolucion" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo de Devolución (a Admin):</label>
                                <textarea id="motivoDevolucion" wire:model.live="motivoDevolucion" rows="2" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm" placeholder="Explique por qué devuelve este documento..."></textarea>
                                @error('motivoDevolucion') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                <button wire:click="devolverAAdmin" wire:loading.attr="disabled" class="mt-2 w-full justify-center inline-flex items-center px-4 py-2 bg-yellow-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-400">Devolver Documento</button>
                            </div>
                        </div>
                    @else
                        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                             <h3 class="font-bold text-lg mb-2 text-gray-900 dark:text-gray-100 border-b pb-2">Estado Final</h3>
                             <p class="text-sm font-semibold {{ $documento->resultado_validacion == 'Aprobado' ? 'text-green-600' : 'text-red-600' }}">Estado: {{ $documento->resultado_validacion }}</p>
                             <p class="text-sm text-gray-600 dark:text-gray-400">Fecha Validación: {{ $documento->fecha_validacion ? $documento->fecha_validacion->format('d-m-Y H:i') : 'N/A' }}</p>
                             @if($documento->observacion_rechazo) <p class="text-sm text-gray-600 dark:text-gray-400 mt-2"><strong>Motivo del Rechazo:</strong> <br> {!! nl2br(e($documento->observacion_rechazo)) !!}</p> @endif
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow text-center">
                <h3 class="text-lg font-bold text-gray-700 dark:text-gray-300">Documento no encontrado</h3>
                <p class="text-gray-500 dark:text-gray-400 mt-2">No se ha podido cargar el documento solicitado.</p>
                <a href="{{ route('asem.panel-validacion') }}" wire:navigate class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-500">Volver al Panel</a>
            </div>
        @endif
    </div>
</div>