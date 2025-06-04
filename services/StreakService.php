<?php
namespace Voilaah\Gamify\Services;

use Carbon\Carbon;
use Voilaah\Gamify\Models\UserStreak;
use Illuminate\Support\Facades\Log;

class StreakService
{
    /**
     * Update a streak for a user based on the given type and date.
     *
     * @param \RainLab\User\Models\User $user
     * @param string $type
     * @param string|\Carbon\Carbon $date
     * @return void
     */
    public static function updateStreak($user, string $type, $date)
    {
        $date = Carbon::parse($date)->startOfDay()->toDateString(); // Normalize

        $streak = UserStreak::firstOrNew([
            'user_id' => $user->id,
            'streak_type' => $type,
        ]);

        $lastDate = $streak->last_activity_date
            ? Carbon::parse($streak->last_activity_date)->startOfDay()
            : null;

        // Load or initialize streak_dates as an array
        $streakDates = is_array($streak->streak_dates)
            ? $streak->streak_dates
            : json_decode($streak->streak_dates, true) ?? [];

        if ($lastDate === null) {
            // First streak entry
            $streak->current_streak = 1;
            $streak->longest_streak = 1;
        } else {
            $daysDiff = $lastDate->diffInDays(Carbon::parse($date));

            if ($daysDiff === 1) {
                $streak->current_streak += 1;
                $streak->longest_streak = max($streak->longest_streak, $streak->current_streak);
            } elseif ($daysDiff > 1) {
                $streak->current_streak = 1;
            } else {
                // Same day login → no update needed
                return;
            }
        }

        // Add the current date if not already stored
        if (!in_array($date, $streakDates)) {
            $streakDates[] = $date;
        }

        // Prune to most recent 730 entries
        $streak->streak_dates = self::pruneOldStreakDates($streakDates);

        $streak->last_activity_date = $date;
        $streak->streak_dates = array_values($streakDates); // Reset indexes
        $streak->save();
    }


    protected static function pruneOldStreakDates(array $dates, int $maxDays = 730): array
    {
        return collect($dates)
            ->mapWithKeys(fn($v, $k) => [Carbon::parse($k)->toDateString() => true])
            ->sortKeysDesc()
            ->take($maxDays)
            ->sortKeys()
            ->all();
    }

}
