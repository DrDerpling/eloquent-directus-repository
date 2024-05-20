<?php

declare(strict_types=1);

namespace DrDerpling\DirectusRepository\Api;

class Directus
{
    final public function __construct(protected string $collectionName, protected HttpClient $httpClient)
    {
    }

    /**
     * Returns an instance of Assets or Items based on the collection name.
     *
     * @param string $collectionName The name of the collection.
     * @return Assets|Items Returns Assets if the collection name is 'assets',
     *                      otherwise returns Items.
     */
    public static function query(string $collectionName): Assets|Items
    {
        $directusApi = resolve(HttpClient::class);

        return match ($collectionName) {
            'assets' => new Assets($directusApi),
            default => new Items($directusApi, $collectionName),
        };
    }

    public static function assets(): Assets
    {
        return new Assets(resolve(HttpClient::class));
    }

    public static function items(string $collectionName): Items
    {
        return new Items(resolve(HttpClient::class), $collectionName);
    }
}
