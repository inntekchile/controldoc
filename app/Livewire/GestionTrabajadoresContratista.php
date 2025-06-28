<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Trabajador;
use App\Models\Contratista;
use App\Models\Mandante;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\CargoMandante;
use App\Models\TipoCondicionPersonal;
use App\Models\TrabajadorVinculacion;
use App\Models\ReglaDocumental;
use App\Models\CondicionFechaIngreso;
use App\Models\TipoEntidadControlable;
use App\Models\Nacionalidad;
use App\Models\Sexo;
use App\Models\EstadoCivil;
use App\Models\NivelEducacional;
use App\Models\Etnia;
use App\Models\Region;
use App\Models\Comuna;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use App\Rules\ValidarRutRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Livewire\WithFileUploads;
use App\Models\DocumentoCargado;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GestionTrabajadoresContratista extends Component
{
    use WithPagination;
    use WithFileUploads;

    public ?int $mandanteId = null;
    public ?int $unidadOrganizacionalId = null;
    public ?int $selectedUnidadOrganizacionalId = null;
    public string $nombreVinculacionSeleccionada = '';
    public string $vistaActual = 'listado_trabajadores';
    public ?Trabajador $trabajadorSeleccionado = null;
    public $contratistaId;
    public string $searchTrabajador = '';
    public string $sortByTrabajador = 'trabajadores.id';
    public string $sortDirectionTrabajador = 'asc';
    public bool $showModalFichaTrabajador = false;
    public ?int $trabajadorId = null;
    public string $nombres = '', $apellido_paterno = '', $apellido_materno = '', $rut_trabajador = '';
    public ?string $fecha_nacimiento = null, $email_trabajador = null, $celular_trabajador = null, $fecha_ingreso_empresa = null;
    public ?int $nacionalidad_id = null, $sexo_id = null, $estado_civil_id = null, $nivel_educacional_id = null, $etnia_id = null;
    public ?string $direccion_calle = null, $direccion_numero = null, $direccion_departamento = null;
    public ?int $trabajador_region_id = null, $trabajador_comuna_id = null;
    public bool $trabajador_is_active = true;
    public bool $showModalVinculacion = false;
    public ?int $vinculacionId = null;
    public ?int $v_mandante_id = null;
    public ?int $v_unidad_organizacional_mandante_id = null;
    public ?int $v_cargo_mandante_id = null;
    public ?int $v_tipo_condicion_personal_id = null;
    public ?string $v_fecha_ingreso_vinculacion = null;
    public ?string $v_fecha_contrato = null;
    public bool $v_is_active = true;
    public ?string $v_fecha_desactivacion = null;
    public ?string $v_motivo_desactivacion = null;
    public $nacionalidades = [], $sexos = [], $estadosCiviles = [], $nivelesEducacionales = [], $etnias = [];
    public $regiones = [], $comunasDisponiblesTrabajador = [];
    public $mandantesDisponibles = [], $unidadesOrganizacionalesDisponibles = [], $cargosMandanteDisponibles = [];
    public $tiposCondicionPersonal = [];
    public bool $showModalDocumentosTrabajador = false;
    public ?int $trabajadorParaDocumentosId = null;
    public ?Trabajador $trabajadorParaDocumentos = null;
    public array $documentosRequeridosParaTrabajador = [];
    public ?TrabajadorVinculacion $vinculacionActivaEnUOContexto = null;
    public string $nombreTrabajadorParaDocumentosModal = '';
    public $documentosParaCargar = [];
    public $uploadErrors = [];
    public $uploadSuccess = [];

    protected function messages()
    {
        return [
            '*.required' => 'Este campo es obligatorio.',
            'rut_trabajador.unique' => 'El RUT del trabajador ya existe.',
            'email_trabajador.email' => 'El formato del email no es válido.',
            'email_trabajador.unique' => 'El email del trabajador ya existe.',
            'v_fecha_desactivacion.required_if' => 'La fecha de desactivación es obligatoria si la vinculación no está activa.',
            'v_motivo_desactivacion.required_if' => 'El motivo de desactivación es obligatorio si la vinculación no está activa.',
            'documentosParaCargar.*.archivo_input.mimes' => 'El archivo debe ser de tipo: pdf, jpg, png, doc, xls.',
            'documentosParaCargar.*.archivo_input.max' => 'El archivo no debe superar los 10MB.',
        ];
    }

    public function mount(?int $mandanteId = null, ?int $unidadOrganizacionalId = null)
    {
        $this->mandanteId = $mandanteId;
        $this->unidadOrganizacionalId = $unidadOrganizacionalId;
        $this->selectedUnidadOrganizacionalId = $this->unidadOrganizacionalId;

        $user = Auth::user();
        $this->contratistaId = $user->contratista_id;

        if (!$this->contratistaId) {
            session()->flash('error', 'Usuario no asociado a un contratista válido.');
            return;
        }

        if ($this->selectedUnidadOrganizacionalId) {
            $uoContexto = UnidadOrganizacionalMandante::with('mandante:id,razon_social')->find($this->selectedUnidadOrganizacionalId);
            if ($uoContexto && $uoContexto->mandante) {
                $this->nombreVinculacionSeleccionada = ($uoContexto->mandante->razon_social ?? 'N/A') . ' - ' . $uoContexto->nombre_unidad;
            } else {
                Log::warning("GestionTrabajadoresContratista - No se pudo encontrar UO o Mandante para UO_ID: {$this->selectedUnidadOrganizacionalId}");
                $this->nombreVinculacionSeleccionada = 'Contexto Desconocido';
            }
        }

        $this->nacionalidades = Nacionalidad::orderBy('nombre')->get();
        $this->sexos = Sexo::orderBy('nombre')->get();
        $this->estadosCiviles = EstadoCivil::orderBy('nombre')->get();
        $this->nivelesEducacionales = NivelEducacional::orderBy('nombre')->get();
        $this->etnias = Etnia::orderBy('nombre')->get();
        $this->regiones = Region::orderBy('nombre')->get();
        $this->tiposCondicionPersonal = TipoCondicionPersonal::orderBy('nombre')->get();
        $this->mandantesDisponibles = Mandante::whereHas('unidadesOrganizacionales', function ($query) {
            $query->whereHas('contratistasHabilitados', function ($subQuery) {
                $subQuery->where('contratistas.id', $this->contratistaId);
            });
        })->orderBy('razon_social')->get();
    }

    public function eliminarTrabajador($id)
    {
        $trabajador = Trabajador::where('id', $id)->where('contratista_id', $this->contratistaId)->first();
        if ($trabajador) {
            try {
                $trabajador->delete();
                session()->flash('message_trabajador', 'Trabajador y todas sus vinculaciones han sido eliminados correctamente.');
                if ($this->trabajadorSeleccionado && $this->trabajadorSeleccionado->id == $id) {
                    $this->irAListadoTrabajadores();
                }
            } catch (\Exception $e) {
                Log::error("Error al eliminar trabajador ID {$id}: " . $e->getMessage());
                session()->flash('error_trabajador', 'Ocurrió un error al eliminar el trabajador.');
            }
        } else {
            session()->flash('error_trabajador', 'No se pudo eliminar el trabajador. No fue encontrado o no pertenece a su empresa.');
        }
    }

    public function seleccionarTrabajadorParaVinculaciones($trabajadorId)
    {
        $this->trabajadorSeleccionado = Trabajador::find($trabajadorId);
        if ($this->trabajadorSeleccionado && $this->trabajadorSeleccionado->contratista_id == $this->contratistaId) {
            $this->vistaActual = 'listado_vinculaciones';
            $this->resetPage('vinculacionesPage');
        } else {
            session()->flash('error_trabajador', 'Trabajador no encontrado o no pertenece a su empresa.');
            $this->trabajadorSeleccionado = null;
        }
    }

    public function irAListadoTrabajadores()
    {
        $this->vistaActual = 'listado_trabajadores';
        $this->trabajadorSeleccionado = null;
        $this->resetPage('trabajadoresPage');
    }

    public function rulesFichaTrabajador()
    {
        return [
            'nombres' => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'apellido_materno' => 'nullable|string|max:100',
            'rut_trabajador' => ['required', 'string', new ValidarRutRule(), Rule::unique('trabajadores', 'rut')->ignore($this->trabajadorId)],
            'nacionalidad_id' => 'required|exists:nacionalidades,id',
            'fecha_nacimiento' => 'nullable|date|before_or_equal:today',
            'sexo_id' => 'nullable|exists:sexos,id',
            'email_trabajador' => ['nullable', 'email', 'max:255', Rule::unique('trabajadores', 'email')->ignore($this->trabajadorId)],
            'celular_trabajador' => 'nullable|string|max:20',
            'estado_civil_id' => 'nullable|exists:estados_civiles,id',
            'nivel_educacional_id' => 'nullable|exists:niveles_educacionales,id',
            'etnia_id' => 'nullable|exists:etnias,id',
            'direccion_calle' => 'nullable|string|max:255',
            'direccion_numero' => 'nullable|string|max:50',
            'direccion_departamento' => 'nullable|string|max:50',
            'trabajador_region_id' => 'nullable|exists:regiones,id',
            'trabajador_comuna_id' => 'nullable|exists:comunas,id',
            'fecha_ingreso_empresa' => 'nullable|date',
            'trabajador_is_active' => 'boolean',
        ];
    }

    public function updatedTrabajadorRegionId($value)
    {
        if ($value) {
            $this->comunasDisponiblesTrabajador = Comuna::where('region_id', $value)->orderBy('nombre')->get();
        } else {
            $this->comunasDisponiblesTrabajador = [];
        }
        $this->trabajador_comuna_id = null;
    }

    private function resetFichaTrabajadorFields()
    {
        $this->trabajadorId = null;
        $this->nombres = '';
        $this->apellido_paterno = '';
        $this->apellido_materno = '';
        $this->rut_trabajador = '';
        $this->fecha_nacimiento = null;
        $this->email_trabajador = null;
        $this->celular_trabajador = null;
        $this->fecha_ingreso_empresa = null;
        $this->nacionalidad_id = null;
        $this->sexo_id = null;
        $this->estado_civil_id = null;
        $this->nivel_educacional_id = null;
        $this->etnia_id = null;
        $this->direccion_calle = null;
        $this->direccion_numero = null;
        $this->direccion_departamento = null;
        $this->trabajador_region_id = null;
        $this->trabajador_comuna_id = null;
        $this->trabajador_is_active = true;
        $this->comunasDisponiblesTrabajador = [];
        $this->resetValidation();
    }

    public function abrirModalNuevoTrabajador()
    {
        if (!$this->selectedUnidadOrganizacionalId) {
            session()->flash('error', 'Error: El contexto de operación (Mandante - UO) no está definido.');
            return;
        }
        $this->resetFichaTrabajadorFields();
        $this->showModalFichaTrabajador = true;
    }

    public function abrirModalEditarTrabajador($id)
    {
        if (!$this->selectedUnidadOrganizacionalId) {
            session()->flash('error', 'Error: El contexto de operación (Mandante - UO) no está definido.');
            return;
        }
        $trabajador = Trabajador::with('comuna.region')->find($id);
        if ($trabajador && $trabajador->contratista_id == $this->contratistaId) {
            $this->trabajadorId = $trabajador->id;
            if ($this->vistaActual === 'listado_vinculaciones' && $this->trabajadorSeleccionado && $this->trabajadorSeleccionado->id === $trabajador->id) {}
            else {
                $this->trabajadorSeleccionado = $trabajador;
            }
            $this->nombres = $trabajador->nombres;
            $this->apellido_paterno = $trabajador->apellido_paterno;
            $this->apellido_materno = $trabajador->apellido_materno;
            $this->rut_trabajador = $trabajador->rut;
            $this->nacionalidad_id = $trabajador->nacionalidad_id;
            $this->fecha_nacimiento = $trabajador->fecha_nacimiento ? $trabajador->fecha_nacimiento->format('Y-m-d') : null;
            $this->sexo_id = $trabajador->sexo_id;
            $this->email_trabajador = $trabajador->email;
            $this->celular_trabajador = $trabajador->celular;
            $this->estado_civil_id = $trabajador->estado_civil_id;
            $this->nivel_educacional_id = $trabajador->nivel_educacional_id;
            $this->etnia_id = $trabajador->etnia_id;
            $this->direccion_calle = $trabajador->direccion_calle;
            $this->direccion_numero = $trabajador->direccion_numero;
            $this->direccion_departamento = $trabajador->direccion_departamento;
            $this->trabajador_region_id = $trabajador->comuna?->region_id;
            if ($this->trabajador_region_id) {
                $this->comunasDisponiblesTrabajador = Comuna::where('region_id', $this->trabajador_region_id)->orderBy('nombre')->get();
            }
            $this->trabajador_comuna_id = $trabajador->comuna_id;
            $this->fecha_ingreso_empresa = $trabajador->fecha_ingreso_empresa ? $trabajador->fecha_ingreso_empresa->format('Y-m-d') : null;
            $this->trabajador_is_active = $trabajador->is_active;
            $this->showModalFichaTrabajador = true;
        }
    }

    public function guardarTrabajador()
    {
        if (!$this->selectedUnidadOrganizacionalId) {
            session()->flash('error_trabajador', 'Error: El contexto de operación (Mandante - UO) no está definido para guardar.');
            $this->cerrarModalFichaTrabajador();
            return;
        }
        $validatedData = $this->validate($this->rulesFichaTrabajador());

        $datosParaGuardar = [
            'contratista_id' => $this->contratistaId,
            'nombres' => $validatedData['nombres'],
            'apellido_paterno' => $validatedData['apellido_paterno'],
            'apellido_materno' => $validatedData['apellido_materno'],
            'rut' => $validatedData['rut_trabajador'],
            'nacionalidad_id' => $validatedData['nacionalidad_id'],
            'fecha_nacimiento' => $validatedData['fecha_nacimiento'],
            'sexo_id' => $validatedData['sexo_id'],
            'email' => $validatedData['email_trabajador'],
            'celular' => $validatedData['celular_trabajador'],
            'estado_civil_id' => $validatedData['estado_civil_id'],
            'nivel_educacional_id' => $validatedData['nivel_educacional_id'],
            'etnia_id' => $validatedData['etnia_id'],
            'direccion_calle' => $validatedData['direccion_calle'],
            'direccion_numero' => $validatedData['direccion_numero'],
            'direccion_departamento' => $validatedData['direccion_departamento'],
            'comuna_id' => $validatedData['trabajador_comuna_id'],
            'fecha_ingreso_empresa' => $validatedData['fecha_ingreso_empresa'],
            'is_active' => $validatedData['trabajador_is_active'],
        ];

        DB::beginTransaction();
        try {
            Trabajador::updateOrCreate(['id' => $this->trabajadorId], $datosParaGuardar);
            session()->flash('message_trabajador', $this->trabajadorId ? 'Ficha del trabajador actualizada correctamente.' : 'Trabajador agregado correctamente.');
            DB::commit();
            $this->cerrarModalFichaTrabajador();
            if ($this->trabajadorSeleccionado && $this->trabajadorSeleccionado->id == ($this->trabajadorId ?? null)) {
                $this->trabajadorSeleccionado = Trabajador::find($this->trabajadorSeleccionado->id);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al guardar trabajador: " . $e->getMessage());
            session()->flash('error_trabajador', 'Ocurrió un error al guardar la ficha del trabajador.');
        }
    }

    public function cerrarModalFichaTrabajador()
    {
        $this->showModalFichaTrabajador = false;
        $this->resetFichaTrabajadorFields();
    }

    public function toggleActivoTrabajador(Trabajador $trabajador)
    {
        if ($trabajador && $trabajador->contratista_id == $this->contratistaId) {
            $trabajador->is_active = !$trabajador->is_active;
            $trabajador->save();
            session()->flash('message_trabajador', 'Estado del trabajador cambiado.');
        }
    }

    public function rulesVinculacion()
    {
        $rules = [
            'v_mandante_id' => 'required|exists:mandantes,id',
            'v_unidad_organizacional_mandante_id' => ['required', 'exists:unidades_organizacionales_mandante,id'],
            'v_cargo_mandante_id' => 'required|exists:cargos_mandante,id',
            'v_tipo_condicion_personal_id' => 'nullable|exists:tipos_condicion_personal,id',
            'v_fecha_ingreso_vinculacion' => 'required|date',
            'v_fecha_contrato' => 'nullable|date|after_or_equal:v_fecha_ingreso_vinculacion',
            'v_is_active' => 'required|boolean',
            'v_fecha_desactivacion' => 'nullable|required_if:v_is_active,false|date|after_or_equal:v_fecha_ingreso_vinculacion',
            'v_motivo_desactivacion' => 'nullable|required_if:v_is_active,false|string|max:500',
        ];

        $rules['v_unidad_organizacional_mandante_id'][] = function ($attribute, $value, $fail) {
            if ($this->v_is_active) {
                if (!$this->trabajadorSeleccionado) {
                    $fail('No hay un trabajador seleccionado para esta validación.');
                    return;
                }
                $query = TrabajadorVinculacion::where('trabajador_id', $this->trabajadorSeleccionado->id)
                    ->where('unidad_organizacional_mandante_id', $value)
                    ->where('is_active', true);

                if ($this->vinculacionId) {
                    $query->where('id', '!=', $this->vinculacionId);
                }

                if ($query->exists()) {
                    $fail('El trabajador ya tiene una vinculación activa en esta Unidad Organizacional.');
                }
            }
        };
        return $rules;
    }

    public function updatedVMandanteId($mandanteId)
    {
        $this->unidadesOrganizacionalesDisponibles = [];
        $this->cargosMandanteDisponibles = [];
        $this->v_unidad_organizacional_mandante_id = null;
        $this->v_cargo_mandante_id = null;

        if ($mandanteId) {
            $this->unidadesOrganizacionalesDisponibles = UnidadOrganizacionalMandante::where('mandante_id', $mandanteId)->where('is_active', true)->whereHas('contratistasHabilitados', function ($query) { $query->where('contratista_id', $this->contratistaId); })->orderBy('nombre_unidad')->get();
            $this->cargosMandanteDisponibles = CargoMandante::where('mandante_id', $mandanteId)->where('is_active', true)->orderBy('nombre_cargo')->get();
        }
    }

    private function resetVinculacionFields()
    {
        $this->vinculacionId = null;
        $this->v_mandante_id = null;
        $this->v_unidad_organizacional_mandante_id = null;
        $this->v_cargo_mandante_id = null;
        $this->v_tipo_condicion_personal_id = null;
        $this->v_fecha_ingreso_vinculacion = null;
        $this->v_fecha_contrato = null;
        $this->v_is_active = true;
        $this->v_fecha_desactivacion = null;
        $this->v_motivo_desactivacion = null;
        $this->unidadesOrganizacionalesDisponibles = [];
        $this->cargosMandanteDisponibles = [];
        $this->resetValidation();
    }

    public function abrirModalNuevaVinculacion()
    {
        if (!$this->trabajadorSeleccionado) { session()->flash('error_vinculacion', 'Debe seleccionar un trabajador para agregar una vinculación.'); return; }
        if (!$this->mandanteId || !$this->unidadOrganizacionalId) { session()->flash('error_vinculacion', 'El contexto de operación (Mandante - UO) no está definido.'); return; }

        $this->resetVinculacionFields();
        $this->v_mandante_id = $this->mandanteId;
        $this->updatedVMandanteId($this->v_mandante_id);

        if ($this->unidadesOrganizacionalesDisponibles->contains('id', $this->unidadOrganizacionalId)) {
            $this->v_unidad_organizacional_mandante_id = $this->unidadOrganizacionalId;
        }

        $this->v_fecha_ingreso_vinculacion = now()->format('Y-m-d');
        $this->showModalVinculacion = true;
    }

    public function abrirModalEditarVinculacion($id)
    {
        $vinculacion = TrabajadorVinculacion::with('unidadOrganizacionalMandante.mandante')->find($id);
        if ($vinculacion && $vinculacion->trabajador_id == $this->trabajadorSeleccionado?->id) {
            $this->vinculacionId = $vinculacion->id;
            $this->v_mandante_id = $vinculacion->unidadOrganizacionalMandante?->mandante_id;
            $this->updatedVMandanteId($this->v_mandante_id);

            $this->v_unidad_organizacional_mandante_id = $vinculacion->unidad_organizacional_mandante_id;
            $this->v_cargo_mandante_id = $vinculacion->cargo_mandante_id;
            $this->v_tipo_condicion_personal_id = $vinculacion->tipo_condicion_personal_id;
            $this->v_fecha_ingreso_vinculacion = $vinculacion->fecha_ingreso_vinculacion->format('Y-m-d');
            $this->v_fecha_contrato = $vinculacion->fecha_contrato ? $vinculacion->fecha_contrato->format('Y-m-d') : null;
            $this->v_is_active = $vinculacion->is_active;
            $this->v_fecha_desactivacion = $vinculacion->fecha_desactivacion ? $vinculacion->fecha_desactivacion->format('Y-m-d') : null;
            $this->v_motivo_desactivacion = $vinculacion->motivo_desactivacion;
            $this->showModalVinculacion = true;
        } else {
            session()->flash('error_vinculacion', 'Vinculación no encontrada o no pertenece al trabajador seleccionado.');
        }
    }

    public function guardarVinculacion()
    {
        if (!$this->trabajadorSeleccionado) { session()->flash('error_vinculacion', 'No se ha seleccionado un trabajador.'); return; }
        if (!$this->mandanteId || !$this->unidadOrganizacionalId) { session()->flash('error_vinculacion', 'El contexto de operación (Mandante - UO) no está definido.'); return; }

        $validatedData = $this->validate($this->rulesVinculacion());
        if ($validatedData['v_is_active']) { $validatedData['v_fecha_desactivacion'] = null; $validatedData['v_motivo_desactivacion'] = null; }

        $dataToSave = [
            'trabajador_id' => $this->trabajadorSeleccionado->id,
            'unidad_organizacional_mandante_id' => $validatedData['v_unidad_organizacional_mandante_id'],
            'cargo_mandante_id' => $validatedData['v_cargo_mandante_id'],
            'tipo_condicion_personal_id' => $validatedData['v_tipo_condicion_personal_id'],
            'fecha_ingreso_vinculacion' => $validatedData['v_fecha_ingreso_vinculacion'],
            'fecha_contrato' => $validatedData['v_fecha_contrato'],
            'is_active' => $validatedData['v_is_active'],
            'fecha_desactivacion' => $validatedData['v_fecha_desactivacion'],
            'motivo_desactivacion' => $validatedData['v_motivo_desactivacion'],
        ];

        try {
            TrabajadorVinculacion::updateOrCreate(['id' => $this->vinculacionId], $dataToSave);
            session()->flash('message_vinculacion', $this->vinculacionId ? 'Vinculación actualizada correctamente.' : 'Vinculación creada correctamente.');
            $this->cerrarModalVinculacion();
        } catch (\Exception $e) {
            Log::error("Error al guardar vinculación: " . $e->getMessage());
            session()->flash('error_vinculacion', 'Ocurrió un error al guardar la vinculación.');
        }
    }

    public function cerrarModalVinculacion()
    {
        $this->showModalVinculacion = false;
        $this->resetVinculacionFields();
    }

    public function toggleActivoVinculacion(TrabajadorVinculacion $vinculacion)
    {
        if ($vinculacion && $vinculacion->trabajador_id == $this->trabajadorSeleccionado?->id) {
            if ($vinculacion->is_active) {
                $vinculacion->is_active = false;
                $vinculacion->fecha_desactivacion = $vinculacion->fecha_desactivacion ?? now();
                $vinculacion->motivo_desactivacion = $vinculacion->motivo_desactivacion ?? 'Desactivado manualmente desde listado.';
            } else {
                $existeOtraActivaEnMismaUO = TrabajadorVinculacion::where('trabajador_id', $vinculacion->trabajador_id)->where('unidad_organizacional_mandante_id', $vinculacion->unidad_organizacional_mandante_id)->where('is_active', true)->where('id', '!=', $vinculacion->id)->exists();
                if ($existeOtraActivaEnMismaUO) { session()->flash('error_vinculacion', 'No se puede activar. El trabajador ya tiene otra vinculación activa en esta Unidad Organizacional.'); return; }
                $vinculacion->is_active = true;
                $vinculacion->fecha_desactivacion = null;
                $vinculacion->motivo_desactivacion = null;
            }
            $vinculacion->save();
            session()->flash('message_vinculacion', 'Estado de la vinculación cambiado.');
        }
    }

    public function abrirModalDocumentosTrabajador($trabajadorId, $mantenerMensajes = false)
    {
        if (!$this->selectedUnidadOrganizacionalId) { session()->flash('error', 'Por favor, seleccione primero una Vinculación (Mandante - UO) para operar.'); return; }

        $this->trabajadorParaDocumentosId = $trabajadorId;
        $this->trabajadorParaDocumentos = Trabajador::with('nacionalidad')->find($trabajadorId);

        if (!$this->trabajadorParaDocumentos || $this->trabajadorParaDocumentos->contratista_id != $this->contratistaId) {
            session()->flash('error_modal_documentos', 'Trabajador no encontrado o no pertenece a su empresa.');
            $this->resetModalDocumentosFields();
            return;
        }
        $this->nombreTrabajadorParaDocumentosModal = $this->trabajadorParaDocumentos->nombre_completo;

        $this->vinculacionActivaEnUOContexto = TrabajadorVinculacion::with(['cargoMandante:id,nombre_cargo', 'tipoCondicionPersonal:id,nombre'])->where('trabajador_id', $this->trabajadorParaDocumentos->id)->where('unidad_organizacional_mandante_id', $this->selectedUnidadOrganizacionalId)->where('is_active', true)->orderBy('fecha_ingreso_vinculacion', 'desc')->first();

        if (!$this->vinculacionActivaEnUOContexto) {
            Log::info("Trabajador ID {$trabajadorId} no tiene vinculación activa en UO ID {$this->selectedUnidadOrganizacionalId}. Algunos filtros de reglas pueden no aplicar.");
        }

        $this->determinarDocumentosRequeridos();

        if (!$mantenerMensajes) { $this->uploadErrors = []; $this->uploadSuccess = []; }

        $tempDocumentosParaCargar = [];
        foreach ($this->documentosRequeridosParaTrabajador as $doc) {
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
        $this->showModalDocumentosTrabajador = true;
    }

    public function cerrarModalDocumentosTrabajador()
    {
        $this->resetModalDocumentosFields();
    }

    private function resetModalDocumentosFields()
    {
        $this->showModalDocumentosTrabajador = false;
        $this->trabajadorParaDocumentosId = null;
        $this->trabajadorParaDocumentos = null;
        $this->documentosRequeridosParaTrabajador = [];
        $this->vinculacionActivaEnUOContexto = null;
        $this->nombreTrabajadorParaDocumentosModal = '';
        $this->documentosParaCargar = [];
        $this->uploadErrors = [];
        $this->uploadSuccess = [];
        $this->resetValidation();
    }

    private function esAncestro($idUoAncestroPotencial, $idUoDescendiente)
    {
        $uoActual = UnidadOrganizacionalMandante::find($idUoDescendiente);
        while ($uoActual && $uoActual->parent_id) {
            if ($uoActual->parent_id == $idUoAncestroPotencial) { return true; }
            $uoActual = UnidadOrganizacionalMandante::find($uoActual->parent_id);
        }
        return false;
    }

    private function determinarDocumentosRequeridos()
    {
        if (!$this->trabajadorParaDocumentos || !$this->selectedUnidadOrganizacionalId) { $this->documentosRequeridosParaTrabajador = []; return; }

        $trabajador = $this->trabajadorParaDocumentos;
        $vinculacion = $this->vinculacionActivaEnUOContexto;
        $uoContextoId = $this->selectedUnidadOrganizacionalId;
        $uoContexto = UnidadOrganizacionalMandante::find($uoContextoId);

        if (!$uoContexto) { $this->documentosRequeridosParaTrabajador = []; return; }

        $mandanteContextoId = $uoContexto->mandante_id;
        $condicionContratistaEnUO = DB::table('contratista_unidad_organizacional')->where('contratista_id', $this->contratistaId)->where('unidad_organizacional_mandante_id', $uoContextoId)->value('tipo_condicion_id');
        $tipoEntidadPersonaId = TipoEntidadControlable::where('nombre_entidad', 'Persona')->value('id');

        $reglasCandidatas = ReglaDocumental::with(['nombreDocumento', 'observacionDocumento', 'formatoDocumento', 'documentoRelacionado', 'unidadesOrganizacionales', 'criterios.criterioEvaluacion', 'criterios.subCriterio', 'criterios.textoRechazo', 'criterios.aclaracionCriterio', 'tipoVencimiento', 'cargosAplica', 'nacionalidadesAplica'])->where('mandante_id', $mandanteContextoId)->where('tipo_entidad_controlada_id', $tipoEntidadPersonaId)->where('is_active', true)->get();

        $documentosCargadosExistentes = DocumentoCargado::where('entidad_id', $trabajador->id)->where('entidad_type', Trabajador::class)->where('archivado', false)->orderBy('created_at', 'desc')->get()->keyBy('regla_documental_id_origen');
        $documentosFinales = [];
        $idsDocumentosAgregados = [];

        foreach ($reglasCandidatas as $regla) {
            $aplicaUO = false;
            $idsUORegla = $regla->unidadesOrganizacionales->pluck('id')->toArray();
            if (!empty($idsUORegla)) { if (in_array($uoContextoId, $idsUORegla)) { $aplicaUO = true; } else { foreach ($idsUORegla as $idUoRegla) { if ($this->esAncestro($idUoRegla, $uoContextoId)) { $aplicaUO = true; break; } } } } else { $aplicaUO = false; }
            if (!$aplicaUO) continue;
            if (!empty($regla->rut_especificos) && !in_array($trabajador->rut, array_map('trim', explode(',', $regla->rut_especificos)))) continue;
            if (!empty($regla->rut_excluidos) && in_array($trabajador->rut, array_map('trim', explode(',', $regla->rut_excluidos)))) continue;
            if ($regla->aplica_empresa_condicion_id && $regla->aplica_empresa_condicion_id != $condicionContratistaEnUO) continue;
            if ($regla->aplica_persona_condicion_id && (!$vinculacion || $regla->aplica_persona_condicion_id != $vinculacion->tipo_condicion_personal_id)) continue;
            $idsCargosRegla = $regla->cargosAplica->pluck('id')->toArray();
            if (!empty($idsCargosRegla) && (!$vinculacion || !in_array($vinculacion->cargo_mandante_id, $idsCargosRegla))) continue;
            $idsNacionalidadesRegla = $regla->nacionalidadesAplica->pluck('id')->toArray();
            if (!empty($idsNacionalidadesRegla) && (!$trabajador->nacionalidad_id || !in_array($trabajador->nacionalidad_id, $idsNacionalidadesRegla))) continue;
            if ($regla->condicion_fecha_ingreso_id && $regla->fecha_comparacion_ingreso) {
                $condicionFecha = CondicionFechaIngreso::find($regla->condicion_fecha_ingreso_id);
                $fechaIngresoTrabajador = $vinculacion ? Carbon::parse($vinculacion->fecha_ingreso_vinculacion) : null;
                $fechaComparacionRegla = Carbon::parse($regla->fecha_comparacion_ingreso);
                if (!$fechaIngresoTrabajador || !$condicionFecha || !$condicionFecha->evaluar($fechaIngresoTrabajador, $fechaComparacionRegla)) continue;
            }

            if (!in_array($regla->nombre_documento_id, $idsDocumentosAgregados)) {
                $docCargado = $documentosCargadosExistentes->get($regla->id);
                $estadoActual = 'No Cargado';
                if ($docCargado) {
                    if ($docCargado->resultado_validacion === 'Rechazado') { $estadoActual = 'Rechazado'; }
                    elseif ($docCargado->estado_validacion === 'Pendiente') { $estadoActual = 'Pendiente Validación'; }
                    elseif ($docCargado->estado_validacion === 'En Revisión') { $estadoActual = 'En Revisión'; }
                    elseif ($docCargado->resultado_validacion === 'Aprobado') { $estadoActual = $docCargado->estadoVigencia; }
                }

                $criteriosParaJson = $regla->criterios->map(function ($c) {
                    return [
                        'criterio' => $c->criterioEvaluacion?->nombre_criterio,
                        'aclaracion' => $c->aclaracionCriterio?->titulo,
                        'sub_criterio' => $c->subCriterio?->nombre,
                        'texto_rechazo' => $c->textoRechazo?->titulo,
                    ];
                })->all();
                
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
                    'criterios_evaluacion' => $criteriosParaJson,
                    'afecta_cumplimiento' => (bool) $regla->afecta_porcentaje_cumplimiento,
                    'restringe_acceso' => (bool) $regla->restringe_acceso,
                ];
                $idsDocumentosAgregados[] = $regla->nombre_documento_id;
            }
        }
        $this->documentosRequeridosParaTrabajador = $documentosFinales;
    }

    public function cargarDocumentos()
    {
        if (!$this->trabajadorParaDocumentos) { session()->flash('error_modal_documentos', 'Error: No se ha seleccionado un trabajador válido.'); return; }

        $this->uploadErrors = [];
        $this->uploadSuccess = [];
        $this->resetErrorBag();
        $this->validate(['documentosParaCargar.*.archivo_input' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:10240']);

        $contratistaId = $this->contratistaId;
        $usuarioCargaId = Auth::id();
        $huboArchivosParaProcesar = false;

        foreach ($this->documentosParaCargar as $reglaId => $data) {
            if (empty($data['archivo_input'])) continue;

            $huboArchivosParaProcesar = true;
            $infoRegla = $data['regla_info'];
            $archivo = $data['archivo_input'];

            $errorValidacion = null;
            if (($infoRegla['valida_emision'] || $infoRegla['tipo_vencimiento_nombre'] === 'DESDE EMISION') && empty($data['fecha_emision_input'])) { $errorValidacion = 'Se requiere Fecha de Emisión.'; }
            if (($infoRegla['valida_vencimiento'] || $infoRegla['tipo_vencimiento_nombre'] === 'FIJO') && empty($data['fecha_vencimiento_input'])) { $errorValidacion = 'Se requiere Fecha de Vencimiento.'; }
            if ($infoRegla['tipo_vencimiento_nombre'] === 'PERIODO' && empty($data['periodo_input'])) { $errorValidacion = 'Se requiere el Período (formato YYYY-MM).'; }
            if ($errorValidacion) { $this->addError('documentosParaCargar.' . $reglaId . '.archivo_input', $errorValidacion); continue; }

            try {
                // ***** CORRECCIÓN FINAL Y DEFINITIVA *****
                // Se recarga la regla con TODAS sus relaciones BelongsTo para asegurar que tenemos todos los datos necesarios.
                $reglaOriginal = ReglaDocumental::with([
                    'nombreDocumento', 'observacionDocumento', 'formatoDocumento', 'tipoVencimiento'
                ])->findOrFail($reglaId);

                $documentoPendienteExistente = DocumentoCargado::where('entidad_id', $this->trabajadorParaDocumentos->id)->where('entidad_type', Trabajador::class)->where('regla_documental_id_origen', $reglaId)->where('estado_validacion', 'Pendiente')->where('archivado', false)->first();
                if ($documentoPendienteExistente) { Storage::disk('public')->delete($documentoPendienteExistente->ruta_archivo); $documentoPendienteExistente->delete(); }

                $nombreArchivo = Str::uuid() . '.' . $archivo->getClientOriginalExtension();
                $rutaDirectorio = "documentos/c-{$contratistaId}/trabajadores/t-{$this->trabajadorParaDocumentos->id}";
                $rutaArchivo = $archivo->storeAs($rutaDirectorio, $nombreArchivo, 'public');
                
                DocumentoCargado::create([
                    'contratista_id' => $contratistaId, 'mandante_id' => $this->mandanteId,
                    'unidad_organizacional_id' => $this->selectedUnidadOrganizacionalId,
                    'entidad_id' => $this->trabajadorParaDocumentos->id,
                    'entidad_type' => Trabajador::class,
                    'regla_documental_id_origen' => $reglaId,
                    'usuario_carga_id' => $usuarioCargaId,
                    'ruta_archivo' => $rutaArchivo,
                    'nombre_original_archivo' => $archivo->getClientOriginalName(),
                    'mime_type' => $archivo->getMimeType(),
                    'tamano_archivo' => $archivo->getSize(),
                    'fecha_emision' => $data['fecha_emision_input'] ?? null,
                    'fecha_vencimiento' => $data['fecha_vencimiento_input'] ?? null,
                    'periodo' => $data['periodo_input'] ?? null,
                    'estado_validacion' => 'Pendiente',

                    // Usamos la $reglaOriginal (fresca de la BD) y las relaciones corregidas.
                    'nombre_documento_snapshot' => $reglaOriginal->nombreDocumento?->nombre,
                    'observacion_documento_snapshot' => $reglaOriginal->observacionDocumento?->titulo,
                    'formato_documento_snapshot' => $reglaOriginal->formatoDocumento?->nombre, // <--- CORREGIDO CON RELACIÓN
                    'documento_relacionado_id_snapshot' => $reglaOriginal->documento_relacionado_id,
                    'tipo_vencimiento_snapshot' => $reglaOriginal->tipoVencimiento?->nombre,
                    'valida_emision_snapshot' => (bool)$reglaOriginal->valida_emision,
                    'valida_vencimiento_snapshot' => (bool)$reglaOriginal->valida_vencimiento,
                    'valor_nominal_snapshot' => $reglaOriginal->valor_nominal_documento,  // <--- CORREGIDO CON NOMBRE DE CAMPO REAL
                    'habilita_acceso_snapshot' => (bool)$reglaOriginal->restringe_acceso,
                    'afecta_cumplimiento_snapshot' => (bool)$reglaOriginal->afecta_porcentaje_cumplimiento,
                    'es_perseguidor_snapshot' => (bool)$reglaOriginal->documento_es_perseguidor, // <--- CORREGIDO CON NOMBRE DE CAMPO REAL
                    'criterios_snapshot' => $infoRegla['criterios_evaluacion'],
                ]);
                // ***** FIN DE LA CORRECCIÓN *****

                $this->uploadSuccess[$reglaId] = 'Archivo cargado exitosamente.';
            } catch (\Exception $e) {
                Log::error("Error al cargar documento para trabajador {$this->trabajadorParaDocumentos->id}, Regla {$reglaId}: " . $e->getMessage() . ' en la linea ' . $e->getLine());
                $this->addError('documentosParaCargar.' . $reglaId . '.archivo_input', 'Error inesperado al procesar el archivo.');
            }
        }

        if (!$huboArchivosParaProcesar) { session()->flash('info_modal_documentos', 'No se seleccionó ningún archivo nuevo para cargar.'); }
        $this->abrirModalDocumentosTrabajador($this->trabajadorParaDocumentos->id, true);
    }

    public function eliminarDocumentoCargado($documentoCargadoId)
    {
        $doc = DocumentoCargado::find($documentoCargadoId);
        if ($doc && $doc->contratista_id == $this->contratistaId && $doc->estado_validacion === 'Pendiente') {
            try {
                Storage::disk('public')->delete($doc->ruta_archivo);
                $doc->delete();
                session()->flash('info_modal_documentos', 'Documento pendiente eliminado correctamente.');
            } catch (\Exception $e) {
                Log::error("Error al eliminar documento cargado ID {$documentoCargadoId}: " . $e->getMessage());
                session()->flash('error_modal_documentos', 'Ocurrió un error al eliminar el documento.');
            }
        } else {
            session()->flash('error_modal_documentos', 'No se puede eliminar el documento. O no fue encontrado, o no le pertenece, o ya no está en estado "Pendiente".');
        }
        if($this->trabajadorParaDocumentos){
            $this->abrirModalDocumentosTrabajador($this->trabajadorParaDocumentos->id, true);
        }
    }

    public function render()
    {
        $trabajadoresPaginados = null;
        $vinculacionesPaginadas = null;

        if ($this->vistaActual === 'listado_trabajadores') {
            $queryTrabajadores = Trabajador::query();
            if ($this->selectedUnidadOrganizacionalId) {
                $queryTrabajadores->where('contratista_id', $this->contratistaId)->whereHas('vinculaciones', function ($query) { $query->where('unidad_organizacional_mandante_id', $this->selectedUnidadOrganizacionalId); });
            } else {
                $queryTrabajadores->whereRaw('1 = 0');
            }
            $queryTrabajadores->when($this->searchTrabajador, function ($query) {
                $query->where(function ($q) { $q->where('trabajadores.rut', 'like', '%' . $this->searchTrabajador . '%')->orWhere('trabajadores.nombres', 'like', '%' . $this->searchTrabajador . '%')->orWhere('trabajadores.apellido_paterno', 'like', '%' . $this->searchTrabajador . '%')->orWhere('trabajadores.apellido_materno', 'like', '%' . $this->searchTrabajador . '%'); });
            })
            ->with(['vinculaciones' => function ($query) { $query->where('unidad_organizacional_mandante_id', $this->selectedUnidadOrganizacionalId)->where('is_active', true)->with('cargoMandante:id,nombre_cargo')->orderBy('fecha_ingreso_vinculacion', 'desc'); }])
            ->orderBy($this->sortByTrabajador, $this->sortDirectionTrabajador);
            $trabajadoresPaginados = $queryTrabajadores->paginate(10, ['*'], 'trabajadoresPage');
        } elseif ($this->vistaActual === 'listado_vinculaciones' && $this->trabajadorSeleccionado) {
            $unidadesHabilitadasGlobal = Contratista::find($this->contratistaId)->unidadesOrganizacionalesMandante()->pluck('unidades_organizacionales_mandante.id');
            $vinculacionesPaginadas = TrabajadorVinculacion::where('trabajador_id', $this->trabajadorSeleccionado->id)
                ->whereIn('unidad_organizacional_mandante_id', $unidadesHabilitadasGlobal)
                ->with(['unidadOrganizacionalMandante.mandante', 'cargoMandante', 'tipoCondicionPersonal'])
                ->orderBy('is_active', 'desc')
                ->orderBy('fecha_ingreso_vinculacion', 'desc')
                ->paginate(10, ['*'], 'vinculacionesPage');
        }

        return view('livewire.gestion-trabajadores-contratista', [
            'trabajadoresPaginados' => $trabajadoresPaginados,
            'vinculacionesPaginadas' => $vinculacionesPaginadas,
        ]);
    }
}