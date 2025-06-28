<?php

namespace App\Http\Livewire\Contratista;

use Livewire\Component;
use App\Models\Vehiculo;
use App\Models\Contratista;
use App\Models\TipoVehiculo;
use App\Models\MarcaVehiculo;
use App\Models\ColorVehiculo;
use App\Models\TenenciaVehiculo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;

class GestionVehiculos extends Component
{
    use WithPagination;

    // --- Propiedades para recibir el contexto del PanelOperacion ---
    // LA LÍNEA MÁS IMPORTANTE ES LA SIGUIENTE. DEBE SER PÚBLICA (public).
    public ?int $unidadOrganizacionalId = null;
    public ?int $mandanteId = null;
    // ---------------------------------------------------------------

    public $contratistaId;

    public string $searchVehiculo = '';
    public string $sortByVehiculo = 'vehiculos.id';
    public string $sortDirectionVehiculo = 'asc';

    // --- Propiedades para el Modal de Ficha del Vehículo ---
    public bool $showModalFichaVehiculo = false;
    public ?int $vehiculoId = null;
    
    public string $patente_letras = '';
    public string $patente_numeros = '';
    public ?string $ano_fabricacion = null;
    public ?int $marca_vehiculo_id = null;
    public ?int $color_vehiculo_id = null;
    public ?int $tipo_vehiculo_id = null;
    public ?int $tenencia_vehiculo_id = null;
    public bool $vehiculo_is_active = true;

    // --- Colecciones para los Selects ---
    public $tiposVehiculo, $marcasVehiculo, $coloresVehiculo, $tenenciasVehiculo;

    protected function messages()
    {
        return [
            '*.required' => 'Este campo es obligatorio.',
            'patente_letras.unique' => 'La patente ingresada ya existe para su empresa.',
            'ano_fabricacion.digits' => 'El año debe ser de 4 dígitos.',
            'ano_fabricacion.integer' => 'El año debe ser un número.',
            'ano_fabricacion.min' => 'El año de fabricación no parece ser válido.',
        ];
    }
    
