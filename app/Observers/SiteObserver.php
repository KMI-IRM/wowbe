<?php

namespace App\Observers;

use App\Mail\SiteCreated;
use App\Models\Site;
use Illuminate\Support\Facades\Mail;

class SiteObserver
{
    /**
     * Handle the Site "created" event.
     */
    public function created(Site $site): void
    {
        Mail::to($site->user)
            ->send(new SiteCreated($site));
    }

    /**
     * Handle the Site "deleted" event.
     */
    public function deleted(Site $site): void
    {
        // (Soft-)Delete all observations associated with the site
        $site->observations()->delete();

        // Delete all associated media
        $site->media()->delete();
    }
}
