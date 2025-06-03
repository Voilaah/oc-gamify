<?php

namespace Voilaah\Gamify\Tests\Badges;

use Voilaah\Gamify\BadgeType;
use Voilaah\Gamify\Tests\Models\User;

class FirstThousandPoints extends BadgeType
{
    /**
     * Description for badge
     *
     * @var string
     */
    protected $description = 'Congrats! you have reached 1000 points.';

    /**
     * Check is user qualifies for badge
     *
     * @param User $user
     * @return bool
     */
    public function qualifier($user)
    {
        return $user->getPoints() >= 1000;
    }
}
