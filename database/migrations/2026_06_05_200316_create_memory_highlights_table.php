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
        Schema::create('memory_highlights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memory_record_id')->constrained()->cascadeOnDelete();
            $table->text('text');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['memory_record_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memory_highlights');
    }
};
