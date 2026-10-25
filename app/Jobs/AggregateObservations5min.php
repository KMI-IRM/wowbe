<?php

namespace App\Jobs;

use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AggregateObservations5min implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds after which the job's unique lock will be released.
     */
    public int $uniqueFor = 900;

    public Carbon $fromUTC;

    public Carbon $fromLocal;

    public Carbon $toUTC;

    public Carbon $toLocal;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $siteId,
        DateTime $dateutc,
        string $timezone
    ) {
        $datetime = Carbon::parse($dateutc, DateTimeZone::UTC);

        $this->fromUTC = $datetime->copy()->startOfMinute()->subMinutes($datetime->minute % 5);
        $this->toUTC = $this->fromUTC->copy()->addMinutes(5);

        $datetimeTz = $datetime->copy()->setTimezone(new DateTimeZone($timezone));

        $this->fromLocal = $datetimeTz->copy()->startOfMinute()->subMinutes($datetimeTz->minute % 5);
        $this->toLocal = $this->fromLocal->copy()->addMinutes(5);
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return "obs_agg:5min:{$this->siteId}:{$this->fromUTC->timestamp}:{$this->toUTC->timestamp}";
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting 5-minutes aggregation for site \"{$this->siteId}\" from {$this->fromUTC} to {$this->toUTC}.", [
            'jobId' => $this->uniqueId(),
            'siteId' => $this->siteId,
            'from' => $this->fromUTC,
            'to' => $this->toUTC,
        ]);

        DB::transaction(function () {
            $this->update(); // UTC
            $this->update(true); // Local

            Log::info("Completed 5-minutes aggregation for site \"{$this->siteId}\" from {$this->fromUTC} to {$this->toUTC}.", [
                'jobId' => $this->uniqueId(),
                'siteId' => $this->siteId,
                'from' => $this->fromUTC,
                'to' => $this->toUTC,
            ]);
        });
    }

    private function update(bool $local = false): void
    {
        // Clean up previously aggregated observations for this site and period
        if ($local === true) {
            DB::table('observations_agg_5min_local')
                ->where('site_id', $this->siteId)
                ->where('datelocal', '>=', $this->fromLocal)
                ->where('datelocal', '<', $this->toLocal)
                ->delete();
        } else {
            DB::table('observations_agg_5min')
                ->where('site_id', $this->siteId)
                ->where('dateutc', '>=', $this->fromUTC)
                ->where('dateutc', '<', $this->toUTC)
                ->delete();
        }

        // Perform the aggregation logic here
        DB::statement($local ? self::sqlLocal() : self::sqlUTC(), [
            'site_id' => $this->siteId,
            'from' => $local ? $this->fromLocal->copy()->setTimezone(new DateTimeZone('UTC')) : $this->fromUTC,
            'to' => $local ? $this->toLocal->copy()->setTimezone(new DateTimeZone('UTC')) : $this->toUTC,
        ]);
    }

    /**
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     */
    private static function sqlUTC(): string
    {
        return <<<'SQL'
            WITH cleaned AS (
                SELECT
                    id,
                    site_id,
                    dateutc,
                    -- Temperature in Celsius if in range, else NULL
                    CASE
                        WHEN ((tempf - 32) / 1.8) BETWEEN -90 AND 60
                        THEN ((tempf - 32) / 1.8)::numeric
                    END AS temperature,
                    -- Dewpoint in Celsius if in range, else NULL
                    CASE
                        WHEN ((dewptf - 32) / 1.8) BETWEEN -100 AND 35
                        THEN ((dewptf - 32) / 1.8)::numeric
                    END AS dewpoint,
                    -- Humidity if in range, else NULL
                    CASE
                        WHEN humidity BETWEEN 0 AND 100
                        THEN humidity::numeric
                    END AS humidity,
                    -- Pressure in hPa if in range, else NULL
                    COALESCE(
                        CASE
                            WHEN baromin IS NOT NULL AND (1013.25 * (baromin / 29.92)) BETWEEN 870 AND 1100
                            THEN (1013.25 * (baromin / 29.92))::numeric
                        END,
                        CASE
                            WHEN absbaromin IS NOT NULL AND (1013.25 * (absbaromin / 29.92)) BETWEEN 870 AND 1100
                                AND tempf IS NOT NULL AND altitude IS NOT NULL
                                AND (1013.25 * (absbaromin2baromin(absbaromin, tempf, altitude) / 29.92)) BETWEEN 870 AND 1100
                            THEN (1013.25 * (absbaromin2baromin(absbaromin, tempf, altitude) / 29.92))::numeric
                        END
                    ) AS pressure,
                    -- Absolute Pressure in hPa if in range, else NULL
                    CASE
                        WHEN absbaromin IS NOT NULL AND (1013.25 * (absbaromin / 29.92)) BETWEEN 870 AND 1100
                        THEN (1013.25 * (absbaromin / 29.92))::numeric
                    END AS abspressure,
                    -- Wind speed in km/h if in range, else NULL
                    CASE
                        WHEN (windspeedmph * 1.60934) BETWEEN 0 AND 200
                        THEN (windspeedmph * 1.60934)::numeric
                    END AS windspeed,
                    -- Wind gust speed in km/h if in range, else NULL
                    CASE
                        WHEN (windgustmph * 1.60934) BETWEEN 0 AND 400
                        THEN (windgustmph * 1.60934)::numeric
                    END AS windgustspeed,
                    -- Wind direction if in range, else NULL
                    CASE
                        WHEN winddir BETWEEN 0 AND 360
                        THEN winddir::numeric
                    END AS winddir,
                    -- Wind gust direction if in range, else NULL
                    CASE
                        WHEN windgustdir BETWEEN 0 AND 360
                        THEN windgustdir::numeric
                    END AS windgustdir,
                    -- Visibility if in range, else NULL
                    CASE
                        WHEN visibility >= 0
                        THEN visibility::numeric
                    END AS visibility,
                    -- Soil moisture if in range, else NULL
                    CASE
                        WHEN soilmoisture BETWEEN 0 AND 100
                        THEN soilmoisture::numeric
                    END AS soilmoisture,
                    -- Soil temperature in Celsius if in range, else NULL
                    CASE
                        WHEN ((soiltempf - 32) / 1.8) BETWEEN -90 AND 60
                        THEN ((soiltempf - 32) / 1.8)::numeric
                    END AS soiltemperature,
                    -- Daily Rainin in mm if in range, else NULL
                    CASE
                        WHEN (dailyrainin * 25.4) BETWEEN 0 AND 300
                        THEN (dailyrainin * 25.4)::numeric
                    END AS dailyrain,
                    -- Rainin in mm/h
                    (rainin * 25.4)::numeric AS rain,
                    -- Solar radiation if in range, else NULL
                    CASE
                        WHEN solarradiation BETWEEN 0 AND 1100
                        THEN solarradiation::numeric
                    END AS solarradiation
                FROM observations
                WHERE
                    site_id = :site_id
                    AND dateutc >= :from
                    AND dateutc < :to
                    AND deleted_at IS NULL
            )
            INSERT INTO observations_agg_5min (
                site_id,
                dateutc,
                temperature,
                dewpoint,
                humidity,
                pressure,
                abspressure,
                windspeed,
                windgustspeed,
                winddir,
                windgustdir,
                visibility,
                soilmoisture,
                soiltemperature,
                dailyrain,
                rain,
                solarradiation,
                count
            )
            SELECT
                site_id,
                date_trunc('minute', dateutc) - INTERVAL '1 minute' * (extract(minute from dateutc)::int % 5) AS dateutc,
                ROUND(AVG(temperature), 2) AS temperature,
                ROUND(AVG(dewpoint), 2) AS dewpoint,
                ROUND(AVG(humidity), 2) AS humidity,
                ROUND(AVG(pressure), 2) AS pressure,
                ROUND(AVG(abspressure), 2) AS abspressure,
                ROUND(AVG(windspeed), 2) AS windspeed,
                ROUND(AVG(windgustspeed), 2) AS windgustspeed,
                ROUND(AVG(winddir), 2) AS winddir,
                ROUND(AVG(windgustdir), 2) AS windgustdir,
                ROUND(AVG(visibility), 2) AS visibility,
                ROUND(AVG(soilmoisture), 2) AS soilmoisture,
                ROUND(AVG(soiltemperature), 2) AS soiltemperature,
                ROUND(MAX(dailyrain), 2) AS dailyrain,
                ROUND(AVG(rain), 2) AS rain,
                ROUND(AVG(solarradiation), 2) AS solarradiation,
                COUNT(*) AS count
            FROM cleaned
            GROUP BY 1, 2;
        SQL;
    }

    /**
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     */
    private static function sqlLocal(): string
    {
        return <<<'SQL'
            WITH cleaned AS (
                SELECT
                    o.id,
                    o.site_id,
                    o.dateutc AT TIME ZONE 'UTC' AT TIME ZONE s.timezone AS datelocal,
                    -- Temperature in Celsius if in range, else NULL
                    CASE
                        WHEN ((tempf - 32) / 1.8) BETWEEN -90 AND 60
                        THEN ((tempf - 32) / 1.8)::numeric
                    END AS temperature,
                    -- Dewpoint in Celsius if in range, else NULL
                    CASE
                        WHEN ((dewptf - 32) / 1.8) BETWEEN -100 AND 35
                        THEN ((dewptf - 32) / 1.8)::numeric
                    END AS dewpoint,
                    -- Humidity if in range, else NULL
                    CASE
                        WHEN humidity BETWEEN 0 AND 100
                        THEN humidity::numeric
                    END AS humidity,
                    -- Pressure in hPa if in range, else NULL
                    COALESCE(
                        CASE
                            WHEN baromin IS NOT NULL AND (1013.25 * (baromin / 29.92)) BETWEEN 870 AND 1100
                            THEN (1013.25 * (baromin / 29.92))::numeric
                        END,
                        CASE
                            WHEN absbaromin IS NOT NULL AND (1013.25 * (absbaromin / 29.92)) BETWEEN 870 AND 1100
                                AND tempf IS NOT NULL AND o.altitude IS NOT NULL
                                AND (1013.25 * (absbaromin2baromin(absbaromin, tempf, o.altitude) / 29.92)) BETWEEN 870 AND 1100
                            THEN (1013.25 * (absbaromin2baromin(absbaromin, tempf, o.altitude) / 29.92))::numeric
                        END
                    ) AS pressure,
                    -- Absolute Pressure in hPa if in range, else NULL
                    CASE
                        WHEN absbaromin IS NOT NULL AND (1013.25 * (absbaromin / 29.92)) BETWEEN 870 AND 1100
                        THEN (1013.25 * (absbaromin / 29.92))::numeric
                    END AS abspressure,
                    -- Wind speed in km/h if in range, else NULL
                    CASE
                        WHEN (windspeedmph * 1.60934) BETWEEN 0 AND 200
                        THEN (windspeedmph * 1.60934)::numeric
                    END AS windspeed,
                    -- Wind gust speed in km/h if in range, else NULL
                    CASE
                        WHEN (windgustmph * 1.60934) BETWEEN 0 AND 400
                        THEN (windgustmph * 1.60934)::numeric
                    END AS windgustspeed,
                    -- Wind direction if in range, else NULL
                    CASE
                        WHEN winddir BETWEEN 0 AND 360
                        THEN winddir::numeric
                    END AS winddir,
                    -- Wind gust direction if in range, else NULL
                    CASE
                        WHEN windgustdir BETWEEN 0 AND 360
                        THEN windgustdir::numeric
                    END AS windgustdir,
                    -- Visibility if in range, else NULL
                    CASE
                        WHEN visibility >= 0
                        THEN visibility::numeric
                    END AS visibility,
                    -- Soil moisture if in range, else NULL
                    CASE
                        WHEN soilmoisture BETWEEN 0 AND 100
                        THEN soilmoisture::numeric
                    END AS soilmoisture,
                    -- Soil temperature in Celsius if in range, else NULL
                    CASE
                        WHEN ((soiltempf - 32) / 1.8) BETWEEN -90 AND 60
                        THEN ((soiltempf - 32) / 1.8)::numeric
                    END AS soiltemperature,
                    -- Daily Rainin in mm if in range, else NULL
                    CASE
                        WHEN (dailyrainin * 25.4) BETWEEN 0 AND 300
                        THEN (dailyrainin * 25.4)::numeric
                    END AS dailyrain,
                    -- Rainin in mm/h
                    (rainin * 25.4)::numeric AS rain,
                    -- Solar radiation if in range, else NULL
                    CASE
                        WHEN solarradiation BETWEEN 0 AND 1100
                        THEN solarradiation::numeric
                    END AS solarradiation
                FROM observations o
                JOIN sites s ON s.id = o.site_id
                WHERE
                    o.site_id = :site_id
                    AND o.dateutc >= :from
                    AND o.dateutc < :to
                    AND o.deleted_at IS NULL
                    AND s.deleted_at IS NULL
            )
            INSERT INTO observations_agg_5min_local (
                site_id,
                datelocal,
                temperature,
                dewpoint,
                humidity,
                pressure,
                abspressure,
                windspeed,
                windgustspeed,
                winddir,
                windgustdir,
                visibility,
                soilmoisture,
                soiltemperature,
                dailyrain,
                rain,
                solarradiation,
                count
            )
            SELECT
                site_id,
                date_trunc('minute', datelocal) - INTERVAL '1 minute' * (extract(minute from datelocal)::int % 5) AS datelocal,
                ROUND(AVG(temperature), 2) AS temperature,
                ROUND(AVG(dewpoint), 2) AS dewpoint,
                ROUND(AVG(humidity), 2) AS humidity,
                ROUND(AVG(pressure), 2) AS pressure,
                ROUND(AVG(abspressure), 2) AS abspressure,
                ROUND(AVG(windspeed), 2) AS windspeed,
                ROUND(AVG(windgustspeed), 2) AS windgustspeed,
                ROUND(AVG(winddir), 2) AS winddir,
                ROUND(AVG(windgustdir), 2) AS windgustdir,
                ROUND(AVG(visibility), 2) AS visibility,
                ROUND(AVG(soilmoisture), 2) AS soilmoisture,
                ROUND(AVG(soiltemperature), 2) AS soiltemperature,
                ROUND(MAX(dailyrain), 2) AS dailyrain,
                ROUND(AVG(rain), 2) AS rain,
                ROUND(AVG(solarradiation), 2) AS solarradiation,
                COUNT(*) AS count
            FROM cleaned
            GROUP BY 1, 2;
        SQL;
    }
}
