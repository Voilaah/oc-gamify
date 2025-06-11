<?php

namespace Voilaah\Gamify\Models;

use Model;
use Voilaah\Gamify\Events\BadgesAwarded;
use Voilaah\Gamify\Events\BadgesRemoved;

class Mission extends Model
{
    /**
     * @var string The database table used by the model.
     */
    protected $table = 'voilaah_gamify_missions';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function __construct()
    {
        parent::__construct();

        /**
         * @return October\Rain\Database\Relations\BelongsToMany
         */
        $this->belongsToMany['users'] = [
            config('gamify.payee_model'),
            'table' => 'voilaah_gamify_user_missions',
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
        $this->users()->attach($user);

        BadgesAwarded::dispatch($user, [$this->id]);

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
}
