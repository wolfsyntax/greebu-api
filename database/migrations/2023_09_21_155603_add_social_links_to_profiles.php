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
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('youtube', 255)->nullable()->default('');
            $table->string('spotify', 255)->nullable()->default('');
            $table->string('twitter', 255)->nullable()->default('X');
            $table->string('instagram', 255)->nullable()->default('');
            $table->string('facebook', 255)->nullable()->default('');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('youtube');
            $table->dropColumn('spotify');
            $table->dropColumn('twitter');
            $table->dropColumn('instagram');
            $table->dropColumn('facebook');
        });
    }
};
