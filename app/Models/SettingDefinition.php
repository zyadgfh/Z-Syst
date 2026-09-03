<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SettingDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'module',
        'group',
        'name',
        'description',
        'type',
        'type_options',
        'default_value',
        'validation_rules',
        'is_system_configurable',
        'is_organization_configurable',
        'is_branch_configurable',
        'is_role_configurable',
        'is_user_configurable',
        'is_enabled',
        'sort_order',
    ];

    protected $casts = [
        'type_options' => 'json',
        'default_value' => 'json',
        'validation_rules' => 'json',
        'is_system_configurable' => 'boolean',
        'is_organization_configurable' => 'boolean',
        'is_branch_configurable' => 'boolean',
        'is_role_configurable' => 'boolean',
        'is_user_configurable' => 'boolean',
        'is_enabled' => 'boolean',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(SettingValue::class, 'setting_definition_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(SettingAuditLog::class, 'setting_definition_id');
    }

    /**
     * Get all definitions for a given module.
     */
    public static function forModule(string $module): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('module', $module)
            ->where('is_enabled', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get all definitions grouped by module.
     */
    public static function groupedByModule(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_enabled', true)
            ->orderBy('module')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('module');
    }

    /**
     * Get allowed scope types for this definition.
     */
    public function getAllowedScopes(): array
    {
        $scopes = [];
        if ($this->is_system_configurable) {
            $scopes[] = 'system';
        }
        if ($this->is_organization_configurable) {
            $scopes[] = 'organization';
        }
        if ($this->is_branch_configurable) {
            $scopes[] = 'branch';
        }
        if ($this->is_role_configurable) {
            $scopes[] = 'role';
        }
        if ($this->is_user_configurable) {
            $scopes[] = 'user';
        }

        return $scopes;
    }

    /**
     * Validate a value against this definition's rules.
     */
    public function validateValue(mixed $value): bool
    {
        $rules = $this->validation_rules ?? [];

        // Base type validation
        $typeRules = match ($this->type) {
            'boolean' => 'boolean',
            'integer' => 'integer',
            'decimal' => 'numeric',
            'string' => 'string',
            'select' => 'string',
            'multi_select' => 'array',
            'json' => 'json',
            default => 'string',
        };

        $allRules = array_merge(['value' => $typeRules], $rules);

        $validator = \Illuminate\Support\Facades\Validator::make(
            ['value' => $value],
            $allRules
        );

        return !$validator->fails();
    }

    /**
     * Cast value to the correct PHP type based on setting type.
     */
    public function castValue(mixed $value): mixed
    {
        return match ($this->type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'decimal' => (float) $value,
            'json' => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }
}
