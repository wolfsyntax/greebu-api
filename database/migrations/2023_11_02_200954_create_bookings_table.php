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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('event_id')->constrained()->comment('Event Details');

            $table->foreignUuid('creator_id')->constrained(table: 'profiles')->comment('Creator Details');
            $table->foreignUuid('artist_id')->constrained()->comment('Artist Details');

            $table->unsignedDecimal('talent_fee', 12, 2)->comment();
            $table->longText('cover_letter')->nullable();
            $table->string('first_name')->comment();
            $table->string('last_name')->comment()->nullable();
            $table->string('email')->comment()->nullable();
            $table->string('phone', 32)->comment()->nullable();
            $table->enum('status', ['confirmed', 'denied', 'completed', 'expired', 'cancelled', 'pending',])->comment()->nullable()->default('pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