    public function rulesFichaVehiculo()
    {
        return [
            'patente_letras' => [
                'required',
                'string',
                'min:2',
                'max:4',
                // Validación de unicidad compuesta para la patente (letras y números)
                Rule::unique('vehiculos')->where(function ($query) {
                    return $query->where('contratista_id', $this->contratistaId)
                                 ->where('patente_numeros', $this->patente_numeros)
                                 ->where('patente_letras', $this->patente_letras);
                })->ignore($this->vehiculoId),
            ],
            'patente_numeros' => 'required|string|min:2|max:4',
            'ano_fabricacion' => 'required|integer|digits:4|min:1950|max:' . (date('Y') + 1),
            'marca_vehiculo_id' => 'required|exists:marcas_vehiculo,id',
            'color_vehiculo_id' => 'required|exists:colores_vehiculo,id',
            'tipo_vehiculo_id' => 'required|exists:tipos_vehiculo,id',
            'tenencia_vehiculo_id' => 'nullable|exists:tenencias_vehiculo,id',
            'vehiculo_is_active' => 'boolean',
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
        
        // Cargar los catálogos para los selects del formulario
        $this->tiposVehiculo = TipoVehiculo::where('is_active', true)->orderBy('nombre')->get();
        $this->marcasVehiculo = MarcaVehiculo::where('is_active', true)->orderBy('nombre')->get();
        $this->coloresVehiculo = ColorVehiculo::where('is_active', true)->orderBy('nombre')->get();
        $this->tenenciasVehiculo = TenenciaVehiculo::where('is_active', true)->orderBy('nombre')->get();
    }
    
    private function resetFichaVehiculoFields()
    {
        $this->vehiculoId = null;
        $this->patente_letras = '';
        $this->patente_numeros = '';
        $this->ano_fabricacion = null;
        $this->marca_vehiculo_id = null;
        $this->color_vehiculo_id = null;
        $this->tipo_vehiculo_id = null;
        $this->tenencia_vehiculo_id = null;
        $this->vehiculo_is_active = true;
        $this->resetValidation();
    }

    public function abrirModalNuevoVehiculo()
    {
        if (!$this->unidadOrganizacionalId) {
             session()->flash('error', 'Error: El contexto de operación (Mandante - UO) no está definido.');
             return;
         }
        $this->resetFichaVehiculoFields();
        $this->showModalFichaVehiculo = true;
    }

    public function abrirModalEditarVehiculo($id)
    {
        $vehiculo = Vehiculo::where('id', $id)->where('contratista_id', $this->contratistaId)->first();

        if ($vehiculo) {
            $this->vehiculoId = $vehiculo->id;
            $this->patente_letras = $vehiculo->patente_letras;
            $this->patente_numeros = $vehiculo->patente_numeros;
            $this->ano_fabricacion = $vehiculo->ano_fabricacion;
            $this->marca_vehiculo_id = $vehiculo->marca_vehiculo_id;
            $this->color_vehiculo_id = $vehiculo->color_vehiculo_id;
            $this->tipo_vehiculo_id = $vehiculo->tipo_vehiculo_id;
            $this->tenencia_vehiculo_id = $vehiculo->tenencia_vehiculo_id;
            $this->vehiculo_is_active = $vehiculo->is_active;
            $this->showModalFichaVehiculo = true;
        } else {
            session()->flash('error', 'Vehículo no encontrado o no pertenece a su empresa.');
        }
    }

    public function guardarVehiculo()
    {
        if (!$this->unidadOrganizacionalId) {
            session()->flash('error', 'Error: El contexto de operación (Mandante - UO) no está definido para guardar.');
            $this->cerrarModalFichaVehiculo();
            return;
        }

        $validatedData = $this->validate($this->rulesFichaVehiculo());
        $validatedData['contratista_id'] = $this->contratistaId;
        // Transformar patentes a mayúsculas
        $validatedData['patente_letras'] = strtoupper($this->patente_letras);
        $validatedData['patente_numeros'] = strtoupper($this->patente_numeros);
        
        try {
            if ($this->vehiculoId) {
                $vehiculo = Vehiculo::find($this->vehiculoId);
                if ($vehiculo && $vehiculo->contratista_id == $this->contratistaId) {
                    $vehiculo->update($validatedData);
                    session()->flash('message', 'Ficha del vehículo actualizada correctamente.');
                } else {
                    session()->flash('error', 'Error al actualizar: Vehículo no encontrado.');
                }
            } else {
                Vehiculo::create($validatedData);
                session()->flash('message', 'Vehículo agregado correctamente.');
            }

            $this->cerrarModalFichaVehiculo();
        } catch (\Exception $e) {
            Log::error("Error al guardar vehículo: " . $e->getMessage());
            session()->flash('error', 'Ocurrió un error al guardar la ficha del vehículo. Verifique que la patente no esté duplicada.');
        }
    }
    
    public function cerrarModalFichaVehiculo()
    {
        $this->showModalFichaVehiculo = false;
        $this->resetFichaVehiculoFields();
    }
    
    public function toggleActivoVehiculo(Vehiculo $vehiculo)
    {
        if ($vehiculo && $vehiculo->contratista_id == $this->contratistaId) {
            $vehiculo->is_active = !$vehiculo->is_active;
            $vehiculo->save();
            session()->flash('message', 'Estado del vehículo cambiado.');
        }
    }

    public function eliminarVehiculo($id)
    {
        $vehiculo = Vehiculo::where('id', $id)->where('contratista_id', $this->contratistaId)->first();
        
        if ($vehiculo) {
            $vehiculo->delete();
            session()->flash('message', 'Vehículo eliminado correctamente.');
        } else {
            session()->flash('error', 'No se pudo eliminar el vehículo.');
        }
    }
    
    public function sortBy($field)
    {
        if ($this->sortByVehiculo === $field) {
            $this->sortDirectionVehiculo = $this->sortDirectionVehiculo === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirectionVehiculo = 'asc';
        }
        $this->sortByVehiculo = $field;
    }

    public function render()
    {
        $queryVehiculos = Vehiculo::query()->where('contratista_id', $this->contratistaId);

        if (!$this->unidadOrganizacionalId) {
            $queryVehiculos->whereRaw('1 = 0');
        }

        $queryVehiculos->when($this->searchVehiculo, function ($query) {
            $query->where(function ($q) {
                $q->where('patente_letras', 'like', '%' . $this->searchVehiculo . '%')
                  ->orWhere('patente_numeros', 'like', '%' . $this->searchVehiculo . '%')
                  ->orWhereHas('marcaVehiculo', function($subQuery){
                      $subQuery->where('nombre', 'like', '%' . $this->searchVehiculo . '%');
                  });
            });
        })
        ->with(['marcaVehiculo', 'tipoVehiculo'])
        ->orderBy($this->sortByVehiculo, $this->sortDirectionVehiculo);

        $vehiculosPaginados = $queryVehiculos->paginate(10, ['*'], 'vehiculosPage');

        return view('livewire.contratista.gestion-vehiculos', [
            'vehiculosPaginados' => $vehiculosPaginados
        ]);
    }
}