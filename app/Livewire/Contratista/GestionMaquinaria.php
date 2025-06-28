<?php

namespace App\Livewire\Contratista;

use Livewire\Component;
use App\Models\Maquinaria;
use App\Models\Contratista;
use App\Models\TipoMaquinaria;
use App\Models\MarcaVehiculo;
use App\Models\TenenciaVehiculo;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\MaquinariaAsignacion;
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

class GestionMaquinaria extends Component
{
    use WithPagination;
    use WithFileUploads;

    public ?int $mandanteId = null;
    public ?int $unidadOrganizacionalId = null;
    public $contratistaId;
    public string $vistaActual = 'listado_maquinaria';
    public ?Maquinaria $maquinariaSeleccionada = null;
    public string $nombreVinculacionSeleccionada = '';
    public string $searchMaquinaria = '';
    public string $sortBy = 'maquinarias.id';
    public string $sortDirection = 'asc';
    public bool $showModalFicha = false;
    public ?int $maquinariaId = null;
    public string $identificador_letras = '', $identificador_numeros = '';
    public ?string $ano_fabricacion = null;
    public ?int $marca_vehiculo_id = null, $tipo_maquinaria_id = null, $tenencia_vehiculo_id = null;
    public bool $maquinaria_is_active = true;
    public bool $showModalAsignacion = false;
    public ?int $asignacionId = null;
    public ?int $a_unidad_organizacional_id = null;
    public ?string $a_fecha_asignacion = null;
    public bool $a_is_active = true;
    public ?string $a_fecha_desasignacion = null;
    public ?string $a_motivo_desasignacion = null;
    public $tiposMaquinaria, $marcas, $tenencias;
    public $unidadesOrganizacionalesDisponibles = [];
    public bool $showDocumentosModal = false;
    public ?Maquinaria $maquinariaParaDocumentos = null;
    public string $nombreMaquinariaParaDocumentosModal = '';
    public array $documentosRequeridos = [];
    public array $documentosParaCargar = [];
    public array $uploadErrors = [];
    public array $uploadSuccess = [];

    private DocumentoRequeridoService $documentoService;

    public function boot(DocumentoRequeridoService $documentoService) { $this->documentoService = $documentoService; }
    protected function messages() { return [ '*.required' => 'Este campo es obligatorio.', 'identificador_letras.unique' => 'El código/patente ingresado ya existe para su empresa.', 'a_unidad_organizacional_id.required' => 'Debe seleccionar una Unidad Organizacional.', 'a_fecha_asignacion.required' => 'La fecha de asignación es obligatoria.', 'a_fecha_desasignacion.required_if' => 'La fecha es obligatoria si la asignación no está activa.', 'a_motivo_desasignacion.required_if' => 'El motivo es obligatorio si la asignación no está activa.', ]; }

    public function mount(?int $mandanteId = null, ?int $unidadOrganizacionalId = null)
    {
        $this->mandanteId = $mandanteId;
        $this->unidadOrganizacionalId = $unidadOrganizacionalId;
        $user = Auth::user();
        if (!$user || !$user->contratista_id) { session()->flash('error', 'Usuario no asociado a un contratista válido.'); return; }
        $this->contratistaId = $user->contratista_id;
        if ($this->unidadOrganizacionalId) {
            $uoContexto = UnidadOrganizacionalMandante::with('mandante:id,razon_social')->find($this->unidadOrganizacionalId);
            if ($uoContexto && $uoContexto->mandante) {
                $this->nombreVinculacionSeleccionada = ($uoContexto->mandante->razon_social ?? 'N/A') . ' - ' . $uoContexto->nombre_unidad;
            }
        }
        $this->tiposMaquinaria = TipoMaquinaria::where('is_active', true)->orderBy('nombre')->get();
        $this->marcas = MarcaVehiculo::where('is_active', true)->orderBy('nombre')->get();
        $this->tenencias = TenenciaVehiculo::where('is_active', true)->orderBy('nombre')->get();
    }
    
