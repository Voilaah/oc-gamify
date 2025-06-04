<?php
namespace Voilaah\Gamify\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateGamifyTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::dropIfExists('voilaah_gamify_reputations');
        Schema::dropIfExists('voilaah_gamify_badges');
        Schema::dropIfExists('voilaah_gamify_user_badges');
        Schema::dropIfExists('voilaah_gamify_missions');
        Schema::dropIfExists('voilaah_gamify_user_missions');
        Schema::dropIfExists('voilaah_gamify_user_streaks');

        // reputations table
        Schema::create('voilaah_gamify_reputations', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->mediumInteger('point', false)->default(0);
            $table->integer('subject_id')->nullable();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('payee_id')->nullable();
            $table->text('meta')->nullable();
            $table->timestamps();
        });

        // badges table
        Schema::create('voilaah_gamify_badges', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('icon')->nullable();
            $table->tinyInteger('level')->default(config('gamify.badge_default_level', 1));
            $table->timestamps();
        });

        // user_badges pivot
        Schema::create('voilaah_gamify_user_badges', function ($table) {
            $table->primary(['user_id', 'badge_id']);
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('badge_id');
            $table->timestamps();
        });

        // missions table
        Schema::create('voilaah_gamify_missions', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('icon')->nullable();
            $table->tinyInteger('level')->default(config('gamify.mission_default_level', 1));
            $table->timestamps();
        });

        // user_missions pivot
        Schema::create('voilaah_gamify_user_missions', function ($table) {
            $table->primary(['user_id', 'mission_id']);
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('mission_id');
            $table->timestamps();
        });

        // user streaks
        Schema::create('voilaah_gamify_user_streaks', function ($table) {
            $table->increments('id')->unsigned();
            $table->unsignedBigInteger('user_id')->unsigned();
            // $table->integer('sort_order')->unsigned()->nullable();
            $table->string('streak_type', 64); // e.g. user_login, top_50_rank, etc.
            $table->json('streak_dates')->nullable();
            $table->dateTime('last_activity_date')->nullable();
            $table->smallInteger('current_streak')->default(0);
            $table->smallInteger('longest_streak')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('voilaah_gamify_reputations');
        Schema::dropIfExists('voilaah_gamify_badges');
        Schema::dropIfExists('voilaah_gamify_user_badges');
        Schema::dropIfExists('voilaah_gamify_missions');
        Schema::dropIfExists('voilaah_gamify_user_missions');
        Schema::dropIfExists('voilaah_gamify_user_streaks');
    }
}
