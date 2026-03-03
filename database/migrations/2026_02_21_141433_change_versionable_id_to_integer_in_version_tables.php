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
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE subscription_versions ALTER COLUMN versionable_id TYPE bigint USING versionable_id::bigint');
            DB::statement('ALTER TABLE transaction_versions ALTER COLUMN versionable_id TYPE bigint USING versionable_id::bigint');
        } else {
            Schema::table('subscription_versions', function (Blueprint $table) {
                $table->unsignedBigInteger('versionable_id')->change();
            });

            Schema::table('transaction_versions', function (Blueprint $table) {
                $table->unsignedBigInteger('versionable_id')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE subscription_versions ALTER COLUMN versionable_id TYPE varchar(255)');
            DB::statement('ALTER TABLE transaction_versions ALTER COLUMN versionable_id TYPE varchar(255)');
        } else {
            Schema::table('subscription_versions', function (Blueprint $table) {
                $table->string('versionable_id')->change();
            });

            Schema::table('transaction_versions', function (Blueprint $table) {
                $table->string('versionable_id')->change();
            });
        }
    }
};
