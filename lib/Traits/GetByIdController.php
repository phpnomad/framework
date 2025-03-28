<?php

namespace PHPNomad\Framework\Traits;

use PHPNomad\Datastore\Exceptions\RecordNotFoundException;
use PHPNomad\Datastore\Exceptions\DatastoreErrorException;
use PHPNomad\Datastore\Interfaces\CanConvertModelToArray;
use PHPNomad\Datastore\Interfaces\Datastore;
use PHPNomad\Logger\Interfaces\LoggerStrategy;
use PHPNomad\Http\Enums\Method;
use PHPNomad\Http\Interfaces\Request;
use PHPNomad\Http\Interfaces\Response;
use Siren\Collaborators\Core\Datastores\Collaborator\Interfaces\CollaboratorDatastore;
use Siren\Collaborators\Core\Models\Adapters\CollaboratorAdapter;

trait GetByIdController
{
    protected Datastore $datastore;
    protected Response $response;
    protected CanConvertModelToArray $adapter;
    protected LoggerStrategy $logger;

    /** @inheritDoc */
    public function getResponse(Request $request): Response
    {
        //TODO: UPDATE THIS TO USE RESOLVERS INSTEAD OF RETURNING DATABASE RECORD DIRECTLY.
        $response = clone $this->response;

        try {
            $record = $this->datastore->find($request->getParam('id'));

            $response->setStatus(200);
            $response->setJson($this->adapter->toArray($record));
        } catch (RecordNotFoundException $e) {
            $response->setStatus(404);
        } catch (DatastoreErrorException $e) {
            $this->logger->logException($e);
            $response->setError('Something went wrong fetching data from the database.', 500);
        }

        return $response;
    }

    /** @inheritDoc */
    public function getMethod(): string
    {
        return Method::Get;
    }


    /** @inheritDoc */
    public function getEndpoint(): string
    {
        return $this->getEndpointBase() . '/{id}';
    }

    /**
     * Returns the base for the endpoint, eg: /modelName
     *
     * @return string
     */
    abstract protected function getEndpointBase(): string;
}