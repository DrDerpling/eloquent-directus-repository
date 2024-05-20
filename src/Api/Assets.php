<?php

declare(strict_types=1);

namespace Drderpling\DirectusRepository\Api;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class Assets extends Builder
{
    public function download(
        string $id,
        ?FilesystemAdapter $disk = null,
        ?string $filePath = null
    ): string {
        if (is_null($disk)) {
            $disk = Storage::disk('local');
        }

        return $this->httpClient->downloadAssets($id, $this->buildQueryParameters(), $disk, $filePath);
    }

    public function find(int|string $id): array
    {
        if (is_numeric($id)) {
            throw new InvalidArgumentException('Asset ID must be a string');
        }

        return $this->httpClient->findAssets($id, $this->buildQueryParameters());
    }
}
