<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Contratista;
use App\Models\User;
use App\Models\Role;
use App\Models\TipoEmpresaLegal;
use App\Models\Rubro;
use App\Models\Region;
use App\Models\Comuna;
use App\Models\RangoCantidadTrabajadores;
use App\Models\Mutualidad;
use App\Models\Mandante;
use App\Models\UnidadOrganizacionalMandante;
use App\Models\TipoCondicion;
use App\Models\ContratistaUnidadOrganizacional;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Rules\ValidarRutRule;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Log; // Asegúrate que Log está importado
use Illuminate\Support\Collection;
use Livewire\Attributes\On; 

#[Layout('layouts.app')]
class GestionContratistas extends Component
{
    use WithPagination;

    // Propiedades para el CRUD de Contratistas
    public $contratistaId, $razon_social, $nombre_fantasia, $rut_contratista, $direccion_calle, $direccion_numero, $comuna_id;
    public $selected_region_id_contratista;
    public $telefono_empresa, $email_empresa, $tipo_empresa_legal_id, $rubro_id, $is_active = true, $tipo_inscripcion = 'Contratista';
    public $rango_cantidad_trabajadores_id, $mutualidad_id;
    public $rep_legal_nombres, $rep_legal_apellido_paterno, $rep_legal_apellido_materno, $rep_legal_rut, $rep_legal_telefono, $rep_legal_email;

    // Propiedades para el Admin User del Contratista
    public $admin_user_id;
    public string $admin_name = '';
    public string $admin_rut_usuario = '';
    public string $admin_email = '';
    public $admin_password, $admin_password_confirmation;
    public $admin_is_active = true;
    public bool $crear_nuevo_admin = true;
    public bool $generar_password_auto = true;

    // Listados para selects del modal Contratista
    public $tiposEmpresaLegal, $rubros, $regiones, $comunasDisponiblesContratista = [], $rangosCantidad, $mutualidades;

    // Búsqueda y Ordenamiento para Contratistas
    public $search = '';
    public $sortField = 'contratistas.id';
    public $sortDirection = 'desc';

    // Estado del Modal de Contratista
    public $isOpen = false;

    // --- PROPIEDADES PARA ASIGNAR UOs ---
    public bool $showModalAsignarUOs = false;
    public ?int $contratistaParaAsignarUOs_id = null;
    public string $nombreContratistaParaAsignarUOs = '';
    public $mandantesParaAsignarUOs = [];
    public $selectedMandanteIdParaAsignarUOs = null;
    public array $unidadesOrganizacionalesAplanadas = [];
    public array $selectedUnidadesConCondicion = []; 
    public $tiposCondicionDisponibles = [];

    private function getPrefixedUoKey($uoId): string
    {
        return 'uo_' . $uoId;
    }

    #[On('toggleUOCondicionEvent')]
    public function handleToggleUOCondicion($uoId, $isChecked)
    {
        $prefixedKey = $this->getPrefixedUoKey($uoId); 
        $tempArray = $this->selectedUnidadesConCondicion; 

        Log::info("handleToggleUOCondicion - EVENTO RECIBIDO - UO ID: {$uoId}, Clave Prefijada: {$prefixedKey}, IsChecked: " . ($isChecked ? 'true' : 'false'));
        Log::info("handleToggleUOCondicion - selectedUnidadesConCondicion ANTES (CLAVES PREFIJADAS):", $tempArray);

        if ($isChecked) {
            if (!array_key_exists($prefixedKey, $tempArray)) {
                $tempArray[$prefixedKey] = null; 
            }
        } else {
            unset($tempArray[$prefixedKey]);
        }
        
        $this->selectedUnidadesConCondicion = $tempArray; 
        Log::info("handleToggleUOCondicion - selectedUnidadesConCondicion DESPUÉS (CLAVES PREFIJADAS):", $this->selectedUnidadesConCondicion);
    }

