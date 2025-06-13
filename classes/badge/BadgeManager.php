<?php

namespace Voilaah\Gamify\Classes\Badge;

use Event;
use Voilaah\Gamify\Classes\Badge\BaseBadge;

class BadgeManager
{
    /**
     * @var array<string, BaseBadge>
     */
    protected array $badges = [];

    /**
     * Register a mission into the system.
     */
    public function register(BaseBadge $mission): void
    {
        $this->badges[$mission->getBadgeId()] = $mission;
    }

    /**
     * Get all registered badges.
     *
     * @return array<string, BaseBadge>
     */
    public function all(): array
    {
        return $this->badges;
    }

    /**
     * Get only enabled badges.
     *
     * @return array<string, BaseBadge>
     */
    public function allEnabled(): \Illuminate\Support\Collection
    {
        return collect($this->badges)->filter(function ($badge) {
            return method_exists($badge, 'isEnabled') ? $badge->isEnabled() : true;
        });
        /* return array_filter(
            $this->badges,
            fn($badge) => method_exists($badge, 'isEnabled') ? $badge->isEnabled() : true
        ); */
    }

    /**
     * Get a mission by its unique code.
     *
     * @param string $code
     * @return BaseBadge|null
     */
    public function find(string $code): ?BaseBadge
    {
        return $this->badges[$code] ?? null;
    }

    /**
     * Check if a mission exists.
     */
    public function has(string $code): bool
    {
        return isset($this->badges[$code]);
    }

}
