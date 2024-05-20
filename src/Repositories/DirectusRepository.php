<?php

declare(strict_types=1);

namespace Drderpling\DirectusRepository\Repositories;

use Drderpling\DirectusRepository\Api\Directus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as DatabaseCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;

abstract class DirectusRepository
{
    abstract public function getContext(): Context;

    /**
     * retrieves a model by its ID, if it exists in the database, otherwise retrieves it from Directus
     *
     * @param int $id
     * @return Model
     */
    public function get(int $id): Model
    {
        $this->validateModelClass();

        if ($this->getContext()->isForceRefresh()) {
            return $this->getList()->firstWhere('id', $id);
        }

        $model = $this->getModelQuery()->find($id);

        if (!$model) {
            $model = $this->getList()->firstWhere('id', $id);
        }

        if (!$model) {
            throw new InvalidArgumentException(
                "Model with ID {$id} not found."
                . " Please ensure the model exists in the database or in the CMS."
            );
        }

        return $model;
    }

    public function getByCmsId(int $cmsId): Model
    {
        if ($this->getContext()->isForceRefresh()) {
            return $this->getList()->firstWhere('cms_id', $cmsId);
        }

        $this->validateModelClass();

        $model = $this->getContext()->getModelClass()::where('cms_id', $cmsId)->first();

        if (!$model) {
            $model = $this->getList()->firstWhere('cms_id', $cmsId);
        }

        if (!$model) {
            throw new InvalidArgumentException(
                "Model with CMS ID {$cmsId} not found."
                . " Please ensure the model exists in the database or in the CMS."
            );
        }

        return $model;
    }

    /**
     * This method is used to update or create a model instance based on the provided data.
     *
     * @param Collection $item
     * @return Model The updated or newly created model instance.
     */
    public function updateOrCreate(Collection $item): Model
    {
        return $this->getModelQuery()->updateOrCreate(
            ['cms_id' => $item->get('cms_id')],
            $item->toArray()
        );
    }

    /**
     * Utility method to filter and prepare data for persistence.
     * This is always run before updating or creating a model.
     * Override this method in subclass repositories to handle model-specific data preparation.
     *
     * @param array $data The original data array.
     * @return Collection Object containing the prepared data and allows for easy further manipulation.
     */
    protected function prepareData(array $data): Collection
    {
        $object = collect($data);
        $object->put('cms_id', $object->get('id'));

        return $object;
    }

    /**
     * Retrieves data from Directus and updates or creates models based on the provided context.
     *
     * @param int|null $cmdId
     * @return Model|array<array-key,Model>
     */
    protected function getFromDirectus(int $cmdId = null): Model|array
    {
        $query = Directus::collection($this->getContext()->getCollectionName());

        if (!empty($this->getContext()->getFields())) {
            $query->fields(...$this->getContext()->getFields());
        }

        if ($cmdId) {
            return $this->updateOrCreate($this->prepareData($query->find($cmdId)));
        }

        return array_map(function ($item) {
            return $this->updateOrCreate($this->prepareData($item));
        }, $query->get());
    }

    /**
     * Validates that the provided class name is a valid Eloquent model class.
     */
    protected function validateModelClass(): void
    {
        if (!is_subclass_of($this->getContext()->getModelClass(), Model::class)) {
            throw new InvalidArgumentException(
                "The class {$this->getContext()->getModelClass()} must be a subclass of " . Model::class
            );
        }
    }

    /**
     * Get a list of models from the database or from Directus if the database is empty.
     *
     * @return DatabaseCollection<Model>
     */
    public function getList(): DatabaseCollection
    {
        if ($this->getContext()->isForceRefresh()) {
            return new DatabaseCollection($this->getFromDirectus());
        }

        $query = $this->getModelQuery();

        if ($this->getContext()->getOrderBy()) {
            $query->orderBy($this->getContext()->getOrderBy());
        }

        $list = $this->getModelQuery()->get();

        if ($list->isEmpty()) {
            return new DatabaseCollection($this->getFromDirectus());
        }

        return $list;
    }

    /**
     * Get a query builder for the model.
     *
     * @return Builder
     */
    protected function getModelQuery(): Builder
    {
        return $this->getContext()->getModelClass()::query();
    }
}
