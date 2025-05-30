<?php namespace Syehan\Gamify\Updates;

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
        // reputations table
        Schema::create('syehan_gamify_reputations', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->mediumInteger('point', false)->default(0);
            $table->integer('subject_id')->nullable();
            $table->string('subject_type')->nullable();
            $table->unsignedInteger('payee_id')->nullable();
            $table->text('meta')->nullable();
            $table->timestamps();
        });

        // badges table
        Schema::create('syehan_gamify_badges', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('icon')->nullable();
            $table->tinyInteger('level')->default(config('gamify.badge_default_level', 1));
            $table->timestamps();
        });

        // user_badges pivot
        Schema::create('syehan_gamify_user_badges', function ($table) {
            $table->primary(['user_id', 'badge_id']);
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('badge_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('syehan_gamify_reputations');
        Schema::dropIfExists('syehan_gamify_badges');
        Schema::dropIfExists('syehan_gamify_user_badges');
    }
}
