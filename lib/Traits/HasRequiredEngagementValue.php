<?php

namespace PHPNomad\Framework\Traits;

use PHPNomad\Rest\Factories\ValidationSet;
use PHPNomad\Rest\Validations\IsNumeric;
use Siren\Incentives\Core\Events\CreateEngagementTypeValidationsInitiated;

trait HasRequiredEngagementValue
{
    /**
     * @param CreateEngagementTypeValidationsInitiated $event
     * @param string $engagementType
     * @return void
     */
    public function addRequiredEngagementValue(CreateEngagementTypeValidationsInitiated $event, string $engagementType): void
    {
        if ($event->hasEngagementType($engagementType)) {
            $event->addValidationSet(
                $engagementType,
                'engagementValue',
                (new ValidationSet())
                    ->setRequired()
                    ->addValidation(fn() => new IsNumeric())
            );
        }
    }
}