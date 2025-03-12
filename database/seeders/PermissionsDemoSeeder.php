<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;

class PermissionsDemoSeeder extends Seeder
{

    public function run(): void
    {
        // Reinicia la caché de roles y permisos
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        //dashboard
        Permission::create(['guard_name' => 'api', 'name' => 'view_dashboard']);

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

        // Creación del rol Administrador y asignación de permisos
        $adminRole = Role::create(['guard_name' => 'api', 'name' => 'Administrador']);
        $adminRole->givePermissionTo(Permission::all());

        // Creación del rol Usuario y asignación de permisos de solo vista
        $userRole = Role::create(['guard_name' => 'api', 'name' => 'Usuario']);
        $userRole->givePermissionTo([
            'view_dashboard',
        ]);

        // Creación del usuario administrador y asignación del rol
        $user = User::factory()->create([
            'name'     => 'System',
            'email'    => 'admin@admin.com',
            'lastname' => 'Admin',
            'phone'    => '3102225093',
            'type_document' => 'CC',
            'document_number' => '1234567890',
            'birthday' => '1990-01-01',
            'gender' => 'M',
            'role_id'  => 1,
            'password' => bcrypt('qwerty'),
        ]);
        $user->assignRole($adminRole);

        // Creación del usuario con permisos de solo vista y asignación del rol
        $viewUser = User::factory()->create([
            'name'     => 'ViewOnly',
            'email'    => 'viewonly@user.com',
            'lastname' => 'User',
            'phone'    => '3102225094',
            'type_document' => 'CC',
            'document_number' => '0987654321',
            'birthday' => '1995-01-01',
            'gender' => 'F',
            'role_id'  => 2,
            'password' => bcrypt('qwerty'),
        ]);
        $viewUser->assignRole($userRole);

    }

}






