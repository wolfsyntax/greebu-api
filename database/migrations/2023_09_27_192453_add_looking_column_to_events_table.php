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
        Schema::table('events', function (Blueprint $table) {
            $table->string('look_for')->nullable()->default('')->after('description');
            // $table->string('look_type')->nullable()->default('')->after('look_for');
            $table->longText('requirement')->nullable()->after('description');
            // Venue address
            $table->longText('street_address')->nullable()->after('event_name')->comment('Unit/Floor No. Premises/Bldg. Name, House/Bldg. No., Street Name');
            $table->string('barangay')->nullable()->default('')->after('street_address')->comment('Village/Subdivision, District, Barangay');
            $table->string('city')->nullable()->default('')->after('barangay')->comment('Town/City');
            $table->string('province')->nullable()->default('')->after('city')->comment('Province');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('look_for');
            $table->dropColumn('requirement');
            $table->dropColumn('street_address');
            $table->dropColumn('barangay');
            $table->dropColumn('city');
            $table->dropColumn('province');
        });
    }
};
