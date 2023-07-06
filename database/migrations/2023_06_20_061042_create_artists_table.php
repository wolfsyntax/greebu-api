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
        Schema::create('artists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('profile_id')->constrained();
            $table->foreignUuid('artist_type_id')->constrained();

            $table->string('youtube_channel')->nullable()->default('');
            $table->string('twitter_username')->nullable()->default('');
            $table->string('instagram_username')->nullable()->default('');
            $table->string('spotify_profile')->nullable()->default('');
            $table->unsignedDecimal('professional_fee')->nullable()->default(0.00);
            $table->boolean('is_hourly')->nullable()->default(0)->comment('1 - hourly, 0 - by set');
            $table->boolean('isAccepting_request')->nullable()->default(false);
            $table->unsignedBigInteger('set_played')->nullable()->default(1)->comment('songs to be played');
            $table->timestamp('deactivated_at')->nullable();
            $table->boolean('is_freeloader')->default(false)->comment('true - not required subscription (company artist), false - required subscription');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artists');
    }
};
