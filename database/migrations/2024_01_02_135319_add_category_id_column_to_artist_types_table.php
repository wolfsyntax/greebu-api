<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function dropColumnIfExists($tbl, $col)
    {
        if (Schema::hasColumn($tbl, $col)) {
            Schema::table($tbl, function (Blueprint $table) use ($col) {
                $table->$table->dropForeign([$col]);
                $table->$table->dropColumn($col);
            });
        }
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->dropColumnIfExists('artist_types', 'category_id');

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