    public function abrirModalDocumentos($maquinariaId, $mantenerMensajes = false)
    {
        if (!$this->unidadOrganizacionalId || !$this->mandanteId) { session()->flash('error', 'Error de contexto. Por favor, seleccione una vinculación para operar.'); return; }
        
        $this->maquinariaParaDocumentos = Maquinaria::with(['marca', 'tipoMaquinaria'])->find($maquinariaId);
        
        if (!$this->maquinariaParaDocumentos || $this->maquinariaParaDocumentos->contratista_id != $this->contratistaId) { 
            session()->flash('error_modal_documentos', 'Maquinaria no encontrada o no pertenece a su empresa.'); 
            $this->cerrarModalDocumentos(); 
            return; 
        }
        
        $this->nombreMaquinariaParaDocumentosModal = $this->maquinariaParaDocumentos->identificador_completo;
        $this->determinarDocumentosRequeridosParaMaquinaria();
        
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

    private function determinarDocumentosRequeridosParaMaquinaria()
    {
        $reglasCandidatas = $this->documentoService->getReglasParaEntidadEnUO($this->mandanteId, $this->unidadOrganizacionalId, 'MAQUINARIA')
            ->load([
                'nombreDocumento', 'observacionDocumento', 'formatoDocumento', 'documentoRelacionado',
                'tipoVencimiento', 'criterios.criterioEvaluacion', 'criterios.subCriterio',
                'criterios.textoRechazo', 'criterios.aclaracionCriterio', 'tiposMaquinariaAplica:id',
                'tenenciasAplica:id'
            ]);

        $documentosCargadosExistentes = DocumentoCargado::where('entidad_id', $this->maquinariaParaDocumentos->id)
            ->where('entidad_type', Maquinaria::class)->where('archivado', false)
            ->orderBy('created_at', 'desc')->get()->keyBy('regla_documental_id_origen');

        $condicionContratistaEnUO = DB::table('contratista_unidad_organizacional')->where('contratista_id', $this->contratistaId)->where('unidad_organizacional_mandante_id', $this->unidadOrganizacionalId)->value('tipo_condicion_id');
        
        $documentosFinales = []; 
        $idsDocumentosAgregados = [];

        foreach ($reglasCandidatas as $regla) {
            if ($regla->aplica_empresa_condicion_id && $regla->aplica_empresa_condicion_id != $condicionContratistaEnUO) continue;

            $identificadorCompleto = $this->maquinariaParaDocumentos->identificador_letras . $this->maquinariaParaDocumentos->identificador_numeros;
            if (!empty($regla->rut_especificos) && !in_array($identificadorCompleto, array_map('trim', explode(',', $regla->rut_especificos)))) continue;
            if (!empty($regla->rut_excluidos) && in_array($identificadorCompleto, array_map('trim', explode(',', $regla->rut_excluidos)))) continue;

            $idsTiposMaquinariaRegla = $regla->tiposMaquinariaAplica->pluck('id')->toArray();
            if (!empty($idsTiposMaquinariaRegla) && (!$this->maquinariaParaDocumentos->tipo_maquinaria_id || !in_array($this->maquinariaParaDocumentos->tipo_maquinaria_id, $idsTiposMaquinariaRegla))) continue;
            
            $idsTenenciasRegla = $regla->tenenciasAplica->pluck('id')->toArray();
            if (!empty($idsTenenciasRegla) && (!$this->maquinariaParaDocumentos->tenencia_vehiculo_id || !in_array($this->maquinariaParaDocumentos->tenencia_vehiculo_id, $idsTenenciasRegla))) continue;
            
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
        $this->maquinariaParaDocumentos = null;
        $this->nombreMaquinariaParaDocumentosModal = ''; 
        $this->documentosRequeridos = [];
        $this->documentosParaCargar = []; 
        $this->uploadErrors = [];
        $this->uploadSuccess = []; 
        $this->resetValidation();
    }
    
    public function cargarDocumentos() {
        if (!$this->maquinariaParaDocumentos) { session()->flash('error_modal_documentos', 'Error: No se ha seleccionado una maquinaria válida.'); return; }
        
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

                $docPendiente = DocumentoCargado::where('entidad_id', $this->maquinariaParaDocumentos->id)->where('entidad_type', Maquinaria::class)->where('regla_documental_id_origen', $reglaId)->where('estado_validacion', 'Pendiente')->where('archivado', false)->first();
                if ($docPendiente) { Storage::disk('public')->delete($docPendiente->ruta_archivo); $docPendiente->delete(); }
                
                $nombreArchivo = Str::uuid() . '.' . $archivo->getClientOriginalExtension();
                $rutaDirectorio = "documentos/c-{$this->contratistaId}/maquinarias/m-{$this->maquinariaParaDocumentos->id}";
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
                    'contratista_id' => $this->contratistaId, 'mandante_id' => $this->mandanteId, 'unidad_organizacional_id' => $this->unidadOrganizacionalId,
                    'entidad_id' => $this->maquinariaParaDocumentos->id, 'entidad_type' => Maquinaria::class, 'regla_documental_id_origen' => $reglaId,
                    'usuario_carga_id' => $usuarioCargaId, 'ruta_archivo' => $rutaArchivo, 'nombre_original_archivo' => $archivo->getClientOriginalName(),
                    'mime_type' => $archivo->getMimeType(), 'tamano_archivo' => $archivo->getSize(), 'fecha_emision' => $data['fecha_emision_input'] ?? null,
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
                Log::error("Error al cargar doc para maquinaria {$this->maquinariaParaDocumentos->id}, Regla {$reglaId}: " . $e->getMessage()); 
                $this->addError('documentosParaCargar.' . $reglaId . '.archivo_input', 'Error inesperado.'); 
            }
        }
        if (!$huboArchivosParaProcesar) { 
            session()->flash('info_modal_documentos', 'No se seleccionó ningún archivo nuevo para cargar.');
        } else { 
            session()->flash('message_modal_documentos', 'Proceso de carga finalizado. Revise los estados individuales.'); 
        }
        $this->abrirModalDocumentos($this->maquinariaParaDocumentos->id, true);
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
        $this->abrirModalDocumentos($this->maquinariaParaDocumentos->id, true);
    }

