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
        Schema::create('appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('venue_name');

            $table->foreignUuid('artist_id')->constrained();
            $table->foreignUuid('event_type_id')->constrained();
            $table->foreignUuid('profile_id')->constrained();

            // Street, Barangay, City (concatenate)
            $table->string('venue_address');

            $table->string('lat', 32);
            $table->string('long', 32);

            $table->date('event_date');
            $table->time('start_time');
            $table->time('end_time');

            $table->unsignedBigInteger('to_be_played')->default(1);
            $table->boolean('is_confirmed')->nullable()->default(0);
            // Is artist got paid_at
            $table->timestamp('paid_at')->nullable();

            // Customer agreed to artist rate
            $table->boolean('agree_with_rate')->default(false);

            $table->timestamp('agreed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
