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
        Schema::create('email_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('from_name');

            $table->string('subject');
            $table->string('header');
            $table->longText('body')->nullable();
            $table->longText('footer')->nullable();
            $table->string('theme');
            $table->string('template_trigger')->nullable();
            $table->boolean('status')->default(0);

            $table->tinyInteger('email_type')->default(0);
            $table->string('content_color', 7);
            $table->string('amount_color', 7);
            // $table->string('frequency');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
