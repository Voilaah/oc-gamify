<?php

namespace Voilaah\Gamify\Console;

use Illuminate\Console\Command;
use RainLab\User\Models\User;

class TestAllMissions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'gamify:test-all-missions {user_id? : User ID to test with}';

    /**
     * The console command description.
     */
    protected $description = 'Test all registered missions and their translations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id') ?? 1;
        
        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found");
            return;
        }

        $this->info("Testing all missions for user: {$user->name} (ID: {$user->id})");
        $this->line('');
        
        // Get mission manager
        $missionManager = app('gamify.missions');
        $allMissions = $missionManager->all();
        
        if (empty($allMissions)) {
            $this->error('No missions found. Make sure they are registered.');
            return;
        }

        $this->info("Found " . count($allMissions) . " registered missions:");
        $this->line('');
        
        foreach ($allMissions as $code => $mission) {
            $this->displayMissionInfo($mission, $user);
            $this->line('---');
        }
        
        // Test event firing
        if ($this->confirm('Do you want to test firing events?')) {
            $this->testEvents($user);
        }
    }

    private function displayMissionInfo($mission, $user)
    {
        $progress = $mission->getProgress($user);
        
        $this->info("🎯 {$mission->getName()} ({$mission->getCode()})");
        $this->info("   Description: {$mission->getDescription()}");
        $this->info("   Current Progress: Level {$progress['currentLevel']}/{$progress['maxLevel']}");
        $this->info("   Progress: {$progress['value']}/{$progress['goal']}");
        $this->info("   Completed: " . ($progress['completed'] ? 'Yes ✅' : 'No'));
        
        $this->info("   Levels:");
        foreach ($mission->getLevels() as $level => $config) {
            $label = $mission->getLevelLabel($level);
            $description = $mission->getDescriptionForLevel($level);
            $points = $config['points'] ?? 0;
            $goal = $config['goal'] ?? 0;
            
            $this->info("     Level {$level}: {$label} ({$goal} goal, {$points} diamonds)");
            $this->info("       {$description}");
        }
        
        $this->line('');
    }
    
    private function testEvents($user)
    {
        $this->info("🧪 Testing mission events...");
        
        // Test course completion events
        $this->info("Firing course completion event...");
        \Event::fire('skillup.course.completed', [
            (object)['id' => 1, 'title' => 'Test Course', 'category_id' => 1], 
            $user, 
            (object)['completed_at' => now()]
        ]);
        
        // Test perfect score event
        $this->info("Firing perfect score event...");
        \Event::fire('skillup.assessment.perfect_score', [
            (object)['id' => 1, 'title' => 'Test Assessment'], 
            $user,
            (object)['score' => 100, 'max_score' => 100]
        ]);
        
        // Test certificate event
        $this->info("Firing certificate earned event...");
        \Event::fire('skillup.certificate.earned', [
            (object)['id' => 1, 'title' => 'Test Certificate'], 
            $user,
            (object)['id' => 1, 'title' => 'Test Course']
        ]);
        
        // Test feedback event
        $this->info("Firing feedback submitted event...");
        \Event::fire('skillup.feedback.submitted', [
            (object)['content' => 'This is a test feedback with sufficient length.'], 
            $user,
            (object)['id' => 1, 'title' => 'Test Resource']
        ]);
        
        // Test engagement event
        $this->info("Firing daily engagement event...");
        \Event::fire('skillup.user.daily_engagement', [$user, 'login']);
        
        $this->info("✅ Events fired! Check mission progress with individual test commands.");
    }
}