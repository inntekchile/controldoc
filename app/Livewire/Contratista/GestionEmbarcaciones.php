<?php

namespace App\Livewire\Contratista;

use Livewire\Component;
use App\Models\Embarcacion;
use App\Models\Contratista;
use App\Models\TipoEmbarcacion;
use App\Models\TenenciaVehiculo;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\EmbarcacionAsignacion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;
use App\Services\DocumentoRequeridoService;
use App\Models\ReglaDocumental;
use Illuminate\Support\Facades\DB;
use Livewire\WithFileUploads;
use App\Models\DocumentoCargado;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon; // <--- IMPORTACIÓN AÑADIDA

class GestionEmbarcaciones extends Component
{
    use WithPagination;
    use WithFileUploads;

    public ?int $mandanteId = null;
    public ?int $unidadOrganizacionalId = null;
    public $contratistaId;
    public string $vistaActual = 'listado_embarcaciones';
    public ?Embarcacion $embarcacionSeleccionada = null;
    public string $nombreVinculacionSeleccionada = '';
    public string $searchEmbarcacion = '';
    public string $sortBy = 'embarcaciones.id';
    public string $sortDirection = 'asc';
    public bool $showModalFicha = false;
    public ?int $embarcacionId = null;
    public string $matricula_letras = '', $matricula_numeros = '';
    public ?string $ano_fabricacion = null;
    public ?int $tipo_embarcacion_id = null, $tenencia_vehiculo_id = null;
    public bool $embarcacion_is_active = true;
    public bool $showModalAsignacion = false;
    public ?int $asignacionId = null;
    public ?int $a_unidad_organizacional_id = null;
    public ?string $a_fecha_asignacion = null;
    public bool $a_is_active = true;
    public ?string $a_fecha_desasignacion = null;
    public ?string $a_motivo_desasignacion = null;
    public $tiposEmbarcacion, $tenencias;
    public $unidadesOrganizacionalesDisponibles = [];
    public bool $showDocumentosModal = false;
    public ?Embarcacion $embarcacionParaDocumentos = null;
    public string $nombreEmbarcacionParaDocumentosModal = '';
    public array $documentosRequeridos = [];
    public array $documentosParaCargar = [];
    public array $uploadErrors = [];
    public array $uploadSuccess = [];
    
    private DocumentoRequeridoService $documentoService;

    public function boot(DocumentoRequeridoService $documentoService) { $this->documentoService = $documentoService; }
    protected function messages() { return [ '*.required' => 'Este campo es obligatorio.', 'matricula_letras.unique' => 'La matrícula ingresada ya existe para su empresa.', 'a_unidad_organizacional_id.required' => 'Debe seleccionar una Unidad Organizacional.', 'a_fecha_asignacion.required' => 'La fecha de asignación es obligatoria.', ]; }
    
    public function mount(?int $mandanteId = null, ?int $unidadOrganizacionalId = null)
    {
        $this->mandanteId = $mandanteId;
        $this->unidadOrganizacionalId = $unidadOrganizacionalId;
        $user = Auth::user();
        if (!$user || !$user->contratista_id) { session()->flash('error', 'Usuario no asociado a un contratista válido.'); return; }
        $this->contratistaId = $user->contratista_id;
        if ($this->unidadOrganizacionalId) {
            $uoContexto = UnidadOrganizacionalMandante::with('mandante:id,razon_social')->find($this->unidadOrganizacionalId);
            if ($uoContexto && $uoContexto->mandante) { $this->nombreVinculacionSeleccionada = ($uoContexto->mandante->razon_social ?? 'N/A') . ' - ' . $uoContexto->nombre_unidad; }
        }
        $this->tiposEmbarcacion = TipoEmbarcacion::where('is_active', true)->orderBy('nombre')->get();
        $this->tenencias = TenenciaVehiculo::where('is_active', true)->orderBy('nombre')->get();
    }
    
