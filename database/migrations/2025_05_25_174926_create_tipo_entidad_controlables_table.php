<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_entidad_controlable', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_entidad')->unique(); // Ej: "Empresa", "Trabajador", "Vehículo", "Maquinaria"
            $table->text('descripcion')->nullable();   // Descripción de qué abarca este tipo de entidad
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_entidad_controlable');
    }
};