<?php

namespace Syehan\Gamify\Behaviors\Traits;

trait HasBadges
{
    /**
     * Sync badges for qiven user
     *
     * @param $user
     */
    public function syncBadges($user = null)
    {
        traceLog('Sync badges for qiven user from Behaviors trait');
        $user = is_null($user) ? $this->model : $user;

        traceLog($user->email);

        $badgeIds = app('badges')->filter
            ->qualifier($user)
            ->map->getBadgeId();

        traceLog($badgeIds);

        $user->badges()->sync($badgeIds);
    }
}
