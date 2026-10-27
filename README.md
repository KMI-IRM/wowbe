# WOW-BE Reboot

This is the new version of the WOW-BE (Weather Observations Website - Belgium) back-end and database.

You can find more information here: <https://wow.meteo.be/en/about-wow-be/>

This version is developed using [Laravel](https://laravel.com/) and is running inside a [Docker](https://www.docker.com/) container using [Laravel Octane](https://laravel.com/docs/octane) with [FrankenPHP](https://frankenphp.dev/) connected to a [PostgreSQL](https://www.postgresql.org/) database.

## Installation

See [Installation Guide](INSTALL.md).

## Documentation

- API Version 2: <https://wow.meteo.be/docs/api/>
- API Version 1 (backward compatible): <https://wow.meteo.be/docs/api/v1/>

## Rate limiting

Send endpoints are rate limited to **20 requests per minute per site**. Exceeding the limit returns HTTP 429.

The site is identified by the field carrying its identity in each protocol: `siteid` (WOW), `PASSKEY` (Ecowitt) or `ID` (Weather Underground). A reporting interval of 60 seconds is generally recommended.

## Tests

Unit and feature tests are available (see [Laravel documentation](https://laravel.com/docs/testing) for more information).

## External services

### Geocoding API

WOW-BE site registration form uses [Nominatim](https://nominatim.org/) for geocoding (see [usage policy](https://operations.osmfoundation.org/policies/nominatim/)).  
If needed, it can be incorporated via the [Docker container](https://github.com/mediagis/nominatim-docker).

### Elevation API

WOW-BE site registration form uses [Open Elevation](https://open-elevation.com/) for elevation data. Free usage is limited to 1000 requests/month.  
If needed, it can be incorporated via the [Docker container](https://github.com/Jorl17/open-elevation/blob/master/docs/host-your-own.md).
