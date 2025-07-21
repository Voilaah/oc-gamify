<?php

namespace Voilaah\Gamify\Listeners;

use Voilaah\Gamify\Models\Badge;
use Voilaah\Gamify\Events\BadgesAwarded;

class AwardMissionBadges
{
    /**
     * Handle mission level up events
     *
     * @param $user
     * @param $mission
     * @param $level
     * @return void
     */
    public function handle($user, $mission, $level)
    {
        // Find all badges for this mission and level
        $badges = Badge::missionBadges()
            ->forMissionLevel($mission->getCode(), $level)
            ->get();

        foreach ($badges as $badge) {
            // Check if user already has this badge
            if (!$badge->userBadgeExists($badge->id, $user->id)) {
                // Award the badge with mission context
                $badge->awardToWithContext(
                    $user,
                    $level,
                    'mission_level_completion'
                );
                
                \Log::info("Awarded mission badge '{$badge->name}' to user {$user->id} for completing level {$level} of mission '{$mission->getCode()}'");
            }
        }
    }
}