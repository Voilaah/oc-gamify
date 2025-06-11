<?php

// updates/add_mission_levels_table.php
use October\Rain\Database\Updates\Migration;

class AddMissionLevelsTable extends Migration
{
    public function up()
    {
        // Create mission levels table
        Schema::create('voilaah_gamify_mission_levels', function ($table) {
            $table->increments('id');
            $table->integer('mission_id')->unsigned();
            $table->integer('level_order');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('qualification_class');
            $table->json('qualification_config')->nullable();
            $table->integer('reward_points')->default(0);
            $table->integer('reward_badge_id')->unsigned()->nullable();
            $table->string('icon')->nullable();
            $table->timestamps();

            $table->foreign('mission_id')->references('id')->on('voilaah_gamify_missions')->onDelete('cascade');
            $table->foreign('reward_badge_id')->references('id')->on('voilaah_gamify_badges')->onDelete('set null');
            $table->unique(['mission_id', 'level_order']);
            $table->index('mission_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('voilaah_gamify_mission_levels');
    }
}
/*
// updates/add_mission_progress_table.php
use October\Rain\Database\Updates\Migration;

class AddMissionProgressTable extends Migration
{
    public function up()
    {
        // Create mission progress table to track individual user progress
        Schema::create('voilaah_gamify_mission_progress', function ($table) {
            $table->increments('id');
            $table->unsignedBigInteger('user_id');
            $table->integer('mission_id')->unsigned();
            $table->integer('current_level')->default(0);
            $table->json('progress_data')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('last_updated_at');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('mission_id')->references('id')->on('voilaah_gamify_missions')->onDelete('cascade');
            $table->unique(['user_id', 'mission_id']);
            $table->index('user_id');
            $table->index('mission_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('voilaah_gamify_mission_progress');
    }
}

// updates/add_mission_level_completions_table.php
use October\Rain\Database\Updates\Migration;

class AddMissionLevelCompletionsTable extends Migration
{
    public function up()
    {
        // Create table to track completed mission levels
        Schema::create('voilaah_gamify_mission_level_completions', function ($table) {
            $table->increments('id');
            $table->unsignedBigInteger('user_id');
            $table->integer('mission_id')->unsigned();
            $table->integer('mission_level_id')->unsigned();
            $table->integer('level_order');
            $table->timestamp('completed_at');
            $table->json('completion_data')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('mission_id')->references('id')->on('voilaah_gamify_missions')->onDelete('cascade');
            $table->foreign('mission_level_id')->references('id')->on('voilaah_gamify_mission_levels')->onDelete('cascade');

            $table->unique(['user_id', 'mission_level_id']);
            $table->index(['user_id', 'mission_id']);
            $table->index('completed_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('voilaah_gamify_mission_level_completions');
    }
}

// updates/modify_missions_table.php (optional - if you want to add more fields to existing missions)
use October\Rain\Database\Updates\Migration;

class ModifyMissionsTable extends Migration
{
    public function up()
    {
        Schema::table('voilaah_gamify_missions', function ($table) {
            // Add optional fields if you want more mission metadata
            $table->string('slug')->nullable()->after('name');
            $table->string('category')->nullable()->after('icon');
            $table->boolean('is_active')->default(true)->after('level');
            $table->boolean('is_repeatable')->default(false)->after('is_active');
            $table->timestamp('expires_at')->nullable()->after('is_repeatable');

            $table->index(['is_active', 'expires_at']);
            $table->index('category');
        });
    }

    public function down()
    {
        Schema::table('voilaah_gamify_missions', function ($table) {
            $table->dropColumn(['slug', 'category', 'is_active', 'is_repeatable', 'expires_at']);
        });
    }
} */
