<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnidadOrganizacionalMandante extends Model
{
    use HasFactory;

    /**
     * El nombre de la tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'unidades_organizacionales_mandante';

    protected $fillable = [
        'mandante_id',
        'nombre_unidad',
        'codigo_unidad',
        'descripcion',
        'parent_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * El mandante al que pertenece esta unidad organizacional.
     */
    public function mandante(): BelongsTo
    {
        return $this->belongsTo(Mandante::class);
    }

    /**
     * La unidad organizacional padre (para jerarquías).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(UnidadOrganizacionalMandante::class, 'parent_id');
    }

    /**
     * Las unidades organizacionales hijas.
     */
    public function children(): HasMany
    {
        return $this->hasMany(UnidadOrganizacionalMandante::class, 'parent_id');
    }

    /**
     * Obtiene las vinculaciones de trabajadores asociadas a esta unidad organizacional.
     */
    public function trabajadorVinculaciones(): HasMany
    {
        return $this->hasMany(TrabajadorVinculacion::class, 'unidad_organizacional_mandante_id');
    }

    /**
     * Los contratistas habilitados para trabajar en esta unidad organizacional.
     */
    public function contratistasHabilitados(): BelongsToMany
    {
        return $this->belongsToMany(
            Contratista::class,
            'contratista_unidad_organizacional',
            'unidad_organizacional_mandante_id',
            'contratista_id'
        )
        ->using(ContratistaUnidadOrganizacional::class)
        ->withPivot('id', 'tipo_condicion_id');
    }

    /**
     * Los vehículos asignados a esta unidad organizacional.
     */
    public function vehiculosAsignados(): BelongsToMany
    {
        return $this->belongsToMany(
                Vehiculo::class,
                'vehiculo_asignaciones',
                'unidad_organizacional_mandante_id',
                'vehiculo_id'
            )
            ->using(VehiculoAsignacion::class)
            ->withPivot(['id', 'fecha_asignacion', 'fecha_desasignacion', 'is_active', 'motivo_desasignacion'])
            ->withTimestamps();
    }

    /**
     * Las maquinarias asignadas a esta unidad organizacional.
     */
    public function maquinariasAsignadas(): BelongsToMany
    {
        return $this->belongsToMany(
                Maquinaria::class,
                'maquinaria_asignaciones',
                'unidad_organizacional_mandante_id',
                'maquinaria_id'
            )
            ->using(MaquinariaAsignacion::class)
            ->withPivot(['id', 'fecha_asignacion', 'fecha_desasignacion', 'is_active', 'motivo_desasignacion'])
            ->withTimestamps();
    }

    // --- INICIO NUEVA RELACIÓN PARA EMBARCACIONES ---
    /**
     * Las embarcaciones asignadas a esta unidad organizacional.
     */
    public function embarcacionesAsignadas(): BelongsToMany
    {
        return $this->belongsToMany(
                Embarcacion::class,
                'embarcacion_asignaciones', // Nombre de la tabla pivote
                'unidad_organizacional_mandante_id',
                'embarcacion_id'
            )
            ->using(EmbarcacionAsignacion::class) // Modelo pivote personalizado
            ->withPivot(['id', 'fecha_asignacion', 'fecha_desasignacion', 'is_active', 'motivo_desasignacion'])
            ->withTimestamps();
    }
    // --- FIN NUEVA RELACIÓN ---

    /**
     * Helper para obtener todos los trabajadores (a través de vinculaciones) de esta unidad 
     * Y todas sus sub-unidades (recursivo)
     * Es importante cargar eficientemente las relaciones antes de llamar masivamente.
     */
    public function todosLosTrabajadoresEnJerarquiaViaVinculaciones(): \Illuminate\Support\Collection
    {
        $trabajadores = collect();

        // Carga la relación si aún no está cargada para la UO actual
        if (!$this->relationLoaded('trabajadorVinculaciones')) {
            $this->load('trabajadorVinculaciones.trabajador');
        }
        
        $trabajadores = $this->trabajadorVinculaciones->map(function ($vinculacion) {
            return $vinculacion->trabajador; // Asume que la relación 'trabajador' en TrabajadorVinculacion está definida
        })->filter()->unique('id')->values(); // filter() para eliminar nulos si trabajador no existe


        // Para los hijos, carga sus vinculaciones y trabajadores de forma eficiente.
        $childrenUOs = $this->children()->with('trabajadorVinculaciones.trabajador')->get();

        foreach ($childrenUOs as $child) {
            // La llamada recursiva ahora usará las relaciones ya cargadas del hijo.
            $trabajadores = $trabajadores->merge($child->todosLosTrabajadoresEnJerarquiaViaVinculaciones());
        }

        return $trabajadores->unique('id')->values(); // Asegura que los trabajadores sean únicos en la colección final
    }

    // --- INICIO BLOQUE NUEVO ---
    /**
     * Las reglas documentales que aplican a esta unidad organizacional.
     */
    public function reglasDocumentales(): BelongsToMany
    {
        return $this->belongsToMany(
            ReglaDocumental::class,
            'regla_documental_unidad_organizacional',
            'unidad_organizacional_mandante_id',
            'regla_documental_id'
        );
    }
    // --- FIN BLOQUE NUEVO ---
}