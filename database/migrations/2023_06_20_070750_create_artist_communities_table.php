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
        Schema::create('artist_communities', function (Blueprint $table) {
            $table->foreignUuid('artist_id')->index()->constrained();
            $table->foreignUuid('communities_id')->index()->constrained(); // need to assign primary()

            $table->primary(['artist_id', 'communities_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artist_communities');
    }
};
