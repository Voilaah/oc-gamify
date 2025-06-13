<?php

namespace Voilaah\Gamify\Models;

use Model;
use October\Rain\Database\Traits\SoftDelete;

class UserMissionProgress extends Model
{
    use SoftDelete;

    /**
     * @var string The database table used by the model.
     */
    protected $table = 'voilaah_gamify_user_mission_progress';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [
        'user_id',
        'mission_code',
        'level',
        'value',
        'is_completed',
        'completed_at',
        'last_reached_at',
    ];

    /**
     * @var array Dates
     */
    protected $dates = [
        'completed_at',
        'last_reached_at',
        'deleted_at'
    ];

    /**
     * User relation
     */
    public $belongsTo = [
        'user' => ['RainLab\User\Models\User']
    ];

    /**
     * Check if the user has reached a certain level in this mission.
     */
    public function hasReachedLevel(int $level): bool
    {
        return $this->current_level >= $level;
    }

    /**
     * Helper to increment level safely.
     */
    public function reachLevel(int $level): void
    {
        if ($level > $this->current_level) {
            $this->current_level = $level;
            $this->last_reached_at = now();
            $this->save();
        }
    }

    /**
     * Scope by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope by mission
     */
    public function scopeForMission($query, $missionCode)
    {
        return $query->where('mission_code', $missionCode);
    }
}
