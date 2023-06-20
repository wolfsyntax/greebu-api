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
        Schema::create('email_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('email');
            $table->string('from_name');
            $table->string('email_subject');
            $table->string('reply_to')->nullable();

            $table->text('mail_title');
            $table->text('mail_header');
            $table->text('mail_body');
            $table->text('mail_footer');
            $table->timestamp('opened_at')->nullable();

            $table->foreignUuid('receipt_number')->index()->constrained(table: 'transactions', column: 'id');
            $table->foreignUuid('profile_id')->index()->constrained(table: 'profiles', column: 'id');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
