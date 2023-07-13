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
        Schema::create('comment_reactions', function (Blueprint $table) {
            //$table->uuid('id')->primary();
            $table->foreignUuid('comment_id')->index()->constrained(table: 'comments');
            $table->foreignUuid('profile_id')->index()->constrained();
            $table->foreignUuid('reaction_id')->index()->constrained(table: 'emoticons');

            $table->primary(['comment_id', 'profile_id', 'reaction_id',]);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_reactions');
    }
};
