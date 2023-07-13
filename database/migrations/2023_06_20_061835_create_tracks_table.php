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
        Schema::create('tracks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('album_id')->constrained();
            $table->foreignUuid('language_id')->constrained(table: 'supported_languages');
            $table->foreignUuid('genre_id')->constrained();

            $table->string('title');
            $table->unsignedBigInteger('duration');
            $table->boolean('is_playable')->nullable()->default(1);
            $table->string('file_path');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracks');
    }
};
