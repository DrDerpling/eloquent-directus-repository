<?php

declare(strict_types=1);

namespace DrDerpling\DirectusRepository;

use Illuminate\Support\ServiceProvider;

class DirectusServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // To publish configuration files
        $this->publishes([
            __DIR__ . '/../config/directus.php' => config_path('directus.php'),
        ], 'directus');
    }
}
