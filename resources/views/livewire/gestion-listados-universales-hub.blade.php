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
                    <p class="mb-6 text-lg">Aquí puede administrar los diferentes catálogos y listados maestros del sistema.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <a href="{{ route('gestion.documentos') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150"><h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Documentos</h5><p class="font-normal text-gray-700 dark:text-gray-400">Tipos de documentos genéricos.</p></a>
                        <a href="{{ route('gestion.rubros') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150"><h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Rubros</h5><p class="font-normal text-gray-700 dark:text-gray-400">Rubros de empresas.</p></a>
                        <a href="{{ route('gestion.tipos-empresa-legal') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150"><h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Tipos de Empresa</h5><p class="font-normal text-gray-700 dark:text-gray-400">Tipos legales de empresa.</p></a>
                        <a href="{{ route('gestion.nacionalidades') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150"><h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Nacionalidades</h5><p class="font-normal text-gray-700 dark:text-gray-400">Nacionalidades de trabajadores.</p></a>
                        <a href="{{ route('gestion.tipos-condicion-personal') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150"><h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Condiciones Personales</h5><p class="font-normal text-gray-700 dark:text-gray-400">Condiciones para trabajadores.</p></a>
                        <a href="{{ route('gestion.tipos-condicion') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150">
                            <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Tipos Condición (Empresa)</h5>
                            <p class="font-normal text-gray-700 dark:text-gray-400">Condiciones para empresas.</p>
                        </a>
                        <a href="{{ route('gestion.sexos') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150"><h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Sexos</h5><p class="font-normal text-gray-700 dark:text-gray-400">Sexos para perfiles.</p></a>
                        <a href="{{ route('gestion.estados-civiles') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150">
                            <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Estados Civiles</h5>
                            <p class="font-normal text-gray-700 dark:text-gray-400">Gestionar estados civiles.</p>
                        </a>
                        <a href="{{ route('gestion.etnias') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150">
                            <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Etnias / Pueblos Originarios</h5>
                            <p class="font-normal text-gray-700 dark:text-gray-400">Gestionar etnias y pueblos originarios.</p>
                        </a>
                        <a href="{{ route('gestion.niveles-educacionales') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150">
                            <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Niveles Educacionales</h5>
                            <p class="font-normal text-gray-700 dark:text-gray-400">Gestionar niveles de educación.</p>
                        </a>
                        <a href="{{ route('gestion.criterios-evaluacion') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150"><h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Criterios de Evaluación</h5><p class="font-normal text-gray-700 dark:text-gray-400">Criterios para validación.</p></a>
                        <a href="{{ route('gestion.sub-criterios') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150">
                            <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Sub-Criterios</h5>
                            <p class="font-normal text-gray-700 dark:text-gray-400">Sub-criterios para evaluación.</p>
                        </a>
                        <a href="{{ route('gestion.condiciones-fecha-ingreso') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150">
                            <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Condiciones Fecha Ingreso</h5>
                            <p class="font-normal text-gray-700 dark:text-gray-400">Condiciones por fecha de ingreso.</p>
                        </a>
                        <a href="{{ route('gestion.configuraciones-validacion') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150">
                            <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Configuraciones de Validación</h5>
                            <p class="font-normal text-gray-700 dark:text-gray-400">Flujos de validación.</p>
                        </a>
                        <a href="{{ route('gestion.textos-rechazo') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150"><h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Textos de Rechazo</h5><p class="font-normal text-gray-700 dark:text-gray-400">Plantillas para rechazos.</p></a>
                        <a href="{{ route('gestion.aclaraciones-criterio') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150"><h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Aclaraciones de Criterio</h5><p class="font-normal text-gray-700 dark:text-gray-400">Textos de ayuda para criterios.</p></a>
                        <a href="{{ route('gestion.observaciones-documento') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150"><h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Observaciones de Documento</h5><p class="font-normal text-gray-700 dark:text-gray-400">Plantillas para observaciones.</p></a>
                        <a href="{{ route('gestion.tipos-carga') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150"><h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Tipos de Carga</h5><p class="font-normal text-gray-700 dark:text-gray-400">Tipos de carga documental.</p></a>
                        <a href="{{ route('gestion.tipos-vencimiento') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150"><h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Tipos de Vencimiento</h5><p class="font-normal text-gray-700 dark:text-gray-400">Tipos de vencimiento.</p></a>
                        <a href="{{ route('gestion.tipos-entidad-controlable') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150"><h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Tipos de Entidad</h5><p class="font-normal text-gray-700 dark:text-gray-400">Entidades controlables.</p></a>
                        <a href="{{ route('gestion.rangos-cantidad-trabajadores') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150">
                            <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Rangos Cant. Trabajadores</h5>
                            <p class="font-normal text-gray-700 dark:text-gray-400">Rangos para cantidad de trabajadores.</p>
                        </a>
                        <a href="{{ route('gestion.mutualidades') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150">
                            <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Mutualidades</h5>
                            <p class="font-normal text-gray-700 dark:text-gray-400">Mutualidades de seguridad.</p>
                        </a>
                        <a href="{{ route('gestion.regiones') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150">
                            <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Regiones</h5>
                            <p class="font-normal text-gray-700 dark:text-gray-400">Regiones geográficas.</p>
                        </a>
                        <a href="{{ route('gestion.comunas') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150">
                            <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Comunas</h5>
                            <p class="font-normal text-gray-700 dark:text-gray-400">Comunas por región.</p>
                        </a>
                        <a href="{{ route('gestion.formatos-muestra') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150">
                            <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Formatos de Muestra</h5>
                            <p class="font-normal text-gray-700 dark:text-gray-400">Archivos PDF de muestra.</p>
                        </a>
                        <a href="{{ route('gestion.unidades-organizacionales-mandante') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150"><h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Unidades Organizacionales</h5><p class="font-normal text-gray-700 dark:text-gray-400">UOs por mandante.</p></a>
                        <a href="{{ route('gestion.cargos-mandante') }}" wire:navigate class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow hover:bg-gray-100 dark:hover:bg-gray-600 transition ease-in-out duration-150"><h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Cargos por Mandante</h5><p class="font-normal text-gray-700 dark:text-gray-400">Cargos por mandante.</p></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>