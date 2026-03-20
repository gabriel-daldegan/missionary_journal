<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
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

        Permission::findOrCreate('view transactions');
        Permission::findOrCreate('update transactions');

        Permission::findOrCreate('update settings');

        Permission::findOrCreate('view stats');

        // this can be done as separate statements
        $role = Role::findOrCreate('admin');
        $role->givePermissionTo(Permission::all());

    }
}
