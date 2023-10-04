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
        Schema::create('artist_proposals', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('artist_id')->index()->constrained();
            $table->foreignUuid('event_id')->index()->constrained();
            $table->bigInteger('total_member')->nullable()->default(1);
            $table->longText('cover_letter')->nullable();
            $table->string('sample_song')->nullable()->default('');
            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artist_proposals');
    }
};
