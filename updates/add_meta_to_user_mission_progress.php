<?php

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class AddMetaToUserMissionProgress extends Migration
{
    public function up()
    {
        Schema::table('voilaah_gamify_user_mission_progress', function (Blueprint $table) {
            $table->text('meta')->nullable()->after('completed_at');
        });
    }

    public function down()
    {
        Schema::table('voilaah_gamify_user_mission_progress', function (Blueprint $table) {
            $table->dropColumn('meta');
        });
    }
}