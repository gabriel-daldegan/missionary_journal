<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscription_versions', function (Blueprint $table) {
            $table->unsignedBigInteger('versionable_id')->change();
        });

        Schema::table('transaction_versions', function (Blueprint $table) {
            $table->unsignedBigInteger('versionable_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_versions', function (Blueprint $table) {
            $table->string('versionable_id')->change();
        });

        Schema::table('transaction_versions', function (Blueprint $table) {
            $table->string('versionable_id')->change();
        });
    }
};
