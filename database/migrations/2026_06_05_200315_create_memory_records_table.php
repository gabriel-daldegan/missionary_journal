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
        Schema::create('memory_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('author_user_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->foreignId('last_edited_by_user_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('type')->default('diary')->index();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->text('notes')->nullable();
            $table->date('experience_date')->nullable()->index();
            $table->date('period_start_date')->nullable()->index();
            $table->date('period_end_date')->nullable()->index();
            $table->string('location_name')->nullable();
            $table->json('people')->nullable();
            $table->string('source')->nullable();
            $table->json('source_metadata')->nullable();
            $table->timestamps();

            $table->index(
                ['tenant_id', 'type', 'experience_date'],
                'memory_records_tenant_type_experience_date_index'
            );
            $table->index(
                ['tenant_id', 'type', 'period_start_date', 'period_end_date'],
                'memory_records_tenant_type_period_dates_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memory_records');
    }
};
