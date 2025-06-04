<?php
namespace Voilaah\Gamify\Models;

use Model;
use Voilaah\Gamify\Classes\Streak\StreakManager;

/**
 * Model
 */
class UserStreak extends Model
{
    use \October\Rain\Database\Traits\Validation;
    /*     use \October\Rain\Database\Traits\Sortable; */


    /**
     * @var string table in the database used by the model.
     */
    public $table = 'voilaah_gamify_user_streaks';

    /**
     * @var array rules for validation.
     */
    public $rules = [
        'streak_type' => ['required', 'string', 'max:64'],
    ];

    public $jsonable = [
        'streak_dates',
    ];

    public $fillable = [
        'user_id',
        'sort_order',
        'streak_type',
        'streak_dates'
    ];

    public $dates = ['last_activity_date'];

    public function __construct()
    {
        parent::__construct();

        /**
         * Payee User
         *
         * @return October\Rain\Database\Relations\BelongsTo
         */
        $this->belongsTo['user'] = [
            config('gamify.payee_model'),
            'key' => 'user_id',
        ];
    }

    public function afterDelete()
    {
        // $this->prices()->delete();
    }

    public function getStreakTypesOptions(): array
    {
        foreach (StreakManager::all() as $code => $config) {
            $types[$code] = StreakManager::getLabel($code);
        }

        return $types;
    }

    public function scopeApplyStreakType($query, $filtered)
    {
        return $query->whereIn('streak_type', $filtered);
    }

}
