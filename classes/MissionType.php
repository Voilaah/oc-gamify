<?php

namespace Voilaah\Gamify\Classes;

use Illuminate\Support\{Str, Arr};
use Illuminate\Database\Eloquent\Model;
use Voilaah\Gamify\Exceptions\LevelNotDefined;

abstract class MissionType
{
    /**
     * @var Model
     */
    protected $model;

    public $allowDuplicates = true;

    /**
     * BadgeType constructor.
     */
    public function __construct()
    {
        $this->model = $this->storeMission();
    }

    /**
     * Check if user qualifies for this badge
     *
     * @param $user
     * @return bool
     */
    abstract public function qualifier($user);


    /**
     * @param $level
     * @param $subject
     *
     * @return \Illuminate\Config\Repository|mixed
     * @throws \Voilaah\Gamify\Exceptions\LevelNotDefined
     */
    public function levelIsAchieved($level, $subject)
    {
        $level = array_search($level, config('gamify.bmission_levels'));

        if (!$level) {
            throw new LevelNotDefined("Level [ id : $level ] must be define in gamify config file .");
        }

        $method = Str::camel($level);

        if (method_exists($this, $method)) {
            return $this->{$method}($this, $subject);
        }

        return config('gamify.mission_is_archived');
    }


    /**
     * Check for badge point allowed
     *
     * @param PointType $pointType
     * @return bool
     */
    protected function isDuplicateBadgeAllowed()
    {
        return property_exists($this, 'allowDuplicates')
            ? $this->allowDuplicates
            : false;
    }

    /**
     * Check if badge already exists for a user
     *
     * @return bool
     *
     */
    public function missionExists($user)
    {
        return $this->missionQuery($user)->exists();
    }

    /**
     * Get badge query for this badge
     *
     * @return Builder     *
     */
    public function missionQuery($user)
    {
        return $user->missions()->where([
            ['user_id', $user->id],
            ['badge_id', $this->getMissionId()],
        ]);
    }

    /**
     * Get model instance of the badge
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
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
        return isset($this->description)
            ? $this->description
            : '';
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
            : $this->getDefaultIcon()
        ;
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
     * Get the level for mission
     *
     * @return int
     */
    public function getLevel()
    {
        $level = property_exists($this, 'level')
            ? $this->level
            : config('gamify.mission_default_level', 1);

        if (is_numeric($level)) {
            return $level;
        }

        return Arr::get(
            config('gamify.mission_levels', []),
            $level,
            config('gamify.mission_default_level', 1)
        );
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
        $mission = app(config('gamify.mission_model'))
            ->firstOrNew(['name' => $this->getName()])
            ->forceFill([
                'level' => $this->getLevel(),
                'sort_order' => $this->getSortOrder(),
                'description' => $this->getDescription(),
                'icon' => $this->getIcon()
            ]);

        $mission->save();

        return $mission;
    }
}
