<?php

namespace Voilaah\Gamify\Missions;

use RainLab\User\Models\User;
use Voilaah\Gamify\Classes\Mission\BaseMission;

class FeedbackMaestroMission extends BaseMission
{
    protected $nameKey = 'voilaah.gamify::lang.missions.feedback_maestro.name';
    protected $descriptionKey = 'voilaah.gamify::lang.missions.feedback_maestro.description';
    protected $completionLabelKey = 'voilaah.gamify::lang.missions.feedback_maestro.completion_label';
    protected $icon = 'assets/images/missions/feedback-maestro.svg';
    protected $sort_order = 5;
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
                'labelKey' => 'voilaah.gamify::lang.missions.feedback_maestro.levels.1.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.feedback_maestro.levels.1.description',
                'goal' => 5,
                'points' => 10, // 10 diamonds
            ],
            2 => [
                'labelKey' => 'voilaah.gamify::lang.missions.feedback_maestro.levels.2.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.feedback_maestro.levels.2.description',
                'goal' => 10,
                'points' => 25, // 25 diamonds
            ],
            3 => [
                'labelKey' => 'voilaah.gamify::lang.missions.feedback_maestro.levels.3.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.feedback_maestro.levels.3.description',
                'goal' => 20,
                'points' => 50, // 50 diamonds
            ],
            4 => [
                'labelKey' => 'voilaah.gamify::lang.missions.feedback_maestro.levels.4.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.feedback_maestro.levels.4.description',
                'goal' => 50,
                'points' => 100, // 100 diamonds
            ],
        ];
    }

    /**
     * Get the actual feedback submission count for a user.
     */
    public function getActualValue(User $user): int
    {
        // This should return the number of feedback submissions the user has made
        // Adjust based on your LMS feedback system
        
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
            // Listen for feedback submission events
            'skillup.feedback.submitted' => function ($feedback = null, $user = null, $resource = null) {
                return [
                    'user' => $user,
                    'feedback' => $feedback,
                    'resource' => $resource,
                ];
            },
            
            // Listen for course feedback specifically
            'skillup.course.feedback_submitted' => function ($course = null, $user = null, $feedback = null) {
                return [
                    'user' => $user,
                    'course' => $course,
                    'feedback' => $feedback,
                ];
            },
            
            // Listen for resource feedback
            'skillup.resource.feedback_submitted' => function ($resource = null, $user = null, $feedback = null) {
                return [
                    'user' => $user,
                    'resource' => $resource,
                    'feedback' => $feedback,
                ];
            },
            
            // Alternative event names
            'user.submitted.feedback' => function ($user, $target = null, $feedback = null) {
                return [
                    'user' => $user,
                    'target' => $target,
                    'feedback' => $feedback,
                ];
            },
        ];
    }

    /**
     * Override handleEvent to validate feedback quality
     */
    public function handleEvent(string $event, array $payload = []): void
    {
        if (!isset($payload['user']) || !$payload['user'] instanceof User) {
            \Log::warning("Mission {$this->getCode()} received event '{$event}' without valid user payload.");
            return;
        }

        $user = $payload['user'];
        $feedback = $payload['feedback'] ?? null;

        // Optional: Validate feedback quality (minimum length, etc.)
        if ($feedback) {
            $feedbackText = '';
            
            // Handle different feedback formats
            if (is_string($feedback)) {
                $feedbackText = $feedback;
            } elseif (is_array($feedback) && isset($feedback['content'])) {
                $feedbackText = $feedback['content'];
            } elseif (is_object($feedback) && isset($feedback->content)) {
                $feedbackText = $feedback->content;
            }
            
            // Minimum quality check (optional)
            if (strlen(trim($feedbackText)) < 10) {
                \Log::info("Mission {$this->getCode()} user {$user->id} submitted feedback too short, not counting.");
                return;
            }
        }

        \Log::info("Mission {$this->getCode()} user {$user->id} submitted valid feedback");

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
        return 'icon-comment'; // FontAwesome or October CMS icon
    }
}