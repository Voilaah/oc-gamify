<?php

namespace Voilaah\Gamify\Listeners;

use Illuminate\View\Component;
use Voilaah\Gamify\Models\Badge;
use Voilaah\Gamify\Events\BadgesAwarded;
use Voilaah\Skillup\Classes\Notifications;
use Voilaah\Skillup\Classes\Events\NotificationEventHandler;
use Session;

class NotifyBadges
{
    /**
     * Handle the event.
     *
     * @param  \Voilaah\Gamify\Events\BadgesAwarded  $event
     * @return void
     */
    public function handle(BadgesAwarded $event)
    {
        $user = $event->user;

        $badgesData = [];

        foreach ($event->badgesId as $key => $badgeId) {

            $badge = Badge::find($badgeId);

            traceLog("handle badge awarded " . $badge->name . " to " . $user->email);

            // create a user notification
            NotificationEventHandler::createRecord(
                $user->id,
                Notifications::BADGE_EARNED,
                Notifications::TYPE_BADGE, /* "voilaah.skillup::notifications.gamify.badge.earned.inapp.title", */
                "voilaah.skillup::notifications.gamify.badge.earned.inapp.body",
                [
                    'id' => $badgeId,
                    'title' => $badge->name
                ]
            );

            $badgesData[] = [
                'name' => $badge->name,
                'description' => $badge->description,
                'type' => Notifications::TYPE_BADGE,
                'icon' => $badge->icon,
            ];
            /*
                        $controller = new \Cms\Classes\Controller;
                        $controller->dispatchBrowserEvent('badge:earned', [
                            $badgesData
                        ]); */
        }

        /* traceLog("==== add badges to session =====");
        traceLog($badgesData); */

        Session::put('gamify.badges.earned', $badgesData);
    }

}
