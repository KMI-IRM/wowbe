<?php

namespace App\Observers;

use App\Jobs\AggregateObservations5min;
use App\Jobs\AggregateObservationsDay;
use App\Models\Observation;

class ObservationObserver
{
    /**
     * Handle the Observation "created" event.
     */
    public function created(Observation $observation): void
    {
        AggregateObservations5min::dispatch($observation->site_id, $observation->dateutc, $observation->site->timezone);
        AggregateObservationsDay::dispatch($observation->site_id, $observation->dateutc, $observation->site->timezone);
    }
}
