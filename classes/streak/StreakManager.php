<?php

namespace Voilaah\Gamify\Classes\Streak;

class StreakManager
{
    protected static $types = [];

    public static function register(string $code, string $label, string $class): void
    {
        self::$types[$code] = ['class' => $class, 'label' => $label];
    }

    public static function get($code)
    {
        return self::$types[$code] ?? null;
    }

    public static function all()
    {
        return self::$types;
    }

    public static function getLabel(string $code): string
    {
        return self::$types[$code]['label'] ?? ucfirst(str_replace('_', ' ', $code));
    }
}
