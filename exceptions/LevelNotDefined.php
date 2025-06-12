<?php

namespace Voilaah\Gamify\Exceptions;

use Exception;

class LevelNotDefined extends Exception
{
    protected $message = 'Level must be define in gamify config file.';
}
