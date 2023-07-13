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
        Schema::create('discount_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('promo_code');
            $table->enum('discount_type', ['percentage', 'cash',]);
            $table->unsignedDecimal('discount_value', 10, 2);
            $table->unsignedBigInteger('use_limit')->nullable()->default(10);
            $table->longText('description')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('start_at');
            $table->enum('publish_status', ['active', 'disabled'])->default('active');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_codes');
    }
};
