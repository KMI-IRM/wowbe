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

class AggregateObservationsDay implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds after which the job's unique lock will be released.
     */
    public int $uniqueFor = 86400;

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

        $this->fromUTC = $datetime->copy()->startOfDay();
        $this->toUTC = $datetime->copy()->endOfDay();

        $datetimeTz = $datetime->copy()->setTimezone(new DateTimeZone($timezone));

        $this->fromLocal = $datetimeTz->copy()->startOfDay();
        $this->toLocal = $datetimeTz->copy()->endOfDay();
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return "obs_agg:day:{$this->siteId}:{$this->fromUTC->toDateString()}";
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting day aggregation for site \"{$this->siteId}\" from {$this->fromUTC} to {$this->toUTC}.", [
            'jobId' => $this->uniqueId(),
            'siteId' => $this->siteId,
            'from' => $this->fromUTC,
            'to' => $this->toUTC,
        ]);

        DB::transaction(function () {
            $this->update(); // UTC
            $this->update(true); // Local

            Log::info("Completed day aggregation for site \"{$this->siteId}\" from {$this->fromUTC} to {$this->toUTC}.", [
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
        DB::table($local ? 'observations_agg_day_local' : 'observations_agg_day')
            ->where('site_id', $this->siteId)
            ->where('date', '=', $local ? $this->fromLocal->toDateString() : $this->fromUTC->toDateString())
            ->delete();

        // Perform the aggregation logic here
        DB::statement($local ? self::sqlLocal() : self::sqlUTC(), [
            'site_id' => $this->siteId,
            'from' => $local ? $this->fromLocal->copy()->setTimezone(new DateTimeZone('UTC')) : $this->fromUTC,
            'to' => $local ? $this->toLocal->copy()->setTimezone(new DateTimeZone('UTC')) : $this->toUTC,
        ]);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
                    -- Rain duration in seconds
                    CASE
                        WHEN dailyrainin > (LAG(dailyrainin) OVER (PARTITION BY site_id ORDER BY dateutc))
                        THEN EXTRACT(EPOCH FROM (dateutc - (LAG(dateutc) OVER (PARTITION BY site_id ORDER BY dateutc))))
                        ELSE 0
                    END AS rainduration,
                    -- Solar radiation if in range, else NULL
                    CASE
                        WHEN solarradiation BETWEEN 0 AND 1100
                        THEN solarradiation::numeric
                    END AS solarradiation
                FROM observations
                WHERE
                    site_id = :site_id
                    AND dateutc >= :from
                    AND dateutc <= :to
                    AND deleted_at IS NULL
            )
            INSERT INTO observations_agg_day (
                site_id,
                date,
                min_temperature,
                max_temperature,
                avg_temperature,
                avg_dewpoint,
                avg_humidity,
                avg_pressure,
                avg_abspressure,
                max_windspeed,
                max_windgustspeed,
                max_dailyrain,
                max_rain,
                sum_rainduration,
                max_solarradiation,
                avg_solarradiation,
                count
            )
            SELECT
                site_id,
                DATE(dateutc) AS date,
                ROUND(MIN(temperature), 2) AS min_temperature,
                ROUND(MAX(temperature), 2) AS max_temperature,
                ROUND(AVG(temperature), 2) AS avg_temperature,
                ROUND(AVG(dewpoint), 2) AS avg_dewpoint,
                ROUND(AVG(humidity), 2) AS avg_humidity,
                ROUND(AVG(pressure), 2) AS avg_pressure,
                ROUND(AVG(abspressure), 2) AS avg_abspressure,
                ROUND(MAX(windspeed), 2) AS max_windspeed,
                ROUND(MAX(windgustspeed), 2) AS max_windgustspeed,
                ROUND(MAX(dailyrain), 2) AS max_dailyrain,
                ROUND(MAX(rain), 2) AS max_rain,
                ROUND(SUM(rainduration)) AS sum_rainduration,
                ROUND(MAX(solarradiation), 2) AS max_solarradiation,
                ROUND(AVG(solarradiation), 2) AS avg_solarradiation,
                COUNT(*) AS count
            FROM cleaned
            GROUP BY 1, 2;
        SQL;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private static function sqlLocal(): string
    {
        return <<<'SQL'
            WITH cleaned AS (
                SELECT
                    o.id,
                    o.site_id,
                    (o.dateutc AT TIME ZONE 'UTC' AT TIME ZONE s.timezone) AS datelocal,
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
                    -- Rain duration in seconds
                    CASE
                        WHEN dailyrainin > (LAG(dailyrainin) OVER (PARTITION BY site_id ORDER BY (o.dateutc AT TIME ZONE 'UTC' AT TIME ZONE s.timezone)))
                        THEN EXTRACT(EPOCH FROM ((o.dateutc AT TIME ZONE 'UTC' AT TIME ZONE s.timezone) - (LAG((o.dateutc AT TIME ZONE 'UTC' AT TIME ZONE s.timezone)) OVER (PARTITION BY site_id ORDER BY (o.dateutc AT TIME ZONE 'UTC' AT TIME ZONE s.timezone)))))
                        ELSE 0
                    END AS rainduration,
                    -- Solar radiation if in range, else NULL
                    CASE
                        WHEN o.solarradiation BETWEEN 0 AND 1100
                        THEN o.solarradiation::numeric
                    END AS solarradiation
                FROM observations o
                JOIN sites s ON s.id = o.site_id
                WHERE
                    o.site_id = :site_id
                    AND o.dateutc >= :from
                    AND o.dateutc <= :to
                    AND o.deleted_at IS NULL
                    AND s.deleted_at IS NULL
            )
            INSERT INTO observations_agg_day_local (
                site_id,
                date,
                min_temperature,
                max_temperature,
                avg_temperature,
                avg_dewpoint,
                avg_humidity,
                avg_pressure,
                avg_abspressure,
                max_windspeed,
                max_windgustspeed,
                max_dailyrain,
                max_rain,
                sum_rainduration,
                max_solarradiation,
                avg_solarradiation,
                count
            )
            SELECT
                site_id,
                DATE(datelocal) AS date,
                ROUND(MIN(temperature), 2) AS min_temperature,
                ROUND(MAX(temperature), 2) AS max_temperature,
                ROUND(AVG(temperature), 2) AS avg_temperature,
                ROUND(AVG(dewpoint), 2) AS avg_dewpoint,
                ROUND(AVG(humidity), 2) AS avg_humidity,
                ROUND(AVG(pressure), 2) AS avg_pressure,
                ROUND(AVG(abspressure), 2) AS avg_abspressure,
                ROUND(MAX(windspeed), 2) AS max_windspeed,
                ROUND(MAX(windgustspeed), 2) AS max_windgustspeed,
                ROUND(MAX(dailyrain), 2) AS max_dailyrain,
                ROUND(MAX(rain), 2) AS max_rain,
                ROUND(SUM(rainduration)) AS sum_rainduration,
                ROUND(MAX(solarradiation), 2) AS max_solarradiation,
                ROUND(AVG(solarradiation), 2) AS avg_solarradiation,
                COUNT(*) AS count
            FROM cleaned
            GROUP BY 1, 2;
        SQL;
    }
}
