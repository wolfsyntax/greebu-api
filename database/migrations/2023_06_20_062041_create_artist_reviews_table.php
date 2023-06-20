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
        Schema::create('artist_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reviewer_id')->constrained(table: 'profiles');
            $table->foreignUuid('artist_id')->constrained();
            $table->longText('reviews')->nullable();
            $table->boolean('is_review_anonymous');
            $table->boolean('is_artist_recommended');
            $table->unsignedInteger('star_rating')->default(1);

            $table->enum('status', ['active', 'deactive'])->nullable()->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artist_reviews');
    }
};
