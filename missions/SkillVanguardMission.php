<?php

namespace Voilaah\Gamify\Missions;

use RainLab\User\Models\User;
use Voilaah\Gamify\Classes\Mission\BaseMission;

class SkillVanguardMission extends BaseMission
{
    protected $nameKey = 'voilaah.gamify::lang.missions.skill_vanguard.name';
    protected $descriptionKey = 'voilaah.gamify::lang.missions.skill_vanguard.description';
    protected $completionLabelKey = 'voilaah.gamify::lang.missions.skill_vanguard.completion_label';
    protected $icon = 'assets/images/missions/skill-vanguard.svg';
    protected $sort_order = 2;
    protected $completionPoints = 150; // Bonus points for completing all levels

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
                'labelKey' => 'voilaah.gamify::lang.missions.skill_vanguard.levels.1.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.skill_vanguard.levels.1.description',
                'goal' => 5,
                'points' => 20, // 20 diamonds
            ],
            2 => [
                'labelKey' => 'voilaah.gamify::lang.missions.skill_vanguard.levels.2.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.skill_vanguard.levels.2.description',
                'goal' => 10,
                'points' => 50, // 50 diamonds
            ],
            3 => [
                'labelKey' => 'voilaah.gamify::lang.missions.skill_vanguard.levels.3.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.skill_vanguard.levels.3.description',
                'goal' => 20,
                'points' => 100, // 100 diamonds
            ],
            4 => [
                'labelKey' => 'voilaah.gamify::lang.missions.skill_vanguard.levels.4.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.skill_vanguard.levels.4.description',
                'goal' => 30,
                'points' => 200, // 200 diamonds
            ],
        ];
    }

    /**
     * Get the actual course completion count for a user (any category).
     */
    public function getActualValue(User $user): int
    {
        // This should return the total number of courses completed in any category
        // Adjust based on your LMS course completion system

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
            // Listen for course completion events (any category)
            'skillup.course.completed' => function ($course = null, $user = null, $completion = null) {
                return [
                    'user' => $user,
                    'course' => $course,
                    'completion' => $completion,
                ];
            },
        ];
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
        return 'icon-star'; // FontAwesome or October CMS icon
    }
}
