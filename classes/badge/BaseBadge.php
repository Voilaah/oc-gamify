<?php

namespace Voilaah\Gamify\Classes\Badge;

use Illuminate\Support\{Str, Arr};
use Illuminate\Database\Eloquent\Model;

abstract class BaseBadge
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
        $this->model = $this->storeBadge();
    }

    /**
     * Check if user qualifies for this badge
     *
     * @param $user
     * @return bool
     */
    abstract public function qualifier($user);

    public function isEnabled(): bool
    {
        return true;
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
    public function badgeExists($user)
    {
        return $this->badgeQuery($user)->exists();
    }

    /**
     * Get badge query for this badge
     *
     * @return Builder     *
     */
    public function badgeQuery($user)
    {
        return $user->badges()->where([
            ['user_id', $user->id],
            ['badge_id', $this->getBadgeId()],
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
            : $this->getDefaultBadgeName();
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
     * Get the level for badge
     *
     * @return int
     */
    public function getLevel()
    {
        $level = property_exists($this, 'level')
            ? $this->level
            : config('gamify.badge_default_level', 1);

        if (is_numeric($level)) {
            return $level;
        }

        return Arr::get(
            config('gamify.badge_levels', []),
            $level,
            config('gamify.badge_default_level', 1)
        );
    }

    /**
     * Get badge id
     *
     * @return mixed
     */
    public function getBadgeId()
    {
        return $this->model->getKey();
    }

    /**
     * Get the default name if not provided
     *
     * @return string
     */
    protected function getDefaultBadgeName()
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
            rtrim(config('gamify.badge_icon_folder', 'assets/images/badges'), '/'),
            Str::kebab(class_basename($this)),
            config('gamify.badge_icon_extension', '.svg')
        );
    }

    /**
     * Store or update badge
     *
     * @return mixed
     */
    protected function storeBadge()
    {
        $badge = app(config('gamify.badge_model'))
            ->firstOrNew(['name' => $this->getName()])
            ->forceFill([
                'level' => $this->getLevel(),
                'sort_order' => $this->getSortOrder(),
                'description' => $this->getDescription(),
                'icon' => $this->getIcon()
            ]);

        $badge->save();

        return $badge;
    }
}
