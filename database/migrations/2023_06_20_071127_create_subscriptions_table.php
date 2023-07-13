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
        Schema::create('subscriptions', function (Blueprint $table) {

            $table->uuid('id')->primary();
            $table->foreignUuid('profile_id')->index()->constrained();
            $table->foreignUuid('plan_id')->index()->constrained();

            $table->timestamp('trial_period_start_date')->nullable();
            $table->timestamp('trial_period_end_date')->nullable();

            $table->boolean('subscribe_after_trial')->default(false);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            //$table->boolean('');
            $table->longText('statement_descriptor');
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
