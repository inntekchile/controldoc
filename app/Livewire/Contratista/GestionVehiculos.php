<?php

namespace App\Livewire\Contratista;

use Livewire\Component;
use App\Models\Vehiculo;
use App\Models\Contratista;
use App\Models\TipoVehiculo;
use App\Models\MarcaVehiculo;
use App\Models\ColorVehiculo;
use App\Models\TenenciaVehiculo;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\VehiculoAsignacion;
use App\Models\ReglaDocumental;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;
use App\Services\DocumentoRequeridoService;
use Illuminate\Support\Facades\DB;
use Livewire\WithFileUploads;
use App\Models\DocumentoCargado;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon; // <--- IMPORTACIÓN AÑADIDA

class GestionVehiculos extends Component
{
    use WithPagination;
    use WithFileUploads;

    public ?int $mandanteId = null;
    public ?int $unidadOrganizacionalId = null;
    public $contratistaId;
    public string $vistaActual = 'listado_vehiculos';
    public ?Vehiculo $vehiculoSeleccionado = null;
    public string $nombreVinculacionSeleccionada = '';
    public string $searchVehiculo = '';
    public string $sortBy = 'vehiculos.id';
    public string $sortDirection = 'asc';
    public bool $showModalFicha = false;
    public ?int $vehiculoId = null;
    public string $patente_letras = '', $patente_numeros = '';
    public ?string $ano_fabricacion = null;
    public ?int $marca_vehiculo_id = null, $color_vehiculo_id = null, $tipo_vehiculo_id = null, $tenencia_vehiculo_id = null;
    public bool $vehiculo_is_active = true;
    public bool $showModalAsignacion = false;
    public ?int $asignacionId = null;
    public ?int $a_unidad_organizacional_id = null;
    public ?string $a_fecha_asignacion = null;
    public bool $a_is_active = true;
    public ?string $a_fecha_desasignacion = null;
    public ?string $a_motivo_desasignacion = null;
    public $tiposVehiculo, $marcasVehiculo, $coloresVehiculo, $tenenciasVehiculo;
    public $unidadesOrganizacionalesDisponibles = [];
    public bool $showDocumentosModal = false;
    public ?Vehiculo $vehiculoParaDocumentos = null;
    public string $nombreVehiculoParaDocumentosModal = '';
    public array $documentosRequeridos = [];
    public array $documentosParaCargar = [];
    public array $uploadErrors = [];
    public array $uploadSuccess = [];

    private DocumentoRequeridoService $documentoService;

    public function boot(DocumentoRequeridoService $documentoService)
    {
        $this->documentoService = $documentoService;
    }

    protected function messages()
    {
        return [
            '*.required' => 'Este campo es obligatorio.',
            'patente_letras.unique' => 'La patente ingresada ya existe para su empresa.',
            'a_unidad_organizacional_id.required' => 'Debe seleccionar una Unidad Organizacional.',
            'a_fecha_asignacion.required' => 'La fecha de asignación es obligatoria.',
            'a_fecha_desasignacion.required_if' => 'La fecha es obligatoria si la asignación no está activa.',
            'a_motivo_desasignacion.required_if' => 'El motivo es obligatorio si la asignación no está activa.',
        ];
    }

    public function mount(?int $mandanteId = null, ?int $unidadOrganizacionalId = null)
    {
        $this->mandanteId = $mandanteId;
        $this->unidadOrganizacionalId = $unidadOrganizacionalId;
        $user = Auth::user();
        if (!$user || !$user->contratista_id) {
            session()->flash('error', 'Usuario no asociado a un contratista válido.');
            return;
        }
        $this->contratistaId = $user->contratista_id;
        if ($this->unidadOrganizacionalId) {
            $uoContexto = UnidadOrganizacionalMandante::with('mandante:id,razon_social')->find($this->unidadOrganizacionalId);
            if ($uoContexto && $uoContexto->mandante) {
                $this->nombreVinculacionSeleccionada = ($uoContexto->mandante->razon_social ?? 'N/A') . ' - ' . $uoContexto->nombre_unidad;
            }
        }
        $this->tiposVehiculo = TipoVehiculo::where('is_active', true)->orderBy('nombre')->get();
        $this->marcasVehiculo = MarcaVehiculo::where('is_active', true)->orderBy('nombre')->get();
        $this->coloresVehiculo = ColorVehiculo::where('is_active', true)->orderBy('nombre')->get();
        $this->tenenciasVehiculo = TenenciaVehiculo::where('is_active', true)->orderBy('nombre')->get();
    }