    #[On('condicionUoCambiada')]
    public function handleCondicionUoCambiada($uoId, $tipoCondicionId)
    {
        $prefixedKey = $this->getPrefixedUoKey($uoId);
        $tipoCondicionId = ($tipoCondicionId === '' || is_null($tipoCondicionId)) ? null : (int)$tipoCondicionId;

        Log::info("handleCondicionUoCambiada - EVENTO RECIBIDO - UO ID: {$uoId}, Clave Prefijada: {$prefixedKey}, TipoCondicionID: " . var_export($tipoCondicionId, true));

        if (array_key_exists($prefixedKey, $this->selectedUnidadesConCondicion)) {
            $this->selectedUnidadesConCondicion[$prefixedKey] = $tipoCondicionId;
            Log::info("handleCondicionUoCambiada - selectedUnidadesConCondicion ACTUALIZADO (CLAVES PREFIJADAS):", $this->selectedUnidadesConCondicion);
        } else {
            Log::warning("handleCondicionUoCambiada - Clave Prefijada {$prefixedKey} no encontrada en selectedUnidadesConCondicion.");
        }
    }

    public function mount()
    {
        $this->tiposEmpresaLegal = TipoEmpresaLegal::where('is_active', true)->orderBy('nombre')->get();
        $this->rubros = Rubro::where('is_active', true)->orderBy('nombre')->get();
        $this->regiones = Region::where('is_active', true)->orderBy('nombre')->get();
        $this->rangosCantidad = RangoCantidadTrabajadores::where('is_active', true)->orderBy('id')->get();
        $this->mutualidades = Mutualidad::where('is_active', true)->orderBy('nombre')->get();
        $this->mandantesParaAsignarUOs = Mandante::where('is_active', true)->orderBy('razon_social')->get();
        $this->unidadesOrganizacionalesAplanadas = [];
        $this->tiposCondicionDisponibles = TipoCondicion::where('is_active', true)->orderBy('nombre')->get();
    }

    protected function rules()
    {
        $rules = [
            'razon_social' => 'required|string|min:3|max:255',
            'nombre_fantasia' => 'nullable|string|max:255',
            'rut_contratista' => ['required', 'string', 'max:12', Rule::unique('contratistas', 'rut')->ignore($this->contratistaId), new ValidarRutRule()],
            'direccion_calle' => 'required|string|max:255',
            'direccion_numero' => 'nullable|string|max:50',
            'selected_region_id_contratista' => 'required|exists:regiones,id',
            'comuna_id' => 'required|exists:comunas,id',
            'telefono_empresa' => 'nullable|string|max:20',
            'email_empresa' => ['required', 'email', 'max:255', Rule::unique('contratistas', 'email_empresa')->ignore($this->contratistaId)],
            'tipo_empresa_legal_id' => 'required|exists:tipos_empresa_legal,id',
            'rubro_id' => 'required|exists:rubros,id',
            'tipo_inscripcion' => 'required|in:Contratista,Subcontratista',
            'rango_cantidad_trabajadores_id' => 'nullable|exists:rangos_cantidad_trabajadores,id',
            'mutualidad_id' => 'nullable|exists:mutualidades,id',
            'is_active' => 'boolean',
            'rep_legal_nombres' => 'required|string|max:100',
            'rep_legal_apellido_paterno' => 'required|string|max:100',
            'rep_legal_apellido_materno' => 'nullable|string|max:100',
            'rep_legal_rut' => ['nullable', 'string', 'max:12', new ValidarRutRule()],
            'rep_legal_telefono' => 'nullable|string|max:20',
            'rep_legal_email' => 'nullable|email|max:255',
            'admin_is_active' => 'boolean',
            'admin_name' => 'required|string|max:255',
            'admin_rut_usuario' => ['required', 'string', 'max:12', Rule::unique('users', 'rut')->ignore($this->admin_user_id), new ValidarRutRule()],
            'admin_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->admin_user_id)],
            'selectedUnidadesConCondicion' => ['array'],
            'selectedUnidadesConCondicion.*' => ['nullable', 'exists:tipos_condicion,id'], 
        ];

        if ($this->crear_nuevo_admin || !$this->admin_user_id || ($this->admin_user_id && !$this->generar_password_auto && !empty($this->admin_password)) || ($this->admin_user_id && $this->generar_password_auto) ) {
             if (!$this->generar_password_auto) {
                $rules['admin_password'] = 'required|string|min:8|confirmed';
                $rules['admin_password_confirmation'] = 'required';
            }
        }
        return $rules;
    }

