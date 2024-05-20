<?php

declare(strict_types=1);

namespace DrDerpling\DirectusRepository\Api;

use Exception;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class HttpClient
{
    /**
     * @param string $collection
     * @param array $query
     * @return array
     * @throws Exception
     */
    public function getItems(string $collection, array $query = []): array
    {
        $response = $this->get("items/$collection", $query)->json();

        if (isset($response['errors'])) {
            $this->handleError($response);
        }

        return $response['data'];
    }

    public function getItem(string $collection, int $id): array
    {
        $response = $this->get("items/$collection/$id")->json();

        if (isset($response['errors'])) {
            $this->handleError($response);
        }


        return $response['data'];
    }

    public function findAssets(string $id, array $query): array
    {
        $response = $this->get("assets/$id", $query)->json();

        if (isset($response['errors'])) {
            $this->handleError($response);
        }

        return $response['data'];
    }

    public function downloadAssets(
        string $id,
        array $query,
        FilesystemAdapter $disk,
        ?string $filePath = null,
    ): string {
        // This is required to download the file from Directus
        if (!Arr::has($query, 'download')) {
            $query['download'] = '1';
        }

        $response = $this->get("assets/$id", $query);

        if (!$response->successful()) {
            $this->handleError(
                $response->json()
            ); // Might cause an error since I don't know what the response looks like
        }


        $file = $this->get("assets/$id", $query)->body();

        if (!$filePath) {
            $filePath = $response->header('Content-Disposition');
            $filePath = 'assets/' . $filePath;
            $disk->put($filePath, $file);

            return $disk->path($filePath);
        }

        $disk->put($filePath, $file);

        return $disk->path($filePath);
    }

    /**
     * @param array{errors:array{array-key,array{message:string}}} $response
     * @throws Exception
     */
    private function handleError(array $response): void
    {
        /**
         * @var array{message:string} $firstError
         */
        $firstError = Arr::first($response['errors']);

        throw new Exception($firstError['message']);
    }

    /**
     * @param string $endpoint
     * @param array $query
     * @return Response
     * @throws ConnectionException
     */
    private function get(string $endpoint, array $query = []): Response
    {
        $url = $this->getBaseUri() . Str::trim($endpoint, '/');

        return Http::withToken(config('directus.bearer_token'))->get($url, $query);
    }

    private function getBaseUri(): string
    {
        return Str::trim(config('directus.base_uri'), '/') . '/';
    }
}
