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
        Schema::create('artist_genres', function (Blueprint $table) {
            $table->foreignUuid('artist_id')->index()->constrained();
            $table->foreignUuid('genre_id')->index()->constrained();
            // $table->string('title', 255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artist_genres');
    }
};
