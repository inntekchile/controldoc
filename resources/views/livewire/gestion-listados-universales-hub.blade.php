<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestión de Listados Universales') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session()->has('success')) <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded dark:bg-green-700 dark:text-green-100 dark:border-green-600">{{ session('success') }}</div> @endif
            @if (session()->has('error')) <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded dark:bg-red-700 dark:text-red-100 dark:border-red-600">{{ session('error') }}</div> @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <p class="mb-6 text-lg">ESTE ES UN CLON DE LOS LISTADOS UNIVERSALES.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {{-- Primer grupo de listados generales --}}
                        <a href="{{ route('gestion.documentos') }}" wire:navigate class="listado-hub-card"><h5 class="listado-hub-title">Documentos</h5><p class="listado-hub-description">Tipos de documentos genéricos.</p></a>
                        <a href="{{ route('gestion.rubros') }}" wire:navigate class="listado-hub-card"><h5 class="listado-hub-title">Rubros</h5><p class="listado-hub-description">Rubros de empresas.</p></a>
                        <a href="{{ route('gestion.tipos-empresa-legal') }}" wire:navigate class="listado-hub-card"><h5 class="listado-hub-title">Tipos de Empresa</h5><p class="listado-hub-description">Tipos legales de empresa.</p></a>
                        <a href="{{ route('gestion.tipos-condicion') }}" wire:navigate class="listado-hub-card"><h5 class="listado-hub-title">Tipos Condición (Empresa)</h5><p class="listado-hub-description">Condiciones para empresas.</p></a>
                        <a href="{{ route('gestion.rangos-cantidad-trabajadores') }}" wire:navigate class="listado-hub-card"><h5 class="listado-hub-title">Rangos Cant. Trabajadores</h5><p class="listado-hub-description">Rangos para cantidad de trabajadores.</p></a>
                        <a href="{{ route('gestion.mutualidades') }}" wire:navigate class="listado-hub-card"><h5 class="listado-hub-title">Mutualidades</h5><p class="listado-hub-description">Mutualidades de seguridad.</p></a>
                        
                        {{-- Listados relacionados con Personas --}}
                        <a href="{{ route('gestion.nacionalidades') }}" wire:navigate class="listado-hub-card"><h5 class="listado-hub-title">Nacionalidades</h5><p class="listado-hub-description">Nacionalidades de trabajadores.</p></a>
                        <a href="{{ route('gestion.tipos-condicion-personal') }}" wire:navigate class="listado-hub-card"><h5 class="listado-hub-title">Condiciones Personales</h5><p class="listado-hub-description">Condiciones para trabajadores.</p></a>
                        <a href="{{ route('gestion.sexos') }}" wire:navigate class="listado-hub-card"><h5 class="listado-hub-title">Sexos</h5><p class="listado-hub-description">Sexos para perfiles.</p></a>
                        <a href="{{ route('gestion.estados-civiles') }}" wire:navigate class="listado-hub-card"><h5 class="listado-hub-title">Estados Civiles</h5><p class="listado-hub-description">Gestionar estados civiles.</p></a>
                        <a href="{{ route('gestion.etnias') }}" wire:navigate class="listado-hub-card"><h5 class="listado-hub-title">Etnias / Pueblos Originarios</h5><p class="listado-hub-description">Gestionar etnias y pueblos originarios.</p></a>
                        <a href="{{ route('gestion.niveles-educacionales') }}" wire:navigate class="listado-hub-card"><h5 class="listado-hub-title">Niveles Educacionales</h5><p class="listado-hub-description">Gestionar niveles de educación.</p></a>

                        {{-- Listados relacionados con Reglas y Validación --}}
                        <a href="{{ route('gestion.criterios-evaluacion') }}" wire:navigate class="listado-hub-card"><h5 class="listado-hub-title">Criterios de Evaluación</h5><p class="listado-hub-description">Criterios para validación.</p></a>
                        <a href="{{ route('gestion.sub-criterios') }}" wire:navigate class="listado-hub-card"><h5 class="listado-hub-title">Sub-Criterios</h5><p class="listado-hub-description">Sub-criterios para evaluación.</p></a>
                        <a href="{{ route('gestion.textos-rechazo') }}" wire:navigate class="listado-hub-card"><h5 class="listado-hub-title">Textos de Rechazo</h5><p class="listado-hub-description">Plantillas para rechazos.</p></a>
                        <a href="{{ route('gestion.aclaraciones-criterio') }}" wire:navigate class="listado-hub-card"><h5 class="listado-hub-title">Aclaraciones de Criterio</h5><p class="listado-hub-description">Textos de ayuda para criterios.</p></a>
                        <a href="{{ route('gestion.observaciones-documento') }}" wire:navigate class="listado-hub-card"><h5 class="listado-hub-title">Observaciones de Documento</h5><p class="listado-hub-description">Plantillas para observaciones.</p></a>
                        <a href="{{ route('gestion.condiciones-fecha-ingreso') }}" wire:navigate class="listado-hub-card"><h5 class="listado-hub-title">Condiciones Fecha Ingreso</h5><p class="listado-hub-description">Condiciones por fecha de ingreso.</p></a>
                        <a href="{{ route('gestion.configuraciones-validacion') }}" wire:navigate class="listado-hub-card"><h5 class="listado-hub-title">Configuraciones de Validación</h5><p class="listado-hub-description">Flujos de validación.</p></a>
                        <a href="{{ route('gestion.tipos-carga') }}" wire:navigate class="listado-hub-card"><h5 class="listado-hub-title">Tipos de Carga</h5><p class="listado-hub-description">Tipos de carga documental.</p></a>
                        <a href="{{ route('gestion.tipos-vencimiento') }}" wire:navigate class="listado-hub-card"><h5 class="listado-hub-title">Tipos de Vencimiento</h5><p class="listado-hub-description">Tipos de vencimiento.</p></a>
                        <a href="{{ route('gestion.formatos-muestra') }}" wire:navigate class="listado-hub-card"><h5 class="listado-hub-title">Formatos de Muestra</h5><p class="listado-hub-description">Archivos PDF de muestra.</p></a>
                        
                        {{-- Listados de Entidades Específicas --}}
                        <a href="{{ route('gestion.tipos-entidad-controlable') }}" wire:navigate class="listado-hub-card"><h5 class="listado-hub-title">Tipos de Entidad</h5><p class="listado-hub-description">Entidades controlables (Persona, Vehículo, etc.).</p></a>
                        
                        {{-- Listados para Vehículos --}}
                        <a href="{{ route('gestion.tipos-vehiculo') }}" wire:navigate class="listado-hub-card">
                            <h5 class="listado-hub-title">Tipos de Vehículo</h5>
                            <p class="listado-hub-description">Gestionar categorías de vehículos (ej. Camioneta, Auto).</p>
                        </a>
                        <a href="{{ route('gestion.marcas-vehiculo') }}" wire:navigate class="listado-hub-card">
                            <h5 class="listado-hub-title">Marcas de Vehículo</h5>
                            <p class="listado-hub-description">Gestionar marcas de vehículos (ej. Toyota, Ford).</p>
                        </a>
                        <a href="{{ route('gestion.colores-vehiculo') }}" wire:navigate class="listado-hub-card">
                            <h5 class="listado-hub-title">Colores de Vehículo</h5>
                            <p class="listado-hub-description">Gestionar colores de vehículos (ej. Rojo, Azul).</p>
                        </a>
                        <a href="{{ route('gestion.tenencias-vehiculo') }}" wire:navigate class="listado-hub-card">
                            <h5 class="listado-hub-title">Tenencias de Vehículo</h5>
                            <p class="listado-hub-description">Gestionar tipo de tenencia (ej. Propio, Leasing).</p>
                        </a>

                        {{-- Listados para Maquinaria --}}
                        <a href="{{ route('gestion.tipos-maquinaria') }}" wire:navigate class="listado-hub-card">
                            <h5 class="listado-hub-title">Tipos de Maquinaria</h5>
                            <p class="listado-hub-description">Gestionar tipos de maquinaria.</p>
                        </a>
                        
                        {{-- Listados para Embarcaciones --}}
                        <a href="{{ route('gestion.tipos-embarcacion') }}" wire:navigate class="listado-hub-card">
                            <h5 class="listado-hub-title">Tipos de Embarcación</h5>
                            <p class="listado-hub-description">Gestionar tipos de embarcación.</p>
                        </a>

                        {{-- Listados Geográficos y de Estructura Organizacional --}}
                        <a href="{{ route('gestion.regiones') }}" wire:navigate class="listado-hub-card"><h5 class="listado-hub-title">Regiones</h5><p class="listado-hub-description">Regiones geográficas.</p></a>
                        <a href="{{ route('gestion.comunas') }}" wire:navigate class="listado-hub-card"><h5 class="listado-hub-title">Comunas</h5><p class="listado-hub-description">Comunas por región.</p></a>
                        
                        {{-- ****** NUEVO ENLACE AÑADIDO AQUÍ ****** --}}
                        <a href="{{ route('gestion.unidades-organizacionales-mandante') }}" wire:navigate class="listado-hub-card">
                            <h5 class="listado-hub-title">Unidades Organizacionales</h5>
                            <p class="listado-hub-description">Gestionar UOs por Mandante (jerarquía).</p>
                        </a>
                        {{-- *************************************** --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>