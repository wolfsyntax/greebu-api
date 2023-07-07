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
        Schema::create('song_request_artists', function (Blueprint $table) {
            $table->foreignUuid('song_request_id')->index()->constrained();
            $table->foreignUuid('artist_id')->index()->constrained();
            $table->enum('request_status', ['pending', 'accepted', 'declined']); // customer -> artist

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('song_request_artists');
    }
};
