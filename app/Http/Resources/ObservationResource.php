<?php

namespace App\Http\Resources;

use App\Helpers\ObservationHelper;
use App\Models\Observation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Observation
 */
class ObservationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function toArray(Request $request): array
    {
        /** @var Observation $observation */
        $observation = $this->resource;

        $metadata = [
            'created_at' => $observation->created_at,
            'updated_at' => $observation->updated_at,
        ];

        $properties = [
            ...$observation
                ->makeHidden([
                    'id',
                    'site_id', 'site',
                    $observation->getCreatedAtColumn(), $observation->getUpdatedAtColumn(), $observation->getDeletedAtColumn(),
                    'longitude', 'latitude', 'altitude',
                ])
                ->toArray(),
            'site' => new SiteResource($observation->site),
        ];

        return [
            'type' => 'Feature',
            'id' => $observation->id,
            'geometry' => ObservationHelper::serializeGeometry3D($observation),
            'properties' => $properties,
            'metadata' => $metadata,
        ];
    }
}
