<?php

namespace Voilaah\Gamify\Missions;

use RainLab\User\Models\User;
use Voilaah\Gamify\Classes\Mission\BaseMission;
use Carbon\Carbon;

class SteadfastMonarchMission extends BaseMission
{
    protected $nameKey = 'voilaah.gamify::lang.missions.steadfast_monarch.name';
    protected $descriptionKey = 'voilaah.gamify::lang.missions.steadfast_monarch.description';
    protected $completionLabelKey = 'voilaah.gamify::lang.missions.steadfast_monarch.completion_label';
    protected $icon = 'assets/images/missions/steadfast-monarch.svg';
    protected $sort_order = 6;
    protected $completionPoints = 200; // Bonus points for completing all levels

    /**
     * Define the levels of the mission.
     *
     * @return array<int, array> Each level has:
     *   - 'labelKey' => string
     *   - 'descriptionKey' => string
     *   - 'goal' => int
     *   - 'points' => int (diamonds)
     */
    public function getLevels(): array
    {
        return [
            1 => [
                'labelKey' => 'voilaah.gamify::lang.missions.steadfast_monarch.levels.1.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.steadfast_monarch.levels.1.description',
                'goal' => 25,
                'points' => 15, // 15 diamonds
            ],
            2 => [
                'labelKey' => 'voilaah.gamify::lang.missions.steadfast_monarch.levels.2.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.steadfast_monarch.levels.2.description',
                'goal' => 30,
                'points' => 35, // 35 diamonds
            ],
            3 => [
                'labelKey' => 'voilaah.gamify::lang.missions.steadfast_monarch.levels.3.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.steadfast_monarch.levels.3.description',
                'goal' => 60,
                'points' => 70, // 70 diamonds
            ],
            4 => [
                'labelKey' => 'voilaah.gamify::lang.missions.steadfast_monarch.levels.4.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.steadfast_monarch.levels.4.description',
                'goal' => 90,
                'points' => 140, // 140 diamonds
            ],
        ];
    }

    /**
     * Get the actual consecutive engagement days for a user.
     */
    public function getActualValue(User $user): int
    {
        // This should return the current consecutive engagement streak
        // Adjust based on your LMS engagement tracking system
        
        $progress = $this->userMissionProgress($user);
        if (!$progress) {
            return 0;
        }

        // Check if streak is still active (user engaged today or yesterday)
        $lastEngagement = $progress->last_reached_at ? Carbon::parse($progress->last_reached_at) : null;
        if (!$lastEngagement) {
            return 0;
        }

        $daysSinceLastEngagement = $lastEngagement->diffInDays(now());
        
        // If more than 1 day without engagement, streak is broken
        if ($daysSinceLastEngagement > 1) {
            return 0;
        }

        return $progress->value ?? 0;
    }

    /**
     * Return a map of events this mission subscribes to.
     *
     * Format:
     *  'event.name' => function (mixed ...$args): array $payload
     */
    public function getSubscribedEvents(): array
    {
        return [
            // Listen for daily engagement events
            'skillup.user.daily_engagement' => function ($user = null, $activity = null) {
                return [
                    'user' => $user,
                    'activity' => $activity,
                ];
            },
            
            // Listen for login events
            'skillup.user.login' => function ($user = null) {
                return [
                    'user' => $user,
                ];
            },
            
            // Listen for any learning activity that counts as engagement
            'skillup.user.activity' => function ($user = null, $activity_type = null) {
                return [
                    'user' => $user,
                    'activity_type' => $activity_type,
                ];
            },
            
            // Alternative event names
            'user.engaged' => function ($user, $activity = null) {
                return [
                    'user' => $user,
                    'activity' => $activity,
                ];
            },
        ];
    }

    /**
     * Override handleEvent to handle consecutive days logic
     */
    public function handleEvent(string $event, array $payload = []): void
    {
        if (!isset($payload['user']) || !$payload['user'] instanceof User) {
            \Log::warning("Mission {$this->getCode()} received event '{$event}' without valid user payload.");
            return;
        }

        $user = $payload['user'];
        $progress = $this->getOrCreateUserMissionProgress($user);
        $levels = $this->getLevels();
        
        $currentLevel = $progress->level ?? 1;
        $today = Carbon::today();
        
        // Get last engagement date
        $lastEngagement = $progress->last_reached_at ? Carbon::parse($progress->last_reached_at)->startOfDay() : null;
        
        // Don't count multiple engagements on the same day
        if ($lastEngagement && $lastEngagement->isSameDay($today)) {
            \Log::info("Mission {$this->getCode()} user {$user->id} already engaged today, not incrementing streak.");
            return;
        }
        
        $currentStreak = $progress->value ?? 0;
        
        // Check if streak should continue or reset
        if ($lastEngagement) {
            $daysSinceLastEngagement = $lastEngagement->diffInDays($today);
            
            if ($daysSinceLastEngagement == 1) {
                // Consecutive day - increment streak
                $currentStreak += 1;
                \Log::info("Mission {$this->getCode()} user {$user->id} continued streak: {$currentStreak} days");
            } elseif ($daysSinceLastEngagement > 1) {
                // Streak broken - reset to 1
                $currentStreak = 1;
                \Log::info("Mission {$this->getCode()} user {$user->id} streak broken, resetting to 1 day");
            }
        } else {
            // First engagement
            $currentStreak = 1;
            \Log::info("Mission {$this->getCode()} user {$user->id} started engagement streak");
        }
        
        // Update progress
        $progress->value = $currentStreak;
        $progress->last_reached_at = now();
        
        // Check if level goal is reached
        if (isset($levels[$currentLevel])) {
            $levelGoal = $levels[$currentLevel]['goal'] ?? null;
            
            if ($levelGoal && $currentStreak >= $levelGoal) {
                // Level completed
                $hasNextLevel = isset($levels[$currentLevel + 1]);
                
                // Award points and fire events
                $this->awardPointsForLevel($user, $currentLevel);
                \Event::fire('gamify.mission.levelUp', [$user, $this, $currentLevel]);
                \Log::info("Mission {$this->getCode()} user {$user->id} completed level {$currentLevel} with {$currentStreak} consecutive days");
                
                if ($hasNextLevel) {
                    // Move to next level but keep the streak value
                    $progress->level += 1;
                    // Don't reset value - keep the streak going
                } else {
                    // Mission completed
                    $progress->is_completed = true;
                    $progress->completed_at = now();
                    \Event::fire('gamify.mission.completed', [$user, $this]);
                    $this->awardCompletionPoints($user);
                    \Log::info("Mission {$this->getCode()} user {$user->id} completed entire mission with {$currentStreak} consecutive days");
                }
            }
        }
        
        $progress->save();
        
        // Fire general progress update event
        \Event::fire('gamify.mission.progressUpdated', [$user, $this, $progress]);
    }

    /**
     * Check if the mission is enabled.
     */
    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * Get the default icon if not provided
     */
    protected function getDefaultIcon(): string
    {
        return 'icon-calendar'; // FontAwesome or October CMS icon
    }
}