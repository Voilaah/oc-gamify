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

    abstract public function getGoalForLevel(int $level): int;

    abstract public function getCurrentValue(User $user, int $level): int;
    abstract function isCompleted(User $user): bool;

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
        if (!array_key_exists($level, $this->getLevels())) {
            \Log::warning("Please define a level {$level} for the mission {$this->getCode()}.");
            return null;
        }
        return $this->getLevels()[$level] ? $this->getLevels()[$level]['description'] : null;
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

    /**
     * Evaluate the current level of the user.
     */
    public function getCurrentLevel(User $user): int
    {
        // Handle completed missions first
        if (method_exists($this, 'isCompleted') && $this->isCompleted($user)) {
            return 999; // Special code to indicate mission is fully complete
        }

        $level = 0;

        foreach ($this->getLevels() as $index => $data) {
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

        return $level;
    }

    /**
     * Whether the user qualifies for a specific level.
     */
    public function qualifiesForLevel(User $user, int $level): bool
    {
        $levels = $this->getLevels();

        if (!isset($levels[$level])) {
            return false;
        }

        return call_user_func($levels[$level]['qualifier'], $user);
    }

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

        // Log event received with user ID and event name
        \Log::info("Mission {$this->getCode()} handling event '{$event}' for user ID {$user->id}", [
            'event' => $event,
            'user_id' => $user->id,
            'payload' => $payload,
        ]);

        $newLevel = $this->getCurrentLevel($user);

        // You can also log the current level for extra clarity
        \Log::info("Mission {$this->getCode()} user {$user->id} current level evaluated as {$newLevel}");


        $progress = UserMissionProgress::firstOrNew([
            'user_id' => $user->id,
            'mission_code' => $this->getCode()
        ]);

        if ($newLevel > $progress->current_level) {
            $progress->current_level = $newLevel;
            $progress->last_reached_at = now();
            $progress->save();

            // Optionally fire an event
            \Event::fire('voilaah.gamify.mission.levelUp', [$user, $this, $newLevel]);
        }

        if ($newLevel === 999 && !$this->hasBeenCompleted($user)) {
            $this->markAsCompleted($user);
        }
    }

    public function hasBeenCompleted(User $user): bool
    {
        return DB::table('user_mission_progress')
            ->where('user_id', $user->id)
            ->where('mission_code', $this->getCode())
            ->where('is_completed', true)
            ->exists();
    }

    /**
     * Return user progress info.
     */
    public function getProgress(User $user): array
    {
        $level = $this->getCurrentLevel($user);
        $completed = $this->isCompleted($user);

        return [
            'currentLevel' => $level,
            'description' => $this->getDescriptionForLevel($level),
            'goal' => $this->getGoalForLevel($level),
            'value' => $this->getCurrentValue($user, $level),
            'maxLevel' => $this->getMaxLevel(),
            'completed' => $completed,
            'data' => [
                'code' => $this->getCode(),
                'name' => $this->getName(),
            ],
        ];
    }

    public function ensureStored(): void
    {
        if (!$this->model) {
            $this->model = $this->storeMission();
        }
    }
}
