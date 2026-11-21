<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Rules\AuthKey;
use App\Rules\PicturesLimit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SiteController extends Controller
{
    private const PICTURES_COLLECTION = 'pictures';

    private const VALIDATION_RULES = [
        'altitude' => ['required', 'numeric'],
        'brand' => ['nullable', 'string'],
        'latitude' => ['required', 'numeric', 'between:-90,90'],
        'longitude' => ['required', 'numeric', 'between:-180,180'],
        'name' => ['required', 'string'],
        'picture_add' => ['nullable', 'file', 'image', 'max:5120'],
        'picture_remove' => ['nullable', 'array', 'exists:media,uuid'],
        'software' => ['nullable', 'string'],
        'timezone' => ['required', 'string', 'timezone'],
        'website' => ['nullable', 'string', 'url'],
    ];

    /**
     * Show the form for creating a new resource.
     */
    public function create(): InertiaResponse
    {
        return Inertia::render('site/Create', [
            'defaultTimezone' => config('app.timezone'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            ...self::VALIDATION_RULES,
            'password' => [new AuthKey],
            'mac_address' => ['nullable', 'string', 'mac_address', 'unique:'.Site::class],
        ]);

        $authKey = match (true) {
            isset($validated['pincode']) => $validated['pincode'],
            isset($validated['password']) => $validated['password'],
            // @codeCoverageIgnoreStart
            default => throw ValidationException::withMessages([
                'pincode' => 'Either pincode or password must be provided.',
                'password' => 'Either pincode or password must be provided.',
            ]),
            // @codeCoverageIgnoreEnd
        };

        $site = new Site([...$validated, 'auth_key' => $authKey]);
        $site->user()->associate($request->user());
        $site->save();

        if ($request->hasFile('picture_add') === true) {
            $site
                ->addMediaFromRequest('picture_add')
                ->toMediaCollection(self::PICTURES_COLLECTION);
        }

        return to_route('dashboard');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Site $site): InertiaResponse
    {
        Gate::authorize('update', $site);

        $site->makeVisible(['brand', 'software']);

        return Inertia::render('site/Edit', [
            'site' => $site,
            'pictures' => $site->getMedia(self::PICTURES_COLLECTION)->toArray(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Site $site): RedirectResponse
    {
        Gate::authorize('update', $site);

        $validated = $request->validate([
            ...self::VALIDATION_RULES,
            'picture_add' => [...self::VALIDATION_RULES['picture_add'], new PicturesLimit($site, self::PICTURES_COLLECTION)],
        ]);

        $site->update($validated);

        if ($request->hasFile('picture_add') === true) {
            $site
                ->addMediaFromRequest('picture_add')
                ->toMediaCollection(self::PICTURES_COLLECTION);
        }
        if ($request->has('picture_remove') === true) {
            $site->getMedia(self::PICTURES_COLLECTION)
                ->whereIn('uuid', $request->array('picture_remove'))
                ->each(fn ($media) => $media->delete());
        }

        return to_route('site.edit', ['site' => $site->id]);
    }

    /**
     * Show the form for editing the authentication key of the specified resource.
     */
    public function editAuth(Site $site): InertiaResponse
    {
        Gate::authorize('update', $site);

        $site->makeVisible(['auth_key', 'has_pin_code', 'mac_address']);

        return Inertia::render('site/EditAuth', [
            'site' => $site,
        ]);
    }

    /**
     * Update the the authentication key of the specified resource in storage.
     */
    public function updateAuth(Request $request, Site $site): RedirectResponse
    {
        Gate::authorize('update', $site);

        $validator = Validator::make($request->all(), [
            'tab' => ['required', 'string', 'in:pincode,password'],
            'pincode' => [
                Rule::excludeIf(! $request->string('tab')->is('pincode')),
                Rule::requiredIf($request->string('tab')->is('pincode')),
                'array', 'size:6',
            ],
            'password' => [
                Rule::excludeIf(! $request->string('tab')->is('password')),
                Rule::requiredIf($request->string('tab')->is('password')),
                Rules\Password::defaults(),
            ],
            'mac_address' => ['nullable', 'string', 'mac_address', Rule::unique(Site::class)->ignore($site)],
        ]);

        $validated = $validator->validated();

        /** @var string[]|string $authKey */
        $authKey = match ($validated['tab']) {
            'pincode' => $validated['pincode'],
            'password' => $validated['password'],
            // @codeCoverageIgnoreStart
            default => throw ValidationException::withMessages([
                'pincode' => 'Invalid authentication type',
                'password' => 'Invalid authentication type',
            ])
            // @codeCoverageIgnoreEnd
        };

        $site->update(['auth_key' => $authKey, 'mac_address' => $validated['mac_address'] ?? null]);

        return to_route('site.edit_auth', ['site' => $site->id]);
    }

    /**
     * Show the form for deleting the specified resource.
     */
    public function delete(Site $site): InertiaResponse
    {
        Gate::authorize('delete', $site);

        $site->makeVisible(['auth_key', 'has_pin_code']);

        return Inertia::render('site/Delete', [
            'site' => $site,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Site $site): RedirectResponse
    {
        Gate::authorize('delete', $site);

        if ($site->hasPINCode === true) {
            $request->validate([
                'auth_key' => ['required', 'array', 'size:6'],
            ]);
            // Check if the provided auth_key matches the site's authentication key
            if (hash_equals($site->auth_key, implode('', $request->input('auth_key'))) === false) {
                return back()->withErrors(['auth_key' => 'The authentication key is incorrect.']);
            }
        } else {
            $request->validate([
                'auth_key' => ['required', 'string', Rules\Password::defaults()],
            ]);
            // Check if the provided auth_key matches the site's authentication key
            if (hash_equals($site->auth_key, $request->input('auth_key')) === false) {
                return back()->withErrors(['auth_key' => 'The authentication key is incorrect.']);
            }
        }

        $site->delete();

        return to_route('dashboard');
    }
}
