<?php
namespace Syehan\Gamify;

use Backend, Event;
use System\Classes\PluginBase;
use Syehan\Gamify\Console\MakeBadgeCommand;
use Syehan\Gamify\Console\MakePointCommand;
use Syehan\Gamify\Events\ReputationChanged;
use Syehan\Gamify\Listeners\SyncBadges;
use Illuminate\Support\Collection;

/**
 * Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = ['RainLab.User'];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Gamify',
            'description' => 'Add gamification in OctoberCMS with reputation point and badges support',
            'author' => 'Mohd Saqueib Ansari, Syehan',
            'icon' => 'icon-puzzle-piece'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConsoleCommand('syehan.gamify_point', MakePointCommand::class);
        $this->registerConsoleCommand('syehan.gamify_badge', MakeBadgeCommand::class);

        $this->app->singleton('badges', function () {
            return cache()->rememberForever('gamify.badges.all', function () {
                return $this->getBadges()->map(function ($badge) {
                    return new $badge;
                });
            });
        });
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {
        // publish config
        $this->publishes([
            __DIR__ . '/config/gamify.php' => config_path('gamify.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__ . '/config/gamify.php', 'gamify');

        // register event listener
        Event::listen(ReputationChanged::class, SyncBadges::class);

        // binding gamify behavior to user models
        $this->bindBehaviorsRainLabUser();
        $this->bindBehaviorsBackendUser();
    }

    /**
     * Registers backend navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        return [
            'gamify' => [
                'label' => 'Gamify',
                'url' => Backend::url('syehan/gamify/reputations'),
                'icon' => 'icon-star',
                'permissions' => ['syehan.gamify.*'],
                'order' => 500,
                'sideMenu' => [
                    'reputations' => [
                        'label' => 'Reputation',
                        'icon' => 'icon-star',
                        'url' => Backend::url('syehan/gamify/reputations'),
                        'permissions' => ['syehan.gamify.access_reputations']
                    ],
                    'badges' => [
                        'label' => 'Badge',
                        'icon' => 'icon-asterisk',
                        'url' => Backend::url('syehan/gamify/badges'),
                        'permissions' => ['syehan.gamify.access_badges']
                    ],
                ]
            ],
        ];
    }

    /**
     * Get all the badge inside app/Gamify/Badges folder
     *
     * @return Collection
     */
    protected function getBadges()
    {
        $badgeRootNamespace = config(
            'gamify.badge_namespace',
            __NAMESPACE__ . '\Badges'
        );

        $badges = [];

        foreach (glob(plugins_path('/syehan/gamify/badges/') . '*.php') as $file) {
            if (is_file($file)) {
                $badges[] = app($badgeRootNamespace . '\\' . pathinfo($file, PATHINFO_FILENAME));
            }
        }

        return collect($badges);
    }

    protected function bindBehaviorsRainLabUser()
    {
        if (class_exists(\RainLab\User\Models\User::class)) {
            \RainLab\User\Models\User::extend(function ($model) {
                $model->implement[] = 'Syehan.Gamify.Behaviors.UserGamifyBehavior';
            });
        }
    }

    protected function bindBehaviorsBackendUser()
    {
        if (class_exists(\Backend\Models\User::class)) {
            \Backend\Models\User::extend(function ($model) {
                $model->implement[] = 'Syehan.Gamify.Behaviors.UserGamifyBehavior';
            });
        }
    }
}
