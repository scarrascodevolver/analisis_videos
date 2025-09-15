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
        Schema::table('user_profiles', function (Blueprint $table) {
            // Player fields that are currently missing
            $table->string('secondary_position')->nullable()->after('position');
            $table->integer('player_number')->nullable()->after('secondary_position');
            $table->integer('weight')->nullable()->after('player_number'); // in kg
            $table->integer('height')->nullable()->after('weight'); // in cm
            $table->date('date_of_birth')->nullable()->after('height');
            $table->text('goals')->nullable()->after('date_of_birth');
            
            // Coach fields that are currently missing
            $table->integer('coaching_experience')->nullable()->after('goals'); // years
            $table->text('certifications')->nullable()->after('coaching_experience');
            $table->json('specializations')->nullable()->after('certifications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'secondary_position',
                'player_number',
                'weight', 
                'height',
                'date_of_birth',
                'goals',
                'coaching_experience',
                'certifications',
                'specializations'
            ]);
        });
    }
};