<?php

namespace Voilaah\Gamify\Missions;

use RainLab\User\Models\User;
use Voilaah\Gamify\Classes\Mission\BaseMission;

class CourseExplorerMissionTest extends BaseMission
{
    protected $nameKey = 'voilaah.gamify::lang.missions.course_explorer.name';
    protected $descriptionKey = 'voilaah.gamify::lang.missions.course_explorer.description';
    protected $completionLabelKey = 'voilaah.gamify::lang.missions.course_explorer.completion_label';
    protected $icon = 'assets/images/missions/course-explorer.svg';
    protected $sort_order = 1;
    protected $completionPoints = 100; // Bonus points for completing all levels

    /**
     * Define the levels of the mission.
     *
     * @return array<int, array> Each level has:
     *   - 'label' => string
     *   - 'description' => string
     *   - 'goal' => int
     *   - 'points' => int
     */
    public function getLevels(): array
    {
        return [
            1 => [
                'labelKey' => 'voilaah.gamify::lang.missions.course_explorer.levels.1.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.course_explorer.levels.1.description',
                'goal' => 1,
                'points' => 25,
            ],
            2 => [
                'labelKey' => 'voilaah.gamify::lang.missions.course_explorer.levels.2.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.course_explorer.levels.2.description',
                'goal' => 2,
                'points' => 25,
            ],
            3 => [
                'labelKey' => 'voilaah.gamify::lang.missions.course_explorer.levels.3.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.course_explorer.levels.3.description',
                'goal' => 3,
                'points' => 25,
            ],
            4 => [
                'labelKey' => 'voilaah.gamify::lang.missions.course_explorer.levels.4.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.course_explorer.levels.4.description',
                'goal' => 4,
                'points' => 25,
            ],
        ];
    }

    /**
     * Get the actual enrollment count for a user.
     */
    public function getActualValue(User $user): int
    {
        // This method should return the actual number of courses the user is enrolled in
        // You'll need to adjust this based on your LMS course enrollment system

        // Example assuming you have a course_enrollments table or relationship:
        // return $user->courseEnrollments()->count();

        // For demo purposes, we'll simulate based on user progress
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
            // Listen for course enrollment events
            'skillup.course.enrolled' => function ($course = null, $user = null, $enrollment = null) {
                return [
                    'user' => $user,
                    'course' => $course,
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
        return 'icon-graduation-cap'; // FontAwesome or October CMS icon
    }
}
