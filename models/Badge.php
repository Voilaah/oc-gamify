<?php

namespace Voilaah\Gamify\Models;

use Model;
use Voilaah\Gamify\Events\BadgeAwarded;
use Voilaah\Gamify\Events\BadgeRemoved;

class Badge extends Model
{
    /* use \October\Rain\Database\Traits\Sortable; */
    use \October\Rain\Database\Traits\Sluggable;

    /**
     * @var string The database table used by the model.
     */
    protected $table = 'voilaah_gamify_badges';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public $slugs = ['slug' => 'name'];

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
    }

    /**
     * Award badge to a user
     *
     * @param $user
     */
    public function awardTo($user)
    {
        $this->users()->attach($user);

        BadgeAwarded::dispatch($user, $this->id);

    }

    /**
     * Remove badge from user
     *
     * @param $user
     */
    public function removeFrom($user)
    {
        $this->users()->detach($user);

        BadgeRemoved::dispatch($user, $this->id);

    }
}
