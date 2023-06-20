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
        Schema::create('profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->index()->constrained();

            $table->string('business_email', 320)->nullable()->default('');
            $table->string('business_name')->nullable()->default('');
            //$table->string('account_type');
            $table->string('avatar')->nullable();
            $table->string('cover_photo')->nullable();
            $table->string('phone', 24)->nullable();

            $table->longText('bio')->nullable();
            $table->timestamp('last_accessed')->nullable()->useCurrent();
            $table->unsignedDecimal('credit_balance', 10)->nullable()->default(0.00);

            $table->timestamps();
            $table->softDeletes('deactivated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