    public function irAListadoVehiculos()
    {
        $this->vistaActual = 'listado_vehiculos';
        $this->vehiculoSeleccionado = null;
        $this->resetPage('vehiculosPage');
    }

    public function seleccionarVehiculoParaAsignaciones($vehiculoId)
    {
        $this->vehiculoSeleccionado = Vehiculo::find($vehiculoId);
        if ($this->vehiculoSeleccionado && $this->vehiculoSeleccionado->contratista_id == $this->contratistaId) {
            $this->vistaActual = 'listado_asignaciones';
            $this->resetPage('asignacionesPage');
        } else {
            session()->flash('error', 'Vehículo no encontrado o no pertenece a su empresa.');
            $this->vehiculoSeleccionado = null;
        }
    }

    public function abrirModalDocumentos($vehiculoId, $mantenerMensajes = false)
    {
        if (!$this->unidadOrganizacionalId || !$this->mandanteId) {
            session()->flash('error', 'Error de contexto. Por favor, seleccione una vinculación para operar.');
            return;
        }
        $this->vehiculoParaDocumentos = Vehiculo::with(['marcaVehiculo', 'tipoVehiculo'])->find($vehiculoId);
        if (!$this->vehiculoParaDocumentos || $this->vehiculoParaDocumentos->contratista_id != $this->contratistaId) {
            session()->flash('error_modal_documentos', 'Vehículo no encontrado o no pertenece a su empresa.');
            $this->cerrarModalDocumentos();
            return;
        }
        $this->nombreVehiculoParaDocumentosModal = $this->vehiculoParaDocumentos->patente_completa;
        $this->determinarDocumentosRequeridosParaVehiculo();
        if (!$mantenerMensajes) {
            $this->uploadErrors = [];
            $this->uploadSuccess = [];
        }
        $tempDocumentosParaCargar = [];
        foreach ($this->documentosRequeridos as $doc) {
            $reglaId = $doc['regla_documental_id_origen'];
            $tempDocumentosParaCargar[$reglaId] = [
                'archivo_input' => null, 'fecha_emision_input' => null,
                'fecha_vencimiento_input' => null, 'periodo_input' => null,
                'regla_info' => $doc, 
            ];
        }
        $this->documentosParaCargar = $tempDocumentosParaCargar;
        $this->resetErrorBag();
        $this->showDocumentosModal = true;
    }

