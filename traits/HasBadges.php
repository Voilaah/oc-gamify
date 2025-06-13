<?php

namespace Voilaah\Gamify\Traits;

use Voilaah\Gamify\Events\BadgesAwarded;
use Voilaah\Gamify\Events\BadgesRemoved;

trait HasBadges
{
    /**
     * Boot the Has Badges trait for a model
     *
     * @return void
     */
    public static function bootHasBadges()
    {
        static::extend(function ($model) {

            /**
             * Badges user relation
             *
             * @return October\Rain\Database\Relations\BelongsToMany
             */
            $model->belongsToMany['badges'] = [
                config('gamify.badge_model'),
                'table' => 'voilaah_gamify_user_badges',
                'timestamps' => true
            ];
        });
    }

    /**
     * Sync badges for qiven user
     *
     * @param $user
     */
    public function syncBadges($user = null)
    {
        $user = is_null($user) ? $this : $user;

        $badgeIds = app('gamify.badges')
            ->allEnabled()
            ->filter
            ->qualifier($user)
            ->map->getBadgeId();

        $ids = $user->badges()->sync($badgeIds);

        /**
         * array $ids = [
         * 'attached' =>[],
         * 'detached' =>[],
         * 'updated' =>[],
         * ]
         */

        if (!empty($ids['attached'])) {
            BadgesAwarded::dispatch($user, $ids['attached']);
        }

        if (!empty($ids['detached'])) {
            BadgesRemoved::dispatch($user, $ids['detached']);
        }
    }
}
