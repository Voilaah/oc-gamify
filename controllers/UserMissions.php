<?php
namespace Voilaah\Gamify\Controllers;

use Backend;
use BackendMenu;
use Backend\Classes\Controller;

class UserMissions extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';

    public $requiredPermissions = [
        'voilaah.gamify.voilaah.gamify.access_usermissions'
    ];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Voilaah.Gamify', 'gamify', 'usermissions');
    }

}
