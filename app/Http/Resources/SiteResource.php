<?php

namespace App\Http\Resources;

use App\Helpers\SiteHelper;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Site
 */
class SiteResource extends JsonResource
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
        /** @var Site $site */
        $site = $this->resource;

        $metadata = [
            'created_at' => $site->created_at,
            'updated_at' => $site->updated_at,
        ];

        $properties = [
            ...$site
                ->makeHidden([
                    'id',
                    'user_id',
                    $site->getCreatedAtColumn(), $site->getUpdatedAtColumn(), $site->getDeletedAtColumn(),
                    'longitude', 'latitude', 'altitude',
                ])
                ->toArray(),
            'pictures' => $site->getMedia('pictures')->sortBy('order')->pluck('original_url'),
        ];

        return [
            'type' => 'Feature',
            'id' => $site->id,
            'geometry' => SiteHelper::serializeGeometry3D($site),
            'properties' => $properties,
            'metadata' => $metadata,
        ];
    }
}
