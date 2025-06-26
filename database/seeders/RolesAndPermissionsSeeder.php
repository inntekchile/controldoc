<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User; // Para asignar un rol a un usuario de ejemplo

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear Permisos (ejemplos iniciales, podemos añadir más después)
        // Permisos para gestión de la plataforma por ASEM
        Permission::firstOrCreate(['name' => 'manage platform settings', 'guard_name' => 'web']); // guard_name 'web' es el default para sesiones web

        // Permisos para Mandantes
        Permission::firstOrCreate(['name' => 'define document requirements', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view contractor compliance', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage own company users', 'guard_name' => 'web']); // Mandante gestiona sus propios usuarios

        // Permisos para Contratistas
        Permission::firstOrCreate(['name' => 'upload documents', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view own document status', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage own company employees', 'guard_name' => 'web']); // Contratista gestiona sus trabajadores
        Permission::firstOrCreate(['name' => 'manage own contractor users', 'guard_name' => 'web']); // Contratista gestiona sus propios usuarios de plataforma


        // Permisos para ASEM (Validación y Administración)
        Permission::firstOrCreate(['name' => 'validate documents', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage mandantes', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage contratistas', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage all users', 'guard_name' => 'web']); // ASEM gestiona todos los usuarios
        Permission::firstOrCreate(['name' => 'access asem dashboard', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage universal lists', 'guard_name' => 'web']); // Para gestionar rubros, tipos_condicion, etc.

        // Crear Roles
        $roleAsemAdmin = Role::firstOrCreate(['name' => 'ASEM_Admin', 'guard_name' => 'web']);
        $roleAsemValidator = Role::firstOrCreate(['name' => 'ASEM_Validator', 'guard_name' => 'web']);
        $roleMandanteAdmin = Role::firstOrCreate(['name' => 'Mandante_Admin', 'guard_name' => 'web']);
        // Podríamos tener un rol Mandante_User si hay diferentes niveles dentro del mandante
        $roleContratistaAdmin = Role::firstOrCreate(['name' => 'Contratista_Admin', 'guard_name' => 'web']);
        // Podríamos tener un rol Contratista_Uploader si hay diferentes niveles


        // Asignar Permisos a Roles
        $roleAsemAdmin->givePermissionTo(Permission::all()); // ASEM Admin tiene todos los permisos

        $roleAsemValidator->givePermissionTo([
            'validate documents',
            'access asem dashboard',
            // Podría tener permisos de visualización sobre mandantes/contratistas sin poder editarlos
            'view contractor compliance', // Para ver el estado general
        ]);

        $roleMandanteAdmin->givePermissionTo([
            'define document requirements',
            'view contractor compliance',
            'manage own company users',
        ]);

        $roleContratistaAdmin->givePermissionTo([
            'upload documents',
            'view own document status',
            'manage own company employees',
            'manage own contractor users',
        ]);

        // Crear un usuario ASEM Admin de ejemplo (OPCIONAL AQUÍ, MEJOR EN UserSeeder o DatabaseSeeder)
        // Comentado por ahora, ya que crearemos usuarios en el siguiente paso de "Seeders generales"
        /*
        $asemUser = User::firstOrCreate(
            ['email' => 'admin@asem.com'],
            [
                'name' => 'Admin ASEM',
                'password' => bcrypt('password'), // Cambiar en producción
                'user_type' => 'asem',
                'is_active' => true,
            ]
        );
        $asemUser->assignRole($roleAsemAdmin);
        */

        $this->command->info('Roles y Permisos creados y asignados exitosamente.');
    }
}