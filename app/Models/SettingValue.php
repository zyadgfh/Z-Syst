<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettingValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'setting_definition_id',
        'scope_type',
        'scope_id',
        'value',
        'updated_by',
    ];

    protected $casts = [
        'value' => 'json',
    ];

    public function definition(): BelongsTo
    {
        return $this->belongsTo(SettingDefinition::class, 'setting_definition_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Resolve the parent model for this scope.
     */
    public function scopeModel(): ?Model
    {
        return match ($this->scope_type) {
            'organization' => Business::find($this->scope_id),
            'branch' => Branch::find($this->scope_id),
            'role' => \Spatie\Permission\Models\Role::find($this->scope_id),
            'user' => User::find($this->scope_id),
            default => null,
        };
    }

    /**
     * Get a human-readable label for the scope.
     */
    public function getScopeLabel(): string
    {
        return match ($this->scope_type) {
            'system' => __('System Default'),
            'organization' => __('Organization'),
            'branch' => __('Branch'),
            'role' => __('Role'),
            'user' => __('User'),
            default => $this->scope_type,
        };
    }

    /**
     * Get a description of where this setting is inherited from.
     */
    public function getSourceDescription(): string
    {
        $model = $this->scopeModel();

        return match ($this->scope_type) {
            'system' => __('System Default'),
            'organization' => $model ? $model->companyName ?? __('Organization') : __('Organization'),
            'branch' => $model ? $model->branch_name ?? __('Branch') : __('Branch'),
            'role' => $model ? $model->name ?? __('Role') : __('Role'),
            'user' => $model ? $model->name ?? __('User') : __('User'),
            default => $this->scope_type,
        };
    }
}
