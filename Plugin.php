<?php

namespace Voilaah\Gamify;

use Backend, Event;
use FontLib\Table\Type\name;
use System\Classes\PluginBase;
use Illuminate\Support\Collection;
use Voilaah\Gamify\Components\Points;
use Voilaah\Gamify\Listeners\SyncBadges;
use Voilaah\Gamify\Console\MakeBadgeCommand;
use Voilaah\Gamify\Console\MakePointCommand;
use Voilaah\Gamify\Events\ReputationChanged;
use Voilaah\Gamify\Components\UserActivityTracker;

/**
 * Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = ['RainLab.User'];

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConsoleCommand('voilaah.gamify_point', MakePointCommand::class);
        $this->registerConsoleCommand('voilaah.gamify_badge', MakeBadgeCommand::class);
        // $this->registerConsoleCommand('voilaah.gamify_mission', MakeBadgeCommand::class);

        // `php artisan cache:forget gamify.badges.all`
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

    public function registerComponents()
    {
        return [
            UserActivityTracker::class => 'userActivityTracker',
            Points::class => 'points',
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

        $path = str_replace('\\', '/', strtolower($badgeRootNamespace));
        $path .= '/';

        $badges = [];

        // foreach (glob(plugins_path('/voilaah/gamify/badges/') . '*.php') as $file) {
        foreach (glob(plugins_path($path) . '*.php') as $file) {
            if (is_file($file)) {
                $badges[] = app($badgeRootNamespace . '\\' . pathinfo($file, PATHINFO_FILENAME));
            }
        }

        // traceLog(collect($badges)->toArray());
        return collect($badges);
    }

    protected function bindBehaviorsRainLabUser()
    {
        if (class_exists(\RainLab\User\Models\User::class)) {
            \RainLab\User\Models\User::extend(function ($model) {
                $model->implement[] = 'voilaah.Gamify.Behaviors.UserGamifyBehavior';
            });
        }
    }

    protected function bindBehaviorsBackendUser()
    {
        if (class_exists(\Backend\Models\User::class)) {
            \Backend\Models\User::extend(function ($model) {
                $model->implement[] = 'voilaah.Gamify.Behaviors.UserGamifyBehavior';
            });
        }
    }
}
