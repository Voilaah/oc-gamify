<?php

namespace Voilaah\Gamify\Classes\Mission;

use RainLab\User\Models\User;
use Illuminate\Support\{Str, Arr};
use Illuminate\Database\Eloquent\Model;
use Voilaah\Gamify\Models\UserMissionProgress;
use Voilaah\Gamify\Classes\PointType;
use DB;
use Lang;
use App;

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
     *   - 'points' => int (optional)
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

        $levelData = $this->getLevels()[$level];

        // Check for translation key first
        if (isset($levelData['descriptionKey'])) {
            return Lang::get($levelData['descriptionKey'], [], null, App::getLocale());
        }

        return $levelData['description'] ?? Lang::get('voilaah.gamify::lang.common.unknown');
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

    public function getPointsForLevel(int $level): ?int
    {
        if ($level == 999) {
            return property_exists($this, 'completionPoints') ? $this->completionPoints : null;
        }

        if (!array_key_exists($level, $this->getLevels())) {
            return null;
        }

        return $this->getLevels()[$level]['points'] ?? null;
    }

    public function getCompletionMissionLabel(): string
    {
        if (property_exists($this, 'completionLabelKey')) {
            return Lang::get($this->completionLabelKey, [], null, App::getLocale());
        }

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
     * Get name of mission
     *
     * @return string
     */
    public function getName()
    {
        if (property_exists($this, 'nameKey')) {
            return Lang::get($this->nameKey, [], null, App::getLocale());
        }

        return property_exists($this, 'name')
            ? $this->name
            : $this->getDefaultMissionName();
    }

    /**
     * Get description of mission
     *
     * @return string
     */
    public function getDescription()
    {
        if (property_exists($this, 'descriptionKey')) {
            return Lang::get($this->descriptionKey, [], null, App::getLocale());
        }

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
        if ($level === 999) {
            return Lang::get('voilaah.gamify::lang.common.mission_complete');
        }

        $levels = $this->getLevels();
        if (!isset($levels[$level])) {
            return Lang::get('voilaah.gamify::lang.common.unknown');
        }

        $levelData = $levels[$level];

        // Check for translation key first
        if (isset($levelData['labelKey'])) {
            return Lang::get($levelData['labelKey'], [], null, App::getLocale());
        }

        return $levelData['label'] ?? Lang::get('voilaah.gamify::lang.common.unknown');
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
        
        // Ensure progress record has correct initial values
        if (!$progress->level) $progress->level = 1;
        if (!$progress->value) $progress->value = 0;
        if (!$progress->total_value) $progress->total_value = 0;

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
            // Award points and fire events for the completed level BEFORE resetting
            $completedLevel = $currentLevel;
            $this->awardPointsForLevel($user, $completedLevel);
            \Event::fire('gamify.mission.levelUp', [$user, $this, $completedLevel]);
            \Log::info("Mission {$this->getCode()} user {$user->id} completed level {$completedLevel}");

            $progress->total_value = $totalValue;
            $progress->last_reached_at = now();

            if ($hasNextLevel) {
                // Move to next level and reset progress
                $progress->level += 1;
                $progress->value = 0; // reset for next level
                \Log::info("Mission {$this->getCode()} user {$user->id} moved to level {$progress->level}");
            } else {
                // Mission completed entirely
                $progress->is_completed = true;
                $progress->completed_at = now();
                $progress->value = $levelGoal; // Keep the final value for display
                \Log::info("Mission {$this->getCode()} user {$user->id} completed entire mission.");
                \Event::fire('gamify.mission.completed', [$user, $this]);

                // Award completion bonus
                $this->awardCompletionPoints($user);
            }
        } else {
            // Not reached goal yet - normal progress update
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
        $completed = $this->isCompleted($progress);

        // If mission is fully completed, show completion status
        if ($completed) {
            return [
                'currentLevel' => 999,
                'description' => $this->getCompletionMissionLabel(),
                'value' => $this->getMaxLevel(),
                'goal' => $this->getMaxLevel(),
                'maxLevel' => $this->getMaxLevel(),
                'completed' => true,
                'data' => [
                    'code' => $this->getCode(),
                    'name' => $this->getName(),
                ],
            ];
        }

        // Get current progress values
        $currentValue = $this->getCurrentValue($user);
        $currentGoal = $this->getGoalForLevel($level);

        // If user just completed current level, show completion
        if ($currentValue >= $currentGoal && $currentGoal > 0) {
            return [
                'currentLevel' => $level,
                'description' => $this->getDescriptionForLevel($level) . ' ✅ Complete!',
                'value' => $currentGoal, // Show full completion
                'goal' => $currentGoal,
                'maxLevel' => $this->getMaxLevel(),
                'completed' => $completed,
                'levelCompleted' => true, // Flag to indicate level just completed
                'data' => [
                    'code' => $this->getCode(),
                    'name' => $this->getName(),
                ],
            ];
        }

        // Show normal progress toward current level
        return [
            'currentLevel' => $level,
            'description' => $this->getDescriptionForLevel($level),
            'value' => $currentValue,
            'goal' => $currentGoal,
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

    protected function awardPointsForLevel(User $user, int $level): void
    {
        $points = $this->getPointsForLevel($level);

        if ($points && $points > 0) {
            $pointType = $this->createMissionPointType($points, $level);
            $pointType->setSubject($user);

            if ($pointType->qualifier()) {
                $user->givePoint($pointType);
                \Log::info("Mission {$this->getCode()} awarded {$points} points to user {$user->id} for level {$level}");
            }
        }
    }

    protected function awardCompletionPoints(User $user): void
    {
        if (property_exists($this, 'completionPoints') && $this->completionPoints > 0) {
            $pointType = $this->createMissionPointType($this->completionPoints, 999, 'Completion');
            $pointType->setSubject($user);

            if ($pointType->qualifier()) {
                $user->givePoint($pointType);
                \Log::info("Mission {$this->getCode()} awarded {$this->completionPoints} completion points to user {$user->id}");
            }
        }
    }

    protected function createMissionPointType(int $points, int $level, string $suffix = 'Level'): PointType
    {
        return new class ($points, $this->getName(), $level, $suffix, $this->getCode()) extends PointType {
            protected $points;
            protected $name;
            protected $payee = 'id';

            public function __construct(int $points, string $missionName, int $level, string $suffix, string $missionCode)
            {
                $this->points = $points;

                if ($level === 999) {
                    $this->name = Lang::get('voilaah.gamify::lang.points.mission_completion', [
                        'mission' => $missionName
                    ]);
                } else {
                    $this->name = Lang::get('voilaah.gamify::lang.points.mission_level', [
                        'mission' => $missionName,
                        'level' => $level
                    ]);
                }
            }

            public function payee()
            {
                return $this->getSubject();
            }
        };
    }

    public function ensureStored(): void
    {
        if (!$this->model) {
            $this->model = $this->storeMission();
        }
    }
}