    public function seleccionarMaquinariaParaAsignaciones($maquinariaId) { $this->maquinariaSeleccionada = Maquinaria::find($maquinariaId); if ($this->maquinariaSeleccionada && $this->maquinariaSeleccionada->contratista_id == $this->contratistaId) { $this->vistaActual = 'listado_asignaciones'; $this->resetPage('asignacionesPage'); } else { session()->flash('error', 'Maquinaria no encontrada o no pertenece a su empresa.'); $this->maquinariaSeleccionada = null; } }
    
    public function irAListadoMaquinaria() { $this->vistaActual = 'listado_maquinaria'; $this->maquinariaSeleccionada = null; $this->resetPage('maquinariaPage'); }
    
    public function rulesFichaMaquinaria() { return [ 'identificador_letras' => ['required', 'string', 'min:1', 'max:20', Rule::unique('maquinarias')->where(fn($query) => $query->where('contratista_id', $this->contratistaId)->where('identificador_numeros', $this->identificador_numeros))->ignore($this->maquinariaId), ], 'identificador_numeros' => 'required|string|min:1|max:20', 'ano_fabricacion' => 'required|integer|digits:4|min:1950|max:' . (date('Y') + 1), 'marca_vehiculo_id' => 'required|exists:marcas_vehiculo,id', 'tipo_maquinaria_id' => 'required|exists:tipos_maquinaria,id', 'tenencia_vehiculo_id' => 'nullable|exists:tenencias_vehiculo,id', 'maquinaria_is_active' => 'boolean', ]; }
    
    public function guardarMaquinaria() { $validatedData = $this->validate($this->rulesFichaMaquinaria()); $validatedData['contratista_id'] = $this->contratistaId; $validatedData['identificador_letras'] = strtoupper($this->identificador_letras); $validatedData['identificador_numeros'] = strtoupper($this->identificador_numeros); try { if ($this->maquinariaId) { $maquinaria = Maquinaria::find($this->maquinariaId); if ($maquinaria && $maquinaria->contratista_id == $this->contratistaId) { $maquinaria->update($validatedData); session()->flash('message', 'Ficha de la maquinaria actualizada correctamente.'); } } else { $maquinaria = Maquinaria::create($validatedData); if ($this->unidadOrganizacionalId) { $maquinaria->unidadesOrganizacionales()->attach($this->unidadOrganizacionalId, ['fecha_asignacion' => now(), 'is_active' => true,]); session()->flash('message', 'Maquinaria agregada y asignada a esta Unidad Organizacional.'); } else { session()->flash('message', 'Maquinaria agregada correctamente.'); } } $this->cerrarModalFicha(); } catch (\Exception $e) { Log::error("Error al guardar maquinaria: " . $e->getMessage()); session()->flash('error', 'Ocurrió un error al guardar. Verifique que el código/patente no esté duplicado.'); } }
    
