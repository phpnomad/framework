<?php

namespace Phoenix\Framework\Abstracts;

use Phoenix\Cache\Enums\Operation;
use Phoenix\Cache\Services\CacheableService;
use Phoenix\Datastore\Exceptions\DatastoreErrorException;
use Phoenix\Datastore\Interfaces\DataModel;
use Phoenix\Datastore\Interfaces\Datastore as CoreDatastore;
use Phoenix\Logger\Interfaces\LoggerStrategy;
use Phoenix\Utils\Helpers\Arr;

class Datastore implements CoreDatastore
{
    protected CacheableService $cacheableService;
    protected LoggerStrategy $loggerStrategy;

    public function __construct(
        CacheableService $cacheableService,
        LoggerStrategy   $loggerStrategy
    )
    {
        $this->loggerStrategy = $loggerStrategy;
        $this->cacheableService = $cacheableService;
    }

    /**
     * @param array $ids
     * @return DataModel[]
     */
    abstract public function fetchData(array $ids): array;

    public function find($id): DataModel
    {
        return $this->cacheableService->getWithCache(
            Operation::Read,
            $this->getCacheContextForItem($id),
            fn() => $this->modelAdapter->toModel(
                $this->queryStrategy->query(
                    $this->queryBuilder
                        ->select('*')
                        ->from($this->table)
                        ->where('id', '=', $id)
                        ->limit(1)
                )
            )
        );
    }

    public function where(array $conditions, ?int $limit = null, ?int $offset = null): array
    {
        $this->queryBuilder
            ->select('id')
            ->from($this->table);


        if ($limit) {
            $this->queryBuilder->limit($limit);
        }

        if ($offset) {
            $this->queryBuilder->offset($offset);
        }

        $this->buildConditions($conditions);
    }

    public function findBy(string $field, $value): array
    {
        // TODO: Implement findBy() method.
    }

    public function create(array $attributes): int
    {
        // TODO: Implement create() method.
    }

    public function update($id, array $attributes): void
    {
        // TODO: Implement update() method.
    }

    public function delete($id): void
    {
        // TODO: Implement delete() method.
    }

    public function deleteWhere(array $conditions): void
    {
        // TODO: Implement deleteWhere() method.
    }

    public function count(array $conditions = []): int
    {
        // TODO: Implement count() method.
    }

    /**
     * Takes the given array of conditions and adds it to the query builder as a where statement.
     *
     * @param array $conditions
     * @return void
     */
    protected function buildConditions(array $conditions)
    {
        $firstCondition = array_shift($conditions);
        $column = Arr::get($firstCondition, 'column');
        $operator = Arr::get($firstCondition, 'operator');
        $value = Arr::get($firstCondition, 'value');

        $this->queryBuilder->where($column, $operator, $value);

        foreach ($conditions as $condition) {
            $column = Arr::get($condition, 'column');
            $operator = Arr::get($condition, 'operator');
            $value = Arr::get($condition, 'value');

            $this->queryBuilder->andWhere($column, $operator, $value);
        }
    }

    /**
     * Gets the models from the specified list of IDs.
     *
     * @param array|null $ids
     * @return array
     */
    public function getModels(?array $ids = null): array
    {
        try {
            // Filter out the items that are currently in the cache.
            $idsToQuery = Arr::filter(
                $ids,
                fn(int $id) => !$this->cacheableService->exists($this->getCacheContextForItem($id))
            );

        } catch (DatastoreErrorException $e) {
            $this->loggerStrategy->logException($e, 'Could not get by ID');
        }

        if (!empty($idsToQuery)) {
            try {
                // Get the things that aren't in the cache.
                $data = $this->queryStrategy->query(
                    $this->queryBuilder
                        ->select('*')
                        ->from($this->table)
                        ->where('id', 'IN', ...$idsToQuery)
                );
            } catch (DatastoreErrorException $e) {
                $this->loggerStrategy->logException($e, 'Could not get by ID');
            }

            // Cache those items.
            $this->cacheItems(Arr::map($data, [$this->modelAdapter, 'toModel']));
        }

        // Now, use the cache to get all the posts in the proper order.
        return Arr::map($ids, fn(int $id) => $this->find($id));
    }

    /**
     * Caches items in-batch
     *
     * @param DataModel[] $models
     *
     * @return void
     */
    protected function cacheItems(array $models): void
    {
        Arr::map(
            $models,
            fn(DataModel $model) => $this->cacheableService->set($this->getCacheContextForItem($model->getId()), $model)
        );
    }

    /**
     * Gets the cache context for the given ID.
     *
     * @param int $id
     * @return array
     */
    protected function getCacheContextForItem(int $id)
    {
        return ['id' => $id, 'type' => get_called_class()];
    }

    public function findIds(array $conditions, ?int $limit = null, ?int $offset = null): array
    {
        // TODO: Implement findIds() method.
    }
}