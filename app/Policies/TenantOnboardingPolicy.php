<?php

namespace App\Policies;

use App\Models\TenantOnboardingInstance;
use App\Models\OnboardingTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TenantOnboardingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function view(User $user, TenantOnboardingInstance $instance): bool
    {
        return $user->business_id === $instance->business_id;
    }

    public function start(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function processStep(User $user, TenantOnboardingInstance $instance): bool
    {
        return $user->business_id === $instance->business_id &&
               in_array($instance->status, ['in_progress']);
    }

    public function completeChecklist(User $user, $checklistProgress): bool
    {
        return $user->business_id === $checklistProgress->business_id;
    }

    // Template policies
    public function viewAnyTemplates(User $user): bool
    {
        return $user->business_id !== null;
    }

    public function viewTemplate(User $user, OnboardingTemplate $template): bool
    {
        return $template->is_active;
    }

    public function createTemplate(User $user): bool
    {
        return $user->business_id !== null && in_array($user->role, ['admin', 'superadmin']);
    }

    public function updateTemplate(User $user, OnboardingTemplate $template): bool
    {
        return $user->business_id !== null && in_array($user->role, ['admin', 'superadmin']);
    }

    public function deleteTemplate(User $user, OnboardingTemplate $template): bool
    {
        return $user->business_id !== null && in_array($user->role, ['superadmin']) && !$template->is_default;
    }
}