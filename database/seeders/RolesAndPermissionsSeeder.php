<?php

namespace Database\Seeders;

use App\Constants\TenancyPermissionConstants;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        Permission::findOrCreate('create users');
        Permission::findOrCreate('update users');
        Permission::findOrCreate('delete users');
        Permission::findOrCreate('view users');

        Permission::findOrCreate('impersonate users');

        Permission::findOrCreate('create roles');
        Permission::findOrCreate('update roles');
        Permission::findOrCreate('delete roles');
        Permission::findOrCreate('view roles');

        Permission::findOrCreate('create products');
        Permission::findOrCreate('update products');
        Permission::findOrCreate('delete products');
        Permission::findOrCreate('view products');

        Permission::findOrCreate('create plans');
        Permission::findOrCreate('update plans');
        Permission::findOrCreate('delete plans');
        Permission::findOrCreate('view plans');

        Permission::findOrCreate('create subscriptions');
        Permission::findOrCreate('update subscriptions');
        Permission::findOrCreate('delete subscriptions');
        Permission::findOrCreate('view subscriptions');

        Permission::findOrCreate('create orders');
        Permission::findOrCreate('update orders');
        Permission::findOrCreate('delete orders');
        Permission::findOrCreate('view orders');

        Permission::findOrCreate('create one time products');
        Permission::findOrCreate('update one time products');
        Permission::findOrCreate('delete one time products');
        Permission::findOrCreate('view one time products');

        Permission::findOrCreate('create discounts');
        Permission::findOrCreate('update discounts');
        Permission::findOrCreate('delete discounts');
        Permission::findOrCreate('view discounts');

        Permission::findOrCreate('create blog posts');
        Permission::findOrCreate('update blog posts');
        Permission::findOrCreate('delete blog posts');
        Permission::findOrCreate('view blog posts');

        Permission::findOrCreate('create blog post categories');
        Permission::findOrCreate('update blog post categories');
        Permission::findOrCreate('delete blog post categories');
        Permission::findOrCreate('view blog post categories');

        Permission::findOrCreate('create roadmap items');
        Permission::findOrCreate('update roadmap items');
        Permission::findOrCreate('delete roadmap items');
        Permission::findOrCreate('view roadmap items');

        Permission::findOrCreate('create announcements');
        Permission::findOrCreate('update announcements');
        Permission::findOrCreate('delete announcements');
        Permission::findOrCreate('view announcements');

        Permission::findOrCreate('create tenants');
        Permission::findOrCreate('update tenants');
        Permission::findOrCreate('delete tenants');
        Permission::findOrCreate('view tenants');

        Permission::findOrCreate('view transactions');
        Permission::findOrCreate('update transactions');

        Permission::findOrCreate('update settings');

        Permission::findOrCreate('view stats');

        $role = Role::findOrCreate('admin');

        // give all permissions to admin that doesn't start with "tenancy:"
        $role->givePermissionTo(Permission::all()->filter(function ($permission) {
            return str_starts_with($permission->name, TenancyPermissionConstants::TENANCY_PERMISSION_PREFIX) === false;
        }));

        $this->multiTenancyRolesAndPermissions();
    }

    private function multiTenancyRolesAndPermissions()
    {
        $permissions = [
            TenancyPermissionConstants::PERMISSION_CREATE_SUBSCRIPTIONS,
            TenancyPermissionConstants::PERMISSION_UPDATE_SUBSCRIPTIONS,
            TenancyPermissionConstants::PERMISSION_DELETE_SUBSCRIPTIONS,
            TenancyPermissionConstants::PERMISSION_VIEW_SUBSCRIPTIONS,
            TenancyPermissionConstants::PERMISSION_CREATE_ORDERS,
            TenancyPermissionConstants::PERMISSION_UPDATE_ORDERS,
            TenancyPermissionConstants::PERMISSION_DELETE_ORDERS,
            TenancyPermissionConstants::PERMISSION_VIEW_ORDERS,
            TenancyPermissionConstants::PERMISSION_VIEW_TRANSACTIONS,
            TenancyPermissionConstants::PERMISSION_INVITE_MEMBERS,
            TenancyPermissionConstants::PERMISSION_MANAGE_TEAM,
            TenancyPermissionConstants::PERMISSION_UPDATE_TENANT_SETTINGS,
            TenancyPermissionConstants::PERMISSION_VIEW_ROLES,
            TenancyPermissionConstants::PERMISSION_CREATE_ROLES,
            TenancyPermissionConstants::PERMISSION_UPDATE_ROLES,
            TenancyPermissionConstants::PERMISSION_DELETE_ROLES,
        ];

        $tenancyPermissions = [];

        foreach ($permissions as $permission) {
            $tenancyPermissions[] = Permission::findOrCreate($permission);
        }

        $adminRole = Role::query()->firstOrCreate([
            'name' => TenancyPermissionConstants::ROLE_ADMIN,
            'is_tenant_role' => true,
        ], [
            'guard_name' => 'web',
        ]);
        $adminRole->givePermissionTo($tenancyPermissions);

        $userRole = Role::query()->firstOrCreate([
            'name' => TenancyPermissionConstants::ROLE_USER,
            'is_tenant_role' => true,
        ], [
            'guard_name' => 'web',
        ]);

        // assign any permissions that the user role should have here
    }
}
