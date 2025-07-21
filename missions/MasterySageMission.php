<?php

namespace Voilaah\Gamify\Missions;

use RainLab\User\Models\User;
use Voilaah\Gamify\Classes\Mission\BaseMission;

class MasterySageMission extends BaseMission
{
    protected $nameKey = 'voilaah.gamify::lang.missions.mastery_sage.name';
    protected $descriptionKey = 'voilaah.gamify::lang.missions.mastery_sage.description';
    protected $completionLabelKey = 'voilaah.gamify::lang.missions.mastery_sage.completion_label';
    protected $icon = 'assets/images/missions/mastery-sage.svg';
    protected $sort_order = 3;
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
                'labelKey' => 'voilaah.gamify::lang.missions.mastery_sage.levels.1.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.mastery_sage.levels.1.description',
                'goal' => 1,
                'points' => 15, // 15 diamonds
            ],
            2 => [
                'labelKey' => 'voilaah.gamify::lang.missions.mastery_sage.levels.2.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.mastery_sage.levels.2.description',
                'goal' => 3,
                'points' => 40, // 40 diamonds
            ],
            3 => [
                'labelKey' => 'voilaah.gamify::lang.missions.mastery_sage.levels.3.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.mastery_sage.levels.3.description',
                'goal' => 5,
                'points' => 75, // 75 diamonds
            ],
            4 => [
                'labelKey' => 'voilaah.gamify::lang.missions.mastery_sage.levels.4.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.mastery_sage.levels.4.description',
                'goal' => 10,
                'points' => 150, // 150 diamonds
            ],
        ];
    }

    /**
     * Get the actual perfect score count for a user.
     */
    public function getActualValue(User $user): int
    {
        // This should return the number of perfect scores the user has achieved
        // Adjust based on your LMS assessment system
        
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
            // Listen for perfect assessment score events
            'skillup.assessment.perfect_score' => function ($assessment = null, $user = null, $score = null) {
                return [
                    'user' => $user,
                    'assessment' => $assessment,
                    'score' => $score,
                ];
            },
            
            // Alternative event for assessment completion with perfect score
            'skillup.assessment.completed' => function ($assessment = null, $user = null, $result = null) {
                // Only count if it's a perfect score
                if ($result && isset($result['score']) && isset($result['max_score'])) {
                    if ($result['score'] >= $result['max_score']) {
                        return [
                            'user' => $user,
                            'assessment' => $assessment,
                            'result' => $result,
                        ];
                    }
                }
                return null; // Don't process non-perfect scores
            },
            
            // Alternative event names
            'user.perfect.assessment' => function ($user, $assessment = null) {
                return [
                    'user' => $user,
                    'assessment' => $assessment,
                ];
            },
        ];
    }

    /**
     * Override handleEvent to filter out non-perfect scores
     */
    public function handleEvent(string $event, array $payload = []): void
    {
        if (!isset($payload['user']) || !$payload['user'] instanceof User) {
            \Log::warning("Mission {$this->getCode()} received event '{$event}' without valid user payload.");
            return;
        }

        // For assessment.completed events, we need to verify it's a perfect score
        if ($event === 'skillup.assessment.completed') {
            $result = $payload['result'] ?? null;
            if (!$result || !isset($result['score']) || !isset($result['max_score'])) {
                return; // Not enough data to verify perfect score
            }
            
            if ($result['score'] < $result['max_score']) {
                return; // Not a perfect score, don't process
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
        return 'icon-graduation-cap'; // FontAwesome or October CMS icon
    }
}