<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class EmbarcacionAsignacion extends Pivot
{
    protected $table = 'embarcacion_asignaciones';

    public $timestamps = true;

    protected $casts = [
        'is_active' => 'boolean',
        'fecha_asignacion' => 'date',
        'fecha_desasignacion' => 'date',
    ];

    public function embarcacion()
    {
        return $this->belongsTo(Embarcacion::class, 'embarcacion_id');
    }

    public function unidadOrganizacionalMandante()
    {
        return $this->belongsTo(UnidadOrganizacionalMandante::class, 'unidad_organizacional_mandante_id');
    }
}