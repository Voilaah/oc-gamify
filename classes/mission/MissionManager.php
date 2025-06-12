<?php

namespace Voilaah\Gamify\Classes\Missions;

use Voilaah\Gamify\Classes\Missions\BaseMission;

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
    public function allEnabled(): array
    {
        return array_filter(
            $this->missions,
            fn($mission) => method_exists($mission, 'isEnabled') ? $mission->isEnabled() : true
        );
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
    public function handleEventForAll(string $eventName, array $payload = []): void
    {
        foreach ($this->allEnabled() as $mission) {
            $mission->handleEvent($eventName, $payload);
        }
    }
}
