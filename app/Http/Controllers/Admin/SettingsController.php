<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SettingAuditLog;
use App\Models\SettingDefinition;
use App\Models\SettingValue;
use App\Services\Settings\SettingsService;
use App\Services\Settings\SettingsResolver;
use App\Services\Settings\SettingsRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function __construct(
        private SettingsService $settingsService,
        private SettingsResolver $resolver
    ) {
        $this->middleware('permission:settings-view')->only('index', 'search', 'getAuditLog', 'getModuleSettings', 'getModuleMeta');
        $this->middleware('permission:settings-edit')->only('updateSystem', 'updateOrganization', 'updateBranch', 'updateRole', 'updateUser', 'updateBulk', 'resetToInherited', 'seedDefaults');
    }

    /**
     * Main settings page - shows the category sidebar and settings list.
     */
    public function index(Request $request)
    {
        $module = $request->get('module', 'general');
        $modules = SettingsRegistry::moduleMeta();

        // Get settings with their resolved values
        $settings = $this->settingsService->getSettingsWithResolution('system', null);

        // Group by module
        $grouped = [];
        foreach ($settings as $setting) {
            $grouped[$setting['definition']->module][] = $setting;
        }

        return view('admin.settings.app', [
            'modules' => $modules,
            'currentModule' => $module,
            'groupedSettings' => $grouped,
        ]);
    }

    /**
     * Get settings for a specific module (AJAX).
     */
    public function getModuleSettings(Request $request, string $module): JsonResponse
    {
        $scopeType = $request->get('scope_type', 'system');
        $scopeId = $request->get('scope_id');

        $settings = $this->settingsService->getSettingsWithResolution($scopeType, $scopeId);

        // Filter by module
        $filtered = array_filter($settings, fn($s) => $s['definition']->module === $module);

        return response()->json([
            'settings' => array_values($filtered),
            'module' => $module,
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
        ]);
    }

    /**
     * Update a system-level setting.
     */
    public function updateSystem(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'required',
        ]);

        try {
            $result = $this->settingsService->updateSetting(
                $request->key,
                $request->value,
                'system',
                null,
                Auth::user()
            );

            return response()->json([
                'message' => __('Setting updated successfully.'),
                'setting' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update an organization-level setting.
     */
    public function updateOrganization(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'required',
        ]);

        $businessId = Auth::user()->business_id;

        try {
            $result = $this->settingsService->updateSetting(
                $request->key,
                $request->value,
                'organization',
                $businessId,
                Auth::user()
            );

            return response()->json([
                'message' => __('Setting updated successfully.'),
                'setting' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update a branch-level setting.
     */
    public function updateBranch(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'required',
            'branch_id' => 'required|integer|exists:branches,id',
        ]);

        try {
            $result = $this->settingsService->updateSetting(
                $request->key,
                $request->value,
                'branch',
                $request->branch_id,
                Auth::user()
            );

            return response()->json([
                'message' => __('Setting updated successfully.'),
                'setting' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update a role-level setting.
     */
    public function updateRole(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'required',
            'role_id' => 'required|integer|exists:roles,id',
        ]);

        try {
            $result = $this->settingsService->updateSetting(
                $request->key,
                $request->value,
                'role',
                $request->role_id,
                Auth::user()
            );

            return response()->json([
                'message' => __('Setting updated successfully.'),
                'setting' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update a user-level setting.
     */
    public function updateUser(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'required',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        try {
            $result = $this->settingsService->updateSetting(
                $request->key,
                $request->value,
                'user',
                $request->user_id,
                Auth::user()
            );

            return response()->json([
                'message' => __('Setting updated successfully.'),
                'setting' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update multiple settings at once for a scope.
     */
    public function updateBulk(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => 'required|array',
            'scope_type' => 'required|string|in:system,organization,branch,role,user',
            'scope_id' => 'nullable|integer',
        ]);

        $results = $this->settingsService->updateMany(
            $request->settings,
            $request->scope_type,
            $request->scope_id,
            Auth::user()
        );

        return response()->json([
            'message' => __('Settings updated.'),
            'results' => $results,
        ]);
    }

    /**
     * Reset a setting to inherited value.
     */
    public function resetToInherited(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'scope_type' => 'required|string|in:organization,branch,role,user',
            'scope_id' => 'required|integer',
        ]);

        $success = $this->settingsService->resetToInherited(
            $request->key,
            $request->scope_type,
            $request->scope_id,
            Auth::user()
        );

        return response()->json([
            'message' => $success
                ? __('Setting reset to inherited value.')
                : __('No override found to reset.'),
            'reset' => $success,
        ]);
    }

    /**
     * Search settings by keyword.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:1',
        ]);

        $results = $this->settingsService->search($request->query);

        return response()->json([
            'settings' => $results,
            'query' => $request->query,
        ]);
    }

    /**
     * Get audit log for settings changes.
     */
    public function getAuditLog(Request $request): JsonResponse
    {
        $logs = $this->settingsService->getAuditLog(
            $request->get('key'),
            $request->get('scope_type'),
            $request->get('scope_id'),
            (int) $request->get('limit', 50)
        );

        return response()->json([
            'logs' => $logs,
        ]);
    }

    /**
     * Get settings definitions (for the API).
     */
    public function getDefinitions(Request $request): JsonResponse
    {
        $module = $request->get('module');
        $definitions = $this->settingsService->getDefinitions($module);

        return response()->json([
            'definitions' => $definitions,
        ]);
    }

    /**
     * Seed/re-seed default settings.
     */
    public function seedDefaults(): JsonResponse
    {
        $results = $this->settingsService->seedDefaults();

        return response()->json([
            'message' => __('Settings seeded successfully.'),
            'results' => $results,
        ]);
    }

    /**
     * Get the effective value of a setting for the current user.
     */
    public function getEffective(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        $result = $this->resolver->resolveWithSource($request->key);

        return response()->json([
            'key' => $request->key,
            'value' => $result['value'] ?? null,
            'source' => $result['source'] ?? 'default',
            'source_id' => $result['source_id'] ?? null,
        ]);
    }

    /**
     * Get module metadata for the settings UI.
     */
    public function getModuleMeta(): JsonResponse
    {
        return response()->json([
            'modules' => SettingsRegistry::moduleMeta(),
        ]);
    }
}
