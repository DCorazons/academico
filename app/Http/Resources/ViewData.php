<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Turns API resources into plain arrays for the Blade views. The views then get the
 * same data from Eloquent models and from query builder rows, nested resources
 * included, and cannot trigger lazy loading.
 */
final class ViewData
{
    /** @return array<string, mixed> */
    public static function one(JsonResource $resource): array
    {
        return json_decode(json_encode($resource->resolve(request()), JSON_THROW_ON_ERROR), true);
    }

    /**
     * @param  class-string<JsonResource>  $resourceClass
     * @return array<int, array<string, mixed>>
     */
    public static function many(string $resourceClass, iterable $items): array
    {
        return json_decode(json_encode($resourceClass::collection($items)->resolve(request()), JSON_THROW_ON_ERROR), true);
    }
}
