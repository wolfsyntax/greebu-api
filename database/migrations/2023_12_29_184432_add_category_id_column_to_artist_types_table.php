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
        Schema::table('artist_types', function (Blueprint $table) {
            $table->foreignUuid('category_id')->nullable()->constrained(table: 'artist_categories', column: 'id')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('artist_types', function (Blueprint $table) {
            $table->$table->dropForeign(['category_id']);
            $table->$table->dropColumn('category_id');
        });
    }
};
