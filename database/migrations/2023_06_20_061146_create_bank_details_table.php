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
        Schema::create('bank_details', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('profile_id')->index()->constrained(table: 'profiles', column: 'id');
            $table->foreignUuid('bank_id')->index()->constrained(table: 'banks', column: 'id');

            $table->string('account_name');
            $table->string('account_number');
            $table->string('payment_mode', 64)->nullable();
            $table->enum('account_type', ['savings', 'checking']);
            $table->boolean('is_default')->nullable()->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_details');
    }
};
