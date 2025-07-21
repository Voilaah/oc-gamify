<?php

namespace Voilaah\Gamify\Console;

use Illuminate\Console\Command;
use RainLab\User\Models\User;
use Voilaah\Gamify\Models\UserMissionProgress;

class DebugMissionProgress extends Command
{
    protected $signature = 'gamify:debug-progress {user_id} {mission_code?}';
    protected $description = 'Debug user mission progress';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $missionCode = $this->argument('mission_code') ?? 'knowledge-paragon-mission';
        
        $user = User::find($userId);
        if (!$user) {
            $this->error("User {$userId} not found");
            return;
        }

        $this->info("=== Debug Mission Progress ===");
        $this->info("User: {$user->name} (ID: {$userId})");
        $this->info("Mission: {$missionCode}");
        $this->line('');

        // Get mission progress from database
        $progress = UserMissionProgress::where('user_id', $userId)
            ->where('mission_code', $missionCode)
            ->first();

        if (!$progress) {
            $this->error("No progress found for this mission");
            return;
        }

        $this->info("DATABASE VALUES:");
        $this->info("level: " . ($progress->level ?? 'null'));
        $this->info("value: " . ($progress->value ?? 'null'));
        $this->info("total_value: " . ($progress->total_value ?? 'null'));
        $this->info("is_completed: " . ($progress->is_completed ? 'true' : 'false'));
        $this->info("completed_at: " . ($progress->completed_at ?? 'null'));
        $this->info("last_reached_at: " . ($progress->last_reached_at ?? 'null'));
        $this->line('');

        // Get mission instance
        $missionManager = app('gamify.missions');
        $mission = $missionManager->find($missionCode);
        
        if (!$mission) {
            $this->error("Mission not found");
            return;
        }

        // Show mission progress calculation
        $calculatedProgress = $mission->getProgress($user);
        $this->info("CALCULATED PROGRESS:");
        $this->info("currentLevel: " . $calculatedProgress['currentLevel']);
        $this->info("value: " . $calculatedProgress['value']);
        $this->info("goal: " . $calculatedProgress['goal']);
        $this->info("completed: " . ($calculatedProgress['completed'] ? 'true' : 'false'));
        $this->info("description: " . $calculatedProgress['description']);
        $this->line('');

        // Show user badges
        $userBadges = $user->badges()
            ->whereIn('id', function($query) use ($missionCode) {
                $badgeModel = config('gamify.badge_model');
                $query->select('id')
                    ->from((new $badgeModel)->getTable())
                    ->where('mission_code', $missionCode)
                    ->where('is_mission_badge', true);
            })
            ->get();

        $this->info("USER BADGES FOR THIS MISSION:");
        foreach ($userBadges as $badge) {
            $this->info("- {$badge->name} (Level: {$badge->mission_level})");
        }
        
        if ($userBadges->isEmpty()) {
            $this->info("No badges earned for this mission");
        }
    }
}