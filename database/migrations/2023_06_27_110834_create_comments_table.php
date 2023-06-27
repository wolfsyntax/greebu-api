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
        Schema::create('comments', function (Blueprint $table) {
            $table->uuid('id');
            $table->foreignUuid('post_id')->index()->constrained();
            $table->foreignUuid('profile_id')->index()->constrained();
            $table->longText('comment')->nullable();

            $table->string('longitude')->nullable();
            $table->string('latitude')->nullable();
            $table->boolean('is_schedule')->nullable()->default(false);
            $table->timestamp('schedule_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
