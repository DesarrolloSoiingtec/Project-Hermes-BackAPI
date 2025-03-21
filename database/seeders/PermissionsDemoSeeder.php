<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\Auth\Person;
use App\Models\User;

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

        // Chat
        Permission::create(['guard_name' => 'api', 'name' => 'view_chat']);

        // Creación del rol Administrador y asignación de permisos
        $adminRole = Role::create(['guard_name' => 'api', 'name' => 'Administrador']);
        $adminRole->givePermissionTo(Permission::all());

        // Creación del rol Usuario y asignación de permisos de solo vista
        $userRole = Role::create(['guard_name' => 'api', 'name' => 'Usuario']);
        $userRole->givePermissionTo([
            'all',
        ]);

        $user = new User();
        //$user->id = 'f3fdbafe-5da8-49f9-9b1f-e6ad1dedfdb4';
        $user->email = 'admin@admin.com';
        $user->password = bcrypt('qwerty');
        $user->role_id = 1;
        $user->save();


        $person = new Person();
        $person->id = $user->id;
        $person->name = 'System';
        $person->document_number = '1098555293';
        $person->lastname = 'Admin';
        $person->phone = '3102225093';
        $person->legal_document_type_id = 13;
        $person->birthday = '1990-01-01';
        $person->gender = 'M';
        $person->save();

        $user->assignRole($adminRole);


    }

}






