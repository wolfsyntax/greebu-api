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
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('cost_text');
            $table->unsignedDecimal('cost_value', 10, 2)->default(0.00);
            $table->string('currency')->nullable()->default('PHP');
            $table->text('description');
            $table->enum('plan_type', ["monthly", "yearly"]);
            $table->enum('account_type', ['service-provider', 'organizer', 'artists', 'customers'])->default('customers');
            $table->boolean('is_active')->nullable()->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
