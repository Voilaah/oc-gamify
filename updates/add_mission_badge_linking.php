<?php

namespace Voilaah\Gamify\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class AddMissionBadgeLinking extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add mission linking fields to badges table
        Schema::table('voilaah_gamify_badges', function ($table) {
            $table->string('mission_code', 191)->nullable()->after('level');
            $table->tinyInteger('mission_level')->nullable()->after('mission_code');
            $table->boolean('is_mission_badge')->default(false)->after('mission_level');
            
            $table->index(['mission_code', 'mission_level'], 'idx_mission_link');
        });

        // Add context tracking to user_badges pivot
        Schema::table('voilaah_gamify_user_badges', function ($table) {
            $table->tinyInteger('awarded_at_level')->nullable()->after('badge_id');
            $table->string('awarded_context', 50)->default('manual')->after('awarded_at_level');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('voilaah_gamify_badges', function ($table) {
            $table->dropIndex('idx_mission_link');
            $table->dropColumn(['mission_code', 'mission_level', 'is_mission_badge']);
        });

        Schema::table('voilaah_gamify_user_badges', function ($table) {
            $table->dropColumn(['awarded_at_level', 'awarded_context']);
        });
    }
}