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
        Schema::create('devices_info', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->index()->constrained();
            $table->string('platform');
            $table->string('device_token');
            $table->string('device_id')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices_info');
    }
};