    private function resetFichaMaquinariaFields() { $this->maquinariaId = null; $this->identificador_letras = ''; $this->identificador_numeros = ''; $this->ano_fabricacion = null; $this->marca_vehiculo_id = null; $this->tipo_maquinaria_id = null; $this->tenencia_vehiculo_id = null; $this->maquinaria_is_active = true; $this->resetValidation(); }
    
    public function abrirModalNuevaMaquinaria() { $this->resetFichaMaquinariaFields(); $this->showModalFicha = true; }
    
    public function abrirModalEditarMaquinaria($id) { $maquinaria = Maquinaria::find($id); if ($maquinaria && $maquinaria->contratista_id == $this->contratistaId) { $this->maquinariaId = $maquinaria->id; $this->identificador_letras = $maquinaria->identificador_letras; $this->identificador_numeros = $maquinaria->identificador_numeros; $this->ano_fabricacion = $maquinaria->ano_fabricacion; $this->marca_vehiculo_id = $maquinaria->marca_vehiculo_id; $this->tipo_maquinaria_id = $maquinaria->tipo_maquinaria_id; $this->tenencia_vehiculo_id = $maquinaria->tenencia_vehiculo_id; $this->maquinaria_is_active = $maquinaria->is_active; if ($this->vistaActual == 'listado_asignaciones') $this->maquinariaSeleccionada = $maquinaria; $this->showModalFicha = true; } }
    
    public function cerrarModalFicha() { $this->showModalFicha = false; $this->resetFichaMaquinariaFields(); }
    
    public function rulesAsignacion() { return [ 'a_unidad_organizacional_id' => ['required', 'exists:unidades_organizacionales_mandante,id', function ($attribute, $value, $fail) { if ($this->a_is_active) { $query = MaquinariaAsignacion::where('maquinaria_id', $this->maquinariaSeleccionada->id)->where('unidad_organizacional_mandante_id', $value)->where('is_active', true); if ($this->asignacionId) { $query->where('id', '!=', $this->asignacionId); } if ($query->exists()) { $fail('La maquinaria ya tiene una asignación activa en esta UO.'); } } } ], 'a_fecha_asignacion' => 'required|date', 'a_is_active' => 'required|boolean', 'a_fecha_desasignacion' => 'nullable|required_if:a_is_active,false|date|after_or_equal:a_fecha_asignacion', 'a_motivo_desasignacion' => 'nullable|required_if:a_is_active,false|string|max:500', ]; }
    
    private function resetAsignacionFields() { $this->asignacionId = null; $this->a_unidad_organizacional_id = null; $this->a_fecha_asignacion = null; $this->a_is_active = true; $this->a_fecha_desasignacion = null; $this->a_motivo_desasignacion = null; $this->resetValidation(); }
    
    public function abrirModalNuevaAsignacion() { $this->unidadesOrganizacionalesDisponibles = Contratista::find($this->contratistaId)->unidadesOrganizacionalesMandante()->with('mandante:id,razon_social')->orderBy('mandante_id')->get(); $this->resetAsignacionFields(); $this->a_fecha_asignacion = now()->format('Y-m-d'); $this->showModalAsignacion = true; }
    
    public function abrirModalEditarAsignacion($asignacionId) { $asignacion = MaquinariaAsignacion::find($asignacionId); if ($asignacion && $asignacion->maquinaria_id == $this->maquinariaSeleccionada?->id) { $this->unidadesOrganizacionalesDisponibles = Contratista::find($this->contratistaId)->unidadesOrganizacionalesMandante()->with('mandante:id,razon_social')->orderBy('mandante_id')->get(); $this->asignacionId = $asignacion->id; $this->a_unidad_organizacional_id = $asignacion->unidad_organizacional_mandante_id; $this->a_fecha_asignacion = \Carbon\Carbon::parse($asignacion->fecha_asignacion)->format('Y-m-d'); $this->a_is_active = $asignacion->is_active; $this->a_fecha_desasignacion = $asignacion->fecha_desasignacion ? \Carbon\Carbon::parse($asignacion->fecha_desasignacion)->format('Y-m-d') : null; $this->a_motivo_desasignacion = $asignacion->motivo_desasignacion; $this->showModalAsignacion = true; } else { session()->flash('error', 'No se pudo encontrar la asignación a editar.'); } }
    
