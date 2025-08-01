<?php

return [
    // Auth Base, available \Auth or \BackendAuth
    'auth_base' => env('GAMIFY_AUTH_BASE', \Auth::class),

    // Model which will be having points, generally it will be User
    'payee_model' => env('GAMIFY_PAYEE_MODEL', \RainLab\User\Models\User::class),

    // Reputation model
    'reputation_model' => env('GAMIFY_REPUTATION_MODEL', \Voilaah\Gamify\Models\Reputation::class),

    // Allow duplicate reputation points
    'allow_reputation_duplicate' => env('GAMIFY_ALLOW_REPUTATION_DUPLICATE', false),

    // Broadcast on private channel
    'broadcast_on_private_channel' => env('GAMIFY_BROADCAST_ON_PRIVATE_CHANNEL', false),

    // Channel name prefix. If you give the value `null` then channel is non-active, user id will be suffixed ex: "user.reputation."
    'channel_name' => env('GAMIFY_CHANNEL_NAME', null),

    // Badge model
    'badge_model' => env('GAMIFY_BADGE_MODEL', \Voilaah\Gamify\Models\Badge::class),
    'mission_model' => env('GAMIFY_MISSION_MODEL', \Voilaah\Gamify\Models\Mission::class),
    'streak_model' => env('GAMIFY_STREAK_MODEL', \Voilaah\Gamify\Models\UserStreak::class),

    // Where all icon stored (related to the media folder)
    'badge_icon_folder' => env('GAMIFY_BADGE_ICON_FOLDER', 'images/badges/'),
    'mission_icon_folder' => env('GAMIFY_MISSION_ICON_FOLDER', 'images/missions/'),

    // Extention of icons
    'badge_icon_extension' => env('GAMIFY_BADGE_ICON_EXTENSION', '.svg'),
    'mission_icon_extension' => env('GAMIFY_MISSION_ICON_EXTENSION', '.svg'),

    // All the levels for badge (example value on .env 'beginner|1,intermediate|2')
    'badge_levels' => env('GAMIFY_BADGE_LEVELS')
        ? array_map(function ($item) {
            return explode('|', $item);
        }, explode(',', env('GAMIFY_BADGE_LEVELS')))
        : [
            'beginner' => 1,
            'intermediate' => 2,
            'advanced' => 3,
            'expert' => 4,
        ],

    // Default level
    'badge_default_level' => env('GAMIFY_BADGE_DEFAULT_LEVEL', 1),
    'mission_default_level' => env('GAMIFY_MISSION_DEFAULT_LEVEL', 1),

    // Namespaces
    'badge_namespace' => env('GAMIFY_BADGE_NAMESPACE', 'App\Badges'),
    'mission_namespace' => env('GAMIFY_MISSION_NAMESPACE', 'App\Missions'),

    // Badge achieved vy default if check function not exit
    'badge_is_archived' => false,
    'mission_is_archived' => false,
    // point achieved vy default if check function not exit
    'point_is_archived' => true,
];
