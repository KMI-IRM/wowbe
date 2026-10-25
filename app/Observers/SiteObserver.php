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
     * Handle the Site "updated" event.
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function updated(Site $site): void
    {
        //
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

    /**
     * Handle the Site "restored" event.
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function restored(Site $site): void
    {
        //
    }

    /**
     * Handle the Site "force deleted" event.
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function forceDeleted(Site $site): void
    {
        // No need to force delete the observations linked to the Site since it's automatically done via ON DELETE CASCADE in PostgreSQL.
    }
}
