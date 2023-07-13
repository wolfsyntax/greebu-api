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
        Schema::create('event_artists', function (Blueprint $table) {
            $table->foreignUuid('event_id')->index()->constrained();
            $table->foreignUuid('artist_id')->index()->constrained();
            $table->primary(['event_id', 'artist_id']);

            $table->enum('status', ['pending', 'accepted', 'declined'])->nullable()->default('pending');
            $table->unsignedDecimal('talent_fee', 10, 2)->default(0.00);
            $table->timestamp('hired_at')->nullable();
            $table->longText('terms')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_artists');
    }
};
