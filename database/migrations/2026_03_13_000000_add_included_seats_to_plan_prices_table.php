<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_prices', function (Blueprint $table) {
            $table->unsignedInteger('included_seats')->default(0)->after('type');
            $table->unsignedInteger('extra_seat_price')->default(0)->after('included_seats');
        });
    }

    public function down(): void
    {
        Schema::table('plan_prices', function (Blueprint $table) {
            $table->dropColumn(['included_seats', 'extra_seat_price']);
        });
    }
};
