<?php

namespace Voilaah\Gamify\Console;

use Illuminate\Console\Command;
use App;
use RainLab\User\Models\User;

class RefreshUserMissions extends Command
{
    protected $name = 'gamify:refresh-user-missions';
    protected $description = 'Re-evaluates all user missions and levels';

    public function handle()
    {
        $userId = $this->ask('Enter user ID to refresh (leave blank for all users):');

        $users = $userId
            ? User::where('id', $userId)->get()
            : User::all();

        $missionManager = App::make('gamify.missions');
        $missions = $missionManager->all();

        $this->info("Refreshing missions for " . $users->count() . " user(s)...");

        foreach ($users as $user) {
            foreach ($missions as $mission) {
                $this->line("→ User {$user->id}: {$mission->getCode()}");
                $mission->handleEvent('manual.recheck', ['user' => $user]);
            }
        }

        $this->info("✔ All done!");
    }
}
