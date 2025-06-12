<?php

namespace Voilaah\Gamify\Behaviors\Traits;

use Voilaah\Gamify\Events\BadgesAwarded;
use Voilaah\Gamify\Events\BadgesRemoved;
use Voilaah\Gamify\Events\BadgesUpdated;

trait HasMissions
{
    /**
     * Sync badges for qiven user
     *
     * @param $user
     */
    public function syncMission($user = null)
    {
        $user = is_null($user) ? $this->model : $user;

        $missionIds = app('missions')->filter
            ->qualifier($user)
            ->map->getMissionId();

        // traceLog($missionIds);

        $ids = $user->badges()->sync($missionIds);
        /* $ids = $user->badges()->syncWithPivotValues(
            $missionIds,
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

        // TODO
        /* if (!empty($ids['attached'])) {
            BadgesAwarded::dispatch($user, $ids['attached']);
        }

        if (!empty($ids['detached'])) {
            BadgesRemoved::dispatch($user, $ids['detached']);
        }

        if (!empty($ids['updated'])) {
            BadgesUpdated::dispatch($user, $ids['updated']);
        } */
    }
}
