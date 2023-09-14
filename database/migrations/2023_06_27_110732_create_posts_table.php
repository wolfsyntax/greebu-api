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
        Schema::create('posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('creator_id')->index()->constrained(table: 'profiles');

            $table->enum('attachment_type', ['image/video', 'audio', 'none']);
            $table->string('attachment')->nullable()->default('');
            $table->longText('content')->nullable();

            $table->string('longitude')->nullable();
            $table->string('latitude')->nullable();
            $table->boolean('is_schedule')->nullable()->default(false);
            $table->timestamp('scheduled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
