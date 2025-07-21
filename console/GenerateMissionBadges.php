<?php

namespace Voilaah\Gamify\Console;

use Illuminate\Console\Command;
use Voilaah\Gamify\Models\Badge;

class GenerateMissionBadges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gamify:generate-mission-badges 
                            {mission? : The mission code to generate badges for}
                            {--all : Generate badges for all missions}
                            {--completion : Also generate completion badges}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate mission badges for existing missions';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $missionCode = $this->argument('mission');
        $generateAll = $this->option('all');
        $includeCompletion = $this->option('completion');

        if (!$missionCode && !$generateAll) {
            $this->error('Please specify a mission code or use --all to generate badges for all missions');
            return 1;
        }

        $missionManager = app('gamify.missions');
        $missions = $generateAll ? $missionManager->allEnabled() : collect([$missionManager->find($missionCode)]);

        if ($missions->isEmpty() || (!$generateAll && !$missions->first())) {
            $this->error("Mission '{$missionCode}' not found");
            return 1;
        }

        $totalBadges = 0;

        foreach ($missions as $mission) {
            if (!$mission) continue;

            $this->info("Generating badges for mission: {$mission->getName()} ({$mission->getCode()})");
            
            $levels = $mission->getLevels();
            $badgeCount = 0;

            foreach ($levels as $level => $config) {
                $badge = Badge::firstOrCreate([
                    'mission_code' => $mission->getCode(),
                    'mission_level' => $level,
                    'is_mission_badge' => true
                ], [
                    'name' => "{$mission->getName()} - Level {$level}",
                    'description' => $config['description'] ?? "Complete level {$level} of {$mission->getName()}",
                    'icon' => $mission->getIcon(),
                    'level' => $level,
                    'sort_order' => $level
                ]);

                if ($badge->wasRecentlyCreated) {
                    $this->line("  ✓ Created badge: {$badge->name}");
                    $badgeCount++;
                } else {
                    $this->line("  - Badge already exists: {$badge->name}");
                }
            }

            // Generate completion badge if requested
            if ($includeCompletion) {
                $completionBadge = Badge::firstOrCreate([
                    'mission_code' => $mission->getCode(),
                    'mission_level' => 999,
                    'is_mission_badge' => true
                ], [
                    'name' => "{$mission->getName()} - Master",
                    'description' => "Complete all levels of {$mission->getName()}",
                    'icon' => $mission->getIcon(),
                    'level' => 999,
                    'sort_order' => 999
                ]);

                if ($completionBadge->wasRecentlyCreated) {
                    $this->line("  ✓ Created completion badge: {$completionBadge->name}");
                    $badgeCount++;
                } else {
                    $this->line("  - Completion badge already exists: {$completionBadge->name}");
                }
            }

            $totalBadges += $badgeCount;
            $this->info("  Generated {$badgeCount} new badges for {$mission->getName()}");
        }

        $this->info("Total badges generated: {$totalBadges}");
        return 0;
    }
}