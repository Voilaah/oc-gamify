<?php

namespace Voilaah\Gamify\Behaviors\Traits;

use Voilaah\Gamify\Events\BadgeAwarded;
use Voilaah\Gamify\Events\BadgeRemoved;

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

        $ids = $user->badges()->syncWithPivotValues(
            $badgeIds,
            [
                'created_at' => \Carbon\Carbon::now(),
                // 'updated_at' => \Carbon\Carbon::now(),
            ]
        );

        // trace_log($ids);

        /**
         * array $ids = [
         * 'attached' =>[],
         * 'detached' =>[],
         * 'updated' =>[],
         * ]
         */

        if (!empty($ids['attached'])) {
            foreach ($ids['attached'] as $badgeId) {
                BadgeAwarded::dispatch($user, $badgeId);
            }
        }

        if (!empty($ids['detached'])) {
            foreach ($ids['detached'] as $badgeId) {
                BadgeRemoved::dispatch($user, $badgeId);
            }
        }
    }
}
