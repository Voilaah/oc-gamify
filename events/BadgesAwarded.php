<?php

namespace Voilaah\Gamify\Events;

use Voilaah\Gamify\Models\Badge;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;


class BadgesAwarded
{
    use Dispatchable, SerializesModels;

    /**
     * @var User
     */
    public $user;

    /**
     * @var array int
     */
    public $badgesId;


    /**
     * Create a new event instance.
     *
     * @param $user
     * @param $badgesId
     */
    public function __construct($user, array $badgesId)
    {
        $this->user = $user;
        $this->badgesId = $badgesId;
    }
}
