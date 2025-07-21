<?php

namespace Voilaah\Gamify\Listeners;

use Voilaah\Gamify\Models\Badge;
use Voilaah\Gamify\Events\BadgesAwarded;

class AwardCompletionBadges
{
    /**
     * Handle mission completion events
     *
     * @param $user
     * @param $mission
     * @return void
     */
    public function handle($user, $mission)
    {
        // Find completion badges for this mission (level 999 or special completion badges)
        $badges = Badge::missionBadges()
            ->forMission($mission->getCode())
            ->where(function ($query) {
                $query->where('mission_level', 999)
                      ->orWhere('mission_level', 0); // 0 can represent completion badge
            })
            ->get();

        foreach ($badges as $badge) {
            // Check if user already has this badge
            if (!$badge->userBadgeExists($badge->id, $user->id)) {
                // Award the completion badge
                $badge->awardToWithContext(
                    $user,
                    999,
                    'mission_completion'
                );
                
                \Log::info("Awarded completion badge '{$badge->name}' to user {$user->id} for completing mission '{$mission->getCode()}'");
            }
        }
    }
}