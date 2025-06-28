<?php

namespace App\Livewire\Contratista;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\User; 
use App\Models\Contratista; 
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\Log;

class PanelOperacion extends Component
{
    public $vinculacionesDisponibles;
    
    #[Url]
    public $vinculacionSeleccionada = null; 

    public $mandanteContextoId = null;
    public $unidadOrganizacionalContextoId = null;
    public $nombreMandanteContexto = '';
    public $nombreUnidadContexto = '';
    public $tiposEntidadPermitidosContextoActual = [];

    #[Url(as: 'pestaña')]
    public $pestañaActiva = '';

    public function mount()
    {
        $this->cargarVinculaciones(); 

        if ($this->vinculacionSeleccionada && $this->vinculacionesDisponibles && $this->vinculacionesDisponibles->isNotEmpty()) {
            $this->updatedVinculacionSeleccionada($this->vinculacionSeleccionada);
        }

        if (empty($this->pestañaActiva) && request()->has('tab_inicial')) {
            $tabInicial = request()->query('tab_inicial');
            $pestanasConocidas = ['mi_ficha', 'documentos_empresa', 'trabajadores', 'vehiculos', 'maquinaria', 'embarcaciones'];
            if (in_array($tabInicial, $pestanasConocidas)) {
                $this->pestañaActiva = $tabInicial;
            }
        }
        
        if ($this->vinculacionSeleccionada && empty($this->pestañaActiva) && !empty($this->tiposEntidadPermitidosContextoActual)) {
             $this->establecerPestanaActivaPorDefecto();
        } elseif (empty($this->vinculacionSeleccionada) && $this->pestañaActiva !== 'mi_ficha') {
             $this->pestañaActiva = 'mi_ficha';
        } else if (empty($this->vinculacionSeleccionada) && empty($this->pestañaActiva)) {
             $this->pestañaActiva = 'mi_ficha';
        }
    }

    public function cargarVinculaciones()
    {
        Log::info("PanelOperacion - Iniciando cargarVinculaciones()");
        $user = Auth::user();
        
        $data = ['vinculaciones_operables' => collect()];

        if ($user && isset($user->user_type) && strtolower($user->user_type) === 'contratista' && !empty($user->contratista_id)) { 
            Log::info("PanelOperacion - Usuario es contratista con ID: {$user->contratista_id}");
            $contratista = $user->contratista;

            if ($contratista) {
                Log::info("PanelOperacion - Contratista encontrado: {$contratista->razon_social}");
                $unidadesAsignadas = $contratista->unidadesOrganizacionalesMandante()
                                                ->with(['mandante.tiposEntidadControlable', 'mandante:id,razon_social'])
                                                ->get();
                
                Log::info("PanelOperacion - Unidades Asignadas recuperadas (pre-formato):", $unidadesAsignadas->map(fn($u)=>"ID:".$u->id .', Nombre:'.$u->nombre_unidad . ', MandanteID: ' . $u->mandante_id)->toArray());

                $vinculacionesFormateadas = collect();
                if ($unidadesAsignadas->isNotEmpty()) {
                    foreach ($unidadesAsignadas as $unidadOrg) {
                        $mandante = $unidadOrg->mandante; 
                        if ($mandante) {
                            Log::info("PanelOperacion - Procesando UO: {$unidadOrg->nombre_unidad} del Mandante: {$mandante->razon_social}");
                            $vinculacionesFormateadas->push([
                                'id_seleccion' => $unidadOrg->id,
                                'texto_visible' => $mandante->razon_social . ' - ' . $unidadOrg->nombre_unidad,
                                'mandante_id' => $mandante->id,
                                'unidad_organizacional_mandante_id' => $unidadOrg->id,
                                'mandante_razon_social' => $mandante->razon_social,
                                'unidad_organizacional_nombre' => $unidadOrg->nombre_unidad,
                                'tipos_entidad_permitidos' => $mandante->tiposEntidadControlable
                                                                    ->pluck('nombre_entidad')
                                                                    ->map(fn($nombre) => strtoupper($nombre))
                                                                    ->unique()
                                                                    ->values()
                                                                    ->toArray()
                            ]);
                        } else {
                             Log::warning("PanelOperacion - UO ID: {$unidadOrg->id} ({$unidadOrg->nombre_unidad}) no tiene un mandante asociado o no se cargó correctamente la relación 'mandante'.", ['uo_data' => $unidadOrg->toArray()]);
                        }
                    }
                    $data['vinculaciones_operables'] = $vinculacionesFormateadas->sortBy('texto_visible')->values();
                } else {
                    Log::info("PanelOperacion - No se encontraron unidades asignadas para el contratista.");
                }
            } else {
                 Log::warning("PanelOperacion - No se pudo cargar el modelo Contratista para el user_id: {$user->id} y contratista_id: {$user->contratista_id}");
            }
        } else {
            Log::warning("PanelOperacion - CONDICIÓN IF FALLÓ: El usuario no es considerado contratista válido o no tiene contratista_id.", ['user_id' => $user?->id, 'user_type' => $user?->user_type, 'contratista_id' => $user?->contratista_id]);
        }
        
        $this->vinculacionesDisponibles = $data['vinculaciones_operables'];
        
        // ****** CORRECCIÓN EN ESTA LÍNEA DE LOG ******
        Log::info("PanelOperacion - cargarVinculaciones() FINALIZADO.", ['vinculaciones_count' => $this->vinculacionesDisponibles->count()]);
        // **********************************************
    }

