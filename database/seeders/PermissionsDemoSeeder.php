<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\Auth\Person;
use App\Models\User;
use App\Models\Auth\Medical;

class PermissionsDemoSeeder extends Seeder
{

    public function run(): void
    {
        // Reinicia la caché de roles y permisos
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        //dashboard
        Permission::create(['guard_name' => 'api', 'name' => 'all']);

        // Permisos para Usuarios
        Permission::create(['guard_name' => 'api', 'name' => 'view_user']);
        Permission::create(['guard_name' => 'api', 'name' => 'create_user']);
        Permission::create(['guard_name' => 'api', 'name' => 'edit_user']);
        Permission::create(['guard_name' => 'api', 'name' => 'delete_user']);

        // Permisos para Roles
        Permission::create(['guard_name' => 'api', 'name' => 'view_role']);
        Permission::create(['guard_name' => 'api', 'name' => 'create_role']);
        Permission::create(['guard_name' => 'api', 'name' => 'edit_role']);
        Permission::create(['guard_name' => 'api', 'name' => 'delete_role']);

        // Permisos para Empresa
        Permission::create(['guard_name' => 'api', 'name' => 'view_company']);
        Permission::create(['guard_name' => 'api', 'name' => 'create_company']);
        Permission::create(['guard_name' => 'api', 'name' => 'edit_company']);

        // Permisos para siau -> sancionados
        Permission::create(['guard_name' => 'api', 'name' => 'view_sanctioned']);
        Permission::create(['guard_name' => 'api', 'name' => 'create_sanctioned']);
        Permission::create(['guard_name' => 'api', 'name' => 'edit_sanctioned']);
        Permission::create(['guard_name' => 'api', 'name' => 'delete_sanctioned']);
        Permission::create(['guard_name' => 'api', 'name' => 'send_reminders']);

        // Permisos para siau -> parametros
        Permission::create(['guard_name' => 'api', 'name' => 'view_siau_parameter']);
        Permission::create(['guard_name' => 'api', 'name' => 'create_siau_parameter']);
        Permission::create(['guard_name' => 'api', 'name' => 'edit_siau_parameter']);
        Permission::create(['guard_name' => 'api', 'name' => 'delete_siau_parameter']);

        // Permisos para siau -> reportes
        Permission::create(['guard_name' => 'api', 'name' => 'view_siau_report']);
        Permission::create(['guard_name' => 'api', 'name' => 'create_siau_report']);
        Permission::create(['guard_name' => 'api', 'name' => 'edit_siau_report']);
        Permission::create(['guard_name' => 'api', 'name' => 'delete_siau_report']);

        // Permisos para Sucursales
        Permission::create(['guard_name' => 'api', 'name' => 'view_branch']);
        Permission::create(['guard_name' => 'api', 'name' => 'create_branch']);
        Permission::create(['guard_name' => 'api', 'name' => 'edit_branch']);

        // Permisos para Especialidades
        Permission::create(['guard_name' => 'api', 'name' => 'view_specialty']);
        Permission::create(['guard_name' => 'api', 'name' => 'create_specialty']);
        Permission::create(['guard_name' => 'api', 'name' => 'edit_specialty']);
        Permission::create(['guard_name' => 'api', 'name' => 'delete_specialty']);

        // Permisos para Servicios
        Permission::create(['guard_name' => 'api', 'name' => 'view_services']);
        Permission::create(['guard_name' => 'api', 'name' => 'create_services']);
        Permission::create(['guard_name' => 'api', 'name' => 'edit_services']);
        Permission::create(['guard_name' => 'api', 'name' => 'delete_services']);

        // Permisos para Conceptos de Servicio
        Permission::create(['guard_name' => 'api', 'name' => 'view_service_concepts']);
        Permission::create(['guard_name' => 'api', 'name' => 'create_service_concepts']);
        Permission::create(['guard_name' => 'api', 'name' => 'edit_service_concepts']);
        Permission::create(['guard_name' => 'api', 'name' => 'delete_service_concepts']);

        // Permisos para Contratos
        Permission::create(['guard_name' => 'api', 'name' => 'view_apb']);
        Permission::create(['guard_name' => 'api', 'name' => 'create_apb']);
        Permission::create(['guard_name' => 'api', 'name' => 'edit_apb']);
        Permission::create(['guard_name' => 'api', 'name' => 'delete_apb']);
        // Permisos para convenios
        Permission::create(['guard_name' => 'api', 'name' => 'view_contratos']);
        Permission::create(['guard_name' => 'api', 'name' => 'create_contratos']);
        Permission::create(['guard_name' => 'api', 'name' => 'edit_contratos']);
        Permission::create(['guard_name' => 'api', 'name' => 'delete_contratos']);

        // Chat
        Permission::create(['guard_name' => 'api', 'name' => 'view_chat']);
        Permission::create(['guard_name' => 'api', 'name' => 'view_profile']);


        // Creación del rol Administrador y asignación de permisos
        $adminRole = Role::create(['guard_name' => 'api', 'name' => 'Administrador']);
        $adminRole->givePermissionTo(Permission::all());

        // Creación del rol Usuario y asignación de permisos de solo vista
        $userRole = Role::create(['guard_name' => 'api', 'name' => 'Usuario']);
        $userRole->givePermissionTo([
            'all',
        ]);

        $person = new Person();
        $person->name = 'System';
        $person->document_number = '1098555293';
        $person->lastname = 'Admin';
        $person->phone = '3102225093';
        $person->legal_document_type_id = 13;
        $person->birthday = '1990-01-01';
        $person->gender = 'M';
        $person->save();

        $user = new User();
        //$user->id = 'f3fdbafe-5da8-49f9-9b1f-e6ad1dedfdb4';
        $user->id = $person->id;
        $user->email = 'admin@admin.com';
        $user->password = bcrypt('qwerty');
        $user->role_id = 1;
        $user->save();

        $user->assignRole($adminRole);
    }
}
