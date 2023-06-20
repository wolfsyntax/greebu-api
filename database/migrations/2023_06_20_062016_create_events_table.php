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

            $table->foreignUuid('organizer_id')->constrained();
            $table->foreignUuid('artist_id')->constrained();

            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('thumbnail');
            $table->text('venue');
            $table->string('lat', 32);
            $table->string('long', 32);
            $table->unsignedBigInteger('capacity')->nullable()->default(1);
            $table->boolean('is_public')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->enum('status', ['draft', 'open', 'closed', 'ongoing', 'past', 'cancelled'])->nullable()->default('draft');

            $table->date('event_date');
            $table->time('start_time');
            $table->time('end_time');

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
