<?php

namespace Voilaah\Gamify\Classes\Badge;

use Illuminate\Support\{Str, Arr};
use Illuminate\Database\Eloquent\Model;

abstract class MissionBadge extends BaseBadge
{
    protected $isMissionBadge = true;
    
    /**
     * Get the mission code this badge is linked to
     *
     * @return string
     */
    abstract public function getMissionCode(): string;
    
    /**
     * Get the mission level this badge is awarded for
     *
     * @return int
     */
    abstract public function getMissionLevel(): int;
    
    /**
     * Mission badges are awarded automatically via events
     * Override qualifier to return false
     *
     * @param $user
     * @return bool
     */
    public function qualifier($user)
    {
        return false;
    }
    
    /**
     * Get the default name for mission badge
     *
     * @return string
     */
    protected function getDefaultBadgeName()
    {
        $missionName = $this->getMissionName();
        $level = $this->getMissionLevel();
        
        return "{$missionName} - Level {$level}";
    }
    
    /**
     * Get the default description for mission badge
     *
     * @return string
     */
    public function getDescription()
    {
        if (isset($this->description)) {
            return $this->description;
        }
        
        $missionName = $this->getMissionName();
        $level = $this->getMissionLevel();
        
        return "Complete level {$level} of {$missionName}";
    }
    
    /**
     * Get mission name from mission code
     *
     * @return string
     */
    protected function getMissionName(): string
    {
        $mission = app('gamify.missions')->find($this->getMissionCode());
        
        if ($mission) {
            return $mission->getName();
        }
        
        return ucwords(str_replace(['_', '-'], ' ', $this->getMissionCode()));
    }
    
    /**
     * Store mission badge with additional fields
     *
     * @return mixed
     */
    protected function storeBadge()
    {
        $badge = app(config('gamify.badge_model'))
            ->firstOrNew([
                'mission_code' => $this->getMissionCode(),
                'mission_level' => $this->getMissionLevel(),
                'is_mission_badge' => true
            ])
            ->forceFill([
                'name' => $this->getName(),
                'level' => $this->getLevel(),
                'sort_order' => $this->getSortOrder(),
                'description' => $this->getDescription(),
                'icon' => $this->getIcon(),
                'mission_code' => $this->getMissionCode(),
                'mission_level' => $this->getMissionLevel(),
                'is_mission_badge' => true
            ]);

        $badge->save();
        return $badge;
    }
    
    /**
     * Override default icon to use mission icon or level-specific icon
     *
     * @return string
     */
    protected function getDefaultIcon()
    {
        if (property_exists($this, 'icon')) {
            return $this->icon;
        }
        
        // Try to get mission icon first
        $mission = app('gamify.missions')->find($this->getMissionCode());
        if ($mission) {
            return $mission->getIcon();
        }
        
        // Fall back to level-specific icon
        return sprintf(
            '%s/%s-level-%d%s',
            rtrim(config('gamify.badge_icon_folder', 'assets/images/badges'), '/'),
            Str::kebab($this->getMissionCode()),
            $this->getMissionLevel(),
            config('gamify.badge_icon_extension', '.svg')
        );
    }
}