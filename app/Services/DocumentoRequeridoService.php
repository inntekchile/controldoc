<?php

namespace App\Services;

use App\Models\ReglaDocumental;
use App\Models\TipoEntidadControlable;
use App\Models\UnidadOrganizacionalMandante; // <-- Importante añadir este modelo
use Illuminate\Support\Facades\Log;

class DocumentoRequeridoService
{
    /**
     * Obtiene las reglas documentales activas para una entidad específica dentro de una Unidad Organizacional.
     * Considera reglas específicas de la UO, reglas heredadas de UOs padre, y reglas globales del Mandante.
     *
     * @param int $mandanteId
     * @param int $unidadOrganizacionalId
     * @param string $nombreEntidad (ej: 'EMPRESA', 'PERSONA', 'VEHICULO')
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getReglasParaEntidadEnUO(int $mandanteId, int $unidadOrganizacionalId, string $nombreEntidad)
    {
        Log::info("DocumentoRequeridoService: Buscando reglas para Mandante ID: {$mandanteId}, UO ID: {$unidadOrganizacionalId}, Entidad: {$nombreEntidad}");

        $tipoEntidad = TipoEntidadControlable::where('nombre_entidad', strtoupper($nombreEntidad))->first();

        if (!$tipoEntidad) {
            Log::warning("DocumentoRequeridoService: No se encontró el TipoEntidadControlable para '{$nombreEntidad}'.");
            return collect();
        }

        // --- INICIO DE LA LÓGICA MEJORADA (HERENCIA DE UO) ---

        // 1. Obtener la UO actual y todos sus ancestros (padres, abuelos, etc.).
        $uoActual = UnidadOrganizacionalMandante::find($unidadOrganizacionalId);
        $idsUoAplicables = [$unidadOrganizacionalId]; // Empezamos con la UO actual.

        if ($uoActual) {
            $parentId = $uoActual->parent_id;
            // 2. "Escalamos" el árbol mientras haya un padre.
            while ($parentId) {
                $idsUoAplicables[] = $parentId; // Añadimos el ID del padre a nuestra lista de UOs válidas.
                $ancestro = UnidadOrganizacionalMandante::find($parentId);
                $parentId = $ancestro ? $ancestro->parent_id : null; // Buscamos al siguiente padre.
            }
        }
        
        Log::info("DocumentoRequeridoService: UOs aplicables por herencia: " . implode(', ', $idsUoAplicables));

        // --- FIN DE LA LÓGICA MEJORADA ---


        $query = ReglaDocumental::query()
            ->where('is_active', true)
            ->where('mandante_id', $mandanteId)
            ->where('tipo_entidad_controlada_id', $tipoEntidad->id)
            ->where(function ($query) use ($idsUoAplicables) { // Pasamos el array de IDs de UO aplicables.
                // Condición 1: La regla está vinculada a la UO actual O a cualquiera de sus ancestros.
                $query->whereHas('unidadesOrganizacionales', function ($subQuery) use ($idsUoAplicables) {
                    $subQuery->whereIn('unidad_organizacional_mandante_id', $idsUoAplicables);
                })
                // Condición 2: O la regla es GLOBAL para el mandante (no está vinculada a NINGUNA UO).
                ->orWhereDoesntHave('unidadesOrganizacionales');
            })
            // Cargamos relaciones para mostrarlas en la vista eficientemente.
            ->with(['nombreDocumento', 'tipoVencimiento']);

        $reglas = $query->get();
        
        Log::info("DocumentoRequeridoService: Se encontraron {$reglas->count()} reglas después de aplicar la lógica de herencia.");

        return $reglas;
    }
}