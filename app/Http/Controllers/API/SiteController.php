<?php

namespace App\Http\Controllers\API;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\SiteResource;
use App\Models\FiveMinutesAggregate;
use App\Models\Site;
use Illuminate\Http\JsonResponse;

/**
 * @codeCoverageIgnore Not used in production, yet.
 */
class SiteController extends Controller
{
    /**
     * Get the details of a specific site.
     */
    public function show(Site $site): JsonResponse
    {
        return response()->json(new SiteResource($site));
    }

    /**
     * Get the latest observations for a specific site.
     */
    public function latest(Site $site): JsonResponse
    {
        $latest = $site->fiveMinutesAggregate()
            // ->whereDate('dateutc', '>', now()->utc()->subHours(24))
            ->latest('dateutc')
            ->first();

        $result = [
            'type' => 'Feature',
            'id' => $latest?->id,
            'geometry' => SiteHelper::serializeGeometry3D($latest->site),
            'properties' => [
                'site_id' => $site->id, // Required for MapLibre (only integer is allowed for feature.id)
                'timestamp' => $latest?->dateutc->format(DATE_ATOM),
                'primary' => [
                    'dt' => $latest?->temperature,
                    'dws' => $latest?->windspeed,
                    'dwd' => $latest?->winddir,
                    'drr' => $latest?->rain,
                    'dra' => $latest?->dailyrain,
                    'dm' => $latest?->pressure,
                    'dh' => $latest?->humidity,
                    'dsr' => $latest?->solarradiation,
                ],
            ],
        ];

        return response()->json($result);
    }

    /**
     * Get the graph data for a specific site.
     */
    public function graph(Site $site): JsonResponse
    {
        $observations = $site->fiveMinutesAggregate()
            ->whereDate('dateutc', '>', now()->utc()->subHours(24))
            ->orderBy('dateutc')
            ->get();

        $result = $observations->map(fn (FiveMinutesAggregate $observation) => [
            'timestamp' => $observation->dateutc->format(DATE_ATOM),
            'primary' => [
                'dt' => $observation->temperature,
                'dws' => $observation->windspeed,
                'dwd' => $observation->winddir,
                'drr' => $observation->rain,
                'dra' => $observation->dailyrain,
                'dm' => $observation->pressure,
                'dh' => $observation->humidity,
                'dsr' => $observation->solarradiation,
            ],
        ]);

        return response()->json($result);
    }
}