    public function abrirModalDocumentos($embarcacionId, $mantenerMensajes = false)
    {
        if (!$this->unidadOrganizacionalId || !$this->mandanteId) { session()->flash('error', 'Error de contexto.'); return; }
        
        $this->embarcacionParaDocumentos = Embarcacion::with(['tipoEmbarcacion'])->find($embarcacionId);
        
        if (!$this->embarcacionParaDocumentos || $this->embarcacionParaDocumentos->contratista_id != $this->contratistaId) { 
            session()->flash('error_modal_documentos', 'Embarcación no encontrada.'); 
            $this->cerrarModalDocumentos(); 
            return; 
        }

        $this->nombreEmbarcacionParaDocumentosModal = $this->embarcacionParaDocumentos->matricula_completa;
        $this->determinarDocumentosRequeridosParaEmbarcacion();
        
        if (!$mantenerMensajes) { 
            $this->uploadErrors = []; 
            $this->uploadSuccess = []; 
        }

        $tempDocumentosParaCargar = [];
        foreach ($this->documentosRequeridos as $doc) {
            $reglaId = $doc['regla_documental_id_origen'];
            $tempDocumentosParaCargar[$reglaId] = [
                'archivo_input' => null, 
                'fecha_emision_input' => null, 
                'fecha_vencimiento_input' => null, 
                'periodo_input' => null,
                'regla_info' => $doc,
            ];
        }
        $this->documentosParaCargar = $tempDocumentosParaCargar;
        $this->resetErrorBag();
        $this->showDocumentosModal = true;
    }

