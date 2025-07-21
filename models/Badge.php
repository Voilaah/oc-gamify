<?php

namespace Voilaah\Gamify\Models;

use Model;
use Voilaah\Gamify\Events\BadgesAwarded;
use Voilaah\Gamify\Events\BadgesRemoved;

class Badge extends Model
{
    /* use \October\Rain\Database\Traits\Sortable; */
    /* use \October\Rain\Database\Traits\Sluggable; */

    /**
     * @var string The database table used by the model.
     */
    protected $table = 'voilaah_gamify_badges';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    /* public $slugs = ['slug' => 'name']; */

    public function __construct()
    {
        parent::__construct();

        /**
         * @return October\Rain\Database\Relations\BelongsToMany
         */
        $this->belongsToMany['users'] = [
            config('gamify.payee_model'),
            'table' => 'voilaah_gamify_user_badges',
            'timestamps' => true
        ];

        /**
         * @return October\Rain\Database\Relations\BelongsTo
         */
        $this->belongsTo['mission'] = [
            config('gamify.mission_model'),
            'key' => 'mission_code',
            'otherKey' => 'code',
        ];
    }

    /**
     * Award badge to a user
     *
     * @param $user
     */
    public function awardTo($user)
    {
        // if it does not already contains the userId
        if (!$this->userBadgeExists($this->id, $user->id)) {

            $this->users()->attach($user);

            BadgesAwarded::dispatch($user, [$this->id]);
        }

    }

    /**
     * Remove badge from user
     *
     * @param $user
     */
    public function removeFrom($user)
    {
        $this->users()->detach($user);

        BadgesRemoved::dispatch($user, [$this->id]);

    }

    public function userBadgeExists($badgeId, $userId)
    {
        return $this->userBadgeQuery($badgeId, $userId)
            ->exists();
    }

    public function userBadgeQuery($badgeId, $userId)
    {
        return $this->users()->where([
            ['user_id', $userId],
            ['badge_id', $badgeId],
        ]);
    }

    public function getIconImgAttribute()
    {
        return $this->icon;
    }

    /**
     * Check if this badge is linked to a mission
     *
     * @return bool
     */
    public function isMissionBadge(): bool
    {
        return $this->is_mission_badge && !empty($this->mission_code);
    }

    /**
     * Award badge to user with mission context
     *
     * @param $user
     * @param int|null $missionLevel
     * @param string $context
     */
    public function awardToWithContext($user, $missionLevel = null, $context = 'manual')
    {
        if (!$this->userBadgeExists($this->id, $user->id)) {
            $pivotData = [
                'awarded_context' => $context,
                'created_at' => now(),
                'updated_at' => now()
            ];

            if ($missionLevel) {
                $pivotData['awarded_at_level'] = $missionLevel;
            }

            $this->users()->attach($user->id, $pivotData);
            BadgesAwarded::dispatch($user, [$this->id]);
        }
    }

    /**
     * Scope for mission badges
     */
    public function scopeMissionBadges($query)
    {
        return $query->where('is_mission_badge', true);
    }

    /**
     * Scope for specific mission
     */
    public function scopeForMission($query, $missionCode)
    {
        return $query->where('mission_code', $missionCode);
    }

    /**
     * Scope for mission level
     */
    public function scopeForMissionLevel($query, $missionCode, $level)
    {
        return $query->where('mission_code', $missionCode)
                    ->where('mission_level', $level);
    }

}
