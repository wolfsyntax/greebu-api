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
        Schema::create('plan_inclusions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('plan_id')->index()->constrained(table: 'plans');
            $table->string('inclusions');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_inclusions');
    }
};
