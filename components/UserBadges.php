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

        $this->addJs('/plugins/voilaah/gamify/assets/js/drag.js');

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

            // Calculate achieved level (current level - 1, with special handling)
            $achievedLevel = $currentLevel > 1 ? $currentLevel - 1 : 0;

            // If mission is completed (level 999), show completion badge
            if ($progress['completed']) {
                $achievedLevel = 999;
            }

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

            // Find the badge for the achieved level (or current level if no achievement yet)
            $displayLevel = $achievedLevel > 0 ? $achievedLevel : $currentLevel;
            $badge = $badgeModel::where('mission_code', $mission->getCode())
                ->where('mission_level', $displayLevel)
                ->where('is_mission_badge', true)
                ->first();

            if ($badge) {
                $levelConfig = $mission->getLevels()[$displayLevel] ?? null;

                // Determine status based on achieved level
                $status = 'not_started';
                $levelLabel = '-';

                if ($achievedLevel > 0) {
                    $status = 'earned';
                    $levelLabel = $mission->getLevelLabel($achievedLevel);
                } elseif ($hasStarted) {
                    $status = 'in_progress';
                    $levelLabel = '-';
                } else {
                    $status = 'not_started';
                    $levelLabel = '-';
                }

                $currentBadges[] = [
                    'badge_id' => $badge->id,
                    'name' => $mission->getName() . ' - ' . ($achievedLevel > 0 ? $mission->getLevelLabel($achievedLevel) : 'Not Yet Achieved'),
                    'description' => $achievedLevel > 0 ? $mission->getDescriptionForLevel($achievedLevel) : 'Keep progressing to earn your first badge!',
                    'icon' => $mission->getIcon(),
                    'mission_name' => $mission->getName(),
                    'mission_code' => $mission->getCode(),
                    'current_level' => $currentLevel,
                    'achieved_level' => $achievedLevel,
                    'display_level' => $displayLevel,
                    'max_level' => $mission->getMaxLevel(),
                    'level_label' => $levelLabel,
                    'progress' => $progress['value'],
                    'goal' => $progress['goal'],
                    'total_progress' => $totalValue,
                    'points' => $levelConfig['points'] ?? 0,
                    'status' => $status,
                    'completion_percentage' => $progress['goal'] > 0
                        ? round(($progress['value'] / $progress['goal']) * 100, 1)
                        : 0,
                    'is_completed' => $progress['completed'],
                    'has_started' => $hasStarted,
                    'sort_order' => $mission->getSortOrder(),
                ];
            }
        }

        // Sort by non-grayed badges first (has_started OR achieved_level > 0)
        usort($currentBadges, function ($a, $b) {
            // A badge is NOT grayed if: has_started OR achieved_level > 0
            $aIsNotGrayed = ($a['has_started'] || $a['achieved_level'] > 0) ? 1 : 0;
            $bIsNotGrayed = ($b['has_started'] || $b['achieved_level'] > 0) ? 1 : 0;

            // First, prioritize non-grayed badges
            if ($aIsNotGrayed !== $bIsNotGrayed) {
                return $bIsNotGrayed <=> $aIsNotGrayed; // Show non-grayed first
            }

            // Among non-grayed badges, sort by achieved level (highest first)
            if ($a['achieved_level'] !== $b['achieved_level']) {
                return $b['achieved_level'] <=> $a['achieved_level']; // Highest level first
            }

            // Finally, sort by mission order as fallback
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

    public function onFetchMission()
    {
        $missionCode = post('id');
        $user = Auth::getUser();

        if (!$user || !$missionCode) {
            return [
                'error' => 'Invalid request'
            ];
        }

        // Get the mission directly by mission code
        $missionManager = app('gamify.missions');
        $mission = $missionManager->find($missionCode);

        if (!$mission) {
            return [
                'error' => 'Mission not found'
            ];
        }

        // Get user progress for this mission
        $progress = $mission->getProgress($user);
        $currentLevel = $progress['currentLevel'];
        $userBadgeIds = $user->badges()->pluck('voilaah_gamify_badges.id')->toArray();

        // Calculate achieved level (same logic as in badge list)
        $achievedLevel = $currentLevel > 1 ? $currentLevel - 1 : 0;

        // If mission is completed (level 999), show completion badge
        if ($progress['completed']) {
            $achievedLevel = 999;
        }

        // Get user progress record for total values
        $userProgressRecord = \Voilaah\Gamify\Models\UserMissionProgress::where('user_id', $user->id)
            ->where('mission_code', $mission->getCode())
            ->first();

        $totalValue = $userProgressRecord ? $userProgressRecord->total_value ?? 0 : 0;

        // Build mission data with all levels
        $missionData = [
            'mission_code' => $mission->getCode(),
            'mission_name' => $mission->getName(),
            'mission_description' => $mission->getDescription(),
            'mission_icon' => $mission->getIcon(),
            'current_level' => $currentLevel,
            'achieved_level' => $achievedLevel,
            'is_completed' => $progress['completed'],
            'total_progress' => $totalValue,
            'levels' => []
        ];

        // Get all level badges for this mission
        $badgeModel = config('gamify.badge_model');
        $levelBadges = $badgeModel::where('mission_code', $mission->getCode())
            ->where('is_mission_badge', true)
            ->orderBy('mission_level')
            ->get()
            ->keyBy('mission_level');

        // Build level data
        foreach ($mission->getLevels() as $level => $config) {
            $levelBadge = $levelBadges->get($level);
            $hasEarnedLevel = $levelBadge && in_array($levelBadge->id, $userBadgeIds);

            // Calculate progress for this level
            $levelProgress = 0;
            $levelStatus = 'locked';

            if ($level < $progress['currentLevel']) {
                // Past levels are completed
                $levelProgress = 100;
                $levelStatus = 'completed';
            } elseif ($level == $progress['currentLevel']) {
                // Current level shows actual progress
                $levelProgress = $progress['goal'] > 0
                    ? round(($progress['value'] / $progress['goal']) * 100, 1)
                    : 0;
                $levelStatus = $hasEarnedLevel ? 'completed' : 'in_progress';
            } elseif ($level == $progress['currentLevel'] + 1) {
                // Next level is available but not started
                $levelStatus = 'available';
            }

            $missionData['levels'][] = [
                'level' => $level,
                'label' => $mission->getLevelLabel($level),
                'description' => $mission->getDescriptionForLevel($level),
                'goal' => $config['goal'] ?? 0,
                'points' => $config['points'] ?? 0,
                'icon' => $mission->getIcon(), // You might want level-specific icons
                'progress' => $levelProgress,
                'status' => $levelStatus,
                'is_earned' => $hasEarnedLevel,
                'badge_id' => $levelBadge ? $levelBadge->id : null,
            ];
        }

        // Add completion level if exists
        $completionBadge = $levelBadges->get(999);
        if ($completionBadge) {
            $hasEarnedCompletion = in_array($completionBadge->id, $userBadgeIds);

            $missionData['levels'][] = [
                'level' => 999,
                'label' => 'Mission Complete',
                'description' => 'You have mastered this mission!',
                'goal' => 0,
                'points' => $mission->completionPoints ?? 0,
                'icon' => $mission->getIcon(),
                'progress' => $progress['completed'] ? 100 : 0,
                'status' => $progress['completed'] ? 'completed' : 'locked',
                'is_earned' => $hasEarnedCompletion,
                'badge_id' => $completionBadge->id,
            ];
        }

        $this->page['missionData'] = $missionData;

        return [
            'missionData' => $missionData
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
