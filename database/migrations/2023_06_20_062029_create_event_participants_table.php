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
        Schema::create('event_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('event_pricing_id')->constrained();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone');
            $table->enum('is_paid', ['paid', 'free']);

            $table->string('id_num')->nullable()->default('');
            $table->string('id_type')->nullable()->default('');

            $table->timestamp('booked_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_participants');
    }
};
