<?php namespace Voilaah\Gamify\Controllers;

use Backend;
use BackendMenu;
use Backend\Classes\Controller;

class UserLoginStreaks extends Controller
{
    public $implement = [
        \Backend\Behaviors\ListController::class
    ];

    public $listConfig = 'config_list.yaml';

    public $requiredPermissions = [
        'voilaah.gamify.voilaah.gamify.access_userloginstreaks' 
    ];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Voilaah.Gamify', 'gamify', 'userloginstreaks');
    }

}
