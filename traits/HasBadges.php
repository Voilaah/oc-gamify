<?php

namespace Voilaah\Gamify\Traits;

use Voilaah\Gamify\Events\BadgeAwarded;
use Voilaah\Gamify\Events\BadgeRemoved;

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

        $badgeIds = app('badges')->filter
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
