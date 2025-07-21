<?php

namespace Voilaah\Gamify\Console;

use Illuminate\Console\Command;
use RainLab\User\Models\User;
use Event;

class TestCourseExplorerMission extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'gamify:test-course-explorer {user_id? : User ID to test with}';

    /**
     * The console command description.
     */
    protected $description = 'Test CourseExplorerMission by simulating course enrollments';

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

        $this->info("Testing CourseExplorerMission for user: {$user->name} (ID: {$user->id})");

        // Get mission manager and find our mission
        $missionManager = app('gamify.missions');
        $mission = $missionManager->find('course-explorer-mission-test');

        if (!$mission) {
            $this->error('CourseExplorerMission not found. Make sure it is registered.');
            return;
        }

        // Show current progress
        $this->displayProgress($user, $mission);

        // Ask if user wants to simulate enrollments
        if ($this->confirm('Do you want to simulate course enrollments?')) {
            $enrollments = $this->ask('How many course enrollments to simulate?', '1');

            for ($i = 1; $i <= (int) $enrollments; $i++) {
                $courseData = [
                    'id' => $i,
                    'title' => "Demo Course {$i}",
                    'description' => "This is a demo course #{$i}"
                ];
                $course = (object) $courseData;
                $this->info("Simulating enrollment in: {$courseData['title']}");
                Event::fire('skillup.course.enrolled', [$course, $user, null]);

                // Small delay to see the progression
                usleep(500000); // 0.5 seconds
            }

            $this->info('Course enrollments simulated!');
            $this->displayProgress($user, $mission);
        }
    }

    private function displayProgress($user, $mission)
    {
        $progress = $mission->getProgress($user);

        $this->info('=== Course Explorer Mission Progress ===');
        $this->info("Current Level: {$progress['currentLevel']} / {$progress['maxLevel']}");
        $this->info("Description: {$progress['description']}");
        $this->info("Progress: {$progress['value']} / {$progress['goal']}");
        
        // Show if level was just completed
        if (isset($progress['levelCompleted']) && $progress['levelCompleted']) {
            $this->info("🎉 Level {$progress['currentLevel']} Complete! Badge Unlocked!");
        }
        
        $this->info("Mission Completed: " . ($progress['completed'] ? 'Yes 🏆' : 'No'));

        if ($progress['completed']) {
            $this->info('🎉 Entire Mission Complete! Master Badge Unlocked!');
        } elseif (!isset($progress['levelCompleted'])) {
            $remaining = $progress['goal'] - $progress['value'];
            $this->info("Enrollments needed for next level: {$remaining}");
        }

        $this->line('');
    }
}