    public function updated($propertyName) { 
        if ( $propertyName !== 'selectedUnidadesConCondicion' && 
             !Str::startsWith($propertyName, 'selectedUnidadesConCondicion.')
           ) { 
            $this->validateOnly($propertyName); 
        } 
    }

    public function updatedSelectedRegionIdContratista($region_id) { 
        if (!empty($region_id)) { 
            $this->comunasDisponiblesContratista = Comuna::where('region_id', $region_id)->where('is_active', true)->orderBy('nombre')->get(); 
        } else { 
            $this->comunasDisponiblesContratista = []; 
        } 
        $this->comuna_id = null; 
    }

    public function create() { 
        $this->resetInputFields(); 
        $this->admin_is_active = true; 
        $this->is_active = true; 
        $this->crear_nuevo_admin = true; 
        $this->generar_password_auto = true; 
        $this->openModal(); 
    }

    public function edit($id) { 
        // ****** LÍNEA DE LOG AÑADIDA AQUÍ ******
        Log::info("GestionContratistas - Editando contratista ID: {$id} - ¡MÉTODO EDIT LLAMADO!"); 
        // ****************************************
        $contratista = Contratista::with('adminUser', 'comuna.region')->findOrFail($id); 
        $this->contratistaId = $id; 
        $this->razon_social = $contratista->razon_social; 
        $this->nombre_fantasia = $contratista->nombre_fantasia; 
        $this->rut_contratista = $contratista->rut; 
        $this->direccion_calle = $contratista->direccion_calle; 
        $this->direccion_numero = $contratista->direccion_numero; 
        $this->selected_region_id_contratista = $contratista->comuna?->region_id; 
        $this->updatedSelectedRegionIdContratista($this->selected_region_id_contratista); 
        $this->comuna_id = $contratista->comuna_id; 
        $this->telefono_empresa = $contratista->telefono_empresa; 
        $this->email_empresa = $contratista->email_empresa; 
        $this->tipo_empresa_legal_id = $contratista->tipo_empresa_legal_id; 
        $this->rubro_id = $contratista->rubro_id; $this->is_active = $contratista->is_active; 
        $this->tipo_inscripcion = $contratista->tipo_inscripcion; 
        $this->rango_cantidad_trabajadores_id = $contratista->rango_cantidad_trabajadores_id; 
        $this->mutualidad_id = $contratista->mutualidad_id; 
        $this->rep_legal_nombres = $contratista->rep_legal_nombres; 
        $this->rep_legal_apellido_paterno = $contratista->rep_legal_apellido_paterno; 
        $this->rep_legal_apellido_materno = $contratista->rep_legal_apellido_materno; 
        $this->rep_legal_rut = $contratista->rep_legal_rut; 
        $this->rep_legal_telefono = $contratista->rep_legal_telefono; 
        $this->rep_legal_email = $contratista->rep_legal_email; 
        if ($contratista->adminUser) { 
            $this->admin_user_id = $contratista->adminUser->id; 
            $this->admin_name = $contratista->adminUser->name; 
            $this->admin_rut_usuario = $contratista->adminUser->rut; 
            $this->admin_email = $contratista->adminUser->email; 
            $this->admin_is_active = $contratista->adminUser->is_active; 
            $this->crear_nuevo_admin = false; 
        } else { 
            $this->resetAdminUserFields(); 
            $this->crear_nuevo_admin = true; 
        } 
        $this->generar_password_auto = true; 
        $this->admin_password = ''; 
        $this->admin_password_confirmation = ''; 
        $this->openModal(); 
    }

