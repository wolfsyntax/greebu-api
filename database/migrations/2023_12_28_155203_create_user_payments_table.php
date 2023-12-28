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
        Schema::create('user_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('profile_id')->index()->constrained();
            $table->string('payment_method')->nullable()->comment('Payment method ID');

            $table->string('payment_intent')->comment('Payment Intent ID');
            $table->string('amount')->nullable()->default('0');
            $table->enum('status', ['pending', 'success', 'failed',])->nullable()->default('pending');
            $table->boolean('is_save')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_payments');
    }
};
