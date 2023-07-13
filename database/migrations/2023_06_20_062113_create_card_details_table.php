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
        Schema::create('card_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('card_owner');
            $table->string('brand')->comment('i.e. Visa, Mastercard');
            $table->string('card_num', 32)->comment('card number last digit');
            $table->string('card_cvv', 6)->comment('card number last digit');
            $table->string('exp_month', 2)->comment('i.e. 05 = May');
            $table->string('exp_year', 4)->comment('i.e. 2028');

            // Relationship
            $table->foreignUuid('profile_id')->index()->constrained();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_details');
    }
};
