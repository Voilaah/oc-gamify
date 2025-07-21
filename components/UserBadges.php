<?php

namespace Voilaah\Gamify\Components;

use Cms\Classes\ComponentBase;
use Auth;

/**
 * Badges Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class UserBadges extends ComponentBase
{
    public $allBadges;

    public function componentDetails()
    {
        return [
            'name' => 'User Badges Component',
            'description' => 'No description provided yet...'
        ];
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [
            'showProgress' => [
                'title' => 'Show Progress Bars',
                'description' => 'Show progress toward current level',
                'type' => 'checkbox',
                'default' => true
            ],
            'columnsDesktop' => [
                'title' => 'Desktop Columns',
                'description' => 'Number of columns on desktop',
                'type' => 'string',
                'default' => '3'
            ]
        ];
    }

    public function onRun()
    {
        $user = Auth::getUser();
        if (!$user) {
            $this->page['currentBadges'] = [];
            $this->page['stats'] = [];
            return;
        }

        $currentBadges = $this->getCurrentMissionBadges($user);

        $this->page['currentBadges'] = $currentBadges;
        $this->page['stats'] = $this->getMissionStats($currentBadges);
        $this->page['showProgress'] = $this->property('showProgress');
        $this->page['columnsDesktop'] = $this->property('columnsDesktop');

    }

    function getCurrentMissionBadges($user)
    {
        $missionManager = app('gamify.missions');
        $badgeModel = config('gamify.badge_model');
        $userBadgeIds = $user->badges()->pluck('voilaah_gamify_badges.id')->toArray();

        $currentBadges = [];

        foreach ($missionManager->allEnabled() as $mission) {
            $progress = $mission->getProgress($user);
            $currentLevel = $progress['currentLevel'];

            // Fix: Use direct database query instead of protected method
            $userProgressRecord = \Voilaah\Gamify\Models\UserMissionProgress::where('user_id', $user->id)
                ->where('mission_code', $mission->getCode())
                ->first();

            // Check if mission has started by looking at total_value or any earned badges
            $totalValue = $userProgressRecord ? $userProgressRecord->total_value ?? 0 : 0;
            $missionBadgeIds = $badgeModel::where('mission_code', $mission->getCode())
                ->where('is_mission_badge', true)
                ->pluck('id')
                ->toArray();

            $hasEarnedMissionBadge = !empty(array_intersect($userBadgeIds, $missionBadgeIds));
            $hasStarted = $totalValue > 0 || $hasEarnedMissionBadge || $progress['completed'];

            // Find the badge for user's current level
            $badge = $badgeModel::where('mission_code', $mission->getCode())
                ->where('mission_level', $currentLevel)
                ->where('is_mission_badge', true)
                ->first();

            if ($badge) {
                $levelConfig = $mission->getLevels()[$currentLevel] ?? null;

                $currentBadges[] = [
                    'badge_id' => $badge->id,
                    'name' => $mission->getName() . ' - ' . $mission->getLevelLabel($currentLevel),
                    'description' => $mission->getDescriptionForLevel($currentLevel),
                    'icon' => $mission->getIcon(),
                    'mission_name' => $mission->getName(),
                    'mission_code' => $mission->getCode(),
                    'current_level' => $currentLevel,
                    'max_level' => $mission->getMaxLevel(),
                    'level_label' => $mission->getLevelLabel($currentLevel),
                    'progress' => $progress['value'],
                    'goal' => $progress['goal'],
                    'total_progress' => $totalValue,
                    'points' => $levelConfig['points'] ?? 0,
                    'status' => in_array($badge->id, $userBadgeIds) ? 'earned' : 'in_progress',
                    'completion_percentage' => $progress['goal'] > 0
                        ? round(($progress['value'] / $progress['goal']) * 100, 1)
                        : 0,
                    'is_completed' => $progress['completed'],
                    'has_started' => $hasStarted,
                    'sort_order' => $mission->getSortOrder(),
                ];
            }
        }

        // Sort by mission order
        usort($currentBadges, function ($a, $b) {
            return $a['sort_order'] <=> $b['sort_order'];
        });

        return $currentBadges;
    }

    private function getMissionStats($badges)
    {
        $completedMissions = array_filter($badges, fn($b) => $b['is_completed']);
        $startedMissions = array_filter($badges, fn($b) => $b['has_started']);
        $earnedBadges = array_filter($badges, fn($b) => $b['status'] === 'earned');

        return [
            'total_missions' => count($badges),
            'started_missions' => count($startedMissions),
            'not_started_missions' => count($badges) - count($startedMissions),
            'completed_missions' => count($completedMissions),
            'earned_current_badges' => count($earnedBadges),
            'average_progress' => count($startedMissions) > 0
                ? round(array_sum(array_map(fn($b) => $b['has_started'] ? $b['completion_percentage'] : 0, $badges)) / count($startedMissions), 1)
                : 0
        ];
    }



    /* function getAllMissionBadgesWithStatus($user)
    {
        $missionManager = app('gamify.missions');
        $badgeModel = config('gamify.badge_model');
        $userBadgeIds = $user->badges()->pluck('voilaah_gamify_badges.id')->toArray();

        $allBadges = [];

        foreach ($missionManager->allEnabled() as $mission) {
            $progress = $mission->getProgress($user);
            $missionBadges = $this->getMissionBadges($mission, $userBadgeIds, $progress);

            $allBadges[] = [
                'mission' => [
                    'code' => $mission->getCode(),
                    'name' => $mission->getName(),
                    'description' => $mission->getDescription(),
                    'icon' => $mission->getIcon(),
                ],
                'progress' => $progress,
                'badges' => $missionBadges,
            ];
        }

        return $allBadges;

    }

    private function getMissionBadges($mission, $userBadgeIds, $progress)
    {
        $badgeModel = config('gamify.badge_model');
        $badges = [];

        // Get level badges
        foreach ($mission->getLevels() as $level => $config) {
            $badge = $badgeModel::where('mission_code', $mission->getCode())
                ->where('mission_level', $level)
                ->where('is_mission_badge', true)
                ->first();

            if ($badge) {
                $badges[] = [
                    'badge' => $badge,
                    'level' => $level,
                    'type' => 'level',
                    'status' => $this->getBadgeStatus($badge, $userBadgeIds, $progress, $level),
                    'points' => $config['points'] ?? 0,
                    'goal' => $config['goal'] ?? 0,
                ];
            }
        }

        // Get completion badge
        $completionBadge = $badgeModel::where('mission_code', $mission->getCode())
            ->where('mission_level', 999)
            ->where('is_mission_badge', true)
            ->first();

        if ($completionBadge) {
            $badges[] = [
                'badge' => $completionBadge,
                'level' => 999,
                'type' => 'completion',
                'status' => $this->getBadgeStatus($completionBadge, $userBadgeIds, $progress, 999),
                'points' => $mission->completionPoints ?? 0,
                'goal' => 'Complete all levels',
            ];
        }

        return $badges;

    }

    private function getBadgeStatus($badge, $userBadgeIds, $progress, $level)
    {
        if (in_array($badge->id, $userBadgeIds)) {
            return 'earned';
        }

        if ($level === 999) {
            return $progress['completed'] ? 'earned' : 'locked';
        }

        if ($progress['currentLevel'] >= $level) {
            return 'earned';
        } elseif ($progress['currentLevel'] === $level && $progress['value'] > 0) {
            return 'in_progress';
        } else {
            return 'locked';
        }

    } */


}
