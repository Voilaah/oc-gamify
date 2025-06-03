<?php

namespace Voilaah\Gamify\Events;

use Voilaah\Gamify\Models\Badge;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;


class BadgeAwarded
{
    use Dispatchable, SerializesModels;

    /**
     * @var User
     */
    public $user;

    /**
     * @var int
     */
    public $badgeId;


    /**
     * Create a new event instance.
     *
     * @param $user
     * @param $badgeId
     */
    public function __construct($user, int $badgeId)
    {
        $this->user = $user;
        $this->badgeId = $badgeId;
    }
}
