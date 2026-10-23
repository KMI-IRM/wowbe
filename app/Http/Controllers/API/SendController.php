<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\EcowittSendRequest;
use App\Http\Requests\API\SendRequest;
use App\Http\Requests\API\WeatherUndergroundSendRequest;
use App\Http\Resources\ObservationResource;
use App\Models\Observation;
use App\Models\Site;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

#[Group(weight: 0)]
class SendController extends Controller
{
    /**
     * Send data.
     *
     * You can use this endpoint to send your data. This is compatible with **all protocols** supported by the API.
     *
     * The payload for each protocol is described in the following sections:
     * - [WOW protocol](/operations/send.wow)
     * - [Ecowitt protocol](/operations/send.ecowitt)
     * - [Weather Underground protocol](/operations/send.weatherunderground)
     *
     * Requests are rate limited to **20 per minute per site** (HTTP 429 on excess).
     *
     * @throws ValidationException
     */
    #[Response(status: 200, description: '`ObservationResource`')]
    #[Response(status: 403, description: 'Invalid site credentials', type: 'array{message: string}')]
    #[Response(status: 429, description: 'Too many requests')]
    public function __invoke(Request $request): JsonResponse
    {
        // Detect Ecowitt protocol
        if ($request->has('PASSKEY') === true) {
            return $this->handleEcowittRequest($request);
        }

        // Detect WeatherUnderground protocol
        if ($request->has(['ID', 'PASSWORD']) === true) {
            return $this->handleWeatherUndergroundRequest($request);
        }

        // Use default WOW protocol
        return $this->handleRequest($request);
    }

    /**
     * Send data with WOW protocol.
     *
     * You also can also use the global [`/send` endpoint](/operations/send).
     */
    #[Response(status: 403, description: 'Invalid site credentials', type: 'array{message: string}')]
    #[Response(status: 429, description: 'Too many requests')]
    public function wow(SendRequest $request): JsonResponse
    {
        return $this->handleRequest($request);
    }

    /**
     * Send data with Ecowitt protocol.
     *
     * You also can also use the global [`/send` endpoint](/operations/send).
     */
    #[Response(status: 429, description: 'Too many requests')]
    public function ecowitt(EcowittSendRequest $request): JsonResponse
    {
        return $this->handleEcowittRequest($request);
    }

    /**
     * Send data with Weather Underground protocol.
     *
     * You also can also use the global [`/send` endpoint](/operations/send).
     */
    #[Response(status: 403, description: 'Invalid site credentials', type: 'array{message: string}')]
    #[Response(status: 429, description: 'Too many requests')]
    public function weatherunderground(WeatherUndergroundSendRequest $request): JsonResponse
    {
        return $this->handleWeatherUndergroundRequest($request);
    }

    /**
     * Handle default WOW protocol request.
     */
    private function handleRequest(Request $request): JsonResponse
    {
        // Prepare data for validation
        $data = $request->all();
        $data['dateutc'] = self::decodeDateTime($request->input('dateutc'));

        // Get validation rules
        $rules = (new SendRequest)->rules();
        $validated = Validator::make($data, $rules)->validate();

        // Check authorization
        $siteID = $request->input('siteid');
        $siteAuthenticationKey = $request->input('siteAuthenticationKey');

        if (Str::isUuid($siteID) === true) {
            /** @var Site $site */
            $site = Site::findOrFail($siteID);
        } else {
            /** @var Site $site */
            $site = Site::where('short_id', $siteID)->firstOrFail();
        }

        if (is_null($siteAuthenticationKey) || hash_equals($site->auth_key, $siteAuthenticationKey) === false) {
            abort(403, 'Invalid site credentials');
        }

        $observation = $this->createObservation($validated, $site);

        return response()->json(new ObservationResource($observation));
    }

    /**
     * Handle Ecowitt protocol request.
     */
    private function handleEcowittRequest(Request $request): JsonResponse
    {
        // Prepare data for validation
        $data = $request->all();
        $data['dateutc'] = self::decodeDateTime($request->input('dateutc'));

        // Get validation rules
        $rules = (new EcowittSendRequest)->rules();
        $validated = Validator::make($data, $rules)->validate();

        // Check authorization
        /** @var Site $site */
        $site = Site::where(new Expression('UPPER(MD5(UPPER("mac_address"::text)))'), $validated['PASSKEY'])->firstOrFail();

        // Transform data
        $validated['softwaretype'] = $validated['stationtype'];
        $validated['baromin'] = $validated['baromrelin'] ?? null;
        $validated['absbaromin'] = $validated['baromabsin'] ?? null;
        $validated['rainin'] = $validated['rainratein'] ?? null;

        $observation = $this->createObservation($validated, $site);

        return response()->json(new ObservationResource($observation));
    }

    /**
     * Handle WeatherUnderground protocol request.
     */
    private function handleWeatherUndergroundRequest(Request $request): JsonResponse
    {
        // Prepare data for validation
        $data = $request->all();
        $data['dateutc'] = self::decodeDateTime($request->input('dateutc'));

        // Get validation rules
        $rules = (new WeatherUndergroundSendRequest)->rules();
        $validated = Validator::make($data, $rules)->validate();

        // Check authorization
        $siteID = $request->input('ID');
        $siteAuthenticationKey = $request->input('PASSWORD');

        if (Str::isUuid($siteID) === true) {
            /** @var Site $site */
            $site = Site::findOrFail($siteID);
        } else {
            /** @var Site $site */
            $site = Site::where('short_id', $siteID)->firstOrFail();
        }

        if (is_null($siteAuthenticationKey) || hash_equals($site->auth_key, $siteAuthenticationKey) === false) {
            abort(403, 'Invalid site credentials');
        }

        $observation = $this->createObservation($validated, $site);

        return response()->json(new ObservationResource($observation));
    }

    /**
     * @param  array<string,mixed>  $data
     */
    private function createObservation(array $data, Site $site): Observation
    {
        $observation = new Observation($data);
        $observation->site()->associate($site);
        $observation->save();

        return $observation;
    }

    private static function decodeDateTime(?string $source): ?string
    {
        if (is_null($source)) {
            return null;
        }

        $timezone = date_default_timezone_get();

        date_default_timezone_set('UTC');

        $source = urldecode($source);

        $datetime = strtotime($source);
        if ($datetime !== false) {
            return date('Y-m-d H:i:s', $datetime);
        }

        date_default_timezone_set($timezone);

        return $source;
    }
}
