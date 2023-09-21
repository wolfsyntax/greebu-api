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
        Schema::create('organizer_staff', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organizer_id')->constrained();

            $table->string('first_name');
            $table->string('last_name')->default('')->nullable();

            $table->enum('gender', ['male', 'female', 'rather not say'])->nullable()->default('rather not say');
            $table->string('avatar')->nullable()->default('');
            $table->string('role');

            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('facebook_profile')->nullable();
            $table->date('birthdate')->default(now())->nullable();
            $table->date('hired_since')->nullable();
            // can deactivate member
            $table->timestamp('deactivated_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizer_staff');
    }
};
