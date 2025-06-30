<?php

namespace App\Livewire\Contratista;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Contratista;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\Log;
use App\Services\DocumentoRequeridoService;
use Illuminate\Support\Facades\DB;
use Livewire\WithFileUploads;
use App\Models\DocumentoCargado;
use App\Models\ReglaDocumental;
use App\Models\ObservacionDocumento;
use App\Models\FormatoDocumentoMuestra;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PanelOperacion extends Component
{
    use WithFileUploads;

    public $vinculacionesDisponibles;
    
    #[Url]
    public $vinculacionSeleccionada = null;

    public $mandanteContextoId = null;
    public $unidadOrganizacionalContextoId = null;
    public $nombreMandanteContexto = '';
    public $nombreUnidadContexto = '';
    public $tiposEntidadPermitidosContextoActual = [];

    #[Url(as: 'pestaña')]
    public $pestañaActiva = 'mi_ficha';
    
    public array $documentosRequeridosEmpresa = [];
    private ?DocumentoRequeridoService $documentoService;

    public array $documentosParaCargar = [];
    public array $uploadErrors = [];
    public array $uploadSuccess = [];

    public function boot(?DocumentoRequeridoService $documentoService = null)
    {
        $this->documentoService = $documentoService;
    }

    public function mount()
    {
        $this->cargarVinculaciones();
        if ($this->vinculacionSeleccionada && $this->vinculacionesDisponibles && $this->vinculacionesDisponibles->isNotEmpty()) {
            $this->updatedVinculacionSeleccionada($this->vinculacionSeleccionada);
        } else {
             $this->pestañaActiva = 'mi_ficha';
        }
    }

    public function cargarVinculaciones()
    {
        $user = Auth::user();
        $contratista = $user->contratista;
        if (!$contratista) return;

        $unidadesAsignadas = $contratista->unidadesOrganizacionalesMandante()
            ->with(['mandante.tiposEntidadControlable', 'mandante:id,razon_social'])
            ->get();
        
        $vinculacionesFormateadas = collect();
        if ($unidadesAsignadas->isNotEmpty()) {
            foreach ($unidadesAsignadas as $unidadOrg) {
                if ($mandante = $unidadOrg->mandante) {
                    $vinculacionesFormateadas->push([
                        'id_seleccion' => $unidadOrg->id,
                        'texto_visible' => $mandante->razon_social . ' - ' . $unidadOrg->nombre_unidad,
                        'mandante_id' => $mandante->id,
                        'unidad_organizacional_mandante_id' => $unidadOrg->id,
                        'mandante_razon_social' => $mandante->razon_social,
                        'unidad_organizacional_nombre' => $unidadOrg->nombre_unidad,
                        'tipos_entidad_permitidos' => $mandante->tiposEntidadControlable
                            ->pluck('nombre_entidad')->map(fn($nombre) => strtoupper($nombre))
                            ->unique()->values()->toArray()
                    ]);
                }
            }
        }
        $this->vinculacionesDisponibles = $vinculacionesFormateadas->sortBy('texto_visible')->values();
    }

    public function updatedVinculacionSeleccionada($value)
    {
        $this->documentosRequeridosEmpresa = [];
        if (empty($value)) {
            $this->resetContexto();
            return;
        }

        if (is_null($this->vinculacionesDisponibles)) {
            $this->cargarVinculaciones();
        }

        $vinculacion = collect($this->vinculacionesDisponibles)->firstWhere('id_seleccion', (int) $value);
        if ($vinculacion) {
            $this->mandanteContextoId = $vinculacion['mandante_id'];
            $this->unidadOrganizacionalContextoId = $vinculacion['unidad_organizacional_mandante_id'];
            $this->nombreMandanteContexto = $vinculacion['mandante_razon_social'];
            $this->nombreUnidadContexto = $vinculacion['unidad_organizacional_nombre'];
            $this->tiposEntidadPermitidosContextoActual = $vinculacion['tipos_entidad_permitidos'];
            
            $mapPestanaEntidad = $this->getMapPestanaEntidad();
            $entidadRequerida = $mapPestanaEntidad[$this->pestañaActiva] ?? null;
            if ($this->pestañaActiva !== 'mi_ficha' && !in_array($entidadRequerida, $this->tiposEntidadPermitidosContextoActual)) {
                $this->establecerPestanaActivaPorDefecto();
            }
            if ($this->pestañaActiva === 'documentos_empresa') {
                $this->determinarDocumentosRequeridosParaEmpresa();
            }
        } else {
            $this->resetContexto();
        }
    }
    
    protected function establecerPestanaActivaPorDefecto()
    {
        $this->pestañaActiva = 'mi_ficha';
        if (!empty($this->tiposEntidadPermitidosContextoActual)) {
            $map = [
                'EMPRESA' => 'documentos_empresa', 'PERSONA' => 'trabajadores',
                'VEHICULO' => 'vehiculos', 'MAQUINARIA' => 'maquinaria', 'EMBARCACION' => 'embarcaciones',
            ];
            foreach ($map as $entidad => $pestana) {
                if (in_array($entidad, $this->tiposEntidadPermitidosContextoActual)) {
                    $this->pestañaActiva = $pestana;
                    break;
                }
            }
        }
        if ($this->pestañaActiva === 'documentos_empresa' && $this->vinculacionSeleccionada) {
            $this->determinarDocumentosRequeridosParaEmpresa();
        }
    }
    
    public function resetContexto()
    {
        $this->vinculacionSeleccionada = null; $this->mandanteContextoId = null;
        $this->unidadOrganizacionalContextoId = null; $this->nombreMandanteContexto = '';
        $this->nombreUnidadContexto = ''; $this->tiposEntidadPermitidosContextoActual = [];
        $this->pestañaActiva = 'mi_ficha'; $this->documentosRequeridosEmpresa = [];
    }

    protected function getMapPestanaEntidad(): array
    {
        return [
            'documentos_empresa' => 'EMPRESA', 'trabajadores' => 'PERSONA',
            'vehiculos' => 'VEHICULO', 'maquinaria' => 'MAQUINARIA', 'embarcaciones' => 'EMBARCACION',
        ];
    }

    public function seleccionarPestaña(string $nombrePestaña, $mantenerMensajes = false)
    {
        $this->pestañaActiva = $nombrePestaña;
        if (!$mantenerMensajes) {
            $this->uploadErrors = []; $this->uploadSuccess = [];
        }
        if ($this->pestañaActiva === 'documentos_empresa' && $this->vinculacionSeleccionada) {
            $this->determinarDocumentosRequeridosParaEmpresa();
        }
    }

    public function determinarDocumentosRequeridosParaEmpresa()
    {
        if (!$this->vinculacionSeleccionada || !$this->documentoService) { $this->documentosRequeridosEmpresa = []; return; }
        
        $contratista = Auth::user()->contratista;
        if (!$contratista) { $this->documentosRequeridosEmpresa = []; return; }

        $reglasCandidatas = $this->documentoService->getReglasParaEntidadEnUO($this->mandanteContextoId, $this->unidadOrganizacionalContextoId, 'EMPRESA')
            ->load([
                'nombreDocumento', 'observacionDocumento', 'formatoDocumento', 'documentoRelacionado',
                'tipoVencimiento', 'criterios.criterioEvaluacion', 'criterios.subCriterio',
                'criterios.textoRechazo', 'criterios.aclaracionCriterio'
            ]);

        $documentosCargadosExistentes = DocumentoCargado::where('entidad_id', $contratista->id)->where('entidad_type', Contratista::class)->where('archivado', false)
            ->orderBy('created_at', 'desc')->get()->keyBy('regla_documental_id_origen');

        $condicionContratistaEnUO = DB::table('contratista_unidad_organizacional')->where('contratista_id', $contratista->id)
            ->where('unidad_organizacional_mandante_id', $this->unidadOrganizacionalContextoId)->value('tipo_condicion_id');

        $documentosFinales = []; $idsDocumentosAgregados = [];

        foreach ($reglasCandidatas as $regla) {
            if ($regla->aplica_empresa_condicion_id && $regla->aplica_empresa_condicion_id != $condicionContratistaEnUO) continue;
            if (!empty($regla->rut_especificos) && !in_array($contratista->rut, array_map('trim', explode(',', $regla->rut_especificos)))) continue;
            if (!empty($regla->rut_excluidos) && in_array($contratista->rut, array_map('trim', explode(',', $regla->rut_excluidos)))) continue;

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
                    'criterios_evaluacion' => $regla->criterios->map(fn ($c) => [
                        'criterio' => $c->criterioEvaluacion?->nombre_criterio,
                        'sub_criterio' => $c->subCriterio?->nombre,
                        'texto_rechazo' => $c->textoRechazo?->titulo,
                        'aclaracion' => $c->aclaracionCriterio?->titulo
                    ])->all(),
                    'afecta_cumplimiento' => (bool) $regla->afecta_porcentaje_cumplimiento,
                    'restringe_acceso' => (bool) $regla->restringe_acceso,
                ];
                $idsDocumentosAgregados[] = $regla->nombre_documento_id;
            }
        }
        $this->documentosRequeridosEmpresa = $documentosFinales;

        $tempDocumentosParaCargar = [];
        foreach ($this->documentosRequeridosEmpresa as $doc) {
            $reglaId = $doc['regla_documental_id_origen'];
            $tempDocumentosParaCargar[$reglaId] = [
                'archivo_input' => null, 'fecha_emision_input' => null,
                'fecha_vencimiento_input' => null, 'periodo_input' => null,
                'regla_info' => $doc, 
            ];
        }
        $this->documentosParaCargar = $tempDocumentosParaCargar;
        $this->resetErrorBag();
    }

    public function cargarDocumentos()
    {
        $contratista = Auth::user()->contratista;
        if (!$contratista) { session()->flash('error_docs_empresa', 'Error: No se ha encontrado un contratista válido.'); return; }
        
        $this->uploadErrors = []; $this->uploadSuccess = []; $this->resetErrorBag();
        $this->validate(['documentosParaCargar.*.archivo_input' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:10240']);

        $usuarioCargaId = Auth::id(); $huboArchivosParaProcesar = false;

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

                $docPendiente = DocumentoCargado::where('entidad_id', $contratista->id)->where('entidad_type', Contratista::class)->where('regla_documental_id_origen', $reglaId)
                    ->where('estado_validacion', 'Pendiente')->where('archivado', false)->first();
                if ($docPendiente) { Storage::disk('public')->delete($docPendiente->ruta_archivo); $docPendiente->delete(); }
                
                $nombreArchivo = Str::uuid() . '.' . $archivo->getClientOriginalExtension();
                // ==============================================================================
                // <-- INICIO DE LA MODIFICACIÓN CLAVE -->
                // Simplificamos la ruta de guardado a la estructura "entidad/id"
                $rutaDirectorio = "empresa/{$contratista->id}";
                // <-- FIN DE LA MODIFICACIÓN CLAVE -->
                // ==============================================================================
                $rutaArchivo = $archivo->storeAs($rutaDirectorio, $nombreArchivo, 'public');

                $fechaVencimientoCalculada = $data['fecha_vencimiento_input'] ?? null;
                if ($reglaOriginal->tipoVencimiento?->nombre === 'DESDE EMISION' && !empty($data['fecha_emision_input'])) {
                    $diasValidez = $reglaOriginal->dias_validez_documento ?? 0;
                    $fechaVencimientoCalculada = Carbon::parse($data['fecha_emision_input'])->addDays($diasValidez)->format('Y-m-d');
                }
                
                DocumentoCargado::create([
                    'contratista_id' => $contratista->id, 
                    'mandante_id' => $this->mandanteContextoId, 
                    'unidad_organizacional_id' => $this->unidadOrganizacionalContextoId,
                    'entidad_id' => $contratista->id, 
                    'entidad_type' => Contratista::class, 
                    'regla_documental_id_origen' => $reglaId,
                    'usuario_carga_id' => $usuarioCargaId, 
                    'ruta_archivo' => $rutaArchivo, 
                    'nombre_original_archivo' => $archivo->getClientOriginalName(),
                    'mime_type' => $archivo->getMimeType(), 
                    'tamano_archivo' => $archivo->getSize(), 
                    'fecha_emision' => $data['fecha_emision_input'] ?? null,
                    'fecha_vencimiento' => $fechaVencimientoCalculada,
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
                Log::error("Error al cargar documento para empresa {$contratista->id}, Regla {$reglaId}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                $this->addError('documentosParaCargar.' . $reglaId . '.archivo_input', 'Error inesperado al procesar el archivo.');
            }
        }
        
        if (!$huboArchivosParaProcesar) {
            session()->flash('info_docs_empresa', 'No se seleccionó ningún archivo nuevo para cargar.');
        } else {
            session()->flash('message_docs_empresa', 'Proceso de carga finalizado. Revise los estados individuales.');
        }
        
        $this->seleccionarPestaña('documentos_empresa', true);
    }
    
    public function eliminarDocumentoCargado($documentoCargadoId)
    {
        $doc = DocumentoCargado::find($documentoCargadoId);
        $contratistaId = Auth::user()->contratista_id;
        if ($doc && $doc->contratista_id == $contratistaId && $doc->estado_validacion === 'Pendiente') {
            try {
                Storage::disk('public')->delete($doc->ruta_archivo); $doc->delete();
                session()->flash('message_docs_empresa', 'Documento pendiente eliminado correctamente.');
            } catch (\Exception $e) {
                Log::error("Error al eliminar documento cargado ID {$documentoCargadoId}: " . $e->getMessage());
                session()->flash('error_docs_empresa', 'Ocurrió un error al eliminar el documento.');
            }
        } else { session()->flash('error_docs_empresa', 'No se puede eliminar el documento.'); }
        
        $this->seleccionarPestaña('documentos_empresa', true);
    }

    public function render()
    {
        return view('livewire.contratista.panel-operacion')->layout('layouts.app');
    }
}