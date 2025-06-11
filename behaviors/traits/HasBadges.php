<?php

namespace Voilaah\Gamify\Behaviors\Traits;

use Voilaah\Gamify\Events\BadgesAwarded;
use Voilaah\Gamify\Events\BadgesRemoved;
use Voilaah\Gamify\Events\BadgesUpdated;

trait HasBadges
{
    /**
     * Sync badges for qiven user
     *
     * @param $user
     */
    public function syncBadges($user = null)
    {
        $user = is_null($user) ? $this->model : $user;

        $badgeIds = app('badges')->filter
            ->qualifier($user)
            ->map->getBadgeId();

        // traceLog($badgeIds);

        $ids = $user->badges()->sync($badgeIds);
        /* $ids = $user->badges()->syncWithPivotValues(
            $badgeIds,
            [
                'created_at' => \Carbon\Carbon::now(),
                // 'updated_at' => \Carbon\Carbon::now(),
            ]
        ); */

        /**
         * array $ids = [
         * 'attached' =>[],
         * 'detached' =>[],
         * 'updated' =>[],
         * ]
         */
        // traceLog($ids);

        if (!empty($ids['attached'])) {
            BadgesAwarded::dispatch($user, $ids['attached']);
        }

        if (!empty($ids['detached'])) {
            BadgesRemoved::dispatch($user, $ids['detached']);
        }

        if (!empty($ids['updated'])) {
            BadgesUpdated::dispatch($user, $ids['updated']);
        }
    }
}
