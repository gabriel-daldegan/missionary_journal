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
        Schema::table('discount_codes', function (Blueprint $table) {
            $table->foreignId('referral_reward_id')->nullable()->after('discount_id')->constrained('referral_rewards')->onDelete('cascade');
            $table->boolean('is_referral_reward')->default(false)->after('referral_reward_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discount_codes', function (Blueprint $table) {
            $table->dropForeign(['referral_reward_id']);
            $table->dropColumn(['referral_reward_id', 'is_referral_reward']);
        });
    }
};
