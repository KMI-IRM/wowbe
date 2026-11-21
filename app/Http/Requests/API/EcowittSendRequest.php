<?php

namespace App\Http\Requests\API;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EcowittSendRequest extends FormRequest
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
            // PassKey (MD5 hash of site MAC-address)
            'PASSKEY' => ['required', 'string'],

            // Station Type
            'stationtype' => ['string'],
            // Absolute Pressure i.e. raw pressure measurement at the station's location (Inch of Mercury) (float)
            'baromabsin' => ['numeric'],
            // Relative Pressure i.e. pressure adjusted to sea level (Inch of Mercury) (float)
            'baromrelin' => ['numeric'],
            // Daily Accumulated rainfall so far today (Inches) (float)
            'dailyrainin' => ['numeric'],
            // Daily Accumulated rainfall (piezo) so far today (Inches)
            'drain_piezo' => ['numeric'],
            // Outdoor Humidity (0-100 %) (float)
            'humidity' => ['numeric'],
            // Site Model
            'model' => ['string'],
            // Instantaneous Rain Rate (Inches/h) (float)
            'rainratein' => ['numeric'],
            // Solar Radiation (Watt per Square Metre) (float)
            'solarradiation' => ['numeric'],
            // Outdoor Temperature (Fahrenheit) (float)
            'tempf' => ['numeric'],
            // Instantaneous Wind Direction (Degrees (0-360)) (float)
            'winddir' => ['numeric'],
            // Current Wind Gust Speed (using software specific time period) (Miles per Hour) (float)
            'windgustmph' => ['numeric'],
            // Instantaneous Wind Speed (Miles per Hour) (float)
            'windspeedmph' => ['numeric'],

            // 'absbaromin' => ['numeric'], // Overwritten by `baromabsin`
            // 'baromin' => ['numeric'], // Overwritten by `baromrelin`
            // 'rainin' => ['numeric'], // Overwritten by `rainratein`
            // 'softwaretype' => ['string'], // Overwritten by `stationtype`

            /**
             * Outdoor Dewpoint (Fahrenheit) (float)
             * *Not part of Ecowitt protocol but supported by WOW-BE backend.*
             */
            'dewptf' => ['numeric'],
            /**
             * % Moisture (0-100 %) (float)
             * *Not part of Ecowitt protocol but supported by WOW-BE backend.*
             */
            'soilmoisture' => ['numeric'],
            /**
             * Soil Temperature (10cm) (Fahrenheit) (float)
             * *Not part of Ecowitt protocol but supported by WOW-BE backend.*
             */
            'soiltempf' => ['numeric'],
            /**
             * Visibility (Kilometres) (float)
             * *Not part of Ecowitt protocol but supported by WOW-BE backend.*
             */
            'visibility' => ['numeric'],
            /**
             * Current Wind Gust Direction (using software specific time period) (Degrees (0-360)) (float)
             * *Not part of Ecowitt protocol but supported by WOW-BE backend.*
             */
            'windgustdir' => ['numeric'],

            // Event Rain (Inches)
            // 'eventrainin' => ['numeric'],
            // Frequency
            // 'frequency' => ['string'],
            // Hourly Rain (Inches)
            // 'hourlyrainin' => ['numeric'],
            // Indoor Humidity (0-100 %)
            // 'humidityin' => ['numeric'],
            // Maximum Daily Gust Speed (Miles per Hour)
            // 'maxdailygust' => ['numeric'],
            // Monthly Rain (Inches)
            // 'monthlyrainin' => ['numeric'],
            // Indoor Temperature (Fahrenheit)
            // 'tempinf' => ['numeric'],
            // Total Rain (Inches)
            // 'totalrainin' => ['numeric'],
            // UV Index
            // 'uv' => ['numeric'],
            // VPD
            // 'vpd' => ['numeric'],
            // Weekly Rain (Inches)
            // 'weeklyrainin' => ['numeric'],
            // WH65Batt
            // 'wh65batt' => ['numeric'],
            // Yearly Rain (Inches)
            // 'yearlyrainin' => ['numeric'],

        ];
    }
}
