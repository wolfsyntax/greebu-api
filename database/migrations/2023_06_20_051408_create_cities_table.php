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
        Schema::create('cities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // $table->foreignUuid('country_id')->constrained()->nullable();

            $table->string('name');
            $table->string('ascii')->nullable();

            $table->string('lat', 32)->nullable()->default('0.0000000')->comment('Latitude');
            $table->string('lng', 32)->nullable()->default('0.0000000')->comment('Longitude');

            $table->string('province')->nullable()->default('Camarines Sur');
            $table->string('country')->nullable()->default('Philippines');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
