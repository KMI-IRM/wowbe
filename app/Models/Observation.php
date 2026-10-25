<?php

namespace App\Models;

use App\Observers\ObservationObserver;
use Database\Factories\ObservationFactory;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([ObservationObserver::class])]
class Observation extends Model
{
    /** @use HasFactory<ObservationFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'dateutc',
        'softwaretype',
        'baromin',
        'absbaromin',
        'dailyrainin',
        'dewptf',
        'humidity',
        'rainin',
        'soilmoisture',
        'soiltempf',
        'tempf',
        'visibility',
        'winddir',
        'windspeedmph',
        'windgustdir',
        'windgustmph',
        'solarradiation',
        'model',
    ];

    protected $casts = [
        'dateutc' => 'datetime',
        'baromin' => 'float',
        'absbaromin' => 'float',
        'dailyrainin' => 'float',
        'dewptf' => 'float',
        'humidity' => 'float',
        'rainin' => 'float',
        'soilmoisture' => 'float',
        'soiltempf' => 'float',
        'tempf' => 'float',
        'visibility' => 'float',
        'winddir' => 'float',
        'windspeedmph' => 'float',
        'windgustdir' => 'float',
        'windgustmph' => 'float',
        'solarradiation' => 'float',
        'longitude' => 'float',
        'latitude' => 'float',
        'altitude' => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $observation) {
            if (isset($observation->site)) {
                $observation->longitude = $observation->site->longitude;
                $observation->latitude = $observation->site->latitude;
                $observation->altitude = $observation->site->altitude;
            }
        });
    }

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format(DATE_ATOM);
    }

    /**
     * @return Attribute<DateTime,DateTime>
     */
    public function dateutc(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => new DateTime($value, new DateTimeZone('UTC')),
        );
    }

    /**
     * Get the site producing the observation.
     *
     * @return BelongsTo<Site,self>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id'); // @phpstan-ignore return.type
    }
}
