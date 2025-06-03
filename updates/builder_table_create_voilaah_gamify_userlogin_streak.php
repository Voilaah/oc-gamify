<?php namespace Voilaah\Gamify\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateVoilaahGamifyUserloginStreak extends Migration
{
    public function up()
    {
        Schema::create('voilaah_gamify_userlogin_streak', function($table)
        {
            $table->increments('id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
            $table->dateTime('last_activity_date')->nullable();
            $table->smallInteger('current_streak')->nullable()->unsigned();
            $table->smallInteger('longest_streak')->nullable()->unsigned();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('voilaah_gamify_userlogin_streak');
    }
}
