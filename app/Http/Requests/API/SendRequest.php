<?php

namespace App\Http\Requests\API;

use App\Rules\SiteID;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Date & Time in UTC
            'dateutc' => ['required', 'date_format:Y-m-d H:i:s,Y-m-d\TH:i:s\Z'],
            // Authentication Key (PIN code or Password)
            'siteAuthenticationKey' => ['required', 'string'],
            // Site ID
            'siteid' => ['required', 'string', new SiteID],

            // Software Type
            'softwaretype' => ['string'],
            // Absolute Pressure i.e. raw pressure measurement at the station's location (Inch of Mercury) (float)
            'absbaromin' => ['numeric'],
            // Relative Pressure i.e. pressure adjusted to sea level (Inch of Mercury) (float)
            'baromin' => ['numeric'],
            // Daily Accumulated rainfall so far today (Inches) (float)
            'dailyrainin' => ['numeric'],
            // Outdoor Dewpoint (Fahrenheit) (float)
            'dewptf' => ['numeric'],
            // Outdoor Humidity (0-100 %) (float)
            'humidity' => ['numeric'],
            // Site Model
            'model' => ['string'],
            // Instantaneous Rain Rate (Inches/h) (float)
            'rainin' => ['numeric'],
            // % Moisture (0-100 %) (float)
            'soilmoisture' => ['numeric'],
            // Soil Temperature (10cm) (Fahrenheit) (float)
            'soiltempf' => ['numeric'],
            // Solar Radiation (Watt per Square Metre) (float)
            'solarradiation' => ['numeric'],
            // Outdoor Temperature (Fahrenheit) (float)
            'tempf' => ['numeric'],
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
        ];
    }
}
