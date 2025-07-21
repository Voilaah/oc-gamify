<?php

namespace Voilaah\Gamify\Missions;

use RainLab\User\Models\User;
use Voilaah\Gamify\Classes\Mission\BaseMission;

class LearningEpicMission extends BaseMission
{
    protected $nameKey = 'voilaah.gamify::lang.missions.learning_epic.name';
    protected $descriptionKey = 'voilaah.gamify::lang.missions.learning_epic.description';
    protected $completionLabelKey = 'voilaah.gamify::lang.missions.learning_epic.completion_label';
    protected $icon = 'assets/images/missions/learning-epic.svg';
    protected $sort_order = 4;
    protected $completionPoints = 300; // Bonus points for completing all levels

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
                'labelKey' => 'voilaah.gamify::lang.missions.learning_epic.levels.1.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.learning_epic.levels.1.description',
                'goal' => 10,
                'points' => 50, // 50 diamonds
            ],
            2 => [
                'labelKey' => 'voilaah.gamify::lang.missions.learning_epic.levels.2.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.learning_epic.levels.2.description',
                'goal' => 20,
                'points' => 100, // 100 diamonds
            ],
            3 => [
                'labelKey' => 'voilaah.gamify::lang.missions.learning_epic.levels.3.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.learning_epic.levels.3.description',
                'goal' => 30,
                'points' => 200, // 200 diamonds
            ],
            4 => [
                'labelKey' => 'voilaah.gamify::lang.missions.learning_epic.levels.4.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.learning_epic.levels.4.description',
                'goal' => 50,
                'points' => 400, // 400 diamonds
            ],
        ];
    }

    /**
     * Get the actual cross-category course completion count for a user.
     */
    public function getActualValue(User $user): int
    {
        // This should return the number of courses completed across different categories
        // For true cross-category tracking, you might want to count unique categories
        // and ensure courses span multiple categories

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
            // Listen for course completion events (cross-category)
            'skillup.course.completed' => function ($course, $user, $completion = null) {
                return [
                    'user' => $user,
                    'course' => $course,
                    'completion' => $completion,
                ];
            },
        ];
    }

    /**
     * Override handleEvent to potentially track category diversity
     */
    public function handleEvent(string $event, array $payload = []): void
    {
        if (!isset($payload['user']) || !$payload['user'] instanceof User) {
            \Log::warning("Mission {$this->getCode()} received event '{$event}' without valid user payload.");
            return;
        }

        $user = $payload['user'];
        $course = $payload['course'] ?? null;

        // Optional: Track category diversity (only if course has category)
        if ($course && (isset($course->category_id) || isset($course->category))) {
            $progress = $this->getOrCreateUserMissionProgress($user);

            // Store completed categories in metadata
            $categories = [];
            if ($progress->meta) {
                $categories = json_decode($progress->meta, true) ?: [];
            }

            $categoryId = $course->category_id ?? $course->category ?? 'general';

            if (!in_array($categoryId, $categories)) {
                $categories[] = $categoryId;
                $progress->meta = json_encode($categories);
                $progress->save();

                \Log::info("Mission {$this->getCode()} user {$user->id} completed course in new category {$categoryId}");
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
        return true;
    }

    /**
     * Get the default icon if not provided
     */
    protected function getDefaultIcon(): string
    {
        return 'icon-book'; // FontAwesome or October CMS icon
    }
}
