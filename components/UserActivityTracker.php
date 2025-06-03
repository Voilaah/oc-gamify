<?php
namespace Voilaah\Gamify\Components;

use Auth;
use Cache;
use Carbon\Carbon;
use Cms\Classes\ComponentBase;
use Voilaah\Gamify\Models\UserLoginStreak;

/**
 * UserActivityTracker Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class UserActivityTracker extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'User Activity Tracker Component',
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
            $this->trackUserActivity($user);
        }
    }

    protected function trackUserActivity($user)
    {
        $today = Carbon::now()->toDateString();
        $cacheKey = "user_activity_{$user->id}_{$today}";

        // Only track once per day per user (performance optimization)
        if (!Cache::get($cacheKey)) {
            // Touch last seen (your requirement)
            $user->touchLastSeen();

            // Update streak
            $this->updateStreak($user, $today);

            // Cache until end of day
            $minutesUntilMidnight = Carbon::now()->endOfDay()->diffInMinutes();
            Cache::put($cacheKey, true, $minutesUntilMidnight);
        }
    }

    protected function updateStreak($user, $date)
    {
        $streak = UserLoginStreak::firstOrCreate(['user_id' => $user->id]);

        if (!$streak->last_activity_date) {
            // First activity
            $streak->current_streak = 1;
            $streak->longest_streak = 1;
        } else {
            $lastDate = Carbon::parse($streak->last_activity_date);
            $currentDate = Carbon::parse($date);
            $daysDiff = $lastDate->diffInDays($currentDate);

            if ($daysDiff === 1) {
                // Consecutive day - increment streak
                $streak->current_streak++;
                if ($streak->current_streak > $streak->longest_streak) {
                    $streak->longest_streak = $streak->current_streak;
                }
            } elseif ($daysDiff > 1) {
                // Streak broken - reset
                $streak->current_streak = 1;
            }
            // Same day = no change
        }

        $streak->last_activity_date = $date;
        $streak->save();
    }

}
