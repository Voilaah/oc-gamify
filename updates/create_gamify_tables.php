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
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('voilaah_gamify_reputations');
        Schema::dropIfExists('voilaah_gamify_user_badges');
        Schema::dropIfExists('voilaah_gamify_badges');
        Schema::dropIfExists('voilaah_gamify_user_mission_progress');
        Schema::dropIfExists('voilaah_gamify_missions');
        Schema::dropIfExists('voilaah_gamify_user_streaks');

        Schema::enableForeignKeyConstraints();

        // reputations table
        Schema::create('voilaah_gamify_reputations', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->index();
            $table->mediumInteger('point')->default(0);
            $table->integer('subject_id')->nullable()->index();
            $table->string('subject_type')->nullable()->index();
            $table->unsignedBigInteger('payee_id')->nullable()->index();
            $table->text('meta')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
        });

        // badges table
        Schema::create('voilaah_gamify_badges', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->index();
            $table->smallInteger('sort_order')->default(1)->index();
            $table->string('description')->nullable();
            $table->string('icon')->nullable();
            $table->tinyInteger('level')->default(config('gamify.badge_default_level', 1))->index();
            $table->timestamps();
        });

        // user_badges pivot
        Schema::create('voilaah_gamify_user_badges', function ($table) {
            $table->engine = 'InnoDB';
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('badge_id');
            $table->timestamps();

            $table->primary(['user_id', 'badge_id']);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('badge_id')->references('id')->on('voilaah_gamify_badges')->onDelete('cascade');

            // Optional index if needed:
            $table->index(['badge_id']);
        });

        // missions table
        Schema::create('voilaah_gamify_missions', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('code', 191)->index();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('icon')->nullable();
            $table->smallInteger('sort_order')->default(1)->index();
            $table->timestamps();

            $table->unique(['code']);
            // $table->tinyInteger('level')->default(config('gamify.mission_default_level', 1))->index();
        });

        // user_missions pivot
        Schema::create('voilaah_gamify_user_mission_progress', function ($table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('mission_code', 191)->index(); // e.g. 'courses_explorer'
            $table->unsignedTinyInteger('level')->default(1);
            $table->unsignedInteger('value')->default(0);
            $table->unsignedInteger('total_value')->default(0);
            $table->timestamp('last_reached_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('is_completed')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'mission_code']);
            /* $table->primary(['user_id', 'mission_code']); */

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('mission_code')->references('code')->on('voilaah_gamify_missions')->onDelete('cascade');
        });

        // user streaks
        Schema::create('voilaah_gamify_user_streaks', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->unsignedBigInteger('user_id')->index();
            // $table->integer('sort_order')->unsigned()->nullable();
            $table->string('streak_type', 64)->index(); // e.g. user_login, top_50_rank, etc.
            $table->json('streak_dates')->nullable();
            $table->dateTime('last_activity_date')->nullable()->index();
            $table->smallInteger('current_streak')->default(0);
            $table->smallInteger('longest_streak')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'streak_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('voilaah_gamify_reputations');
        Schema::dropIfExists('voilaah_gamify_badges');
        Schema::dropIfExists('voilaah_gamify_user_badges');
        Schema::dropIfExists('voilaah_gamify_missions');
        Schema::dropIfExists('voilaah_gamify_user_mission_progress');
        Schema::dropIfExists('voilaah_gamify_user_streaks');

        Schema::enableForeignKeyConstraints();
    }
}
