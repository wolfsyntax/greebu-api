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
        Schema::create('members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('artist_id')->constrained();
            $table->string('first_name');
            $table->string('last_name')->default('')->nullable();
            $table->enum('gender', ['male', 'female', 'rather not say'])->nullable()->default('rather not say');
            $table->string('avatar');
            $table->string('role');
            // email must be different to business_email and must e unique email
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('facebook_profile')->nullable();
            $table->date('birthdate')->default(now())->nullable();
            $table->date('member_since')->nullable();
            // can deactivate member
            $table->timestamp('deactivated_at')->nullable();

            $table->timestamps();

            // $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
