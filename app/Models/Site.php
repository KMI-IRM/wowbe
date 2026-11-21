<?php

namespace App\Models;

use App\Helpers\SiteHelper;
use App\Observers\SiteObserver;
use Database\Factories\SiteFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ObservedBy([SiteObserver::class])]
class Site extends Model implements HasMedia
{
    /** @use HasFactory<SiteFactory> */
    use HasFactory, HasUuids, InteractsWithMedia, Notifiable, SoftDeletes;

    /**
     * The model's default values for attributes.
     */
    protected $attributes = [
        'is_official' => false,
        'short_id' => '',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'timezone',
        'longitude',
        'latitude',
        'altitude',
        'website',
        'brand',
        'software',
        'auth_key',
        'mac_address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'auth_key',
        'brand',
        'has_pin_code',
        'mac_address',
        'software',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        'has_pin_code',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'longitude' => 'float',
        'latitude' => 'float',
        'altitude' => 'float',
    ];

    /**
     * Perform any actions required after the model boots.
     */
    protected static function booted(): void
    {
        parent::booted();

        static::created(function (Site $site) {
            $site->short_id = SiteHelper::getShortId($site);
            $site->saveQuietly(); // Save without firing events
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
     * Get/Set the auth key attribute.
     *
     * @return Attribute<string,string>
     */
    public function authKey(): Attribute
    {
        return new Attribute(
            get: fn ($value) => $value,
            set: fn (array|string $value) => is_array($value) ? implode('', $value) : $value,
        );
    }

    /**
     * Determine if the site has a PIN code set.
     */
    public function getHasPINCodeAttribute(): bool
    {
        return preg_match('/^\d{6}$/', $this->auth_key) === 1;
    }

    /**
     * Get the user that owns the site.
     *
     * @return BelongsTo<User,self>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id'); // @phpstan-ignore return.type
    }

    /**
     * Get the observations for the site.
     *
     * @return HasMany<Observation,self>
     */
    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class); // @phpstan-ignore return.type
    }

    /**
     * Get the observations aggregate per 5 minutes for the site.
     *
     * @return HasMany<FiveMinutesAggregate,self>
     */
    public function fiveMinutesAggregate(): HasMany
    {
        return $this->hasMany(FiveMinutesAggregate::class, 'site_id', 'id'); // @phpstan-ignore return.type
    }

    /**
     * Get the observations aggregate per day for the site.
     *
     * @return HasMany<DayAggregate,self>
     */
    public function dayAggregate(): HasMany
    {
        return $this->hasMany(DayAggregate::class, 'site_id', 'id'); // @phpstan-ignore return.type
    }

    /**
     * Get the observations aggregate per day for the site in local time.
     *
     * @return HasMany<DayAggregateLocal,self>
     */
    public function dayAggregateLocal(): HasMany
    {
        return $this->hasMany(DayAggregateLocal::class, 'site_id', 'id'); // @phpstan-ignore return.type
    }
}
