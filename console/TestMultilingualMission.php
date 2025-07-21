<?php

namespace Voilaah\Gamify\Console;

use Illuminate\Console\Command;
use RainLab\User\Models\User;
use Event;
use App;
use Lang;

class TestMultilingualMission extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'gamify:test-multilingual {user_id? : User ID to test with} {--locale=en : Language to test}';

    /**
     * The console command description.
     */
    protected $description = 'Test multilingual support for CourseExplorerMission';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id') ?? 1;
        $locale = $this->option('locale');
        
        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found");
            return;
        }

        // Set the locale for testing
        App::setLocale($locale);
        
        $this->info("Testing multilingual CourseExplorerMission in locale: {$locale}");
        $this->info("User: {$user->name} (ID: {$user->id})");
        $this->line('');
        
        // Get mission manager and find our mission
        $missionManager = app('gamify.missions');
        $mission = $missionManager->find('course-explorer-mission');
        
        if (!$mission) {
            $this->error('CourseExplorerMission not found. Make sure it is registered.');
            return;
        }

        // Show mission details in selected language
        $this->displayMissionInfo($mission, $locale);
        
        // Show current progress
        $this->displayProgress($user, $mission);
        
        // Test translation keys
        $this->testTranslations($locale);
    }

    private function displayMissionInfo($mission, $locale)
    {
        $this->info("=== Mission Information (Locale: {$locale}) ===");
        $this->info("Name: " . $mission->getName());
        $this->info("Description: " . $mission->getDescription());
        $this->info("Completion Label: " . $mission->getCompletionMissionLabel());
        $this->line('');
        
        $this->info("=== Mission Levels ===");
        foreach ($mission->getLevels() as $level => $config) {
            $this->info("Level {$level}:");
            $this->info("  Label: " . $mission->getLevelLabel($level));
            $this->info("  Description: " . $mission->getDescriptionForLevel($level));
            $this->info("  Goal: {$config['goal']} courses");
            $this->info("  Points: {$config['points']}");
            $this->line('');
        }
    }

    private function displayProgress($user, $mission)
    {
        $progress = $mission->getProgress($user);
        
        $this->info('=== User Progress ===');
        $this->info("Current Level: {$progress['currentLevel']} / {$progress['maxLevel']}");
        $this->info("Description: {$progress['description']}");
        $this->info("Progress: {$progress['value']} / {$progress['goal']}");
        $this->info("Completed: " . ($progress['completed'] ? 'Yes' : 'No'));
        $this->line('');
    }
    
    private function testTranslations($locale)
    {
        $this->info("=== Translation Test (Locale: {$locale}) ===");
        
        $testKeys = [
            'voilaah.gamify::lang.missions.course_explorer.name',
            'voilaah.gamify::lang.missions.course_explorer.levels.1.label',
            'voilaah.gamify::lang.common.mission_complete',
            'voilaah.gamify::lang.points.mission_level',
            'voilaah.gamify::lang.badges.mission_master'
        ];
        
        foreach ($testKeys as $key) {
            $translation = Lang::get($key, ['mission' => 'Test Mission', 'level' => 1], null, $locale);
            $this->info("{$key}: {$translation}");
        }
        
        $this->line('');
        $this->info("Available locales test:");
        $availableLocales = ['en', 'th', 'fr']; // Add more as needed
        
        foreach ($availableLocales as $testLocale) {
            $missionName = Lang::get('voilaah.gamify::lang.missions.course_explorer.name', [], null, $testLocale);
            $this->info("{$testLocale}: {$missionName}");
        }
    }
}