<?php

namespace Voilaah\Gamify;

use Backend, Event, App, Schema;
use System\Classes\PluginBase;
use Illuminate\Support\Collection;
use Voilaah\Gamify\Events\BadgesAwarded;
use Voilaah\Gamify\Listeners\SyncBadges;
use Voilaah\Gamify\Components\UserBadges;
use Voilaah\Gamify\Listeners\NotifyBadges;
use Voilaah\Gamify\Components\UserMissions;
use Voilaah\Gamify\Console\MakeBadgeCommand;
use Voilaah\Gamify\Console\MakePointCommand;
use Voilaah\Gamify\Events\ReputationChanged;
use Voilaah\Gamify\Components\UserReputation;
use Voilaah\Gamify\Classes\Badge\BadgeManager;
use Voilaah\Gamify\Console\MakeMissionCommand;
use Voilaah\Gamify\Classes\Streak\StreakManager;
use Voilaah\Gamify\Listeners\AwardMissionBadges;
use Voilaah\Gamify\Classes\Mission\MissionManager;
use Voilaah\Gamify\Components\UserActivityTracker;
use Voilaah\Gamify\Listeners\AwardCompletionBadges;

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
        $this->registerConsoleCommand('voilaah.gamify_mission', MakeMissionCommand::class);
        $this->registerConsoleCommand('gamify:refresh-user-missions', \Voilaah\Gamify\Console\RefreshUserMissions::class);
        $this->registerConsoleCommand('gamify:generate-mission-badges', \Voilaah\Gamify\Console\GenerateMissionBadges::class);
        $this->registerConsoleCommand('gamify:test-course-explorer', \Voilaah\Gamify\Console\TestCourseExplorerMission::class);
        $this->registerConsoleCommand('gamify:test-multilingual', \Voilaah\Gamify\Console\TestMultilingualMission::class);
        $this->registerConsoleCommand('gamify:test-all-missions', \Voilaah\Gamify\Console\TestAllMissions::class);
        $this->registerConsoleCommand('gamify:debug-progress', \Voilaah\Gamify\Console\DebugMissionProgress::class);

        // `php artisan cache:forget gamify.badges.all`
        /* $this->app->singleton('badges', function () {
            return cache()->rememberForever('gamify.badges.all', function () {
                return $this->getBadges()->map(function ($badge) {
                    return new $badge;
                });
            });
        }); */

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
        Event::listen(BadgesAwarded::class, NotifyBadges::class);
        // Event::listen(BadgesRemoved::class, SyncBadges::class);

        // register mission badge event listeners
        Event::listen('gamify.mission.levelUp', AwardMissionBadges::class);
        Event::listen('gamify.mission.completed', AwardCompletionBadges::class);

        // binding gamify behavior to user models
        $this->bindBehaviorsRainLabUser();
        $this->bindBehaviorsBackendUser();

        // Example: register a streak type for example purpose
        // \Voilaah\Gamify\Classes\Streak\StreakManager::register('user_login', trans('User Login'), \Voilaah\Gamify\Classes\Streak\StreakTypes\UserLoginStreak::class);

        $this->app->singleton('gamify.badges', function () {
            $manager = new BadgeManager();
            // Let external plugins register their badges
            $external = Event::fire('voilaah.gamify.registerBadges', [$manager]);
            return $manager;
        });

        // Phase 1: Create empty manager & register it early
        $this->app->singleton('gamify.missions', function () {
            return new MissionManager();
        });

        // Phase 2: Let plugins register their missions in booted callback
        \App::booted(function () {
            $manager = app('gamify.missions');

            if (Schema::hasTable("voilaah_gamify_missions")) {
                // Register all missions
                // $manager->register(new \Voilaah\Gamify\Missions\CourseExplorerMissionTest());
                $manager->register(new \Voilaah\Gamify\Missions\VoilaahTestMission());
                $manager->register(new \Voilaah\Gamify\Missions\KnowledgeParagonMission());
                $manager->register(new \Voilaah\Gamify\Missions\SkillVanguardMission());
                $manager->register(new \Voilaah\Gamify\Missions\MasterySageMission());
                $manager->register(new \Voilaah\Gamify\Missions\LearningEpicMission());
                $manager->register(new \Voilaah\Gamify\Missions\FeedbackMaestroMission());
                $manager->register(new \Voilaah\Gamify\Missions\SteadfastMonarchMission());
                $manager->register(new \Voilaah\Gamify\Missions\CertificationVanguardMission());

                // Fire this AFTER all plugins booted, allows other plugin to register Mission
                Event::fire('voilaah.gamify.registerMissions', [$manager]);

                // Now register event listeners
                $manager->registerEventListeners();
            }

        });

    }

    public function registerComponents()
    {
        return [
            UserActivityTracker::class => 'userActivityTracker',
            UserReputation::class => 'userReputation',
            UserBadges::class => 'userBadges',
            UserMissions::class => 'userMissions',
        ];
    }

    /**
     * registerSchedule
     */
    public function registerSchedule($schedule)
    {
        $schedule->call(function () {
            \Log::info('[Gamify] Scheduled streak task triggered at ' . now());

            foreach (StreakManager::all() as $code => $config) {
                $class = $config['class'] ?? null;

                if (!is_string($class) || !class_exists($class)) {
                    \Log::warning("[Gamify] Invalid streak class for code: $code", ['class' => $class]);
                    continue;
                }

                try {
                    $instance = new $class;

                    if (method_exists($instance, 'isScheduled') && $instance->isScheduled()) {
                        \Log::info("[Gamify] Running scheduled streak: $code");
                        $instance->updateForToday();
                    } else {
                        \Log::debug("[Gamify] Streak '$code' is not scheduled to run.");
                    }
                } catch (\Throwable $e) {
                    \Log::error("[Gamify] Error running scheduled streak: $code", [
                        'class' => $class,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        })
            ->dailyAt('23:15');
        //->everyMinute();
        // ->everyFiveMinutes();
    }

    /**
     * @deprecated message
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

    /**
     * @deprecated
     * Get all the mission inside app/Gamify/Missions folder
     *
     * @return Collection
     */
    protected function getMissions()
    {

        $missionRootNamespace = config(
            'gamify.mission_namespace',
            __NAMESPACE__ . '\Missions'
        );

        $path = str_replace('\\', '/', strtolower($missionRootNamespace));
        $path .= '/';

        $missions = [];

        // foreach (glob(plugins_path('/voilaah/gamify/missions/') . '*.php') as $file) {
        foreach (glob(plugins_path($path) . '*.php') as $file) {
            if (is_file($file)) {
                $missions[] = app($missionRootNamespace . '\\' . pathinfo($file, PATHINFO_FILENAME));
            }
        }
        return collect($missions);
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
