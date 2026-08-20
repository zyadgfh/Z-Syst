<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CampaignPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, Campaign $campaign): bool
    {
        return $user->business_id === $campaign->business_id;
    }

    public function create(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $user->business_id === $campaign->business_id;
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        return $user->business_id === $campaign->business_id &&
               $campaign->status === 'draft';
    }

    public function send(User $user, Campaign $campaign): bool
    {
        return $user->business_id === $campaign->business_id &&
               in_array($campaign->status, ['draft', 'scheduled']);
    }
}