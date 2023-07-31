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
        Schema::create('song_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('creator_id')->nullable()->constrained(table: 'profiles');
            // $table->foreignUuid('artist_id')->constrained();

            // $table->foreignUuid('artist_type_id')->nullable()->constrained();
            // $table->foreignUuid('genre_id')->nullable()->constrained();
            $table->foreignUuid('song_type_id')->nullable()->constrained();
            $table->foreignUuid('language_id')->nullable()->constrained(table: 'supported_languages');
            $table->foreignUuid('duration_id')->nullable()->constrained();
            $table->foreignUuid('purpose_id')->nullable()->constrained();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            // can approved a song request
            // can declined a song request
            // $table->enum('request_status', ['pending', 'accepted', 'declined']); // customer -> artist

            $table->string('sender')->nullable();
            $table->string('receiver')->nullable();
            $table->longText('user_story')->nullable();
            $table->enum('page_status', ['info', 'song', 'story', 'review',])->default('info'); // current form page

            // can validate artist song submission
            $table->boolean('verification_status')->nullable()->default(0); // artist output -> approval by admin
            $table->timestamp('delivery_date')->nullable();
            $table->unsignedInteger('estimate_date')->nullable()->default(3);
            $table->timestamp('approved_at')->nullable();

            // can request resubmission (can request a custom song to be edited)
            // can review song request

            $table->enum('approval_status', ['pending', 'inspecting', 'accepted', 'resubmission',])->nullable()->default('pending'); // artist submission to customer
            $table->timestamps();
            // can cancel song request
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('song_requests');
    }
};
