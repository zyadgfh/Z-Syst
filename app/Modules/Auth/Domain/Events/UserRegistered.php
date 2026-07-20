<?php

namespace App\Modules\Auth\Domain\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class UserRegistered
{
    use Dispatchable;

    public function __construct(public User $user)
    {
    }
}
