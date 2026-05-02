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
        Schema::table('discounts', function (Blueprint $table) {
            $table->boolean('is_enabled_for_all_plans')->default(false);
            $table->boolean('is_enabled_for_all_one_time_products')->default(false);
        });

        DB::table('discounts')
            ->whereNotIn('id', function ($query) {
                $query->select('discount_id')
                    ->from('discount_one_time_product');
            })
            ->update(['is_enabled_for_all_one_time_products' => true]);

        DB::table('discounts')
            ->whereNotIn('id', function ($query) {
                $query->select('discount_id')
                    ->from('discount_plan');
            })
            ->update(['is_enabled_for_all_plans' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->dropColumn(['is_enabled_for_all_plans', 'is_enabled_for_all_one_time_products']);
        });
    }
};
