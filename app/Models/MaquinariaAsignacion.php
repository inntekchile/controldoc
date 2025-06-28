<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class MaquinariaAsignacion extends Pivot
{
    protected $table = 'maquinaria_asignaciones';

    public $timestamps = true;

    protected $casts = [
        'is_active' => 'boolean',
        'fecha_asignacion' => 'date',
        'fecha_desasignacion' => 'date',
    ];

    public function maquinaria()
    {
        return $this->belongsTo(Maquinaria::class, 'maquinaria_id');
    }

    public function unidadOrganizacionalMandante()
    {
        return $this->belongsTo(UnidadOrganizacionalMandante::class, 'unidad_organizacional_mandante_id');
    }
}