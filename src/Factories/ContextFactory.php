<?php

declare(strict_types=1);

namespace Drderpling\DirectusRepository\Factories;

use Drderpling\DirectusRepository\Repositories\Context;

class ContextFactory
{
    public static function create(
        string $modelClass,
        array $fields = [],
        string $collectionName = '',
        ?string $orderBy = 'sort',
    ): Context {
        $forceRefresh = false;

        if (config('directus.enable_force_sync')) {
            $request = request();
            $forceRefresh = (bool)$request->input('force_new', false);
        }

        return new Context($modelClass, $fields, $forceRefresh, $collectionName, $orderBy);
    }
}