    public function updatedVinculacionSeleccionada($value)
    {
        if (empty($value)) {
            $this->resetContextoParcial();
            return;
        }

        if (is_null($this->vinculacionesDisponibles)) {
            $this->cargarVinculaciones(); 
        }
        if (!$this->vinculacionesDisponibles instanceof \Illuminate\Support\Collection) {
            $this->vinculacionesDisponibles = collect($this->vinculacionesDisponibles);
        }

        $vinculacion = $this->vinculacionesDisponibles->firstWhere('id_seleccion', (int) $value);

        if ($vinculacion) {
            $this->mandanteContextoId = $vinculacion['mandante_id'];
            $this->unidadOrganizacionalContextoId = $vinculacion['unidad_organizacional_mandante_id'];
            $this->nombreMandanteContexto = $vinculacion['mandante_razon_social'];
            $this->nombreUnidadContexto = $vinculacion['unidad_organizacional_nombre'];
            $this->tiposEntidadPermitidosContextoActual = $vinculacion['tipos_entidad_permitidos'];
            
            $mapPestañaEntidad = $this->getMapPestanaEntidad();
            $entidadDePestanaActual = $mapPestañaEntidad[$this->pestañaActiva] ?? null;

            if ($this->pestañaActiva !== 'mi_ficha' && ($entidadDePestanaActual === null || !in_array($entidadDePestanaActual, $this->tiposEntidadPermitidosContextoActual))) {
                $this->establecerPestanaActivaPorDefecto();
            } elseif (empty($this->pestañaActiva)) {
                $this->establecerPestanaActivaPorDefecto();
            }

        } else {
            Log::warning('updatedVinculacionSeleccionada: No se encontró la vinculación para el valor: ' . $value, ['disponibles' => $this->vinculacionesDisponibles->toArray()]);
            $this->resetContextoParcial();
        }
    }
    
    protected function establecerPestanaActivaPorDefecto()
    {
        $this->pestañaActiva = ''; 
        if (!empty($this->tiposEntidadPermitidosContextoActual)) { 
            if (in_array('PERSONA', $this->tiposEntidadPermitidosContextoActual)) {
                $this->pestañaActiva = 'trabajadores';
            } elseif (in_array('VEHICULO', $this->tiposEntidadPermitidosContextoActual)) {
                $this->pestañaActiva = 'vehiculos';
            } elseif (in_array('MAQUINARIA', $this->tiposEntidadPermitidosContextoActual)) {
                $this->pestañaActiva = 'maquinaria';
            } elseif (in_array('EMBARCACION', $this->tiposEntidadPermitidosContextoActual)) {
                $this->pestañaActiva = 'embarcaciones';
            } elseif (in_array('EMPRESA', $this->tiposEntidadPermitidosContextoActual)) {
                $this->pestañaActiva = 'documentos_empresa';
            } else {
                $this->pestañaActiva = 'mi_ficha'; 
            }
        } else {
             $this->pestañaActiva = 'mi_ficha'; 
        }
        Log::info("Pestaña activa establecida por defecto a: {$this->pestañaActiva}");
    }

    public function resetContextoParcial()
    {
        $this->mandanteContextoId = null;
        $this->unidadOrganizacionalContextoId = null;
        $this->nombreMandanteContexto = '';
        $this->nombreUnidadContexto = '';
        $this->tiposEntidadPermitidosContextoActual = [];
    }

    protected function getMapPestanaEntidad(): array
    {
        return [
            'trabajadores' => 'PERSONA',
            'vehiculos' => 'VEHICULO', // <-- AÑADIR ESTA LÍNEA
            'maquinaria' => 'MAQUINARIA',
            'embarcaciones' => 'EMBARCACION',
            'documentos_empresa' => 'EMPRESA',
        ];
    }

    public function seleccionarPestaña(string $nombrePestaña)
    {
        Log::info("seleccionarPestaña: Intentando seleccionar {$nombrePestaña}");
        $mapPestañaEntidad = $this->getMapPestanaEntidad();
        $entidadRequerida = $mapPestañaEntidad[$nombrePestaña] ?? null;

        if ($entidadRequerida && !$this->vinculacionSeleccionada) {
            Log::info("seleccionarPestaña: Se requiere vinculación para {$nombrePestaña} pero no hay ninguna seleccionada.");
            return;
        }

        if ($nombrePestaña === 'mi_ficha' || ($entidadRequerida && !empty($this->tiposEntidadPermitidosContextoActual) && in_array($entidadRequerida, $this->tiposEntidadPermitidosContextoActual))) {
            $this->pestañaActiva = $nombrePestaña;
            Log::info("seleccionarPestaña: Pestaña activa cambiada a {$this->pestañaActiva}");
        } else {
             Log::info("seleccionarPestaña: No se pudo cambiar a {$nombrePestaña}. Entidad requerida: {$entidadRequerida}. Permitidas: ", $this->tiposEntidadPermitidosContextoActual);
        }
    }

    public function render()
    {
        $mapPestañaEntidad = $this->getMapPestanaEntidad();
        if (array_key_exists($this->pestañaActiva, $mapPestañaEntidad) && !$this->vinculacionSeleccionada) {
            if ($this->pestañaActiva !== 'mi_ficha') { 
                 Log::info("Render: Reseteando pestaña activa porque requiere contexto y no hay vinculación seleccionada.");
                 $this->pestañaActiva = '';
            }
        }

        return view('livewire.contratista.panel-operacion')
                ->layout('layouts.app');
    }
}