    private function determinarDocumentosRequeridosParaVehiculo()
    {
        $reglasCandidatas = $this->documentoService->getReglasParaEntidadEnUO($this->mandanteId, $this->unidadOrganizacionalId, 'VEHICULO')
            ->load([
                'nombreDocumento', 'observacionDocumento', 'formatoDocumento', 'documentoRelacionado',
                'tipoVencimiento', 'criterios.criterioEvaluacion', 'criterios.subCriterio',
                'criterios.textoRechazo', 'criterios.aclaracionCriterio', 'tiposVehiculoAplica:id',
                'tenenciasAplica:id'
            ]);

        $documentosCargadosExistentes = DocumentoCargado::where('entidad_id', $this->vehiculoParaDocumentos->id)
            ->where('entidad_type', Vehiculo::class)->where('archivado', false)
            ->orderBy('created_at', 'desc')->get()->keyBy('regla_documental_id_origen');

        $condicionContratistaEnUO = DB::table('contratista_unidad_organizacional')
            ->where('contratista_id', $this->contratistaId)
            ->where('unidad_organizacional_mandante_id', $this->unidadOrganizacionalId)->value('tipo_condicion_id');

        $documentosFinales = []; $idsDocumentosAgregados = [];

        foreach ($reglasCandidatas as $regla) {
            if ($regla->aplica_empresa_condicion_id && $regla->aplica_empresa_condicion_id != $condicionContratistaEnUO) continue;
            
            $patenteCompleta = $this->vehiculoParaDocumentos->patente_letras . $this->vehiculoParaDocumentos->patente_numeros;
            if (!empty($regla->rut_especificos) && !in_array($patenteCompleta, array_map('trim', explode(',', $regla->rut_especificos)))) continue;
            if (!empty($regla->rut_excluidos) && in_array($patenteCompleta, array_map('trim', explode(',', $regla->rut_excluidos)))) continue;

            $idsTiposVehiculoRegla = $regla->tiposVehiculoAplica->pluck('id')->toArray();
            if (!empty($idsTiposVehiculoRegla) && (!$this->vehiculoParaDocumentos->tipo_vehiculo_id || !in_array($this->vehiculoParaDocumentos->tipo_vehiculo_id, $idsTiposVehiculoRegla))) continue;

            $idsTenenciasRegla = $regla->tenenciasAplica->pluck('id')->toArray();
            if (!empty($idsTenenciasRegla) && (!$this->vehiculoParaDocumentos->tenencia_vehiculo_id || !in_array($this->vehiculoParaDocumentos->tenencia_vehiculo_id, $idsTenenciasRegla))) continue;
            
            if (!in_array($regla->nombre_documento_id, $idsDocumentosAgregados)) {
                $docCargado = $documentosCargadosExistentes->get($regla->id);
                $estadoActual = 'No Cargado';
                if ($docCargado) {
                    if ($docCargado->resultado_validacion === 'Rechazado') { $estadoActual = 'Rechazado'; } 
                    elseif ($docCargado->estado_validacion === 'Pendiente') { $estadoActual = 'Pendiente Validación'; } 
                    elseif ($docCargado->estado_validacion === 'En Revisión') { $estadoActual = 'En Revisión'; } 
                    elseif ($docCargado->resultado_validacion === 'Aprobado') { $estadoActual = $docCargado->estadoVigencia; }
                }

                $documentosFinales[] = [
                    'regla_documental_id_origen' => $regla->id,
                    'nombre_documento_id' => $regla->nombre_documento_id,
                    'nombre_documento_texto' => $regla->nombreDocumento?->nombre ?? 'Doc. Desconocido',
                    'observacion_documento_texto' => $regla->observacionDocumento?->titulo,
                    'estado_actual_documento' => $estadoActual,
                    'archivo_cargado' => $docCargado,
                    'valida_emision' => (bool) $regla->valida_emision,
                    'valida_vencimiento' => (bool) $regla->valida_vencimiento,
                    'tipo_vencimiento_nombre' => $regla->tipoVencimiento?->nombre,
                    'criterios_evaluacion' => $regla->criterios->map(fn ($c) => ['criterio' => $c->criterioEvaluacion?->nombre_criterio, 'sub_criterio' => $c->subCriterio?->nombre, 'texto_rechazo' => $c->textoRechazo?->titulo, 'aclaracion' => $c->aclaracionCriterio?->titulo])->all(),
                    'afecta_cumplimiento' => (bool) $regla->afecta_porcentaje_cumplimiento,
                    'restringe_acceso' => (bool) $regla->restringe_acceso,
                ];
                $idsDocumentosAgregados[] = $regla->nombre_documento_id;
            }
        }
        $this->documentosRequeridos = $documentosFinales;
    }

    public function cerrarModalDocumentos()
    {
        $this->showDocumentosModal = false;
        $this->vehiculoParaDocumentos = null;
        $this->nombreVehiculoParaDocumentosModal = '';
        $this->documentosRequeridos = [];
        $this->documentosParaCargar = [];
        $this->uploadErrors = [];
        $this->uploadSuccess = [];
        $this->resetValidation();
    }
    
