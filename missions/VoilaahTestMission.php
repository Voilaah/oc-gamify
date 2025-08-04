<?php

namespace Voilaah\Gamify\Missions;

use RainLab\User\Models\User;
use Voilaah\Gamify\Classes\Mission\BaseMission;
use Carbon\Carbon;

class VoilaahTestMission extends BaseMission
{
    protected $nameKey = 'voilaah.gamify::lang.missions.voilaah_test.name';
    protected $descriptionKey = 'voilaah.gamify::lang.missions.voilaah_test.description';
    protected $completionLabelKey = 'voilaah.gamify::lang.missions.voilaah_test.completion_label';
    protected $icon = 'assets/images/missions/knowledge-paragon.svg';
    protected $sort_order = 1;
    protected $completionPoints = 100; // Bonus points for completing all levels

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
                'labelKey' => 'voilaah.gamify::lang.missions.voilaah_test.levels.1.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.voilaah_test.levels.1.description',
                'goal' => 1,
                'points' => 5, // 5 diamonds
            ],
            2 => [
                'labelKey' => 'voilaah.gamify::lang.missions.voilaah_test.levels.2.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.voilaah_test.levels.2.description',
                'goal' => 1,
                'points' => 15, // 15 diamonds
            ],
            3 => [
                'labelKey' => 'voilaah.gamify::lang.missions.voilaah_test.levels.3.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.voilaah_test.levels.3.description',
                'goal' => 1,
                'points' => 30, // 30 diamonds
            ],
            4 => [
                'labelKey' => 'voilaah.gamify::lang.missions.voilaah_test.levels.4.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.voilaah_test.levels.4.description',
                'goal' => 1,
                'points' => 50, // 50 diamonds
                'time_constraint' => 30, // within 30 days of joining
            ],
        ];
    }

    /**
     * Get the actual course completion count for a user.
     */
    public function getActualValue(User $user): int
    {
        // This should return the actual number of courses the user has completed
        // Adjust based on your LMS course completion system

        return $user->courses_enrolled()->count();

        // For demo purposes, we'll use mission progress
        $progress = $this->userMissionProgress($user);
        return $progress ? $progress->total_value ?? 0 : 0;
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
            // Listen for course completion events
            'skillup.course.enrolled' => function ($course, $user) {
                return [
                    'user' => $user,
                    'course' => $course
                ];
            },
        ];
    }

    /**
     * Override handleEvent to add time constraint logic for Inferno level
     */
    public function handleEvent(string $event, array $payload = []): void
    {
        if (!isset($payload['user']) || !$payload['user'] instanceof User) {
            \Log::warning("Mission {$this->getCode()} received event '{$event}' without valid user payload.");
            return;
        }

        $user = $payload['user'];
        $progress = $this->getOrCreateUserMissionProgress($user);
        $currentLevel = $progress->level ?? 1;

        // Check time constraint for Inferno level (level 4)
        if ($currentLevel == 4) {
            $levels = $this->getLevels();
            $timeConstraint = $levels[4]['time_constraint'] ?? null;

            if ($timeConstraint) {
                $userJoinedAt = $user->created_at ?? $user->activated_at ?? now();
                $daysAsUser = Carbon::parse($userJoinedAt)->diffInDays(now());

                if ($daysAsUser > $timeConstraint) {
                    \Log::info("Mission {$this->getCode()} user {$user->id} exceeded time limit for Inferno level ({$daysAsUser} days > {$timeConstraint} days).");
                    return; // Don't process this event if time constraint is exceeded
                }
            }
        }

        // Call parent method for normal processing
        parent::handleEvent($event, $payload);
    }

    /**
     * Check if the mission is enabled.
     */
    public function isEnabled(): bool
    {
        return false;
    }

    /**
     * Get the default icon if not provided
     */
    protected function getDefaultIcon(): string
    {
        return 'icon-trophy'; // FontAwesome or October CMS icon
    }
}
