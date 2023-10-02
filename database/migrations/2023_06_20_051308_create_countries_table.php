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
        Schema::create('countries', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name');
            $table->string('iso2')->nullable();
            $table->string('iso3')->nullable();
            $table->string('capital')->nullable();
            $table->string('currency')->nullable();
            $table->string('symbol')->nullable();
            $table->boolean('is_supported')->nullable()->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
