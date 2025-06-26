<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contratistas', function (Blueprint $table) {
            $table->id();

            // Información Básica de la Empresa
            $table->string('razon_social');
            $table->string('nombre_fantasia')->nullable();
            $table->string('rut')->unique(); // Ej: "76123456-7"

            // Dirección del Contratista
            $table->string('direccion_calle');
            $table->string('direccion_numero')->nullable();
            
            // ----- AQUÍ ESTÁ LA CLAVE: comuna_id -----
            $table->unsignedBigInteger('comuna_id'); // Columna para el ID de la comuna
            $table->foreign('comuna_id') // Definición de la clave foránea
                  ->references('id')->on('comunas')
                  ->onDelete('restrict'); // O 'set null' si prefieres y la columna es nullable
            // ----- FIN DE LA CLAVE -----

            // Contacto General de la Empresa Contratista
            $table->string('telefono_empresa')->nullable();
            $table->string('email_empresa')->unique();

            // Claves Foráneas a Tablas de Catálogo principales
            $table->foreignId('tipo_empresa_legal_id')->constrained('tipos_empresa_legal')->onDelete('restrict');
            $table->foreignId('rubro_id')->constrained('rubros')->onDelete('restrict');
            $table->foreignId('rango_cantidad_trabajadores_id')->nullable()->constrained('rangos_cantidad_trabajadores')->onDelete('set null');
            $table->foreignId('mutualidad_id')->nullable()->constrained('mutualidades')->onDelete('set null');

            // Administrador de Plataforma (Usuario en el sistema)
            $table->foreignId('admin_user_id')->nullable()->unique()->constrained('users')->onDelete('set null');

            // Datos Representante Legal
            $table->string('rep_legal_nombres');
            $table->string('rep_legal_apellido_paterno');
            $table->string('rep_legal_apellido_materno')->nullable();
            $table->string('rep_legal_rut')->nullable();
            $table->string('rep_legal_telefono')->nullable();
            $table->string('rep_legal_email')->nullable();

            // Tipo de Contratista
            $table->string('tipo_inscripcion')->nullable()->comment('Tipo: Contratista o Subcontratista');

            // Estado en el Sistema
            $table->boolean('is_active')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contratistas');
    }
};