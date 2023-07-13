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
        Schema::create('reported_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('reported_id')->index()->constrained(table: 'profiles', column: 'id');
            $table->foreignUuid('reporter_id')->index()->constrained(table: 'profiles', column: 'id');
            //$table->primary(['reporter_id', 'reported_id']);

            $table->enum('report_status', ['1', '2']);
            $table->text('reason');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reported_profiles');
    }
};
