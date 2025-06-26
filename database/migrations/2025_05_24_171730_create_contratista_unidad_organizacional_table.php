<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('contratista_unidad_organizacional', function (Blueprint $table) {
            $table->foreignId('contratista_id')
                  ->constrained('contratistas')
                  ->onDelete('cascade'); // Si se borra el contratista, se borra esta vinculación.

            $table->foreignId('unidad_organizacional_mandante_id', 'cuo_unidad_org_mandante_id_foreign') // Nombre corto para el índice de la FK
                  ->constrained('unidades_organizacionales_mandante', 'id', 'cuo_unidad_org_mandante_constrained') // Nombre corto para el índice de la restricción
                  ->onDelete('cascade'); // Si se borra la unidad organizacional, se borra esta vinculación.

            // Clave primaria compuesta para asegurar unicidad y optimizar búsquedas
            $table->primary(['contratista_id', 'unidad_organizacional_mandante_id'], 'pk_contratista_unidad_org');

            // $table->timestamps(); // Opcional: si necesitas saber cuándo se hizo la vinculación
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('contratista_unidad_organizacional');
    }
};