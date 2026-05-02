<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $indexes = Schema::getIndexes('roles');

        Schema::table('roles', function (Blueprint $table) use ($indexes) {
            $table->boolean('is_tenant_role')->default(false);
            $table->foreignId('tenant_id')->nullable()->constrained('tenants');

            // index name is inside the 'name' field
            foreach ($indexes as $index) {
                if ($index['name'] === 'roles_name_guard_name_unique') {
                    $table->dropUnique('roles_name_guard_name_unique');
                    break;
                }
            }

            $table->unique(['tenant_id', 'name', 'guard_name'], 'roles_tenant_name_guard_name_unique');
        });

        DB::table('roles')
            ->where('name', 'like', 'tenancy:%')
            ->update([
                'name' => DB::raw("REPLACE(name, 'tenancy:', '')"),
                'tenant_id' => null,
                'is_tenant_role' => true,
            ]);

        Schema::table('permissions', function (Blueprint $table) {
            $table->boolean('is_tenant_permission')->default(false);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = DB::getDriverName();

        $concat = match ($connection) {
            'pgsql' => "'tenancy:' || name",
            default => "CONCAT('tenancy:', name)",
        };

        DB::table('roles')
            ->where('is_tenant_role', true)
            ->update([
                'name' => DB::raw($concat),
            ]);

        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropUnique('roles_tenant_name_guard_name_unique');
            $table->dropColumn('is_tenant_role');
            $table->dropColumn('tenant_id');

            $table->unique(['name', 'guard_name'], 'roles_name_guard_name_unique');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('is_tenant_permission');
        });
    }
};
