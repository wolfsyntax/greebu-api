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
        Schema::create('request_reviews', function (Blueprint $table) {
            $table->foreignUuid('artist_id')->index()->constrained();
            $table->foreignUuid('request_id')->index()->constrained(table: 'song_requests', column: 'id');
            $table->longText('description')->nullable();
            $table->primary(['artist_id', 'request_id']);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_reviews');
    }
};
