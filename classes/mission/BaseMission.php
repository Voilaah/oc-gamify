<?php

namespace Voilaah\Gamify\Classes\Mission;

use RainLab\User\Models\User;
use Illuminate\Support\{Str, Arr};
use Illuminate\Database\Eloquent\Model;
use Voilaah\Gamify\Models\UserMissionProgress;
use DB;

abstract class BaseMission
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * @var \Voilaah\Gamify\Models\UserMissionProgress
     */
    protected $userProgress;


    /**
     * BadgeType constructor.
     */
    public function __construct()
    {
        $this->model = $this->ensureStored();
        $this->validateLevels();
    }

    /**
     * Define the levels of the mission.
     *
     * @return array<int, array> Each level has:
     *   - 'label' => string
     *   - 'description' => string
     *   - 'qualifier' => Closure(User $user): bool
     */
    abstract public function getLevels(): array;
    abstract public function getActualValue(User $user): int;

    // abstract function isCompleted(User $user): bool;

    /**
     * Get model instance of the badge
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    protected function validateLevels(): void
    {
        $levels = array_keys($this->getLevels());

        if (empty($levels) || $levels[0] !== 1) {
            throw new \RuntimeException("Levels must start from 1 in mission {$this->getCode()}.");
        }

        foreach (range(1, max($levels)) as $expectedLevel) {
            if (!in_array($expectedLevel, $levels)) {
                throw new \RuntimeException("Missing level $expectedLevel in mission {$this->getCode()}.");
            }
        }
    }

    /**
     * Get the mission code
     *
     * @param $user
     * @return bool
     */
    public function getCode(): string
    {
        return property_exists($this, 'code')
            ? $this->code
            : $this->getDefaultCodeName();
    }

    public function getDescriptionForLevel(int $level): ?string
    {
        if ($level == 999)
            return $this->getCompletionMissionLabel();

        if (!array_key_exists($level, $this->getLevels())) {
            \Log::warning("Please define a level {$level} for the mission {$this->getCode()}.");
            return null;
        }

        return $this->getLevels()[$level]['description'] ?? 'Unknown';
    }

    public function getGoalForLevel(int $level): ?int
    {
        if ($level != 999) {
            if (!array_key_exists($level, $this->getLevels())) {
                \Log::warning("Please define a level {$level} for the mission {$this->getCode()}.");
                return null;
            }
            return $this->getLevels()[$level] ? $this->getLevels()[$level]['goal'] : null;
        }
        return 0;
    }

    public function getCompletionMissionLabel(): string
    {
        return property_exists($this, 'completionLabel')
            ? $this->completionLabel
            : $this->getDefaultCompletionLabel();
    }

    public function getMaxLevel(): int
    {
        return max(array_keys($this->getLevels()));
        // return count($this->getLevels());
    }

    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * Get name of badge
     *
     * @return string
     */
    public function getName()
    {
        return property_exists($this, 'name')
            ? $this->name
            : $this->getDefaultMissionName();
    }

    /**
     * Get description of badge
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description ?? '';
    }

    /**
     * Get the icon for badge
     *
     * @return string
     */
    public function getIcon()
    {
        return
            property_exists($this, 'icon')
            ? $this->icon
            : $this->getDefaultIcon();
    }

    /**
     * Get the level for badge
     *
     * @return int
     */
    public function getSortOrder()
    {
        return $this->sort_order ?? 1;
    }

    /**
     * Get badge id
     *
     * @return mixed
     */
    public function getMissionId()
    {
        return $this->model->getKey();
    }

    /**
     * Get the default name if not provided
     *
     * @return string
     */
    protected function getDefaultMissionName()
    {
        return ucwords(Str::snake(class_basename($this), ' '));
    }

    /**
     * Get the default completion label to display
     *
     * @return string
     */
    protected function getDefaultCompletionLabel()
    {
        return "Congrats! Mission accomplished!";
    }

    /**
     * Get the default code if not provided
     *
     * @return string
     */
    protected function getDefaultCodeName()
    {
        return strtolower(Str::snake(class_basename($this), '-'));
    }

    /**
     * Get the default icon if not provided
     *
     * @return string
     */
    protected function getDefaultIcon()
    {
        return sprintf(
            '%s/%s%s',
            rtrim(config('gamify.mission_icon_folder', 'assets/images/missions'), '/'),
            Str::kebab(class_basename($this)),
            config('gamify.mission_icon_extension', '.svg')
        );
    }

    /**
     * Store or update badge
     *
     * @return mixed
     */
    protected function storeMission()
    {

        $modelClass = config('gamify.mission_model');
        if (!class_exists($modelClass)) {
            throw new \RuntimeException("Mission model [$modelClass] does not exist.");
        }

        // $mission = app(config('gamify.mission_model'))
        $mission = app($modelClass)
            ->firstOrNew(['code' => $this->getCode()])
            ->forceFill([
                'name' => $this->getName(),
                'description' => $this->getDescription(),
                'sort_order' => $this->getSortOrder(),
                'icon' => $this->getIcon()
            ]);

        $mission->save();

        return $mission;
    }
    protected function getOrCreateUserMissionProgress($user): UserMissionProgress
    {
        return UserMissionProgress::firstOrNew([
            'user_id' => $user->id,
            'mission_code' => $this->getCode()
        ]);
    }

    protected function userMissionProgressQuery(User $user)
    {
        return UserMissionProgress::query()
            ->where('user_id', $user->id)
            ->where('mission_code', $this->getCode());

        /* return UserMissionProgress::query()
            ->where('user_id', $user->id)
            ->where('mission_code', $this->getCode())
        ; */
    }
    protected function userMissionProgress($user): ?UserMissionProgress
    {
        if ($this->userProgress)
            return $this->userProgress;

        return $this->userProgress = $this->userMissionProgressQuery($user)->first();
    }

    public function markAsCompleted(User $user): void
    {
        UserMissionProgress::updateOrInsert(
            ['user_id' => $user->id, 'mission_code' => $this->getCode()],
            ['is_completed' => true, 'completed_at' => now()]
        );

        /* DB::table('user_mission_progress')
            ->updateOrInsert(
                ['user_id' => $user->id, 'mission_code' => $this->getCode()],
                ['is_completed' => true, 'completed_at' => now()]
            ); */
    }

    public function getLevelLabel(int $level): string
    {
        return match ($level) {
            999 => 'Mission Complete',
            default => $this->getLevels()[$level]['label'] ?? 'Unknown',
        };
    }

    public function getCurrentValue(User $user): int
    {
        $value = 0;

        $userMissionProgress = $this->userMissionProgress($user);

        if ($userMissionProgress) {
            $value = $userMissionProgress->value ?? 0;
        }

        return $value;
    }

    /**
     * Evaluate the current level of the user.
     */
    public function getCurrentLevel(User $user, ?UserMissionProgress $userMissionProgress): int
    {
        $level = 1;

        if (!$userMissionProgress)
            $userMissionProgress = $this->userMissionProgress($user);

        if ($userMissionProgress) {

            $level = $userMissionProgress->level ?? $level;
            if ($userMissionProgress->is_completed) {
                $level = 999;
            }
        }

        return $level;
    }

    /**
     * @deprecated Summary of calculateLevel
     * @param \RainLab\User\Models\User $user
     * @return int|string|null
     */
    public function calculateLevel(User $user)
    {
        // $level = 1;

        // Handle completed missions first
        // if (method_exists($this, 'isCompleted') && $this->isCompleted($user)) {
        //     return 999; // Special code to indicate mission is fully complete
        // }


        $progress = $this->getOrCreateUserMissionProgress($user);
        $levels = $this->getLevels();

        $currentLevel = $progress->level ?? 1;
        $currentValue = $progress->value ?? 0;

        while (isset($levels[$currentLevel]) && $currentValue >= ($levels[$currentLevel]['goal'] ?? PHP_INT_MAX)) {
            $currentLevel++;
            $currentValue = 0; // progress resets at new level
        }

        return isset($levels[$currentLevel]) ? $currentLevel : array_key_last($levels); // Cap at max level


        /* foreach (array_reverse($this->getLevels(), true) as $index => $data) {
            $qualifier = $data['qualifier'];
            if (is_callable($qualifier) && $qualifier($user)) {
                return $index; // return highest qualified level immediately
            }
        }
        return 1; // default to level 1 if none qualify
 */
        /* foreach ($this->getLevels() as $index => $data) {
            $qualifier = $data['qualifier'];
            if (!is_callable($qualifier)) {
                continue;
            }

            if ($qualifier($user)) {
                $level = $index;
            } else {
                break;
            }
        }

        return $level; */
    }

    /**
     * Whether the user qualifies for a specific level.
     */
    /* public function qualifiesForLevel(User $user, int $level): bool
    {
        $levels = $this->getLevels();

        if (!isset($levels[$level])) {
            return false;
        }

        return call_user_func($levels[$level]['qualifier'], $user);
    } */

    /**
     * Return a map of events this mission subscribes to.
     *
     * Format:
     *  'event.name' => function (mixed ...$args): array $payload
     */
    public function getSubscribedEvents(): array
    {
        return [];
    }

    /**
     * Handle a relevant event, optionally updating user mission data.
     *
     * Example: $event = 'user.completed.course'
     */
    public function handleEvent(string $event, array $payload = []): void
    {
        if (!isset($payload['user']) || !$payload['user'] instanceof User) {
            \Log::warning("Mission {$this->getCode()} received event '{$event}' without valid user payload.");
            return;
        }

        $user = $payload['user'];
        $progress = $this->getOrCreateUserMissionProgress($user);
        $levels = $this->getLevels();

        $currentLevel = $progress->level ?? 1;
        $currentValue = $progress->value ?? 0;
        $totalValue = $progress->total_value ?? 0;

        // Prevent overflow if user already completed final level
        if (!isset($levels[$currentLevel])) {
            \Log::info("Mission {$this->getCode()} user {$user->id} already completed all levels.");
            return;
        }


        $levelGoal = $levels[$currentLevel]['goal'] ?? null;

        if (!$levelGoal) {
            \Log::warning("Mission {$this->getCode()} has no goal set for level {$currentLevel}.");
            return;
        }

        // Has user completed this level?
        $hasNextLevel = isset($levels[$currentLevel + 1]);


        // Increment progress toward current level
        $currentValue += 1;
        $totalValue += 1;

        // Check if level up is needed
        if ($currentValue >= $levelGoal) {
            $progress->value = 0; // reset for next level
            $progress->total_value = $totalValue;
            $progress->last_reached_at = now();

            if ($hasNextLevel) {
                $progress->level += 1;
                \Event::fire('gamify.mission.levelUp', [$user, $this, $progress->level]);
            } else {
                $progress->is_completed = true;
                $progress->completed_at = now();
                \Log::info("Mission {$this->getCode()} user {$user->id} completed mission.");
                \Event::fire('gamify.mission.completed', [$user, $this]);
            }
            // $progress->is_completed = $this->isCompleted($user);

            \Log::info("Mission {$this->getCode()} user {$user->id} leveled up to {$progress->level}");

            \Event::fire('gamify.mission.levelUp', [$user, $this, $progress->level]);
        } else {
            // Not reached goal yet
            $progress->value = $currentValue;
            $progress->total_value = $totalValue;
            $progress->last_reached_at = now();
        }

        $progress->save();


        // Fire general progress update event
        \Event::fire('gamify.mission.progressUpdated', [$user, $this, $progress]);

        // If final level and not yet marked complete
        /* if ($this->isCompleted($user) && !$this->hasBeenCompleted($user)) {
            $this->markAsCompleted($user);
        } */
    }

    public function hasBeenCompleted(User $user): bool
    {
        return $this->userMissionProgressQuery($user)
            ->where('is_completed', true)
            ->exists();

        // return DB::table('user_mission_progress')
        //     ->where('user_id', $user->id)
        //     ->where('mission_code', $this->getCode())
        //     ->where('is_completed', true)
        //     ->exists();
    }

    /**
     * Return user progress info.
     */
    public function getProgress(User $user): array
    {
        $progress = $this->getOrCreateUserMissionProgress($user);

        $level = $this->getCurrentLevel($user, $progress);
        $completed = $this->isCompleted($progress); /* $this->isCompleted($user); */

        return [
            'currentLevel' => $level,
            'description' => $this->getDescriptionForLevel($level),
            'value' => $this->getCurrentValue($user), /* $this->getActualValue($user, $level), */
            'goal' => $this->getGoalForLevel($level),
            'maxLevel' => $this->getMaxLevel(),
            'completed' => $completed,
            'data' => [
                'code' => $this->getCode(),
                'name' => $this->getName(),
            ],
        ];
    }

    public function isCompleted(UserMissionProgress $progress): bool
    {
        return $progress ? $progress->is_completed ?? false : false;
    }

    public function ensureStored(): void
    {
        if (!$this->model) {
            $this->model = $this->storeMission();
        }
    }
}
