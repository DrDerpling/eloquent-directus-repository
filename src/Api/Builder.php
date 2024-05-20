<?php

declare(strict_types=1);

namespace Drderpling\DirectusRepository\Api;

use Illuminate\Filesystem\FilesystemAdapter;

/**
 * @method void download(string $id, ?FilesystemAdapter $disk, string $fileName) Download an asset.
 * @method array get() array Get all items. Only works on Items.
 */
abstract class Builder
{
    protected array $filters = [];
    protected array $fields = [];
    protected array $queryParameters = [];

    public function __construct(protected HttpClient $httpClient)
    {
    }

    public function buildQueryParameters(): array
    {
        $parameters = [];

        if (!empty($this->filters)) {
            foreach ($this->filters as $filter) {
                $parameters['filter'][$filter['field']][$filter['operator']] = $filter['value'];
            }
        }

        if (!empty($this->fields)) {
            $parameters['fields'] = implode(',', $this->fields);
        }

        if (!empty($this->queryParameters)) {
            $parameters = array_merge($parameters, $this->queryParameters);
        }

        return $parameters;
    }

    public function where(string $field, string $operator, mixed $value): self
    {
        $this->filters[] = [
            'field' => $field,
            'operator' => $operator,
            'value' => $value
        ];
        return $this;
    }

    public function addQueryParameter(string $key, mixed $value): self
    {
        $this->queryParameters[$key] = $value;
        return $this;
    }

    public function fields(string ...$fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    abstract public function find(int|string $id): array;
}
