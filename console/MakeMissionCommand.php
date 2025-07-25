<?php

namespace Voilaah\Gamify\Console;

use October\Rain\Scaffold\GeneratorCommandBase;

class MakeMissionCommand extends GeneratorCommandBase
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'voilaah:gamify-mission {namespace : App or Plugin Namespace (eg: Acme.Blog)}
    {name : The name of the Mission. Eg: FirstContribution}
    {--o|overwrite : Overwrite existing files with generated ones}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a Gamify mission class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $typeLabel = 'Mission';

    /**
     * makeStubs makes all stubs
     */
    public function makeStubs()
    {
        $this->makeStub('stubs/mission.stub', 'missions/{{studly_name}}.php');
    }

    /**
     * prepareVars prepares variables for stubs
     */
    protected function prepareVars(): array
    {
        return [
            'name' => $this->argument('name'),
            'namespace' => $this->argument('namespace'),
        ];
    }

    /**
     * Execute the console command.
     *
     * @return bool|null
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle()
    {
        // clear the cache for badges
        cache()->forget('gamify.missions.all');

        return parent::handle();
    }


}
