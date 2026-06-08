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
        Schema::create('memory_record_tag', function (Blueprint $table) {
            $table->foreignId('memory_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('memory_tag_id')->constrained()->cascadeOnDelete();

            $table->primary(['memory_record_id', 'memory_tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memory_record_tag');
    }
};
