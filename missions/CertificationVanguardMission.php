<?php

namespace Voilaah\Gamify\Missions;

use RainLab\User\Models\User;
use Voilaah\Gamify\Classes\Mission\BaseMission;

class CertificationVanguardMission extends BaseMission
{
    protected $nameKey = 'voilaah.gamify::lang.missions.certification_vanguard.name';
    protected $descriptionKey = 'voilaah.gamify::lang.missions.certification_vanguard.description';
    protected $completionLabelKey = 'voilaah.gamify::lang.missions.certification_vanguard.completion_label';
    protected $icon = 'assets/images/missions/certification-vanguard.svg';
    protected $sort_order = 7;
    protected $completionPoints = 250; // Bonus points for completing all levels

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
                'labelKey' => 'voilaah.gamify::lang.missions.certification_vanguard.levels.1.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.certification_vanguard.levels.1.description',
                'goal' => 2,
                'points' => 20, // 20 diamonds
            ],
            2 => [
                'labelKey' => 'voilaah.gamify::lang.missions.certification_vanguard.levels.2.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.certification_vanguard.levels.2.description',
                'goal' => 5,
                'points' => 50, // 50 diamonds
            ],
            3 => [
                'labelKey' => 'voilaah.gamify::lang.missions.certification_vanguard.levels.3.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.certification_vanguard.levels.3.description',
                'goal' => 10,
                'points' => 100, // 100 diamonds
            ],
            4 => [
                'labelKey' => 'voilaah.gamify::lang.missions.certification_vanguard.levels.4.label',
                'descriptionKey' => 'voilaah.gamify::lang.missions.certification_vanguard.levels.4.description',
                'goal' => 20,
                'points' => 200, // 200 diamonds
            ],
        ];
    }

    /**
     * Get the actual certificate count for a user.
     */
    public function getActualValue(User $user): int
    {
        // This should return the number of certificates the user has earned
        // Adjust based on your LMS certificate system
        
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
            // Listen for certificate earned events
            'skillup.certificate.earned' => function ($certificate = null, $user = null, $course = null) {
                return [
                    'user' => $user,
                    'certificate' => $certificate,
                    'course' => $course,
                ];
            },
            
            // Listen for course completion with certificate
            'skillup.course.completed_with_certificate' => function ($course = null, $user = null, $certificate = null) {
                return [
                    'user' => $user,
                    'course' => $course,
                    'certificate' => $certificate,
                ];
            },
            
            // Alternative event names
            'user.earned.certificate' => function ($user, $certificate = null, $course = null) {
                return [
                    'user' => $user,
                    'certificate' => $certificate,
                    'course' => $course,
                ];
            },
            
            // Listen for certification program completion
            'skillup.certification.completed' => function ($certification = null, $user = null) {
                return [
                    'user' => $user,
                    'certification' => $certification,
                ];
            },
        ];
    }

    /**
     * Override handleEvent to validate certificate authenticity
     */
    public function handleEvent(string $event, array $payload = []): void
    {
        if (!isset($payload['user']) || !$payload['user'] instanceof User) {
            \Log::warning("Mission {$this->getCode()} received event '{$event}' without valid user payload.");
            return;
        }

        $user = $payload['user'];
        $certificate = $payload['certificate'] ?? null;
        $course = $payload['course'] ?? null;

        // Optional: Validate certificate authenticity
        if ($certificate) {
            // Check if certificate is valid/not revoked
            if (is_object($certificate) && method_exists($certificate, 'isValid')) {
                if (!$certificate->isValid()) {
                    \Log::info("Mission {$this->getCode()} user {$user->id} earned invalid certificate, not counting.");
                    return;
                }
            }
            
            // Check if certificate is for a completed course
            if ($course) {
                $courseTitle = is_object($course) ? ($course->title ?? $course->name ?? 'Unknown') : 'Unknown';
                \Log::info("Mission {$this->getCode()} user {$user->id} earned certificate for: {$courseTitle}");
            }
        }

        \Log::info("Mission {$this->getCode()} user {$user->id} earned valid certificate");

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
        return 'icon-certificate'; // FontAwesome or October CMS icon
    }
}