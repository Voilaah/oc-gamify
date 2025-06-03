<?php
namespace Voilaah\Gamify\Models;

use Model;

/**
 * Model
 */
class UserLoginStreak extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table in the database used by the model.
     */
    public $table = 'voilaah_gamify_userlogin_streak';

    /**
     * @var array rules for validation.
     */
    public $rules = [
    ];

    public $fillable = [
        'user_id'
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

}
