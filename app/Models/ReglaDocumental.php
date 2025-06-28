<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// Models para relaciones directas
use App\Models\Mandante;
use App\Models\TipoEntidadControlable;
use App\Models\NombreDocumento;
use App\Models\TipoCondicion;
use App\Models\TipoCondicionPersonal;
use App\Models\CondicionFechaIngreso;
use App\Models\ObservacionDocumento;
use App\Models\FormatoDocumentoMuestra;
use App\Models\TipoVencimiento;
use App\Models\ConfiguracionValidacion;
use App\Models\ReglaDocumentalCriterio;

// Models para relaciones BelongsToMany
use App\Models\UnidadOrganizacionalMandante;
use App\Models\CargoMandante;
use App\Models\Nacionalidad;
use App\Models\TipoVehiculo;
use App\Models\TipoMaquinaria;
use App\Models\TipoEmbarcacion;
use App\Models\TenenciaVehiculo; // <-- AÑADIDO

class ReglaDocumental extends Model
{
    use HasFactory;

    protected $table = 'reglas_documentales';

    protected $fillable = [
        'mandante_id',
        'tipo_entidad_controlada_id',
        'nombre_documento_id',
        'valor_nominal_documento',
        'aplica_empresa_condicion_id',
        'aplica_persona_condicion_id',
        'condicion_fecha_ingreso_id',
        'fecha_comparacion_ingreso',
        'rut_especificos',
        'rut_excluidos',
        'observacion_documento_id',
        'formato_documento_id',
        'documento_relacionado_id',
        'tipo_vencimiento_id',
        'dias_validez_documento',
        'dias_aviso_vencimiento',
        'valida_emision',
        'valida_vencimiento',
        'configuracion_validacion_id',
        'restringe_acceso',
        'afecta_porcentaje_cumplimiento',
        'documento_es_perseguidor',
        'mostrar_historico_documento',
        'permite_ver_nacionalidad_trabajador',
        'permite_modificar_nacionalidad_trabajador',
        'permite_ver_fecha_nacimiento_trabajador',
        'permite_modificar_fecha_nacimiento_trabajador',
        'is_active',
    ];

    protected $casts = [
        'fecha_comparacion_ingreso' => 'date',
        'valida_emision' => 'boolean',
        'valida_vencimiento' => 'boolean',
        'restringe_acceso' => 'boolean',
        'afecta_porcentaje_cumplimiento' => 'boolean',
        'documento_es_perseguidor' => 'boolean',
        'mostrar_historico_documento' => 'boolean',
        'permite_ver_nacionalidad_trabajador' => 'boolean',
        'permite_modificar_nacionalidad_trabajador' => 'boolean',
        'permite_ver_fecha_nacimiento_trabajador' => 'boolean',
        'permite_modificar_fecha_nacimiento_trabajador' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function mandante(): BelongsTo { return $this->belongsTo(Mandante::class, 'mandante_id'); }
    public function tipoEntidadControlada(): BelongsTo { return $this->belongsTo(TipoEntidadControlable::class, 'tipo_entidad_controlada_id'); }
    public function nombreDocumento(): BelongsTo { return $this->belongsTo(NombreDocumento::class, 'nombre_documento_id'); }
    public function aplicaEmpresaCondicion(): BelongsTo { return $this->belongsTo(TipoCondicion::class, 'aplica_empresa_condicion_id'); }
    public function aplicaPersonaCondicion(): BelongsTo { return $this->belongsTo(TipoCondicionPersonal::class, 'aplica_persona_condicion_id'); }
    public function condicionFechaIngreso(): BelongsTo { return $this->belongsTo(CondicionFechaIngreso::class, 'condicion_fecha_ingreso_id'); }
    public function observacionDocumento(): BelongsTo { return $this->belongsTo(ObservacionDocumento::class, 'observacion_documento_id'); }
    public function formatoDocumento(): BelongsTo { return $this->belongsTo(FormatoDocumentoMuestra::class, 'formato_documento_id'); }
    public function documentoRelacionado(): BelongsTo { return $this->belongsTo(NombreDocumento::class, 'documento_relacionado_id'); }
    public function tipoVencimiento(): BelongsTo { return $this->belongsTo(TipoVencimiento::class, 'tipo_vencimiento_id'); }
    public function configuracionValidacion(): BelongsTo { return $this->belongsTo(ConfiguracionValidacion::class, 'configuracion_validacion_id'); }
    public function criterios(): HasMany { return $this->hasMany(ReglaDocumentalCriterio::class, 'regla_documental_id')->orderBy('orden'); }
    public function unidadesOrganizacionales(): BelongsToMany { return $this->belongsToMany(UnidadOrganizacionalMandante::class, 'regla_documental_unidad_organizacional', 'regla_documental_id', 'unidad_organizacional_mandante_id'); }
    public function cargosAplica(): BelongsToMany { return $this->belongsToMany(CargoMandante::class, 'regla_documental_cargo_mandante', 'regla_documental_id', 'cargo_mandante_id'); }
    public function nacionalidadesAplica(): BelongsToMany { return $this->belongsToMany(Nacionalidad::class, 'regla_documental_nacionalidad', 'regla_documental_id', 'nacionalidad_id'); }
    public function tiposVehiculoAplica(): BelongsToMany { return $this->belongsToMany(TipoVehiculo::class, 'regla_documental_tipo_vehiculo', 'regla_documental_id', 'tipo_vehiculo_id'); }
    public function tiposMaquinariaAplica(): BelongsToMany { return $this->belongsToMany(TipoMaquinaria::class, 'regla_documental_tipo_maquinaria', 'regla_documental_id', 'tipo_maquinaria_id'); }
    public function tiposEmbarcacionAplica(): BelongsToMany { return $this->belongsToMany(TipoEmbarcacion::class, 'regla_documental_tipo_embarcacion', 'regla_documental_id', 'tipo_embarcacion_id'); }

    // --- INICIO NUEVA RELACIÓN ---
    public function tenenciasAplica(): BelongsToMany
    {
        return $this->belongsToMany(
            TenenciaVehiculo::class,
            'regla_documental_tenencia_vehiculo',
            'regla_documental_id',
            'tenencia_vehiculo_id'
        );
    }
    // --- FIN NUEVA RELACIÓN ---
}