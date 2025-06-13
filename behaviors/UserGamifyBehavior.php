<?php

namespace Voilaah\Gamify\Behaviors;

use October\Rain\Extension\ExtensionBase;

class UserGamifyBehavior extends ExtensionBase
{
    use \Voilaah\Gamify\Behaviors\Traits\Gamify;

    protected $model;

    public function __construct($model)
    {
        $this->model = $model;
        $this->relationship($model);
    }

    protected function relationship($model)
    {
        $model->hasMany['streaks'] = [
            config('gamify.streak_model'),
            'table' => 'voilaah_gamify_user_streaks',
            'key' => 'user_id'
        ];

        $model->hasMany['streaks_count'] = [
            config('gamify.streak_model'),
            'table' => 'voilaah_gamify_user_streaks',
            'key' => 'user_id',
            'count' => true
        ];

        $model->belongsToMany['missions'] = [
            config('gamify.mission_model'),
            'table' => 'voilaah_gamify_user_mission_progress',
            'otherKey' => 'mission_code',
            'scope' => 'ordered'
        ];

        $model->belongsToMany['missions_count'] = [
            config('gamify.mission_model'),
            'table' => 'voilaah_gamify_user_mission_progress',
            'otherKey' => 'mission_code',
            'count' => true
        ];

        $model->belongsToMany['badges'] = [
            config('gamify.badge_model'),
            'table' => 'voilaah_gamify_user_badges'
        ];

        $model->belongsToMany['badges_count'] = [
            config('gamify.badge_model'),
            'table' => 'voilaah_gamify_user_badges',
            'count' => true
        ];

        $model->hasMany['reputations'] = [
            config('gamify.reputation_model'),
            'key' => 'payee_id',
            'order' => 'created_at desc',
        ];
    }
}
