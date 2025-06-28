<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TipoEntidadControlable extends Model
{
    use HasFactory;

    protected $table = 'tipos_entidad_controlable';

    protected $fillable = [
        'nombre_entidad',
        'descripcion',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Los contratistas que tienen esta entidad como controlable.
     * Esta relación se definirá cuando modifiquemos Contratista para usar esta tabla
     * en lugar del campo JSON.
     */
    // public function contratistas(): BelongsToMany
    // {
    //     return $this->belongsToMany(Contratista::class, 'contratista_tipo_entidad', 'tipo_entidad_id', 'contratista_id');
    // }

    // Un TipoEntidadControlable podría estar asociado a muchos 'Requisitos de Documento' en el futuro
    // para indicar que un documento aplica a este tipo de entidad.
}