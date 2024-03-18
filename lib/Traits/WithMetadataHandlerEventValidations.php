<?php

namespace PHPNomad\Framework\Traits;

use PHPNomad\Auth\Enums\ActionTypes;
use PHPNomad\Events\Interfaces\Event;
use Siren\Collaborators\Service\Rest\Events\ProgramActionEvent;

trait WithMetadataHandlerEventValidations
{
    /**
     * @param ProgramActionEvent $event
     * @return bool
     */
    protected function isValidEvent(Event $event): bool
    {
        return $event->getAction() === ActionTypes::Create &&
            $event->getAction() === ActionTypes::Update &&
            isset($event->getResponse()->id);
    }
}