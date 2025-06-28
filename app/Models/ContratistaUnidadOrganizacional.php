<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContratistaUnidadOrganizacional extends Pivot
{
    use HasFactory;

    protected $table = 'contratista_unidad_organizacional';

    // Tu tabla no tiene timestamps, así que esto es correcto.
    public $timestamps = false;

    // Especifica que el 'id' es la clave primaria y es autoincremental
    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = [
        'contratista_id',
        'unidad_organizacional_mandante_id',
        'tipo_condicion_id',
    ];

    /**
     * Obtiene el contratista asociado.
     */
    public function contratista()
    {
        return $this->belongsTo(Contratista::class, 'contratista_id');
    }

    /**
     * Obtiene la unidad organizacional asociada.
     */
    public function unidadOrganizacional()
    {
        return $this->belongsTo(UnidadOrganizacionalMandante::class, 'unidad_organizacional_mandante_id');
    }

    /**
     * Obtiene el tipo de condición asociado (si existe).
     */
    public function tipoCondicion()
    {
        return $this->belongsTo(TipoCondicion::class, 'tipo_condicion_id');
    }

    /**
     * Los Tipos de Entidad Controlable asignados a esta específica vinculación Contratista-UO.
     * TEMPORALMENTE COMENTADO/NO UTILIZADO DESDE GestionContratistas para simplificar.
     */
    /*
    public function tiposEntidadControlable(): BelongsToMany
    {
        return $this->belongsToMany(
            TipoEntidadControlable::class,
            'contratista_uo_entidad', // Nombre de la tabla pivote intermedia
            'contratista_unidad_organizacional_id', // FK en la tabla pivote que referencia a esta tabla (contratista_unidad_organizacional)
            'tipo_entidad_controlable_id' // FK en la tabla pivote que referencia a la tabla TipoEntidadControlable
        );
    }
    */
}