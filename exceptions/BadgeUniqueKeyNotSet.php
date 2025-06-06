<?php

namespace Voilaah\Gamify\Exceptions;

use Exception;

class BadgeUniqueKeyNotSet extends Exception
{
    protected $message = 'You must define a $unique_key field in the badge type defintion.';
}