    public function guardarAsignacion() { $validatedData = $this->validate($this->rulesAsignacion()); if ($validatedData['a_is_active']) { $validatedData['a_fecha_desasignacion'] = null; $validatedData['a_motivo_desasignacion'] = null; } if ($this->asignacionId) { $asignacion = MaquinariaAsignacion::find($this->asignacionId); if ($asignacion) { $asignacion->update(['unidad_organizacional_mandante_id' => $validatedData['a_unidad_organizacional_id'], 'fecha_asignacion' => $validatedData['a_fecha_asignacion'], 'is_active' => $validatedData['a_is_active'], 'fecha_desasignacion' => $validatedData['a_fecha_desasignacion'], 'motivo_desasignacion' => $validatedData['a_motivo_desasignacion'],]); session()->flash('message_asignacion', 'Asignación actualizada correctamente.'); } } else { $dataToAttach = [$validatedData['a_unidad_organizacional_id'] => ['fecha_asignacion' => $validatedData['a_fecha_asignacion'], 'is_active' => $validatedData['a_is_active'], 'fecha_desasignacion' => $validatedData['a_fecha_desasignacion'], 'motivo_desasignacion' => $validatedData['a_motivo_desasignacion'],]]; $this->maquinariaSeleccionada->unidadesOrganizacionales()->attach($dataToAttach); session()->flash('message_asignacion', 'Asignación creada correctamente.'); } $this->cerrarModalAsignacion(); }
    
    public function cerrarModalAsignacion() { $this->showModalAsignacion = false; $this->resetAsignacionFields(); }
    
    public function toggleActivoMaquinaria(Maquinaria $maquinaria) { if ($maquinaria && $maquinaria->contratista_id == $this->contratistaId) { $maquinaria->is_active = !$maquinaria->is_active; $maquinaria->save(); session()->flash('message', 'Estado de la maquinaria cambiado.'); } }
    
    public function eliminarMaquinaria($id) { $maquinaria = Maquinaria::where('id', $id)->where('contratista_id', $this->contratistaId)->first(); if ($maquinaria) { $maquinaria->delete(); session()->flash('message', 'Maquinaria y sus asignaciones eliminadas.'); if($this->maquinariaSeleccionada && $this->maquinariaSeleccionada->id == $id) { $this->irAListadoMaquinaria(); } } }
    
    public function sortBy($field) { if ($this->sortBy === $field) { $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc'; } else { $this->sortDirection = 'asc'; } $this->sortBy = $field; }
    
    public function render() { $maquinariasPaginadas = null; $asignacionesPaginadas = null; if ($this->vistaActual === 'listado_maquinaria') { $query = Maquinaria::query()->where('contratista_id', $this->contratistaId); if ($this->unidadOrganizacionalId) { $query->whereHas('unidadesOrganizacionales', fn($q) => $q->where('unidad_organizacional_mandante_id', $this->unidadOrganizacionalId)); } else { $query->whereRaw('1 = 0'); } if (!empty($this->searchMaquinaria)) { $query->where(fn($q) => $q->where( \DB::raw("CONCAT(identificador_letras, identificador_numeros)"), 'like', '%' . str_replace('-', '', $this->searchMaquinaria) . '%')->orWhereHas('marca', fn($sub) => $sub->where('nombre', 'like', '%'.$this->searchMaquinaria.'%'))->orWhereHas('tipoMaquinaria', fn($sub) => $sub->where('nombre', 'like', '%'.$this->searchMaquinaria.'%'))); } $maquinariasPaginadas = $query->with('tipoMaquinaria', 'marca')->orderBy($this->sortBy, $this->sortDirection)->paginate(10, ['*'], 'maquinariaPage'); } elseif ($this->vistaActual === 'listado_asignaciones' && $this->maquinariaSeleccionada) { $asignacionesPaginadas = $this->maquinariaSeleccionada->unidadesOrganizacionales()->with('mandante:id,razon_social')->orderBy('pivot_is_active', 'desc')->orderBy('pivot_fecha_asignacion', 'desc')->paginate(10, ['*'], 'asignacionesPage'); } return view('livewire.contratista.gestion-maquinaria', [ 'maquinariasPaginadas' => $maquinariasPaginadas, 'asignacionesPaginadas' => $asignacionesPaginadas, ]); }
}