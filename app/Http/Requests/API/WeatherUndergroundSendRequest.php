<?php

namespace App\Http\Requests\API;

use App\Rules\SiteID;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class WeatherUndergroundSendRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @see https://support.weather.com/s/article/PWS-Upload-Protocol
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Site ID
            'ID' => ['required', 'string', new SiteID],
            // Authentication Key (PIN code or Password)
            'PASSWORD' => ['required', 'string'],
            // Date & Time in UTC
            'dateutc' => ['required', 'date_format:Y-m-d H:i:s,Y-m-d\TH:i:s\Z'],

            // Action (supposed to be `action=updateraw`)
            'action' => ['string'],
            // Relative Pressure i.e. pressure adjusted to sea level (Inch of Mercury) (float)
            'baromin' => ['numeric'],
            // Daily Accumulated rainfall so far today (Inches) (float)
            'dailyrainin' => ['numeric'],
            // Outdoor Dewpoint (Fahrenheit) (float)
            'dewptf' => ['numeric'],
            // Outdoor Humidity (0-100 %) (float)
            'humidity' => ['numeric'],
            // Instantaneous Rain Rate (Inches/h) (float)
            'rainin' => ['numeric'],
            // % Moisture (0-100 %) (float)
            'soilmoisture' => ['numeric'],
            // Soil Temperature (10cm) (Fahrenheit) (float)
            'soiltempf' => ['numeric'],
            // Solar Radiation (Watt per Square Metre) (float)
            'solarradiation' => ['numeric'],
            // Software Type
            'softwaretype' => ['string'],
            // Outdoor Temperature (Fahrenheit) (float)
            'tempf' => ['numeric'],
            // UV Index (float)
            'UV' => ['numeric'],
            // Visibility (Kilometres) (float)
            'visibility' => ['numeric'],
            // Instantaneous Wind Direction (Degrees (0-360)) (float)
            'winddir' => ['numeric'],
            // Current Wind Gust Direction (using software specific time period) (Degrees (0-360)) (float)
            'windgustdir' => ['numeric'],
            // Current Wind Gust Speed (using software specific time period) (Miles per Hour) (float)
            'windgustmph' => ['numeric'],
            // Instantaneous Wind Speed (Miles per Hour) (float)
            'windspeedmph' => ['numeric'],

            /**
             * Absolute Pressure i.e. raw pressure measurement at the station's location (Inch of Mercury) (float)
             * *Not part of WeatherUnderground protocol but supported by WOW-BE backend.*
             */
            'absbaromin' => ['numeric'],
            /**
             * Site Model
             * *Not part of WeatherUnderground protocol but supported by WOW-BE backend.*
             */
            'model' => ['string'],

            // SKC, FEW, SCT, BKN, OVC
            // 'clouds' => ['string'],
            // Indoor Humidity (0-100 %)
            // 'indoorhumidity' => ['numeric'],
            // Indoor Temperature (Fahrenheit)
            // 'indoortempf' => ['numeric'],
            // Leaf Wetness (0-100 %)
            // 'leafwetness' => ['numeric'],
            // metar style (+RA)
            // 'weather' => ['string'],
            // 0-360 2 minute average wind direction
            // 'winddir_avg2m' => ['numeric'],
            // 0-360 past 10 minutes wind gust direction
            // 'windgustdir_10m' => ['numeric'],
            // mph past 10 minutes wind gust mph
            // 'windgustmph_10m' => ['numeric'],
            // mph 2 minute average wind speed mph
            // 'windspdmph_avg2m' => ['numeric'],
        ];
    }
}
