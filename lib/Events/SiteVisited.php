<?php

namespace PHPNomad\Framework\Events;

use PHPNomad\Events\Interfaces\Event;

class SiteVisited implements Event
{
    public static function getId(): string
    {
        return 'site_visited';
    }
}