    public function cargarDocumentos()
    {
        if (!$this->vehiculoParaDocumentos) { session()->flash('error_modal_documentos', 'Error: No se ha seleccionado un vehículo válido.'); return; }
        
        $this->uploadErrors = []; $this->uploadSuccess = []; $this->resetErrorBag();
        $this->validate(['documentosParaCargar.*.archivo_input' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:10240']);

        $usuarioCargaId = Auth::id(); 
        $huboArchivosParaProcesar = false;

        foreach ($this->documentosParaCargar as $reglaId => $data) {
            if (empty($data['archivo_input'])) continue;
            
            $huboArchivosParaProcesar = true;
            $reglaInfo = $data['regla_info'];
            $archivo = $data['archivo_input'];

            $errorValidacion = null;
            if (($reglaInfo['valida_emision'] || $reglaInfo['tipo_vencimiento_nombre'] === 'DESDE EMISION') && empty($data['fecha_emision_input'])) { $errorValidacion = 'Se requiere Fecha de Emisión.'; }
            if (($reglaInfo['valida_vencimiento'] || $reglaInfo['tipo_vencimiento_nombre'] === 'FIJO') && empty($data['fecha_vencimiento_input'])) { $errorValidacion = 'Se requiere Fecha de Vencimiento.'; }
            if ($reglaInfo['tipo_vencimiento_nombre'] === 'PERIODO' && empty($data['periodo_input'])) { $errorValidacion = 'Se requiere el Período.'; }
            if ($errorValidacion) { $this->addError('documentosParaCargar.' . $reglaId . '.archivo_input', $errorValidacion); continue; }

            try {
                $reglaOriginal = ReglaDocumental::with(['nombreDocumento', 'observacionDocumento', 'formatoDocumento', 'tipoVencimiento'])->findOrFail($reglaId);

                $docPendiente = DocumentoCargado::where('entidad_id', $this->vehiculoParaDocumentos->id)->where('entidad_type', Vehiculo::class)->where('regla_documental_id_origen', $reglaId)
                    ->where('estado_validacion', 'Pendiente')->where('archivado', false)->first();
                if ($docPendiente) { Storage::disk('public')->delete($docPendiente->ruta_archivo); $docPendiente->delete(); }
                
                $nombreArchivo = Str::uuid() . '.' . $archivo->getClientOriginalExtension();
                $rutaDirectorio = "documentos/c-{$this->contratistaId}/vehiculos/v-{$this->vehiculoParaDocumentos->id}";
                $rutaArchivo = $archivo->storeAs($rutaDirectorio, $nombreArchivo, 'public');

                // =========================================================================================
                // INICIO: LÓGICA CORREGIDA PARA EL CÁLCULO DE LA FECHA DE VENCIMIENTO
                // =========================================================================================
                $fechaVencimientoCalculada = $data['fecha_vencimiento_input'] ?? null;
                if ($reglaOriginal->tipoVencimiento?->nombre === 'DESDE EMISION' && !empty($data['fecha_emision_input'])) {
                    $diasValidez = $reglaOriginal->dias_validez_documento ?? 0;
                    $fechaVencimientoCalculada = Carbon::parse($data['fecha_emision_input'])->addDays($diasValidez)->format('Y-m-d');
                }
                // =========================================================================================
                // FIN: LÓGICA CORREGIDA
                // =========================================================================================

                DocumentoCargado::create([
                    'contratista_id' => $this->contratistaId, 
                    'mandante_id' => $this->mandanteId, 
                    'unidad_organizacional_id' => $this->unidadOrganizacionalId,
                    'entidad_id' => $this->vehiculoParaDocumentos->id, 
                    'entidad_type' => Vehiculo::class, 
                    'regla_documental_id_origen' => $reglaId,
                    'usuario_carga_id' => $usuarioCargaId, 
                    'ruta_archivo' => $rutaArchivo, 
                    'nombre_original_archivo' => $archivo->getClientOriginalName(),
                    'mime_type' => $archivo->getMimeType(), 
                    'tamano_archivo' => $archivo->getSize(), 
                    'fecha_emision' => $data['fecha_emision_input'] ?? null,
                    'fecha_vencimiento' => $fechaVencimientoCalculada, // <-- Se usa la variable calculada
                    'periodo' => isset($data['periodo_input']) ? date('Y-m', strtotime($data['periodo_input'])) : null, 
                    'estado_validacion' => 'Pendiente',

                    'nombre_documento_snapshot' => $reglaOriginal->nombreDocumento?->nombre,
                    'observacion_documento_snapshot' => $reglaOriginal->observacionDocumento?->titulo,
                    'formato_documento_snapshot' => $reglaOriginal->formatoDocumento?->nombre,
                    'documento_relacionado_id_snapshot' => $reglaOriginal->documento_relacionado_id,
                    'tipo_vencimiento_snapshot' => $reglaOriginal->tipoVencimiento?->nombre,
                    'valida_emision_snapshot' => (bool)$reglaOriginal->valida_emision,
                    'valida_vencimiento_snapshot' => (bool)$reglaOriginal->valida_vencimiento,
                    'valor_nominal_snapshot' => $reglaOriginal->valor_nominal_documento,
                    'habilita_acceso_snapshot' => (bool)$reglaOriginal->restringe_acceso,
                    'afecta_cumplimiento_snapshot' => (bool)$reglaOriginal->afecta_porcentaje_cumplimiento,
                    'es_perseguidor_snapshot' => (bool)$reglaOriginal->documento_es_perseguidor,
                    'criterios_snapshot' => $reglaInfo['criterios_evaluacion'],
                ]);
                $this->uploadSuccess[$reglaId] = 'Archivo cargado exitosamente.';
            } catch (\Exception $e) {
                Log::error("Error al cargar documento para vehículo {$this->vehiculoParaDocumentos->id}, Regla {$reglaId}: " . $e->getMessage());
                $this->addError('documentosParaCargar.' . $reglaId . '.archivo_input', 'Error inesperado al procesar.');
            }
        }
        if (!$huboArchivosParaProcesar) { 
            session()->flash('info_modal_documentos', 'No se seleccionó ningún archivo nuevo para cargar.');
        } else { 
            session()->flash('message_modal_documentos', 'Proceso de carga finalizado. Revise los estados individuales.'); 
        }
        $this->abrirModalDocumentos($this->vehiculoParaDocumentos->id, true);
    }
    
    public function eliminarDocumentoCargado($documentoCargadoId)
    {
        $doc = DocumentoCargado::find($documentoCargadoId);
        if ($doc && $doc->contratista_id == $this->contratistaId && $doc->estado_validacion === 'Pendiente') {
            try {
                Storage::disk('public')->delete($doc->ruta_archivo); $doc->delete();
                session()->flash('message_modal_documentos', 'Documento pendiente eliminado correctamente.');
            } catch (\Exception $e) {
                Log::error("Error al eliminar documento cargado ID {$documentoCargadoId}: " . $e->getMessage());
                session()->flash('error_modal_documentos', 'Ocurrió un error al eliminar el documento.');
            }
        } else { session()->flash('error_modal_documentos', 'No se puede eliminar el documento.'); }
        $this->abrirModalDocumentos($this->vehiculoParaDocumentos->id, true);
    }

    public function rulesFichaVehiculo() { return [ 'patente_letras' => ['required', 'string', 'min:2', 'max:4', Rule::unique('vehiculos')->where(fn ($query) => $query->where('contratista_id', $this->contratistaId)->where('patente_numeros', $this->patente_numeros))->ignore($this->vehiculoId, 'id'),], 'patente_numeros' => 'required|string|min:2|max:4', 'ano_fabricacion' => 'required|integer|digits:4|min:1950|max:' . (date('Y') + 1), 'marca_vehiculo_id' => 'required|exists:marcas_vehiculo,id', 'color_vehiculo_id' => 'required|exists:colores_vehiculo,id', 'tipo_vehiculo_id' => 'required|exists:tipos_vehiculo,id', 'tenencia_vehiculo_id' => 'nullable|exists:tenencias_vehiculo,id', 'vehiculo_is_active' => 'boolean', ]; }
    public function guardarVehiculo() { $validatedData = $this->validate($this->rulesFichaVehiculo()); $validatedData['contratista_id'] = $this->contratistaId; $validatedData['patente_letras'] = strtoupper($this->patente_letras); $validatedData['patente_numeros'] = strtoupper($this->patente_numeros); try { if ($this->vehiculoId) { $vehiculo = Vehiculo::find($this->vehiculoId); if ($vehiculo && $vehiculo->contratista_id == $this->contratistaId) { $vehiculo->update($validatedData); session()->flash('message', 'Ficha del vehículo actualizada correctamente.'); if ($this->vehiculoSeleccionado) { $this->vehiculoSeleccionado->refresh(); } } } else { $vehiculo = Vehiculo::create($validatedData); if ($this->unidadOrganizacionalId) { $vehiculo->unidadesOrganizacionales()->attach($this->unidadOrganizacionalId, ['fecha_asignacion' => now(), 'is_active' => true]); session()->flash('message', 'Vehículo agregado y asignado a esta Unidad Organizacional.'); } else { session()->flash('message', 'Vehículo agregado correctamente.'); } } $this->cerrarModalFicha(); } catch (\Exception $e) { Log::error("Error al guardar vehículo: " . $e->getMessage()); session()->flash('error', 'Ocurrió un error al guardar. Verifique que la patente no esté duplicada.'); } }
    private function resetFichaVehiculoFields() { $this->vehiculoId = null; $this->patente_letras = ''; $this->patente_numeros = ''; $this->ano_fabricacion = null; $this->marca_vehiculo_id = null; $this->color_vehiculo_id = null; $this->tipo_vehiculo_id = null; $this->tenencia_vehiculo_id = null; $this->vehiculo_is_active = true; $this->resetValidation(); }
    public function abrirModalNuevoVehiculo() { $this->resetFichaVehiculoFields(); $this->showModalFicha = true; }
    public function abrirModalEditarVehiculo($id) { $vehiculo = Vehiculo::find($id); if ($vehiculo && $vehiculo->contratista_id == $this->contratistaId) { $this->vehiculoId = $vehiculo->id; $this->patente_letras = $vehiculo->patente_letras; $this->patente_numeros = $vehiculo->patente_numeros; $this->ano_fabricacion = $vehiculo->ano_fabricacion; $this->marca_vehiculo_id = $vehiculo->marca_vehiculo_id; $this->color_vehiculo_id = $vehiculo->color_vehiculo_id; $this->tipo_vehiculo_id = $vehiculo->tipo_vehiculo_id; $this->tenencia_vehiculo_id = $vehiculo->tenencia_vehiculo_id; $this->vehiculo_is_active = $vehiculo->is_active; if (!$this->vehiculoSeleccionado) { $this->vehiculoSeleccionado = $vehiculo; } $this->showModalFicha = true; } }
    public function cerrarModalFicha() { $this->showModalFicha = false; $this->resetFichaVehiculoFields(); }
    public function rulesAsignacion() { return [ 'a_unidad_organizacional_id' => ['required', 'exists:unidades_organizacionales_mandante,id', function ($attribute, $value, $fail) { if ($this->a_is_active) { $query = VehiculoAsignacion::where('vehiculo_id', $this->vehiculoSeleccionado->id) ->where('unidad_organizacional_mandante_id', $value)->where('is_active', true); if ($this->asignacionId) { $query->where('id', '!=', $this->asignacionId); } if ($query->exists()) { $fail('El vehículo ya tiene una asignación activa en esta Unidad Organizacional.'); } } } ], 'a_fecha_asignacion' => 'required|date', 'a_is_active' => 'required|boolean', 'a_fecha_desasignacion' => 'nullable|required_if:a_is_active,false|date|after_or_equal:a_fecha_asignacion', 'a_motivo_desasignacion' => 'nullable|required_if:a_is_active,false|string|max:500', ]; }
    private function resetAsignacionFields() { $this->asignacionId = null; $this->a_unidad_organizacional_id = null; $this->a_fecha_asignacion = null; $this->a_is_active = true; $this->a_fecha_desasignacion = null; $this->a_motivo_desasignacion = null; $this->resetValidation(); }
    public function abrirModalNuevaAsignacion() { $this->unidadesOrganizacionalesDisponibles = Contratista::find($this->contratistaId)->unidadesOrganizacionalesMandante()->with('mandante:id,razon_social')->orderBy('mandante_id')->get(); $this->resetAsignacionFields(); $this->a_fecha_asignacion = now()->format('Y-m-d'); $this->showModalAsignacion = true; }
    public function abrirModalEditarAsignacion($asignacionId) { $asignacion = VehiculoAsignacion::find($asignacionId); if ($asignacion && $asignacion->vehiculo_id == $this->vehiculoSeleccionado?->id) { $this->unidadesOrganizacionalesDisponibles = Contratista::find($this->contratistaId) ->unidadesOrganizacionalesMandante()->with('mandante:id,razon_social')->orderBy('mandante_id')->get(); $this->asignacionId = $asignacion->id; $this->a_unidad_organizacional_id = $asignacion->unidad_organizacional_mandante_id; $this->a_fecha_asignacion = \Carbon\Carbon::parse($asignacion->fecha_asignacion)->format('Y-m-d'); $this->a_is_active = $asignacion->is_active; $this->a_fecha_desasignacion = $asignacion->fecha_desasignacion ? \Carbon\Carbon::parse($asignacion->fecha_desasignacion)->format('Y-m-d') : null; $this->a_motivo_desasignacion = $asignacion->motivo_desasignacion; $this->showModalAsignacion = true; } else { session()->flash('error', 'No se pudo encontrar la asignación a editar.'); } }
    public function guardarAsignacion() { $validatedData = $this->validate($this->rulesAsignacion()); if ($validatedData['a_is_active']) { $validatedData['a_fecha_desasignacion'] = null; $validatedData['a_motivo_desasignacion'] = null; } if ($this->asignacionId) { $asignacion = VehiculoAsignacion::find($this->asignacionId); if ($asignacion) { $asignacion->update([ 'unidad_organizacional_mandante_id' => $validatedData['a_unidad_organizacional_id'], 'fecha_asignacion' => $validatedData['a_fecha_asignacion'], 'is_active' => $validatedData['a_is_active'], 'fecha_desasignacion' => $validatedData['a_fecha_desasignacion'], 'motivo_desasignacion' => $validatedData['a_motivo_desasignacion'], ]); session()->flash('message_asignacion', 'Asignación actualizada correctamente.'); } } else { $dataToSync = [ $validatedData['a_unidad_organizacional_id'] => [ 'fecha_asignacion' => $validatedData['a_fecha_asignacion'], 'is_active' => $validatedData['a_is_active'], 'fecha_desasignacion' => $validatedData['a_fecha_desasignacion'], 'motivo_desasignacion' => $validatedData['a_motivo_desasignacion'], ] ]; $this->vehiculoSeleccionado->unidadesOrganizacionales()->attach($dataToSync); session()->flash('message_asignacion', 'Asignación creada correctamente.'); } $this->cerrarModalAsignacion(); }
    public function cerrarModalAsignacion() { $this->showModalAsignacion = false; $this->resetAsignacionFields(); }
    public function toggleActivoVehiculo(Vehiculo $vehiculo) { if ($vehiculo && $vehiculo->contratista_id == $this->contratistaId) { $vehiculo->is_active = !$vehiculo->is_active; $vehiculo->save(); session()->flash('message', 'Estado del vehículo cambiado.'); } }
    public function eliminarVehiculo($id) { $vehiculo = Vehiculo::where('id', $id)->where('contratista_id', $this->contratistaId)->first(); if ($vehiculo) { $vehiculo->delete(); session()->flash('message', 'Vehículo y sus asignaciones eliminados.'); if ($this->vehiculoSeleccionado && $this->vehiculoSeleccionado->id == $id) { $this->irAListadoVehiculos(); } } }
    public function sortBy($field) { if ($this->sortBy === $field) { $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc'; } else { $this->sortDirection = 'asc'; } $this->sortBy = $field; }

    public function render()
    {
        $vehiculosPaginados = null; $asignacionesPaginadas = null;
        if ($this->vistaActual === 'listado_vehiculos') {
            $query = Vehiculo::query()->where('contratista_id', $this->contratistaId);
            if ($this->unidadOrganizacionalId) { $query->whereHas('unidadesOrganizacionales', fn ($q) => $q->where('unidad_organizacional_mandante_id', $this->unidadOrganizacionalId)); } else { $query->whereRaw('1 = 0'); }
            if (!empty($this->searchVehiculo)) { $query->where(fn ($q) => $q->where(\DB::raw("CONCAT(patente_letras, patente_numeros)"), 'like', '%' . str_replace('-', '', $this->searchVehiculo) . '%')->orWhereHas('marcaVehiculo', fn ($sub) => $sub->where('nombre', 'like', '%' . $this->searchVehiculo . '%'))->orWhereHas('tipoVehiculo', fn ($sub) => $sub->where('nombre', 'like', '%' . $this->searchVehiculo . '%'))); }
            $vehiculosPaginados = $query->with('tipoVehiculo', 'marcaVehiculo')->orderBy($this->sortBy, $this->sortDirection)->paginate(10, ['*'], 'vehiculosPage');
        } elseif ($this->vistaActual === 'listado_asignaciones' && $this->vehiculoSeleccionado) {
            $asignacionesPaginadas = $this->vehiculoSeleccionado->unidadesOrganizacionales()->with('mandante:id,razon_social')->orderBy('pivot_is_active', 'desc')->orderBy('pivot_fecha_asignacion', 'desc')->paginate(10, ['*'], 'asignacionesPage');
        }
        return view('livewire.contratista.gestion-vehiculos', [ 'vehiculosPaginados' => $vehiculosPaginados, 'asignacionesPaginadas' => $asignacionesPaginadas, ]);
    }
}