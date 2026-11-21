<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\SiteHelper;
use App\Http\Controllers\Controller;
use App\Models\DayAggregate;
use App\Models\FiveMinutesAggregate;
use App\Models\Site;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

class SiteController extends Controller
{
    /**
     * Display a listing of the site.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['required', 'string', 'min:2'],
        ]);

        $sites = Site::whereLike('name', '%'.$validated['search'].'%', false)
            ->get()
            ->map(fn (Site $site) => (object) [
                'id' => $site->id,
                'name' => $site->name,
                'description' => null,
                'longitude' => $site->longitude,
                'latitude' => $site->latitude,
                'altitude' => $site->altitude,
            ]);

        return response()->json($sites);
    }

    /**
     * Display the specified site.
     */
    public function show(Site $site): JsonResponse
    {
        $latest = $site->fiveMinutesAggregate()
            ->latest('dateutc')
            ->first();

        $result = [
            'id' => $site->id,
            'siteName' => $site->name,
            'groupId' => null,
            'groupName' => null,
            'groupTypeName' => null,
            'groupImageUrl' => null,
            'belongsToBrand' => null,
            'hasGroup' => false,
            'isSchool' => false,
            'isOfficial' => $site->is_official,
            'metOfficeId' => null,
            'location' => [
                'geography' => [
                    'coordinateSystemId' => 4326,
                    'wellKnownText' => sprintf(
                        'POINT (%.6f %.6f %.6f)',
                        $site->longitude,
                        $site->latitude,
                        $site->altitude
                    ),
                ],
            ],
            'siteLogoImagePath' => null,
            'northSideImagePath' => null,
            'eastSideImagePath' => null,
            'southSideImagePath' => null,
            'westSideImagePath' => null,
            'siteRating' => null,
            'description' => null,
            'isActive' => null,
            'allowDownload' => false,
            'reason' => null,
            'timeZone' => $site->timezone,
            'locationExposureAttrib' => null,
            'temperatureAttrib' => null,
            'rainfallAttrib' => null,
            'windAttrib' => null,
            'urbanClimateZoneAttrib' => null,
            'reportingHoursAttrib' => null,
            'website' => null,
            'otherReason' => null,
            'equipmentType' => null,
            'defaultGraphFields' => null,
            'defaultTableFields' => null,
            'isDcnn' => null,
            'hasEditPermission' => false,
            'hasViewHolidaysPermission' => false,
            'hasManageUsersPermission' => false,
            'latestBadge' => [
                'id' => null,
                'year' => null,
                'siteId' => null,
                'type' => null,
                'operationalSince' => null,
            ],
            'isReported' => false,
            'ownerId' => $site->user->id,
            'lastObservationDate' => $latest?->dateutc->format(DATE_ATOM),
            'hasWebcams' => false,
            'hasMagnetometers' => false,
            'hasMagnetometerLicenceRequirement' => false,
            'timeZoneStandard' => config('app.timezone'),
        ];

        return response()->json($result);
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
            'geometry' => SiteHelper::serializeGeometry2D($latest->site),
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
        ];

        return response()->json($result);
    }

    /**
     * Get the graph data for a specific site.
     */
    public function graph(Request $request, Site $site): JsonResponse
    {
        $validated = $request->validate([
            'start' => ['date_format:Y-m-d\\TH:i:s.vp'],
            'end' => ['date_format:Y-m-d\\TH:i:s.vp'],
        ]);

        $start = isset($validated['start']) ? Date::parse($validated['start']) : now()->subHours(24);
        $end = isset($validated['end']) ? Date::parse($validated['end']) : now();

        $observations = $site->fiveMinutesAggregate()
            ->where('dateutc', '>=', $start->utc()->format('Y-m-d H:i:s'))
            ->where('dateutc', '<=', $end->utc()->format('Y-m-d H:i:s'))
            ->orderBy('dateutc');

        $result = $observations->get()->map(fn (FiveMinutesAggregate $observation) => [
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

    /**
     * Get the daily summaries for a specific site.
     */
    public function daily(Request $request, Site $site, bool $local = false): JsonResponse
    {
        $validated = $request->validate([
            'day1' => ['date_format:Y-m-d\\TH:i:s.vp'],
            'day2' => ['date_format:Y-m-d\\TH:i:s.vp'],
        ]);

        $dailySummaries = $local === true ? $site->dayAggregateLocal() : $site->dayAggregate();

        if (isset($validated['day1'], $validated['day2'])) {
            $dailySummaries
                ->where(function (Builder $query) use ($validated) {
                    $day1 = Date::parse($validated['day1']);
                    $day2 = Date::parse($validated['day2']);

                    $query
                        ->whereDate('date', '=', $day1->utc())
                        ->orWhereDate('date', '=', $day2->utc());
                });
        }

        $result = $dailySummaries
            ->orderBy('date')
            ->get()
            ->map(fn (DayAggregate $agg): array => [
                'timestamp' => $agg->date->format(DATE_ATOM),
                'data' => [
                    'temperature' => [
                        'min' => $agg->min_temperature,
                        'max' => $agg->max_temperature,
                        'mean' => $agg->avg_temperature,
                    ],
                    'dewpoint' => [
                        'mean' => $agg->avg_dewpoint,
                    ],
                    'humidity' => [
                        'mean' => $agg->avg_humidity,
                    ],
                    'rainfall' => [
                        'max_intensity' => $agg->max_rain,
                        'precipitation_quantity' => $agg->max_dailyrain,
                        'precipitation_duration' => $agg->sum_rainduration,
                    ],
                    'wind' => [
                        'max' => $agg->max_windspeed,
                        'gust' => $agg->max_windgustspeed,
                    ],
                    'pressure' => [
                        'mean' => $agg->avg_pressure,
                    ],
                    'solarradiation' => [
                        'max' => $agg->max_solarradiation,
                        'mean' => $agg->avg_solarradiation,
                    ],
                ],
            ]);

        return response()->json($result);
    }

    /**
     * Get the daily summaries in local time for a specific site.
     */
    public function dailyLocal(Request $request, Site $site): JsonResponse
    {
        $request->validate([
            'day1' => ['date_format:Y-m-d\\TH:i:s.vp'],
            'day2' => ['date_format:Y-m-d\\TH:i:s.vp'],
        ]);

        return $this->daily($request, $site, true);
    }
}
