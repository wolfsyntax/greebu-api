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
            $table->string('street_address')->nullable();
            $table->string('city')->comment('i.e. Naga City');
            $table->string('zip_code')->comment('i.e. 4400');
            $table->string('province')->comment('i.e. Camarines Sur');
            $table->string('country')->nullable()->default('philippines');

            $table->longText('bio')->nullable();
            $table->timestamp('last_accessed')->nullable()->useCurrent();
            $table->unsignedDecimal('credit_balance', 10)->nullable()->default(0.00);
            $table->string('bucket')->nullable();
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
