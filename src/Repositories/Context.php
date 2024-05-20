<?php

declare(strict_types=1);

namespace DrDerpling\DirectusRepository\Repositories;

use Illuminate\Database\Eloquent\Model;
use Str;

readonly class Context
{
    /**
     * @template T of Model
     * @param class-string<T> $modelClass The model class to use for the repository.
     * @param array $fields The fields to retrieve from the CMS. If empty, all fields will be retrieved.
     * @param bool $forceRefresh Whether to force a refresh of the data from the CMS.
     */
    public function __construct(
        private string $modelClass,
        private array $fields = [],
        private bool $forceRefresh = false,
        private string $collectionName = '',
        private ?string $orderBy = 'sort'
    ) {
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function isForceRefresh(): bool
    {
        return $this->forceRefresh;
    }

    /**
     * @return class-string<Model>
     */
    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    public function getCollectionName(): string
    {
        if ($this->collectionName) {
            return $this->collectionName;
        }

        return Str::plural(Str::lower(class_basename($this->modelClass)));
    }

    public function getOrderBy(): ?string
    {
        return $this->orderBy;
    }
}
