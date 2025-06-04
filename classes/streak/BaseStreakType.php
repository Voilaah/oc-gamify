<?php

namespace Voilaah\Gamify\Classes\Streak;

use Carbon\Carbon;
use RainLab\User\Models\User;
use Illuminate\Support\Collection;
use Voilaah\Gamify\Classes\PointType;
use Voilaah\Gamify\Models\UserStreak;
use Voilaah\Gamify\Services\StreakService;

abstract class BaseStreakType
{
    protected ?UserStreak $streak = null;

    /**
     * Unique type code (e.g. 'user_login', 'top_50_rank')
     */
    abstract public function getTypeCode(): string;


    // Optional: define milestones for point rewards
    public function getPointMilestones(): array
    {
        return []; // Override in subclasses
    }

    /**
     * Optional helper
     */
    public function getPointForStreak(int $streakCount): ?PointType
    {
        $milestones = $this->getPointMilestones();

        return isset($milestones[$streakCount])
            ? new $milestones[$streakCount]($this->streak)
            : null;
    }

    public function setStreak(UserStreak $streak)
    {
        $this->streak = $streak;
    }


    public function shouldGivePoint(int $currentStreak): bool
    {
        return in_array($currentStreak, $this->getPointMilestones());
    }



    /**
     * Real-time check to determine if this user activity should trigger streak logic
     */
    public function shouldHandleUser(User $user): bool
    {
        return false; // Override in real-time streaks like login
    }

    /**
     * For scheduled streaks: returns users eligible to maintain streak
     */
    public function getUsersToUpdate(): Collection
    {
        return collect(); // Override in scheduled types
    }

    /**
     * Should this streak type be run by a scheduler?
     */
    public function isScheduled(): bool
    {
        return false; // Override in scheduled types
    }

    /**
     * Daily batch update for scheduled streak types
     */
    public function updateForToday(): void
    {
        if (!$this->isScheduled()) {
            return;
        }

        $today = Carbon::today()->toDateString();
        $eligibleUsers = $this->getUsersToUpdate();
        $eligibleIds = $eligibleUsers->pluck('id')->toArray();

        foreach ($eligibleUsers as $user) {
            StreakService::updateStreak($user, $this->getTypeCode(), $today);
        }

        // Reset streak for users who were active yesterday but not today
        $yesterday = Carbon::yesterday()->toDateString();
        UserStreak::where('streak_type', $this->getTypeCode())
            ->where('last_activity_date', $yesterday)
            ->whereNotIn('user_id', $eligibleIds)
            ->update(['current_streak' => 0]);
    }
}
