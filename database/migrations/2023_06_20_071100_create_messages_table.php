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
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('room_id')->index()->constrained(table: 'chat_rooms', column: 'id');
            $table->foreignUuid('sender_id')->index()->constrained(table: 'profiles', column: 'id');

            $table->ipAddress('sender_ip');
            $table->enum('content_type', ['TEXT', 'AUDIO', 'PHOTO', 'VIDEO'])->default('TEXT');
            $table->text('content');
            $table->timestamp('expired_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
