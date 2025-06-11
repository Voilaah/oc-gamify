<?php

namespace Voilaah\Gamify\Models;

use Model;
use Voilaah\Gamify\Events\BadgesAwarded;
use Voilaah\Gamify\Events\BadgesRemoved;

class Badge extends Model
{
    /**
     * change the primary key so that we can control the key itself
     * in case we add / remove or update new Badge while user has badges
     *
     * @var string
     */
    // protected $primaryKey = 'unique_key';

    /**
     * boolean that if false indicates that the primary key is not an incrementing integer value.
     *
     * @var bool
     */
    // public $incrementing = false;

    /**
     * @var string The database table used by the model.
     */
    protected $table = 'voilaah_gamify_badges';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $dates = ['created_at', 'updated_at'];

    public function __construct()
    {
        parent::__construct();

        /**
         * @return October\Rain\Database\Relations\BelongsToMany
         */
        $this->belongsToMany['users'] = [
            config('gamify.payee_model'),
            'table' => 'voilaah_gamify_user_badges',
            // 'otherKey' => 'unique_key'
            'timestamps' => true
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

            BadgesAwarded::dispatch($user, $this->id);

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

        BadgesRemoved::dispatch($user, $this->id);

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

}
