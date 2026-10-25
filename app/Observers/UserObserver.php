<?php

namespace App\Observers;

use App\Mail\AccountCreated;
use App\Mail\AccountDeleted;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        Mail::to($user)
            ->send(new AccountCreated($user));
    }

    /**
     * Handle the User "updated" event.
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        Mail::to($user)
            ->send(new AccountDeleted($user));
    }

    /**
     * Handle the User "restored" event.
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        Mail::to($user)
            ->send(new AccountDeleted($user));
    }
}
