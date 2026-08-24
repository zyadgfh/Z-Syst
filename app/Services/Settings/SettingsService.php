<?php

namespace App\Services\Settings;

use App\Models\SettingDefinition;
use App\Models\SettingValue;
use App\Models\SettingAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Central service for all settings management operations.
 */
class SettingsService
{
    public function __construct(
        private SettingsResolver $resolver
    ) {}

    /**
     * Get all setting definitions, optionally filtered by module.
     */
    public function getDefinitions(?string $module = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = SettingDefinition::where('is_enabled', true)->orderBy('sort_order');

        if ($module) {
            $query->where('module', $module);
        }

        return $query->get();
    }

    /**
     * Get definitions grouped by module.
     */
    public function getDefinitionsGrouped(): array
    {
        return SettingDefinition::where('is_enabled', true)
            ->orderBy('module')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('module')
            ->toArray();
    }

    /**
     * Get all setting values for a specific scope.
     *
     * @return array<string, mixed>
     */
    public function getScopeValues(string $scopeType, $scopeId = null): array
    {
        $values = SettingValue::with('definition')
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->get();

        $result = [];
        foreach ($values as $value) {
            $result[$value->definition->key] = [
                'value' => $value->definition->castValue($value->value),
                'definition' => $value->definition,
                'value_id' => $value->id,
            ];
        }

        return $result;
    }

    /**
     * Get all settings with resolved values for a user scope.
     * Returns each setting with its effective value, source, and current scope value.
     */
    public function getSettingsWithResolution(string $scopeType, $scopeId = null, ?User $user = null): array
    {
        $definitions = SettingDefinition::where('is_enabled', true)->orderBy('sort_order')->get();
        $scopeValues = $this->getScopeValues($scopeType, $scopeId);

        $results = [];
        foreach ($definitions as $definition) {
            $resolved = $this->resolver->resolveWithSource($definition->key, $user);
            $scopeValue = $scopeValues[$definition->key] ?? null;

            $results[] = [
                'definition' => $definition,
                'scope_value' => $scopeValue ? $scopeValue['value'] : null,
                'scope_value_id' => $scopeValue ? $scopeValue['value_id'] : null,
                'effective_value' => $resolved['value'] ?? $definition->default_value,
                'source' => $resolved['source'] ?? 'default',
                'source_id' => $resolved['source_id'] ?? null,
                'is_overridden' => $scopeValue !== null,
            ];
        }

        return $results;
    }

    /**
     * Update a setting value for a specific scope.
     */
    public function updateSetting(
        string $key,
        mixed $value,
        string $scopeType,
        $scopeId = null,
        ?User $updatedBy = null
    ): SettingValue {
        $definition = SettingDefinition::where('key', $key)->firstOrFail();

        // Validate the setting can be set at this scope
        if (!$this->canSetAtScope($definition, $scopeType)) {
            throw ValidationException::withMessages([
                'value' => "Setting '{$key}' cannot be configured at the '{$scopeType}' scope.",
            ]);
        }

        // Validate the value against the definition's rules
        $this->validateValue($definition, $value);

        // Cast the value to the correct type
        $castValue = $definition->castValue($value);

        // Find existing value or create new
        $existingValue = SettingValue::where('setting_definition_id', $definition->id)
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->first();

        $oldValue = $existingValue?->value;

        $settingValue = SettingValue::updateOrCreate(
            [
                'setting_definition_id' => $definition->id,
                'scope_type' => $scopeType,
                'scope_id' => $scopeId,
            ],
            [
                'value' => $castValue,
                'updated_by' => $updatedBy?->id,
            ]
        );

        // Audit log
        $this->logAudit(
            $definition,
            $scopeType,
            $scopeId,
            $oldValue,
            $castValue,
            $existingValue ? 'updated' : 'created',
            $updatedBy
        );

        // Clear cache
        $this->resolver->clearAllCache();

        return $settingValue;
    }

    /**
     * Update multiple settings at once for a scope.
     */
    public function updateMany(
        array $settings,
        string $scopeType,
        $scopeId = null,
        ?User $updatedBy = null
    ): array {
        $results = [];

        DB::transaction(function () use ($settings, $scopeType, $scopeId, $updatedBy, &$results) {
            foreach ($settings as $key => $value) {
                try {
                    $result = $this->updateSetting($key, $value, $scopeType, $scopeId, $updatedBy);
                    $results[$key] = ['success' => true, 'value' => $result];
                } catch (\Exception $e) {
                    $results[$key] = ['success' => false, 'error' => $e->getMessage()];
                }
            }
        });

        return $results;
    }

