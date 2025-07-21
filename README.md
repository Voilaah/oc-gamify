## OctoberCMS Gamify 🕹 🏆

This is a fork with fixes of the [gamify plugin](https://github.com/voilaah/gamify-plugin).

### Fixes:

- use `RainLab.User` v3
- fix the `config/gamify.php` php code reading the `.env` variables `GAMIFY_BADGE_LEVELS`

Use `voilaah/gamify-plugin` to add reputation points, badges, missions, and streaks in your OctoberCMS with automatic mission-badge linking.

### Installation

**1** - You can install the package via composer:

```bash
$ composer require voilaah/gamify-plugin
```

**2** - Now publish the migration for gamify tables:

```
php artisan october:migrate
```

_Note:_ It will generate migration for `voilaah_gamify_reputations`, `voilaah_gamify_badges` and `voilaah_gamify_user_badges` tables along with add reputation field migration for `users` table to store the points, you will need to run `composer require doctrine/dbal` in order to support dropping and adding columns.

If your payee (model who will be getting the points) model is `RainLab\User\Models\User` then you don't have to change anything in `config/gamify.php`.

## Config Gamify

```php
<?php

return [
    // Auth Base, available \Auth or \BackendAuth
    'auth_base' => env('GAMIFY_AUTH_BASE', \Auth::class),

    // Model which will be having points, generally it will be User
    'payee_model' => '\RainLab\User\Models\User',

    // Reputation model
    'reputation_model' => '\Voilaah\Gamify\Models\Reputation',

    // Allow duplicate reputation points
    'allow_reputation_duplicate' => true,

    // Broadcast on private channel
    'broadcast_on_private_channel' => true,

    // Channel name prefix, user id will be suffixed
    'channel_name' => 'user.reputation.',

    // Badge model
    'badge_model' => '\Voilaah\Gamify\Models\Badge',

    // Where all badges icon stored
    'badge_icon_folder' => 'images/badges/',

    // Extention of badge icons
    'badge_icon_extension' => '.svg',

    // All the levels for badge
    'badge_levels' => [
        'beginner' => 1,
        'intermediate' => 2,
        'advanced' => 3,
    ],

    // Default level
    'badge_default_level' => 1
];
```

## Autoload Helpers

to supporting easy gamify helpers, like `givePoint()` or `undoPoint()`, please make sure you already autoload helpers.php in your `composer.json` root.

here's the example :

```json
"autoload": {
    "psr-4": {
        "System\\Console\\": "modules/system/console"
    },
    "files": [
        "plugins/voilaah/gamify/helpers.php"
    ]
}
```

after that,

```bash
$ composer dumpautoload
```

### Getting Started

**1.** After package installation now add the **Gamify** trait on `RainLab\User\Models\User` model or any model who acts as **user** in your app.

```php
use Voilaah\Gamify\Traits\Gamify;
use Illuminate\Notifications\Notifiable;
use Model;

class YourUserModel extends Model
{
    use Notifiable, Gamify;
```

## ⭐️ 👑 Reputation Point

**2.** Next step is to create a point.

```bash
php artisan voilaah:gamify-point PostCreated
```

It will create a PointType class named `PostCreated` under `app/Gamify/Points/` folder.

```php
<?php

namespace App\Gamify\Points;

use Voilaah\Gamify\Classes\PointType;

class PostCreated extends PointType
{
    /**
     * Number of points
     *
     * @var int
     */
    public $points = 20;

    /**
     * Point constructor
     *
     * @param $subject
     */
    public function __construct($subject)
    {
        $this->subject = $subject;
    }

    /**
     * User who will be receive points
     *
     * @return mixed
     */
    public function payee()
    {
        return $this->getSubject()->user;
    }
}
```

### Give point to User

Now in your Controller where a Post is created you can give points like this:

```php
$user = $request->user();
$post = $user->posts()->create($request->only(['title', 'body']));

// you can use helper function
givePoint(new PostCreated($post));

// or via HasReputation trait method
$user->givePoint(new PostCreated($post));
```

### Undo a given point

In some cases you would want to undo a given point, for example, a user deletes his post.

```php
// via helper function
undoPoint(new PostCreated($post));
$post->delete();

// or via HasReputation trait method
$user->undoPoint(new PostCreated($post));
$post->delete();
```

You can also pass second argument as $user in helper function `givePoint(new PostCreated($post, $user))`, default is auth()->user().

**Pro Tip 👌** You could also hook into the Eloquent model event and give point on `created` event. Similarly, `deleted` event can be used to undo the point.

### Get total reputation

To get the total user reputation you have `$user->getPoints($formatted = false)` method available. Optioally you can pass `$formatted = true` to get reputation as 1K+, 2K+ etc.

```php
// get integer point
$user->getPoints(); // 20

// formatted result
$user->getPoints(true); // if point is more than 1000 1K+
```

### Get reputation history

Since package stores all the reputation event log so you can get the history of reputation via the following relation:

```php
foreach($user->reputations as $reputation) {
    // name of the point type
    $reputation->name

    // payee user
    $reputation->payee

    // how many points
    $reputation->point

    // model on which point was given
    $reputation->subject
}
```

If you want to get all the points given on a `subject` model. You should define a `morphMany` relations. For example on post model.

```php
    /**
     * Get all the post's reputation.
     */
    public function reputations()
    {
        return $this->morphMany('Voilaah\Gamify\Reputation', 'subject');
    }
```

Now you can get all the reputation given on a `Post` using `$post->reputations`.

### Configure a Point Type

#### Point payee

In most of the case your subject model which you pass into point `new PostCreated($post)` will be related to the User via some relation.

```php
class PostCreated extends PointType
{
    public $points = 20;

    protected $payee = 'user';

    // dont need this, payee property will return subject realtion
    // public function payee()
    // {
    //    return $this->getSubject()->user;
    // }
}
```

#### Dynamic point

If a point is calculated based on some logic you should add `getPoints()` method to do the calculation and always return an integer.

```php
class PostCreated extends PointType
{
    protected $payee = 'user';

    public function getPoints()
    {
        return $this->getSubject()->user->getPoint() * 10;
    }
}
```

#### Point qualifier

This is an optional method which returns boolean if its true then this point will be given else it will be ignored.
It's will be helpful if you want to determine the qualification for point dynamically.

#### Prevent duplicate reputation

By default, you can give points multiple times for same model subject. But you can prevent it by adding the following property to the class:

```php
class PostCreated extends PointType
{
    // prevent duplicate point
    public $allowDuplicates = false;

    protected $payee = 'user';
}
```

#### Event on reputation changed

Whenever user point changes it fires `\Voilaah\Gamify\Events\ReputationChanged` event which has the following payload:

```php
class ReputationChanged implements ShouldBroadcast {

    ...
    public function __construct(Model $user, int $point, bool $increment)
    {
        $this->user = $user;
        $this->point = $point;
        $this->increment = $increment;
    }
}
```

This event also broadcast in configured channel name so you can listen to it from your frontend via socket to live update reputation points.

## 🏆 🏅 Achievement Badges

Similar to Point type you have badges. They can be given to users based on rank or any other criteria. You should define badge level in `config/php`.

```php
// All the levels for badge
'badge_levels' => [
    'beginner' => 1,
    'intermediate' => 2,
    'advanced' => 3,
],

// Default level
'badge_default_level' => 1
```

Badge levels are stored as `tinyint` so keep the value as an integer value. It will be faster to do the sorting when needed.

### Create a Badge

To generate a badge you can run following provided command:

```bash
php artisan voilaah:gamify-badge plugin.namespace FirstContribution
```

It will create a BadgeType class named `FirstContribution` under `app/Gamify/Badges/` folder.

```php
<?php

namespace App\Gamify\Badges;

use Voilaah\Gamify\BadgeType;

class FirstContribution extends BadgeType
{
    /**
     * Description for badge
     *
     * @var string
     */
    protected $description = '';

    /**
     * Check is user qualifies for badge
     *
     * @param $user
     * @return bool
     */
    public function qualifier($user)
    {
        return $user->getPoints() >= 1000;
    }
}
```

As you can see this badge has a `$description` field and a `qualifier($user)` method.
Gamify package will listen for any change in reputation point and it will run the user against all the available badges and assign all the badges user is qualified.

#### Change badge name

By default, badge name will be a pretty version on the badge class name. In the above case it will be `First Contribution`.
You can change it by adding a `$name` property in class or you can override `getName()` method if you want to name it dynamically.

#### Change badge icon

Similar to name you can change it by `$icon` property or by `getIcon()` method. When you define icon on the class you need to specify full path with extension.
`config/gamify.php` folder `badge_icon_folder` and `badge_icon_extension` won't be used.

#### Change badge level

You have same `$level` property or by `getLevel()` method to change it.
Its like category of badges, all badges are defined in `config/gamify.php` as `badge_levels`. If none is specified then `badge_default_level` will be used from config.

**Warning ⚠️** Don't forget to clear the cache whenever you make any changes add or remove badges by running `php artisan cache:forget gamify.badges.all`. ⚠️

#### Get badges of user

You can get a users badges by calling `$user->badges` which will return collection of badges for a user.

### Use without Badge

If your app doesn't need **Badges** you should just use `HasReputations` trait instead of `Gamify`.

### Use without reputation history

If you dont need to maintain the history of all the point user has rewarded and you just want to increment and decrement reputation, you should use following method:

```php
// to add point
$user->addPoint($point = 1);

// to reduce point
$user->reducePoint($point = 1);

// to reset point back to zero
$user->resetPoint();
```

You dont need to generate point class for this.

## 🎯 🏆 Missions & Mission-Badge Linking

The Gamify plugin now supports missions with automatic badge awarding when users complete mission levels. This creates a seamless progression system where completing mission levels automatically unlocks corresponding badges.

### Mission Structure

Missions are level-based achievements with:

- **Multiple Levels**: Each mission can have several levels (1, 2, 3, 4, etc.)
- **Progress Tracking**: Users progress through levels by completing actions
- **Automatic Badge Awards**: Badges are automatically awarded when levels are completed
- **Completion Badges**: Special badges for completing entire missions

### Creating Mission Badges

#### Automatic Generation

Generate badges for all existing missions:

```bash
# Generate badges for all missions
php artisan gamify:generate-mission-badges --all

# Include completion badges (level 999)
php artisan gamify:generate-mission-badges --all --completion

# Generate badges for specific mission
php artisan gamify:generate-mission-badges your_mission_code
```

#### Manual Creation using MissionBadge Class

You can also create custom mission badges by extending the `MissionBadge` class:

```php
<?php

namespace App\Gamify\Badges;

use Voilaah\Gamify\Classes\Badge\MissionBadge;

class CourseExplorerLevel1 extends MissionBadge
{
    protected $name = 'Course Explorer - Beginner';
    protected $description = 'Complete your first course exploration level';
    protected $icon = 'course-explorer-level-1.svg';

    public function getMissionCode(): string
    {
        return 'course_explorer';
    }

    public function getMissionLevel(): int
    {
        return 1;
    }
}
```

### How Mission-Badge Linking Works

1. **Mission Progress**: When users perform actions that advance mission progress
2. **Level Completion**: Mission system detects when a level is completed
3. **Event Fired**: `gamify.mission.levelUp` event is triggered
4. **Badge Awarded**: Corresponding badge is automatically awarded to the user
5. **Context Tracked**: Badge award is recorded with mission context

### Database Structure

Mission badges have additional fields:

- `mission_code`: Links badge to specific mission
- `mission_level`: Specifies which mission level awards this badge
- `is_mission_badge`: Identifies mission-linked badges

User badge awards track:

- `awarded_at_level`: Which mission level awarded the badge
- `awarded_context`: How the badge was earned (`mission_level_completion`, `mission_completion`, `manual`)

### Configuration Examples

Mission badges inherit settings from the main mission but can be customized:

```php
// config/gamify.php additions
'mission_icon_folder' => 'images/missions/',
'mission_icon_extension' => '.svg',
```

### Badge Types

The system now supports two types of badges:

#### 1. Regular Badges (existing functionality)

- Independent qualification logic
- Manual awarding based on criteria
- Traditional `qualifier($user)` method

#### 2. Mission Badges (new functionality)

- Linked to specific mission levels
- Automatically awarded on mission progress
- Inherit mission properties

### Querying Mission Badges

```php
// Get all mission badges
$missionBadges = Badge::missionBadges()->get();

// Get badges for specific mission
$courseBadges = Badge::forMission('course_explorer')->get();

// Get badge for specific mission level
$level1Badge = Badge::forMissionLevel('course_explorer', 1)->first();

// Check if badge is mission-linked
if ($badge->isMissionBadge()) {
    echo "This badge is linked to mission: " . $badge->mission_code;
}
```

### User Badge History

Track how badges were earned:

```php
foreach($user->badges as $badge) {
    $pivot = $badge->pivot;

    if ($pivot->awarded_context === 'mission_level_completion') {
        echo "Earned by completing level {$pivot->awarded_at_level}";
    }
}
```

### Console Commands

```bash
# Refresh user mission progress
php artisan gamify:refresh-user-missions

# Generate mission badges
php artisan gamify:generate-mission-badges --all --completion
```

### Testing

The package contains some integration/smoke tests, set up with Orchestra. The tests can be run via phpunit.

```bash
$ composer test
```

### Security

If you discover any security related issues, please email sehanlim@outlook.com instead of using the issue tracker.

### Credits

- [Mohd Saqueib Ansari](https://github.com/saqueib) (Author)
- [voilaah](https://github.com/voilaah) (Author)

### License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

### display user badges, missions, etc...

To display a user's badges and achievements in the UI, you can retrieve them using the existing gamify relationships and methods. Here are several approaches:

1. 🏆 Get User Badges (Simple)

```
  // Get current user's badges
  $user = Auth::user();
  $badges = $user->badges; // Returns collection of earned badges

  // Or for specific user
  $user = User::find($userId);
  $badges = $user->badges;

  2. 📊 Get User Mission Progress & Achievements

  $user = Auth::user();
  $missionManager = app('gamify.missions');

  // Get all active missions
  $allMissions = $missionManager->allEnabled();

  // Get user progress for each mission
  $userAchievements = [];
  foreach ($allMissions as $missionCode => $mission) {
      $progress = $mission->getProgress($user);

      $userAchievements[] = [
          'mission' => [
              'code' => $mission->getCode(),
              'name' => $mission->getName(),
              'description' => $mission->getDescription(),
              'icon' => $mission->getIcon(),
          ],
          'progress' => $progress,
          'levels' => $mission->getLevels(),
      ];
  }
```

3. 🎯 Complete User Dashboard Data

```
  function getUserDashboardData($userId)
  {
      $user = User::find($userId);

      return [
          // Basic user info
          'user' => [
              'name' => $user->name,
              'reputation' => $user->getPoints(), // Total points/diamonds
              'rank' => getUserRank($user), // From helpers.php
          ],

          // All earned badges
          'badges' => $user->badges()->orderBy('created_at', 'desc')->get(),
          'badgeCount' => $user->badges_count,

          // Mission progress
          'missions' => $this->getUserMissionProgress($user),

          // Recent achievements (last 30 days)
          'recentBadges' => $user->badges()
              ->wherePivot('created_at', '>=', now()->subDays(30))
              ->orderBy('pivot_created_at', 'desc')
              ->get(),
      ];
  }

  private function getUserMissionProgress($user)
  {
      $missionManager = app('gamify.missions');
      $missions = [];

      foreach ($missionManager->allEnabled() as $mission) {
          $progress = $mission->getProgress($user);

          $missions[] = [
              'code' => $mission->getCode(),
              'name' => $mission->getName(),
              'description' => $mission->getDescription(),
              'icon' => $mission->getIcon(),
              'currentLevel' => $progress['currentLevel'],
              'maxLevel' => $progress['maxLevel'],
              'value' => $progress['value'],
              'goal' => $progress['goal'],
              'completed' => $progress['completed'],
              'completionPercentage' => $progress['goal'] > 0
                  ? round(($progress['value'] / $progress['goal']) * 100, 1)
                  : 0,
          ];
      }

      return $missions;
  }
```

4. 🎨 Component Example (October CMS)

Create a component to display user achievements:

````
  // components/UserAchievements.php
  <?php

  namespace Voilaah\Gamify\Components;

  use Cms\Classes\ComponentBase;
  use RainLab\User\Facades\Auth;

  class UserAchievements extends ComponentBase
  {
      public function componentDetails()
      {
          return [
              'name' => 'User Achievements',
              'description' => 'Display user badges and mission progress'
          ];
      }

      public function onRun()
      {
          $this->page['userAchievements'] = $this->getUserAchievements();
      }

      protected function getUserAchievements()
      {
          $user = Auth::getUser();
          if (!$user) return null;

          $missionManager = app('gamify.missions');

          return [
              'badges' => $user->badges()
                  ->orderBy('voilaah_gamify_user_badges.created_at', 'desc')
                  ->get(),

              'missions' => $missionManager->allEnabled()->map(function($mission) use ($user) {
                  return [
                      'mission' => $mission,
                      'progress' => $mission->getProgress($user)
                  ];
              }),

              'stats' => [
                  'totalBadges' => $user->badges()->count(),
                  'totalPoints' => $user->getPoints(),
                  'completedMissions' => $missionManager->allEnabled()
                      ->filter(function($mission) use ($user) {
                          return $mission->getProgress($user)['completed'];
                      })->count(),
              ]
          ];
      }
  }```

  5. 🚀 Frontend Display (Blade/Twig)
````

  <!-- User Dashboard -->
  <div class="user-achievements">
      <!-- Stats Overview -->
      <div class="stats-row">
          <div class="stat">
              <h3>{{ userAchievements.stats.totalPoints }}</h3>
              <p>Diamonds</p>
          </div>
          <div class="stat">
              <h3>{{ userAchievements.stats.totalBadges }}</h3>
              <p>Badges</p>
          </div>
          <div class="stat">
              <h3>{{ userAchievements.stats.completedMissions }}</h3>
              <p>Missions Complete</p>
          </div>
      </div>

      <!-- Recent Badges -->
      <div class="recent-badges">
          <h2>Recent Badges</h2>
          {% for badge in userAchievements.badges %}
              <div class="badge-card">
                  <img src="{{ badge.icon }}" alt="{{ badge.name }}">
                  <h3>{{ badge.name }}</h3>
                  <p>{{ badge.description }}</p>
                  <small>Earned {{ badge.pivot.created_at|date('M j, Y') }}</small>
              </div>
          {% endfor %}
      </div>

      <!-- Mission Progress -->
      <div class="missions-progress">
          <h2>Mission Progress</h2>
          {% for item in userAchievements.missions %}
              {% set mission = item.mission %}
              {% set progress = item.progress %}

              <div class="mission-card {{ progress.completed ? 'completed' : '' }}">
                  <div class="mission-info">
                      <img src="{{ mission.getIcon() }}" alt="{{ mission.getName() }}">
                      <div>
                          <h3>{{ mission.getName() }}</h3>
                          <p>{{ mission.getDescription() }}</p>
                      </div>
                  </div>

                  <div class="progress-bar">
                      <div class="progress-fill" style="width: {{ (progress.value / progress.goal * 100) }}%"></div>
                  </div>

                  <div class="progress-text">
                      {% if progress.completed %}
                          <span class="completed">✅ Complete!</span>
                      {% else %}
                          <span>{{ progress.value }}/{{ progress.goal }}</span>
                          <span>Level {{ progress.currentLevel }}/{{ progress.maxLevel }}</span>
                      {% endif %}
                  </div>
              </div>
          {% endfor %}
      </div>

  </div>
```
  6. ⚡ API Endpoints (for AJAX)

```
  // In your controller
  public function getUserAchievements($userId = null)
  {
      $user = $userId ? User::find($userId) : Auth::user();

      return response()->json([
          'badges' => $user->badges,
          'missions' => $this->getUserMissionProgress($user),
          'reputation' => $user->getPoints(),
          'rank' => getUserRank($user),
      ]);

}

```

Perfect! You need to show all available badges (both earned and not earned) to motivate users. Here's how to retrieve and display all mission badges with their states:

1. 🎯 Get All Mission Badges with User Status

```
function getAllMissionBadgesWithStatus($user)
{
$missionManager = app('gamify.missions');
$badgeModel = config('gamify.badge_model');
$userBadgeIds = $user->badges()->pluck('voilaah_gamify_badges.id')->toArray();

      $allBadges = [];

      foreach ($missionManager->allEnabled() as $mission) {
          $progress = $mission->getProgress($user);
          $missionBadges = $this->getMissionBadges($mission, $userBadgeIds, $progress);

          $allBadges[] = [
              'mission' => [
                  'code' => $mission->getCode(),
                  'name' => $mission->getName(),
                  'description' => $mission->getDescription(),
                  'icon' => $mission->getIcon(),
              ],
              'progress' => $progress,
              'badges' => $missionBadges,
          ];
      }

      return $allBadges;

}

private function getMissionBadges($mission, $userBadgeIds, $progress)
{
$badgeModel = config('gamify.badge_model');
$badges = [];

      // Get level badges
      foreach ($mission->getLevels() as $level => $config) {
          $badge = $badgeModel::where('mission_code', $mission->getCode())
              ->where('mission_level', $level)
              ->where('is_mission_badge', true)
              ->first();

          if ($badge) {
              $badges[] = [
                  'badge' => $badge,
                  'level' => $level,
                  'type' => 'level',
                  'status' => $this->getBadgeStatus($badge, $userBadgeIds, $progress, $level),
                  'points' => $config['points'] ?? 0,
                  'goal' => $config['goal'] ?? 0,
              ];
          }
      }

      // Get completion badge
      $completionBadge = $badgeModel::where('mission_code', $mission->getCode())
          ->where('mission_level', 999)
          ->where('is_mission_badge', true)
          ->first();

      if ($completionBadge) {
          $badges[] = [
              'badge' => $completionBadge,
              'level' => 999,
              'type' => 'completion',
              'status' => $this->getBadgeStatus($completionBadge, $userBadgeIds, $progress, 999),
              'points' => $mission->completionPoints ?? 0,
              'goal' => 'Complete all levels',
          ];
      }

      return $badges;

}

private function getBadgeStatus($badge, $userBadgeIds, $progress, $level)
  {
      if (in_array($badge->id, $userBadgeIds)) {
return 'earned';
}

      if ($level === 999) {
          return $progress['completed'] ? 'earned' : 'locked';
      }

      if ($progress['currentLevel'] >= $level) {
          return 'earned';
      } elseif ($progress['currentLevel'] === $level && $progress['value'] > 0) {
          return 'in_progress';
      } else {
          return 'locked';
      }

}
```

2. 🎨 Component for Badge Display

```
// components/AllMissionBadges.php

  <?php

  namespace Voilaah\Gamify\Components;

  use Cms\Classes\ComponentBase;
  use RainLab\User\Facades\Auth;

  class AllMissionBadges extends ComponentBase
  {
      public function componentDetails()
      {
          return [
              'name' => 'All Mission Badges',
              'description' => 'Display all mission badges with earned/locked states'
          ];
      }

      public function defineProperties()
      {
          return [
              'showProgress' => [
                  'title' => 'Show Progress Bars',
                  'description' => 'Show progress bars for in-progress badges',
                  'type' => 'checkbox',
                  'default' => true
              ],
              'groupByMission' => [
                  'title' => 'Group by Mission',
                  'description' => 'Group badges by mission or show flat list',
                  'type' => 'checkbox',
                  'default' => true
              ]
          ];
      }

      public function onRun()
      {
          $user = Auth::getUser();
          if (!$user) {
              $this->page['allBadges'] = [];
              return;
          }

          $this->page['allBadges'] = $this->getAllMissionBadgesWithStatus($user);
          $this->page['showProgress'] = $this->property('showProgress');
          $this->page['groupByMission'] = $this->property('groupByMission');
      }

      // Include the methods from above here...
      protected function getAllMissionBadgesWithStatus($user) { /* ... */ }
      private function getMissionBadges($mission, $userBadgeIds, $progress) { /* ... */ }
      private function getBadgeStatus($badge, $userBadgeIds, $progress, $level) { /* ... */ }
  }
```

3. 🎭 Frontend Display with States

```
  <!-- Badge Gallery -->
  <div class="badge-gallery">
      {% if groupByMission %}
          <!-- Grouped by Mission -->
          {% for missionData in allBadges %}
                <div class="mission-section">
                  <div class="mission-header">
                      <img src="{{ missionData.mission.icon }}" alt="{{ missionData.mission.name }}" class="mission-icon">
                      <div class="mission-info">
                          <h2>{{ missionData.mission.name }}</h2>
                          <p>{{ missionData.mission.description }}</p>
                          <div class="mission-progress">
                              {% if missionData.progress.completed %}
                                  <span class="status completed">✅ Mission Complete!</span>
                              {% else %}
                                  <span class="status in-progress">
                                      Level {{ missionData.progress.currentLevel }}/{{ missionData.progress.maxLevel }}
                                      ({{ missionData.progress.value }}/{{ missionData.progress.goal }})
                                  </span>
                              {% endif %}
                          </div>
                      </div>
                  </div>

                  <div class="mission-badges">
                      {% for badgeData in missionData.badges %}
                          <div class="badge-card {{ badgeData.status }}">
                              <div class="badge-image-container">
                                  <img src="{{ badgeData.badge.icon }}" alt="{{ badgeData.badge.name }}" class="badge-image">

                                  <!-- Status Overlay -->
                                  {% if badgeData.status == 'earned' %}
                                      <div class="badge-overlay earned">✅</div>
                                  {% elseif badgeData.status == 'locked' %}
                                      <div class="badge-overlay locked">🔒</div>
                                  {% elseif badgeData.status == 'in_progress' %}
                                      <div class="badge-overlay progress">⏳</div>
                                  {% endif %}
                              </div>

                              <div class="badge-info">
                                  <h3>{{ badgeData.badge.name }}</h3>
                                  <p>{{ badgeData.badge.description }}</p>

                                  {% if badgeData.points > 0 %}
                                      <div class="badge-points">{{ badgeData.points }} 💎</div>
                                  {% endif %}

                                  <!-- Progress Bar for In-Progress Badges -->
                                  {% if badgeData.status == 'in_progress' and showProgress %}
                                      {% set progressPercent = (missionData.progress.value / missionData.progress.goal * 100) %}
                                      <div class="progress-bar">
                                          <div class="progress-fill" style="width: {{ progressPercent }}%"></div>
                                      </div>
                                      <small>{{ missionData.progress.value }}/{{ missionData.progress.goal }}</small>
                                  {% endif %}

                                  <!-- Requirements for Locked Badges -->
                                  {% if badgeData.status == 'locked' %}
                                      <div class="requirements">
                                          <small>{{ badgeData.goal }}</small>
                                      </div>
                                  {% endif %}
                              </div>
                          </div>
                      {% endfor %}
                  </div>
              </div>
          {% endfor %}
      {% else %}
          <!-- Flat Badge List -->
          <div class="badge-grid">
              {% for missionData in allBadges %}
                  {% for badgeData in missionData.badges %}
                      <!-- Same badge card structure as above -->
                  {% endfor %}
              {% endfor %}
          </div>
      {% endif %}
  </div>
```
