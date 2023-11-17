<?php

namespace PHPNomad\Framework\Events;

use PHPNomad\Events\Interfaces\Event;

class SiteVisited implements Event
{
    protected ?int $visitorId;

    public function __construct(?int $visitorId)
    {
        $this->visitorId = $visitorId;
    }

    public static function getId(): string
    {
        return 'site_visited';
    }

    public function getVisitorId(): int
    {
        return $this->visitorId;
    }
}