    /**
     * Reset a setting to inherited value (delete the override).
     */
    public function resetToInherited(
        string $key,
        string $scopeType,
        $scopeId = null,
        ?User $resetBy = null
    ): bool {
        $definition = SettingDefinition::where('key', $key)->firstOrFail();

        $existingValue = SettingValue::where('setting_definition_id', $definition->id)
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->first();

        if (!$existingValue) {
            return false;
        }

        $oldValue = $existingValue->value;
        $existingValue->delete();

        // Audit log
        $this->logAudit(
            $definition,
            $scopeType,
            $scopeId,
            $oldValue,
            null,
            'reset',
            $resetBy
        );

        // Clear cache
        $this->resolver->clearAllCache();

        return true;
    }

    /**
     * Reset all settings for a scope to inherited values.
     */
    public function resetAllToInherited(
        string $scopeType,
        $scopeId = null,
        ?User $resetBy = null
    ): int {
        $deleted = SettingValue::where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->delete();

        // Clear cache
        $this->resolver->clearAllCache();

        return $deleted;
    }

    /**
     * Search settings by keyword.
     */
    public function search(string $query): \Illuminate\Database\Eloquent\Collection
    {
        return SettingDefinition::where('is_enabled', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('key', 'like', "%{$query}%")
                    ->orWhere('module', 'like', "%{$query}%");
            })
            ->orderBy('module')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Check if a setting can be set at a given scope.
     */
    public function canSetAtScope(SettingDefinition $definition, string $scopeType): bool
    {
        return match ($scopeType) {
            'system' => $definition->is_system_configurable,
            'organization' => $definition->is_organization_configurable,
            'branch' => $definition->is_branch_configurable,
            'role' => $definition->is_role_configurable,
            'user' => $definition->is_user_configurable,
            default => false,
        };
    }

    /**
     * Validate a value against a definition.
     */
    public function validateValue(SettingDefinition $definition, mixed $value): void
    {
        if (!$definition->validateValue($value)) {
            throw ValidationException::withMessages([
                'value' => "The value is invalid for setting '{$definition->key}' (type: {$definition->type}).",
            ]);
        }
    }

    /**
     * Seed all default settings from the registry.
     */
    public function seedDefaults(): array
    {
        $registry = SettingsRegistry::all();
        $results = [];

        foreach ($registry as $key => $definition) {
            $existing = SettingDefinition::where('key', $key)->first();

            if (!$existing) {
                SettingDefinition::create($definition);
                $results[$key] = 'created';
            } else {
                // Update existing definition (keep existing values)
                $existing->update([
                    'module' => $definition['module'],
                    'group' => $definition['group'],
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'type' => $definition['type'],
                    'type_options' => $definition['type_options'] ?? null,
                    'default_value' => $definition['default_value'],
                    'validation_rules' => $definition['validation_rules'] ?? null,
                    'is_system_configurable' => $definition['is_system_configurable'],
                    'is_organization_configurable' => $definition['is_organization_configurable'],
                    'is_branch_configurable' => $definition['is_branch_configurable'],
                    'is_role_configurable' => $definition['is_role_configurable'],
                    'is_user_configurable' => $definition['is_user_configurable'],
                    'sort_order' => $definition['sort_order'],
                ]);
                $results[$key] = 'updated';
            }
        }

        return $results;
    }

    /**
     * Get audit log for a setting.
     */
    public function getAuditLog(
        ?string $key = null,
        ?string $scopeType = null,
        $scopeId = null,
        int $limit = 50
    ): \Illuminate\Database\Eloquent\Collection {
        $query = SettingAuditLog::with(['definition', 'user'])
            ->orderBy('created_at', 'desc');

        if ($key) {
            $query->where('key', $key);
        }

        if ($scopeType) {
            $query->where('scope_type', $scopeType);
            if ($scopeId !== null) {
                $query->where('scope_id', $scopeId);
            }
        }

        return $query->limit($limit)->get();
    }

    /**
     * Log a settings audit entry.
     */
    private function logAudit(
        SettingDefinition $definition,
        string $scopeType,
        $scopeId,
        mixed $oldValue,
        mixed $newValue,
        string $action,
        ?User $user
    ): void {
        SettingAuditLog::create([
            'setting_definition_id' => $definition->id,
            'key' => $definition->key,
            'name' => $definition->name,
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'user_id' => $user?->id,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
