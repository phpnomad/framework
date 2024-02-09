<?php

namespace PHPNomad\Framework\Validations;

use PHPNomad\Database\Exceptions\RecordNotFoundException;
use PHPNomad\Datastore\Exceptions\DatastoreErrorException;
use PHPNomad\Datastore\Interfaces\Datastore;
use PHPNomad\Rest\Exceptions\ValidationException;
use PHPNomad\Rest\Interfaces\Request;
use PHPNomad\Rest\Interfaces\Validation;

class IsUniqueDatabaseValue implements Validation
{
    protected Datastore $datastore;

    public function __construct(Datastore $datastore)
    {
        $this->datastore = $datastore;
    }

    /**
     * @param string $key
     * @param Request $request
     * @return bool
     * @throws ValidationException
     */
    public function isValid(string $key, Request $request): bool
    {
        try {
            $this->datastore->findBy($key, $request->getParam($key));
            return false;
        } catch (RecordNotFoundException $e) {
            return true;
        } catch (DatastoreErrorException $e) {
            throw new ValidationException('Something went wrong when validating the uniqueness of a field.',[]);
        }
    }

    public function getErrorMessage(string $key, Request $request): string
    {
        return "Field $key already exists.";
    }

    public function getType(): string
    {
        return 'DUPLICATE_ENTRY';
    }

    public function getContext(): array
    {
        return [];
    }
}