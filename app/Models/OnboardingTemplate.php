<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnboardingTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'steps',
        'default_settings',
        'default_roles',
        'default_permissions',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'steps' => 'array',
        'default_settings' => 'array',
        'default_roles' => 'array',
        'default_permissions' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function instances(): HasMany
    {
        return $this->hasMany(TenantOnboardingInstance::class);
    }

    public function welcomeEmails(): HasMany
    {
        return $this->hasMany(WelcomeEmailTemplate::class);
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(OnboardingChecklistItem::class);
    }

    public function automationRules(): HasMany
    {
        return $this->hasMany(OnboardingAutomationRule::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}