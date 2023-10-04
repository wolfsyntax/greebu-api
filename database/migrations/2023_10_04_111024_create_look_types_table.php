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
        Schema::create('look_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_id')->index()->constrained();
            //$table->foreignUuid('genre_id')->index()->constrained();
            $table->string('look_type', 255)->nullable();
            $table->string('look_for', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('look_types');
    }
};
