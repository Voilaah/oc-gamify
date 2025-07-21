<?php

namespace Voilaah\Gamify\Components;

use App;
use Auth;
use Cms\Classes\ComponentBase;
use Voilaah\Gamify\Models\UserMissionProgress;

/**
 * UserMissions Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class UserMissions extends ComponentBase
{
    public $missions;

    public function componentDetails()
    {
        return [
            'name' => 'User Missions Component',
            'description' => 'No description provided yet...'
        ];
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        if ($user = Auth::getUser()) {
            $userMissions = $this->loadUserMissions($user);
            $this->page["missions"] = $userMissions['missions'];
            /* $this->page["missionProgress"] = $userMissions['userMissionProgress']; */
        }
    }

    public function loadUserMissions($user)
    {
        // be aware of the cache
        // $eligibleMissions = app('gamify.missions');

        // App::make('gamify.missions')->find('course_explore');
        $missions = App::make('gamify.missions')->allEnabled();
        $progress = [];
        foreach ($missions as $mission) {
            $mission->progress = $mission->getProgress($user);
        }

        /* $userMissionProgress = UserMissionProgress::where('user_id', $user->id)->get()->keyBy('mission_code'); */

        return [
            "missions" => $missions,
            // "userMissionProgress" => $userMissionProgress,
        ];
    }
}
