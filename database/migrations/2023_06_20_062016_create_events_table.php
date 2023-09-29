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
        Schema::create('events', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('organizer_id')->constrained()->comment('Event Creator');

            $table->string('cover_photo');

            $table->foreignUuid('event_types_id')->constrained()->comment('Event Type');

            $table->string('event_name')->comment('Name of Event');
            // $table->text('location')->nullable()->comment('Location');
            $table->boolean('audience')->default(true)->comment('Audience');

            $table->date('start_date')->comment('Start Date');
            $table->date('end_date')->comment('End Date');

            $table->time('start_time')->comment('Start Time');
            $table->time('end_time')->comment('End Time');

            $table->longText('description')->nullable()->comment('Event Details');

            // Additional info for venue
            $table->string('lat', 32)->nullable()->default('0.0000000')->comment('Venue Coordinates - Latitude');
            $table->string('long', 32)->nullable()->default('0.0000000')->comment('Venue Coordinates - Longitude');

            $table->unsignedBigInteger('capacity')->nullable()->default(0);

            $table->boolean('is_featured')->default(false);
            $table->boolean('is_free')->default(false);

            $table->enum('status', ['draft', 'open', 'closed', 'ongoing', 'past', 'cancelled'])->nullable()->default('draft');
            $table->enum('review_status', ['pending', 'accepted', 'rejected']); //->nullable()->default('accepted');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