    public function store() { 
        if(is_null($this->tipo_inscripcion)){ $this->tipo_inscripcion = 'Contratista'; } 
        $rulesForContratista = $this->rules();
        unset($rulesForContratista['selectedUnidadesConCondicion']);
        unset($rulesForContratista['selectedUnidadesConCondicion.*']);
        $validatedData = $this->validate($rulesForContratista); 
        DB::beginTransaction(); 
        try { 
            $user = null; $generatedPassword = null; $userPassword = ''; 
            if ($this->crear_nuevo_admin || !$this->admin_user_id) { 
                if ($this->generar_password_auto) { $generatedPassword = Str::random(10); $userPassword = $generatedPassword; } else { $userPassword = $validatedData['admin_password']; } 
                $user = User::create([ 'name' => $validatedData['admin_name'], 'rut' => $validatedData['admin_rut_usuario'], 'email' => $validatedData['admin_email'], 'password' => Hash::make($userPassword), 'is_active' => $validatedData['admin_is_active'], 'user_type' => 'Contratista', ]); 
                $contratistaAdminRole = Role::where('name', 'Contratista_Admin')->firstOrFail(); $user->roles()->attach($contratistaAdminRole); $this->admin_user_id = $user->id; 
            } else { 
                $user = User::findOrFail($this->admin_user_id); 
                $userDataToUpdate = [ 'name' => $validatedData['admin_name'], 'rut' => $validatedData['admin_rut_usuario'], 'email' => $validatedData['admin_email'], 'is_active' => $validatedData['admin_is_active'], ]; 
                if ($this->generar_password_auto) { $generatedPassword = Str::random(10); $userDataToUpdate['password'] = Hash::make($generatedPassword); } elseif (!empty($validatedData['admin_password'])) { $userDataToUpdate['password'] = Hash::make($validatedData['admin_password']); } 
                $user->update($userDataToUpdate); 
            } 
            $dataContratista = [ 'razon_social' => $validatedData['razon_social'], 'nombre_fantasia' => $validatedData['nombre_fantasia'], 'rut' => $validatedData['rut_contratista'], 'direccion_calle' => $validatedData['direccion_calle'], 'direccion_numero' => $validatedData['direccion_numero'], 'comuna_id' => $validatedData['comuna_id'], 'telefono_empresa' => $validatedData['telefono_empresa'], 'email_empresa' => $validatedData['email_empresa'], 'tipo_empresa_legal_id' => $validatedData['tipo_empresa_legal_id'], 'rubro_id' => $validatedData['rubro_id'], 'tipo_inscripcion' => $validatedData['tipo_inscripcion'], 'rango_cantidad_trabajadores_id' => $validatedData['rango_cantidad_trabajadores_id'] ?? null, 'mutualidad_id' => $validatedData['mutualidad_id'] ?? null, 'rep_legal_nombres' => $validatedData['rep_legal_nombres'], 'rep_legal_apellido_paterno' => $validatedData['rep_legal_apellido_paterno'], 'rep_legal_apellido_materno' => $validatedData['rep_legal_apellido_materno'], 'rep_legal_rut' => $validatedData['rep_legal_rut'], 'rep_legal_telefono' => $validatedData['rep_legal_telefono'], 'rep_legal_email' => $validatedData['rep_legal_email'], 'is_active' => $validatedData['is_active'], 'admin_user_id' => $this->admin_user_id, ]; 
            $contratista = Contratista::updateOrCreate(['id' => $this->contratistaId], $dataContratista); 
            if ($user && ($user->contratista_id !== $contratista->id)) { $user->contratista_id = $contratista->id; $user->save(); } 
            DB::commit(); 
            session()->flash('message', $this->contratistaId ? 'Empresa Contratista Actualizada.' : 'Empresa Contratista Creada.'); 
            if ($generatedPassword) { session()->flash('admin_password_generated', "Se generó una nueva contraseña para el administrador {$user->email}: {$generatedPassword}. Por favor, guárdela en un lugar seguro y entréguela al usuario."); } 
            $this->closeModal(); $this->resetInputFields(); 
        } catch (\Illuminate\Validation\ValidationException $e) { 
            DB::rollBack(); Log::error('Error de validación en store Contratista: ' . $e->getMessage(), ['errors' => $e->errors()]); 
            $errorMessages = []; 
            foreach($e->errors() as $key => $messages) { $errorMessages[] = $key . ': ' . implode(', ', $messages); } 
            if (!empty($errorMessages)) { session()->flash('error', 'Error de validación. Por favor revise los campos. (' . implode('; ', $errorMessages).')'); } else { session()->flash('error', 'Error de validación. Por favor revise los campos.'); } 
        } catch (\Exception $e) { 
            DB::rollBack(); Log::error('Error al guardar Contratista: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine(), ['trace' => $e->getTraceAsString()]); 
            session()->flash('error', 'Ocurrió un error al guardar la empresa contratista: ' . $e->getMessage()); 
        } 
    }

    public function toggleActive($id) { 
        $contratista = Contratista::find($id); 
        if ($contratista) { 
            $contratista->is_active = !$contratista->is_active; 
            $contratista->save(); 
            if ($contratista->adminUser) { 
                $contratista->adminUser->is_active = $contratista->is_active; 
                $contratista->adminUser->save(); 
            } 
            session()->flash('message', 'Estado de la empresa contratista cambiado.'); 
        } 
    }

    public function sortBy($field) { 
        if ($this->sortField === $field) { 
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc'; 
        } else { 
            $this->sortDirection = 'asc'; 
        } 
        $this->sortField = $field; 
    }

    public function openModal() { $this->isOpen = true; $this->resetValidation();}
    public function closeModal() { $this->isOpen = false; $this->resetValidation(); }
    
    private function resetInputFields(){ 
        $this->contratistaId = null; $this->razon_social = ''; $this->nombre_fantasia = ''; $this->rut_contratista = ''; 
        $this->direccion_calle = ''; $this->direccion_numero = ''; $this->selected_region_id_contratista = null; 
        $this->comuna_id = null; $this->comunasDisponiblesContratista = []; $this->telefono_empresa = ''; 
        $this->email_empresa = ''; $this->tipo_empresa_legal_id = null; $this->rubro_id = null; $this->is_active = true; 
        $this->tipo_inscripcion = 'Contratista'; $this->rango_cantidad_trabajadores_id = null; $this->mutualidad_id = null; 
        $this->rep_legal_nombres = ''; $this->rep_legal_apellido_paterno = ''; $this->rep_legal_apellido_materno = ''; 
        $this->rep_legal_rut = ''; $this->rep_legal_telefono = ''; $this->rep_legal_email = ''; 
        $this->resetAdminUserFields(); $this->resetValidation(); 
    }
    
    private function resetAdminUserFields() { 
        $this->admin_user_id = null; $this->admin_name = ''; $this->admin_rut_usuario = ''; $this->admin_email = ''; 
        $this->admin_password = ''; $this->admin_password_confirmation = ''; $this->admin_is_active = true; 
        $this->crear_nuevo_admin = true; $this->generar_password_auto = true; 
    }

    public function abrirModalAsignarUOs($contratistaId)
    {
        $contratista = Contratista::find($contratistaId);
        if (!$contratista) {
            session()->flash('error', 'Contratista no encontrado.');
            return;
        }
        $this->contratistaParaAsignarUOs_id = $contratista->id;
        $this->nombreContratistaParaAsignarUOs = $contratista->razon_social;
        
        $vinculacionesUO = ContratistaUnidadOrganizacional::where('contratista_id', $contratistaId)->get();
        Log::info("abrirModalAsignarUOs - Vinculaciones de BD (colección de modelos):", $vinculacionesUO->map(fn($v) => ['pivot_id' => $v->id, 'uo_id' => $v->unidad_organizacional_mandante_id, 'condicion_id' => $v->tipo_condicion_id])->toArray());

        $newSelectedUOs = [];
        foreach ($vinculacionesUO as $vinculacion) {
            $uoId = $vinculacion->unidad_organizacional_mandante_id;
            $tipoCondicionId = $vinculacion->tipo_condicion_id;
            $prefixedKey = $this->getPrefixedUoKey($uoId); 
            
            Log::info("abrirModalAsignarUOs - Procesando para construir array: UO ID {$uoId}, Clave Prefijada {$prefixedKey}, Condicion ID {$tipoCondicionId}");
            $newSelectedUOs[$prefixedKey] = $tipoCondicionId;
        }
        
        $this->selectedUnidadesConCondicion = $newSelectedUOs; 
        Log::info("abrirModalAsignarUOs - Estado FINAL de selectedUnidadesConCondicion (CLAVES DEBEN SER PREFIJADAS 'uo_ID'):", $this->selectedUnidadesConCondicion);

        $this->selectedMandanteIdParaAsignarUOs = null;
        $this->unidadesOrganizacionalesAplanadas = []; 
        $this->showModalAsignarUOs = true;
        $this->resetValidation();
    }

    public function updatedSelectedMandanteIdParaAsignarUOs($mandanteId)
    {
        if (!empty($mandanteId)) {
            $allUOs = UnidadOrganizacionalMandante::where('mandante_id', $mandanteId)
                                                ->where('is_active', true)
                                                ->get();
            $this->unidadesOrganizacionalesAplanadas = $this->buildFlatTree($allUOs); 
            Log::info("updatedSelectedMandanteIdParaAsignarUOs - UOs APLANADAS cargadas:", $this->unidadesOrganizacionalesAplanadas);
        } else {
            $this->unidadesOrganizacionalesAplanadas = [];
        }
    }

    protected function buildFlatTree(Collection $elements, $parentId = null, $level = 0, &$result = [])
    {
        $childrenOfParent = $elements->filter(function ($element) use ($parentId) {
            return $element->parent_id == $parentId;
        })->sortBy('nombre_unidad');

        foreach ($childrenOfParent as $element) {
            $uoData = $element->toArray();
            $uoData['level'] = $level;
            $result[] = $uoData;
            $this->buildFlatTree($elements, $element->id, $level + 1, $result);
        }
        return $result;
    }
    
    public function guardarAsignacionUOs()
    {
        if (is_null($this->contratistaParaAsignarUOs_id)) {
            session()->flash('error_uos', 'No se ha especificado un contratista.');
            Log::warning('guardarAsignacionUOs: contratistaParaAsignarUOs_id es null.');
            return;
        }
        $contratista = Contratista::find($this->contratistaParaAsignarUOs_id);
        if (!$contratista) {
            session()->flash('error_uos', 'Contratista no encontrado.');
            Log::warning("guardarAsignacionUOs: Contratista con ID {$this->contratistaParaAsignarUOs_id} no encontrado.");
            return;
        }

        Log::info("guardarAsignacionUOs: Iniciando para Contratista ID: {$contratista->id}");
        Log::info("guardarAsignacionUOs: selectedUnidadesConCondicion (ANTES de validación, CLAVES PREFIJADAS):", $this->selectedUnidadesConCondicion);

        try {
            $validatedData = $this->validate([
                'selectedUnidadesConCondicion' => ['array'],
                'selectedUnidadesConCondicion.*' => ['nullable', 'exists:tipos_condicion,id'],
            ]);
            Log::info("guardarAsignacionUOs: Validación pasada. selectedUnidadesConCondicion DESPUÉS de validación (CLAVES PREFIJADAS):", $this->selectedUnidadesConCondicion);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("guardarAsignacionUOs: Error de validación.", ['errors' => $e->errors()]);
            $errorMessages = [];
            foreach($e->errors() as $key => $messages) { $errorMessages[] = $key . ': ' . implode(', ', $messages); }
            session()->flash('error_uos', 'Error de validación al guardar: ' . implode('; ', $errorMessages));
            return; 
        }
        
        DB::beginTransaction();
        try {
            $syncDataUOs = [];
            foreach ($this->selectedUnidadesConCondicion as $prefixedKey => $tipoCondicionId) {
                if (Str::startsWith($prefixedKey, 'uo_')) {
                    $currentUoId = (int) Str::after($prefixedKey, 'uo_');
                    $condicionIdParaGuardar = ($tipoCondicionId === '' || $tipoCondicionId === '0' || $tipoCondicionId === 0 || is_null($tipoCondicionId)) ? null : (int) $tipoCondicionId;
                    $syncDataUOs[$currentUoId] = ['tipo_condicion_id' => $condicionIdParaGuardar];
                } else {
                    Log::warning("guardarAsignacionUOs: Clave no esperada en selectedUnidadesConCondicion: {$prefixedKey}");
                }
            }
            
            Log::info("guardarAsignacionUOs: Datos FINALES para sync (syncDataUOs, CLAVES DEBEN SER UO IDs como INT):", $syncDataUOs);
            $contratista->unidadesOrganizacionalesMandante()->sync($syncDataUOs);
            
            Log::info("guardarAsignacionUOs: Sincronización de UOs y Condiciones completada.");
            DB::commit();
            session()->flash('message', 'Unidades Organizacionales y condiciones asignadas/actualizadas correctamente para ' . $contratista->razon_social);
            $this->cerrarModalAsignarUOs();
        } catch (\Exception $e) { 
             DB::rollBack();
            Log::error("guardarAsignacionUOs: Excepción durante la transacción.", [
                'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error_uos', 'Ocurrió un error inesperado al guardar las asignaciones: ' . $e->getMessage());
        }
    }

    public function cerrarModalAsignarUOs() { 
        $this->showModalAsignarUOs = false; 
        $this->contratistaParaAsignarUOs_id = null; 
        $this->nombreContratistaParaAsignarUOs = ''; 
        $this->selectedMandanteIdParaAsignarUOs = null; 
        $this->unidadesOrganizacionalesAplanadas = [];
        $this->selectedUnidadesConCondicion = []; 
        $this->resetValidation(); 
    }

    public function render() { 
        $query = Contratista::with('adminUser')
            ->leftJoin('users', 'contratistas.admin_user_id', '=', 'users.id')
            ->select('contratistas.*', 'users.name as admin_user_name'); 
        if ($this->search) { 
            $query->where(function ($q) { 
                $searchTerm = '%' . $this->search . '%'; 
                $q->where('contratistas.razon_social', 'like', $searchTerm)
                  ->orWhere('contratistas.nombre_fantasia', 'like', $searchTerm)
                  ->orWhere('contratistas.rut', 'like', $searchTerm)
                  ->orWhere('users.name', 'like', $searchTerm)
                  ->orWhere('users.email', 'like', $searchTerm); 
            }); 
        } 
        $sortFieldMapping = [ 
            'contratistas.id' => 'contratistas.id', 'contratistas.razon_social' => 'contratistas.razon_social', 
            'contratistas.rut' => 'contratistas.rut', 'contratistas.is_active' => 'contratistas.is_active', 
            'admin_user_name' => 'users.name' 
        ]; 
        $actualSortField = $sortFieldMapping[$this->sortField] ?? 'contratistas.id'; 
        $contratistasPaginados = $query->orderBy($actualSortField, $this->sortDirection)->paginate(10); 
        return view('livewire.gestion-contratistas', [ 
            'contratistas' => $contratistasPaginados, 
        ]); 
    }
}