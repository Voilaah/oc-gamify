<?php

namespace Voilaah\Gamify\Classes\Mission;

use Event;
use Voilaah\Gamify\Classes\Mission\BaseMission;

class MissionManager
{
    /**
     * @var array<string, BaseMission>
     */
    protected array $missions = [];

    /**
     * Register a mission into the system.
     */
    public function register(BaseMission $mission): void
    {
        $this->missions[$mission->getCode()] = $mission;
    }

    /**
     * Get all registered missions.
     *
     * @return array<string, BaseMission>
     */
    public function all(): array
    {
        return $this->missions;
    }

    /**
     * Get only enabled missions.
     *
     * @return array<string, BaseMission>
     */

    public function allEnabled(): \Illuminate\Support\Collection
    {
        return collect($this->missions)->filter(function ($mission) {
            return method_exists($mission, 'isEnabled') ? $mission->isEnabled() : true;
        });
        /* return array_filter(
            $this->missions,
            fn($mission) => method_exists($mission, 'isEnabled') ? $mission->isEnabled() : true
        ); */
    }

    /**
     * Get a mission by its unique code.
     *
     * @param string $code
     * @return BaseMission|null
     */
    public function find(string $code): ?BaseMission
    {
        return $this->missions[$code] ?? null;
    }

    /**
     * Check if a mission exists.
     */
    public function has(string $code): bool
    {
        return isset($this->missions[$code]);
    }

    /**
     * Manually trigger an event handler on all missions.
     */
    public function registerEventListeners(): void
    {
        /* \Log::info('[Gamify] Registered mission event listeners'); */
        foreach ($this->allEnabled() as $mission) {
            /* \Log::info("--[Gamify] Registered mission event listener for {$mission->getName()}"); */
            /* $mission->handleEvent($eventName, $payload); */
            foreach ($mission->getSubscribedEvents() as $event => $payloadBuilder) {

                Event::listen($event, function (...$args) use ($event, $payloadBuilder, $mission) {
                    $payload = $payloadBuilder(...$args);

                    // Defensive: must return a user
                    if (!isset($payload['user'])) {
                        return;
                    }

                    $mission->handleEvent($event, $payload);
                });
            }
        }
    }
}