    private function determinarDocumentosRequeridosParaEmbarcacion()
    {
        $reglasCandidatas = $this->documentoService->getReglasParaEntidadEnUO($this->mandanteId, $this->unidadOrganizacionalId, 'EMBARCACION')
            ->load([
                'nombreDocumento', 'observacionDocumento', 'formatoDocumento', 'documentoRelacionado',
                'tipoVencimiento', 'criterios.criterioEvaluacion', 'criterios.subCriterio',
                'criterios.textoRechazo', 'criterios.aclaracionCriterio', 'tiposEmbarcacionAplica:id',
                'tenenciasAplica:id'
            ]);
        
        $documentosCargadosExistentes = DocumentoCargado::where('entidad_id', $this->embarcacionParaDocumentos->id)
            ->where('entidad_type', Embarcacion::class)->where('archivado', false)
            ->orderBy('created_at', 'desc')->get()->keyBy('regla_documental_id_origen');

        $condicionContratistaEnUO = DB::table('contratista_unidad_organizacional')->where('contratista_id', $this->contratistaId)->where('unidad_organizacional_mandante_id', $this->unidadOrganizacionalId)->value('tipo_condicion_id');
        
        $documentosFinales = []; 
        $idsDocumentosAgregados = [];

        foreach ($reglasCandidatas as $regla) {
            if ($regla->aplica_empresa_condicion_id && $regla->aplica_empresa_condicion_id != $condicionContratistaEnUO) continue;
            
            $identificadorCompleto = $this->embarcacionParaDocumentos->matricula_letras . $this->embarcacionParaDocumentos->matricula_numeros;
            if (!empty($regla->rut_especificos) && !in_array($identificadorCompleto, array_map('trim', explode(',', $regla->rut_especificos)))) continue;
            if (!empty($regla->rut_excluidos) && in_array($identificadorCompleto, array_map('trim', explode(',', $regla->rut_excluidos)))) continue;
            
            $idsTiposEmbarcacionRegla = $regla->tiposEmbarcacionAplica->pluck('id')->toArray();
            if (!empty($idsTiposEmbarcacionRegla) && (!$this->embarcacionParaDocumentos->tipo_embarcacion_id || !in_array($this->embarcacionParaDocumentos->tipo_embarcacion_id, $idsTiposEmbarcacionRegla))) continue;
            
            $idsTenenciasRegla = $regla->tenenciasAplica->pluck('id')->toArray();
            if (!empty($idsTenenciasRegla) && (!$this->embarcacionParaDocumentos->tenencia_vehiculo_id || !in_array($this->embarcacionParaDocumentos->tenencia_vehiculo_id, $idsTenenciasRegla))) continue;
            
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
                    'criterios_evaluacion' => $regla->criterios->map(fn($c) => ['criterio' => $c->criterioEvaluacion?->nombre_criterio, 'sub_criterio' => $c->subCriterio?->nombre, 'texto_rechazo' => $c->textoRechazo?->titulo, 'aclaracion' => $c->aclaracionCriterio?->titulo,])->all(),
                    'afecta_cumplimiento' => (bool) $regla->afecta_porcentaje_cumplimiento,
                    'restringe_acceso' => (bool) $regla->restringe_acceso,
                ];
                $idsDocumentosAgregados[] = $regla->nombre_documento_id;
            }
        }
        $this->documentosRequeridos = $documentosFinales;
    }

    public function cerrarModalDocumentos() {
        $this->showDocumentosModal = false; 
        $this->embarcacionParaDocumentos = null;
        $this->nombreEmbarcacionParaDocumentosModal = ''; 
        $this->documentosRequeridos = [];
        $this->documentosParaCargar = []; 
        $this->uploadErrors = [];
        $this->uploadSuccess = []; 
        $this->resetValidation();
    }

    public function cargarDocumentos() {
        if (!$this->embarcacionParaDocumentos) { session()->flash('error_modal_documentos', 'Error: No se ha seleccionado una embarcación válida.'); return; }
        
        $this->uploadErrors = []; 
        $this->uploadSuccess = []; 
        $this->resetErrorBag();
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
                
                $docPendiente = DocumentoCargado::where('entidad_id', $this->embarcacionParaDocumentos->id)->where('entidad_type', Embarcacion::class)->where('regla_documental_id_origen', $reglaId)->where('estado_validacion', 'Pendiente')->where('archivado', false)->first();
                if ($docPendiente) { Storage::disk('public')->delete($docPendiente->ruta_archivo); $docPendiente->delete(); }
                
                $nombreArchivo = Str::uuid() . '.' . $archivo->getClientOriginalExtension();
                $rutaDirectorio = "documentos/c-{$this->contratistaId}/embarcaciones/e-{$this->embarcacionParaDocumentos->id}";
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
                    'entidad_id' => $this->embarcacionParaDocumentos->id, 
                    'entidad_type' => Embarcacion::class, // <-- CORREGIDO PARA EMBARCACIÓN
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
                Log::error("Error al cargar doc para embarcación {$this->embarcacionParaDocumentos->id}, Regla {$reglaId}: " . $e->getMessage()); 
                $this->addError('documentosParaCargar.' . $reglaId . '.archivo_input', 'Error inesperado.'); 
            }
        }
        if (!$huboArchivosParaProcesar) { 
            session()->flash('info_modal_documentos', 'No se seleccionó ningún archivo nuevo para cargar.');
        } else { 
            session()->flash('message_modal_documentos', 'Proceso de carga finalizado. Revise los estados individuales.'); 
        }
        $this->abrirModalDocumentos($this->embarcacionParaDocumentos->id, true);
    }
    
    public function eliminarDocumentoCargado($documentoCargadoId) {
        $doc = DocumentoCargado::find($documentoCargadoId);
        if ($doc && $doc->contratista_id == $this->contratistaId && $doc->estado_validacion === 'Pendiente') {
            try { 
                Storage::disk('public')->delete($doc->ruta_archivo); 
                $doc->delete(); 
                session()->flash('message_modal_documentos', 'Documento pendiente eliminado.');
            } catch (\Exception $e) { 
                Log::error("Error al eliminar doc cargado ID {$documentoCargadoId}: " . $e->getMessage()); 
                session()->flash('error_modal_documentos', 'Error al eliminar el documento.'); 
            }
        } else { 
            session()->flash('error_modal_documentos', 'No se puede eliminar el documento.'); 
        }
        $this->abrirModalDocumentos($this->embarcacionParaDocumentos->id, true);
    }
    
    public function seleccionarEmbarcacionParaAsignaciones($embarcacionId) { $this->embarcacionSeleccionada = Embarcacion::find($embarcacionId); if ($this->embarcacionSeleccionada && $this->embarcacionSeleccionada->contratista_id == $this->contratistaId) { $this->vistaActual = 'listado_asignaciones'; $this->resetPage('asignacionesPage'); } else { session()->flash('error', 'Embarcación no encontrada.'); $this->embarcacionSeleccionada = null; } }
    
    public function irAListadoEmbarcaciones() { $this->vistaActual = 'listado_embarcaciones'; $this->embarcacionSeleccionada = null; $this->resetPage('embarcacionesPage'); }
    
    public function rulesFichaEmbarcacion() { return [ 'matricula_letras' => ['required', 'string', 'min:2', 'max:10', Rule::unique('embarcaciones')->where(fn($query) => $query->where('contratista_id', $this->contratistaId)->where('matricula_numeros', $this->matricula_numeros))->ignore($this->embarcacionId), ], 'matricula_numeros' => 'required|string|min:1|max:10', 'ano_fabricacion' => 'required|integer|digits:4|min:1950|max:' . (date('Y') + 1), 'tipo_embarcacion_id' => 'required|exists:tipos_embarcacion,id', 'tenencia_vehiculo_id' => 'nullable|exists:tenencias_vehiculo,id', 'embarcacion_is_active' => 'boolean', ]; }
    
    public function guardarEmbarcacion() { $validatedData = $this->validate($this->rulesFichaEmbarcacion()); $validatedData['contratista_id'] = $this->contratistaId; $validatedData['matricula_letras'] = strtoupper($this->matricula_letras); $validatedData['matricula_numeros'] = strtoupper($this->matricula_numeros); try { if ($this->embarcacionId) { $embarcacion = Embarcacion::find($this->embarcacionId); if ($embarcacion && $embarcacion->contratista_id == $this->contratistaId) { $embarcacion->update($validatedData); session()->flash('message', 'Ficha de la embarcación actualizada.'); } } else { $embarcacion = Embarcacion::create($validatedData); if ($this->unidadOrganizacionalId) { $embarcacion->unidadesOrganizacionales()->attach($this->unidadOrganizacionalId, ['fecha_asignacion' => now(), 'is_active' => true,]); session()->flash('message', 'Embarcación agregada y asignada.'); } else { session()->flash('message', 'Embarcación agregada.'); } } $this->cerrarModalFicha(); } catch (\Exception $e) { Log::error("Error al guardar embarcación: " . $e->getMessage()); session()->flash('error', 'Error al guardar. Verifique matrícula.'); } }
    
    private function resetFichaEmbarcacionFields() { $this->embarcacionId = null; $this->matricula_letras = ''; $this->matricula_numeros = ''; $this->ano_fabricacion = null; $this->tipo_embarcacion_id = null; $this->tenencia_vehiculo_id = null; $this->embarcacion_is_active = true; $this->resetValidation(); }
    
    public function abrirModalNuevaEmbarcacion() { $this->resetFichaEmbarcacionFields(); $this->showModalFicha = true; }
    
    public function abrirModalEditarEmbarcacion($id) { $embarcacion = Embarcacion::find($id); if ($embarcacion && $embarcacion->contratista_id == $this->contratistaId) { $this->embarcacionId = $embarcacion->id; $this->matricula_letras = $embarcacion->matricula_letras; $this->matricula_numeros = $embarcacion->matricula_numeros; $this->ano_fabricacion = $embarcacion->ano_fabricacion; $this->tipo_embarcacion_id = $embarcacion->tipo_embarcacion_id; $this->tenencia_vehiculo_id = $embarcacion->tenencia_vehiculo_id; $this->embarcacion_is_active = $embarcacion->is_active; if ($this->vistaActual == 'listado_asignaciones') $this->embarcacionSeleccionada = $embarcacion; $this->showModalFicha = true; } }
    
    public function cerrarModalFicha() { $this->showModalFicha = false; $this->resetFichaEmbarcacionFields(); }
    
    public function rulesAsignacion() { return [ 'a_unidad_organizacional_id' => ['required', 'exists:unidades_organizacionales_mandante,id', function ($attribute, $value, $fail) { if ($this->a_is_active) { $query = EmbarcacionAsignacion::where('embarcacion_id', $this->embarcacionSeleccionada->id)->where('unidad_organizacional_mandante_id', $value)->where('is_active', true); if ($this->asignacionId) { $query->where('id', '!=', $this->asignacionId); } if ($query->exists()) { $fail('La embarcación ya tiene una asignación activa en esta UO.'); } } } ], 'a_fecha_asignacion' => 'required|date', 'a_is_active' => 'required|boolean', 'a_fecha_desasignacion' => 'nullable|required_if:a_is_active,false|date|after_or_equal:a_fecha_asignacion', 'a_motivo_desasignacion' => 'nullable|required_if:a_is_active,false|string|max:500', ]; }
    
    private function resetAsignacionFields() { $this->asignacionId = null; $this->a_unidad_organizacional_id = null; $this->a_fecha_asignacion = null; $this->a_is_active = true; $this->a_fecha_desasignacion = null; $this->a_motivo_desasignacion = null; $this->resetValidation(); }
    
    public function abrirModalNuevaAsignacion() { $this->unidadesOrganizacionalesDisponibles = Contratista::find($this->contratistaId)->unidadesOrganizacionalesMandante()->with('mandante:id,razon_social')->orderBy('mandante_id')->get(); $this->resetAsignacionFields(); $this->a_fecha_asignacion = now()->format('Y-m-d'); $this->showModalAsignacion = true; }
    
    public function abrirModalEditarAsignacion($asignacionId) { $asignacion = EmbarcacionAsignacion::find($asignacionId); if ($asignacion && $asignacion->embarcacion_id == $this->embarcacionSeleccionada?->id) { $this->unidadesOrganizacionalesDisponibles = Contratista::find($this->contratistaId)->unidadesOrganizacionalesMandante()->with('mandante:id,razon_social')->orderBy('mandante_id')->get(); $this->asignacionId = $asignacion->id; $this->a_unidad_organizacional_id = $asignacion->unidad_organizacional_mandante_id; $this->a_fecha_asignacion = \Carbon\Carbon::parse($asignacion->fecha_asignacion)->format('Y-m-d'); $this->a_is_active = $asignacion->is_active; $this->a_fecha_desasignacion = $asignacion->fecha_desasignacion ? \Carbon\Carbon::parse($asignacion->fecha_desasignacion)->format('Y-m-d') : null; $this->a_motivo_desasignacion = $asignacion->motivo_desasignacion; $this->showModalAsignacion = true; } else { session()->flash('error_asignacion', 'No se pudo encontrar la asignación a editar.'); } }
    
    public function guardarAsignacion() { $validatedData = $this->validate($this->rulesAsignacion()); if ($validatedData['a_is_active']) { $validatedData['a_fecha_desasignacion'] = null; $validatedData['a_motivo_desasignacion'] = null; } if ($this->asignacionId) { $asignacion = EmbarcacionAsignacion::find($this->asignacionId); if ($asignacion) { $asignacion->update(['unidad_organizacional_mandante_id' => $validatedData['a_unidad_organizacional_id'],'fecha_asignacion' => $validatedData['a_fecha_asignacion'],'is_active' => $validatedData['a_is_active'],'fecha_desasignacion' => $validatedData['a_fecha_desasignacion'],'motivo_desasignacion' => $validatedData['a_motivo_desasignacion'],]); session()->flash('message_asignacion', 'Asignación actualizada.'); } } else { $dataToAttach = [$validatedData['a_unidad_organizacional_id'] => ['fecha_asignacion' => $validatedData['a_fecha_asignacion'],'is_active' => $validatedData['a_is_active'],'fecha_desasignacion' => $validatedData['a_fecha_desasignacion'],'motivo_desasignacion' => $validatedData['a_motivo_desasignacion'],]]; $this->embarcacionSeleccionada->unidadesOrganizacionales()->attach($dataToAttach); session()->flash('message_asignacion', 'Asignación creada.'); } $this->cerrarModalAsignacion(); }
    
    public function cerrarModalAsignacion() { $this->showModalAsignacion = false; $this->resetAsignacionFields(); }
    
    public function toggleActivoEmbarcacion(Embarcacion $embarcacion) { if ($embarcacion && $embarcacion->contratista_id == $this->contratistaId) { $embarcacion->is_active = !$embarcacion->is_active; $embarcacion->save(); session()->flash('message', 'Estado de la embarcación cambiado.'); } }
    
    public function eliminarEmbarcacion($id) { $embarcacion = Embarcacion::where('id', $id)->where('contratista_id', $this->contratistaId)->first(); if ($embarcacion) { $embarcacion->delete(); session()->flash('message', 'Embarcación y sus asignaciones eliminadas.'); if($this->embarcacionSeleccionada && $this->embarcacionSeleccionada->id == $id) { $this->irAListadoEmbarcaciones(); } } }
    
    public function sortBy($field) { if ($this->sortBy === $field) { $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc'; } else { $this->sortDirection = 'asc'; } $this->sortBy = $field; }
    
    public function render() { $embarcacionesPaginadas = null; $asignacionesPaginadas = null; if ($this->vistaActual === 'listado_embarcaciones') { $query = Embarcacion::query()->where('contratista_id', $this->contratistaId); if ($this->unidadOrganizacionalId) { $query->whereHas('unidadesOrganizacionales', fn($q) => $q->where('unidad_organizacional_mandante_id', $this->unidadOrganizacionalId)); } else { $query->whereRaw('1 = 0'); } if (!empty($this->searchEmbarcacion)) { $query->where(fn($q) => $q->where( \DB::raw("CONCAT(matricula_letras, matricula_numeros)"), 'like', '%' . str_replace('-', '', $this->searchEmbarcacion) . '%')->orWhereHas('tipoEmbarcacion', fn($sub) => $sub->where('nombre', 'like', '%'.$this->searchEmbarcacion.'%'))); } $embarcacionesPaginadas = $query->with('tipoEmbarcacion')->orderBy($this->sortBy, $this->sortDirection)->paginate(10, ['*'], 'embarcacionesPage'); } elseif ($this->vistaActual === 'listado_asignaciones' && $this->embarcacionSeleccionada) { $asignacionesPaginadas = $this->embarcacionSeleccionada->unidadesOrganizacionales()->with('mandante:id,razon_social')->orderBy('pivot_is_active', 'desc')->orderBy('pivot_fecha_asignacion', 'desc')->paginate(10, ['*'], 'asignacionesPage'); } return view('livewire.contratista.gestion-embarcaciones', [ 'embarcacionesPaginadas' => $embarcacionesPaginadas, 'asignacionesPaginadas' => $asignacionesPaginadas, ]); }
}