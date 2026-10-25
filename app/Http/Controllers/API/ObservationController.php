<?php

namespace App\Http\Controllers\API;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

/**
 * @codeCoverageIgnore Not used in production, yet.
 */
class ObservationController extends Controller
{
    /**
     * Get the latest observations.
     *
     * This endpoint retrieves the latest observations for all sites
     * within a 10-minutes window before the specified date.
     *
     * If no date is provided, it defaults to the **current** time and
     * retrieves observations from the last 30 minutes.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['date'],
        ]);

        $sites = Site::query()
            ->with([
                'fiveMinutesAggregate' => function ($query) use ($validated) {
                    if (isset($validated['date']) === true) {
                        $datetime = Date::parse($validated['date']);

                        $query
                            ->where('dateutc', '<=', $datetime->utc())
                            ->where('dateutc', '>=', $datetime->clone()->subMinutes(10)->utc())
                            ->latest('dateutc');
                    } else {
                        $datetime = now();

                        $query
                            ->where('dateutc', '<=', $datetime->utc())
                            ->where('dateutc', '>=', $datetime->clone()->subMinutes(30)->utc())
                            ->latest('dateutc');
                    }
                },
            ])
            ->get()
            ->filter(fn ($site) => $site->fiveMinutesAggregate->isNotEmpty())
            ->values();

        $result = [
            'type' => 'FeatureCollection',
            'features' => $sites->map(function (Site $site) {
                $latest = $site->fiveMinutesAggregate[0];

                return [
                    'type' => 'Feature',
                    'id' => $site->id,
                    'geometry' => SiteHelper::serializeGeometry($site),
                    'properties' => [
                        'site_id' => $site->id, // Required for MapLibre (only integer is allowed for feature.id)
                        'name' => $site->name,
                        'isOfficial' => $site->is_official,
                        'timestamp' => $latest?->dateutc->format(DATE_ATOM),
                        'primary' => [
                            'dt' => $latest?->temperature,
                            'dpt' => $latest?->dewpoint,
                            'dws' => $latest?->windspeed,
                            'dwd' => $latest?->winddir,
                            'dwg' => $latest?->windgustspeed,
                            'dwgd' => $latest?->windgustdir,
                            'drr' => $latest?->rain,
                            'dra' => $latest?->dailyrain,
                            'dm' => $latest?->pressure,
                            'dh' => $latest?->humidity,
                            'dsr' => $latest?->solarradiation,
                        ],
                    ],
                ];
            }),
        ];

        return response()->json($result);
    }
}
