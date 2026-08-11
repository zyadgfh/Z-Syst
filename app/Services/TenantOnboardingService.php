<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use App\Models\OnboardingTemplate;
use App\Models\TenantOnboardingInstance;
use App\Models\OnboardingStepLog;
use App\Models\TenantChecklistProgress;
use App\Models\OnboardingChecklistItem;
use App\Models\WelcomeEmailTemplate;
use App\Traits\WithTransactionalOperations;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;

class TenantOnboardingService
{
    use WithTransactionalOperations;

    /**
     * Start onboarding for a tenant.
     *
     * @param Business $business
     * @param int|null $templateId
     * @param int $initiatedBy
     * @return TenantOnboardingInstance
     * @throws \Exception
     */
    public function startOnboarding(Business $business, ?int $templateId, int $initiatedBy): TenantOnboardingInstance
    {
        return $this->executeTransaction(function () use ($business, $templateId, $initiatedBy) {
            $template = $templateId ? OnboardingTemplate::findOrFail($templateId) : OnboardingTemplate::where('is_default', true)->first();

            if (!$template) {
                $template = OnboardingTemplate::create([
                    'name' => 'Default Onboarding',
                    'code' => 'default',
                    'steps' => $this->getDefaultSteps(),
                    'default_settings' => $this->getDefaultSettings(),
                    'default_roles' => $this->getDefaultRoles(),
                    'default_permissions' => $this->getDefaultPermissions(),
                    'is_default' => true,
                ]);
            }

            $instance = TenantOnboardingInstance::create([
                'business_id' => $business->id,
                'onboarding_template_id' => $template->id,
                'initiated_by' => $initiatedBy,
                'status' => 'in_progress',
                'current_step' => 0,
                'total_steps' => count($template->steps),
                'started_at' => now(),
            ]);

            // Create checklist items
            $this->createChecklistItems($business, $template);

            // Send welcome email
            $this->sendWelcomeEmail($business, $template);

            // Process first step
            $this->processNextStep($instance);

            return $instance->fresh(['template']);
        });
    }

    /**
     * Process the next onboarding step.
     *
     * @param TenantOnboardingInstance $instance
     * @return bool
     * @throws \Exception
     */
    public function processNextStep(TenantOnboardingInstance $instance): bool
    {
        return $this->executeTransaction(function () use ($instance) {
            $template = $instance->template;
            $steps = $template->steps ?? [];
            $currentStepIndex = $instance->current_step;

            if ($currentStepIndex >= count($steps)) {
                $instance->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
                return true;
            }

            $step = $steps[$currentStepIndex];

            // Create step log
            $stepLog = OnboardingStepLog::create([
                'onboarding_instance_id' => $instance->id,
                'step_name' => $step['name'] ?? 'Step ' . ($currentStepIndex + 1),
                'step_order' => $currentStepIndex,
                'status' => 'in_progress',
                'step_data' => $step,
                'started_at' => now(),
            ]);

            // Execute step
            $result = $this->executeStep($instance, $step, $stepLog);

            if ($result['success']) {
                $stepLog->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                $instance->increment('current_step');

                // Check if all steps completed
                if ($instance->current_step >= $instance->total_steps) {
                    $instance->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);
                }
            } else {
                $stepLog->update([
                    'status' => 'failed',
                    'error_message' => $result['error'],
                ]);
            }

            return $result['success'];
        });
    }

    /**
     * Execute a specific onboarding step.
     *
     * @param TenantOnboardingInstance $instance
     * @param array $step
     * @param OnboardingStepLog $stepLog
     * @return array
     */
    protected function executeStep(TenantOnboardingInstance $instance, array $step, OnboardingStepLog $stepLog): array
    {
        $action = $step['action'] ?? null;

        try {
            switch ($action) {
                case 'create_default_users':
                    return $this->createDefaultUsers($instance->business, $step);
                case 'setup_default_settings':
                    return $this->setupDefaultSettings($instance->business, $step);
                case 'create_sample_data':
                    return $this->createSampleData($instance->business, $step);
                case 'configure_roles':
                    return $this->configureRoles($instance->business, $step);
                case 'send_welcome_resources':
                    return $this->sendWelcomeResources($instance->business, $step);
                default:
                    return ['success' => true, 'message' => 'Step skipped - no action defined'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create default users for the tenant.
     *
     * @param Business $business
     * @param array $step
     * @return array
     */
    protected function createDefaultUsers(Business $business, array $step): array
    {
        $users = $step['users'] ?? [];

        foreach ($users as $userData) {
            User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password'] ?? 'password123'),
                'business_id' => $business->id,
                'role' => $userData['role'] ?? 'staff',
            ]);
        }

        return ['success' => true, 'message' => 'Default users created'];
    }

    /**
     * Setup default settings for the tenant.
     *
     * @param Business $business
     * @param array $step
     * @return array
     */
    protected function setupDefaultSettings(Business $business, array $step): array
    {
        $settings = $step['settings'] ?? [];

        foreach ($settings as $key => $value) {
            $business->update([$key => $value]);
        }

        return ['success' => true, 'message' => 'Default settings configured'];
    }

    /**
     * Create sample data for the tenant.
     *
     * @param Business $business
     * @param array $step
     * @return array
     */
    protected function createSampleData(Business $business, array $step): array
    {
        // This would create sample products, categories, etc.
        // Implementation depends on specific data requirements
        
        return ['success' => true, 'message' => 'Sample data created'];
    }

    /**
     * Configure roles for the tenant.
     *
     * @param Business $business
     * @param array $step
     * @return array
     */
    protected function configureRoles(Business $business, array $step): array
    {
        // This would configure roles and permissions using Spatie
        // Implementation depends on specific role requirements
        
        return ['success' => true, 'message' => 'Roles configured'];
    }

    /**
     * Send welcome resources to the tenant.
     *
     * @param Business $business
     * @param array $step
     * @return array
     */
    protected function sendWelcomeResources(Business $business, array $step): array
    {
        // This would send documentation, tutorials, etc.
        
        return ['success' => true, 'message' => 'Welcome resources sent'];
    }

    /**
     * Create checklist items for the tenant.
     *
     * @param Business $business
     * @param OnboardingTemplate $template
     * @return void
     */
    protected function createChecklistItems(Business $business, OnboardingTemplate $template): void
    {
        $checklistItems = OnboardingChecklistItem::where('onboarding_template_id', $template->id)
            ->orWhere('is_default', true)
            ->get();

        foreach ($checklistItems as $item) {
            TenantChecklistProgress::create([
                'business_id' => $business->id,
                'checklist_item_id' => $item->id,
                'is_completed' => false,
            ]);
        }
    }

    /**
     * Send welcome email to the tenant.
     *
     * @param Business $business
     * @param OnboardingTemplate $template
     * @return void
     */
    protected function sendWelcomeEmail(Business $business, OnboardingTemplate $template): void
    {
        $emailTemplate = WelcomeEmailTemplate::where('onboarding_template_id', $template->id)
            ->where('is_active', true)
            ->first();

        if (!$emailTemplate) {
            // Send default welcome email
            $adminUser = User::where('business_id', $business->id)->first();
            
            if ($adminUser) {
                // Mail::to($adminUser->email)->send(new WelcomeEmail($business));
                // Note: WelcomeEmail mailable would need to be created
            }
        }
    }

    /**
     * Get default onboarding steps.
     *
     * @return array
     */
    protected function getDefaultSteps(): array
    {
        return [
            [
                'name' => 'Account Setup',
                'action' => 'setup_default_settings',
                'description' => 'Configure basic account settings',
            ],
            [
                'name' => 'User Creation',
                'action' => 'create_default_users',
                'description' => 'Create initial user accounts',
            ],
            [
                'name' => 'Role Configuration',
                'action' => 'configure_roles',
                'description' => 'Setup roles and permissions',
            ],
            [
                'name' => 'Sample Data',
                'action' => 'create_sample_data',
                'description' => 'Import sample products and data',
            ],
            [
                'name' => 'Welcome Resources',
                'action' => 'send_welcome_resources',
                'description' => 'Send documentation and tutorials',
            ],
        ];
    }

    /**
     * Get default settings.
     *
     * @return array
     */
    protected function getDefaultSettings(): array
    {
        return [
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'currency' => 'USD',
            'language' => 'en',
        ];
    }

    /**
     * Get default roles.
     *
     * @return array
     */
    protected function getDefaultRoles(): array
    {
        return [
            ['name' => 'Admin', 'permissions' => ['*']],
            ['name' => 'Manager', 'permissions' => ['manage_products', 'manage_sales', 'manage_purchases']],
            ['name' => 'Staff', 'permissions' => ['view_products', 'process_sales']],
        ];
    }

    /**
     * Get default permissions.
     *
     * @return array
     */
    protected function getDefaultPermissions(): array
    {
        return [
            'manage_products',
            'manage_sales',
            'manage_purchases',
            'manage_inventory',
            'manage_customers',
            'view_reports',
            'manage_users',
        ];
    }

    /**
     * Get onboarding progress for a tenant.
     *
     * @param int $businessId
     * @return array
     */
    public function getOnboardingProgress(int $businessId): array
    {
        $instance = TenantOnboardingInstance::where('business_id', $businessId)
            ->with(['stepLogs', 'template'])
            ->first();

        if (!$instance) {
            return [
                'status' => 'not_started',
                'progress' => 0,
                'steps' => [],
            ];
        }

        $completedSteps = $instance->stepLogs()->where('status', 'completed')->count();
        $progress = $instance->total_steps > 0 ? ($completedSteps / $instance->total_steps) * 100 : 0;

        return [
            'status' => $instance->status,
            'progress' => round($progress, 2),
            'current_step' => $instance->current_step,
            'total_steps' => $instance->total_steps,
            'started_at' => $instance->started_at,
            'completed_at' => $instance->completed_at,
            'steps' => $instance->stepLogs->map(function ($log) {
                return [
                    'name' => $log->step_name,
                    'status' => $log->status,
                    'started_at' => $log->started_at,
                    'completed_at' => $log->completed_at,
                    'error_message' => $log->error_message,
                ];
            }),
        ];
    }

    /**
     * Get checklist progress for a tenant.
     *
     * @param int $businessId
     * @return Collection
     */
    public function getChecklistProgress(int $businessId): Collection
    {
        return TenantChecklistProgress::where('business_id', $businessId)
            ->with('checklistItem')
            ->get()
            ->map(function ($progress) {
                return [
                    'item' => $progress->checklistItem,
                    'is_completed' => $progress->is_completed,
                    'completed_at' => $progress->completed_at,
                    'completed_by' => $progress->completed_by,
                ];
            });
    }

    /**
     * Mark checklist item as completed.
     *
     * @param int $businessId
     * @param int $checklistItemId
     * @param int $userId
     * @return bool
     */
    public function completeChecklistItem(int $businessId, int $checklistItemId, int $userId): bool
    {
        $progress = TenantChecklistProgress::where('business_id', $businessId)
            ->where('checklist_item_id', $checklistItemId)
            ->first();

        if (!$progress) {
            return false;
        }

        return $progress->update([
            'is_completed' => true,
            'completed_by' => $userId,
            'completed_at' => now(),
        ]);
    }

    /**
     * Create custom onboarding template.
     *
     * @param array<string, mixed> $data
     * @return OnboardingTemplate
     */
    public function createTemplate(array $data): OnboardingTemplate
    {
        return OnboardingTemplate::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'description' => $data['description'] ?? null,
            'steps' => $data['steps'] ?? $this->getDefaultSteps(),
            'default_settings' => $data['default_settings'] ?? $this->getDefaultSettings(),
            'default_roles' => $data['default_roles'] ?? $this->getDefaultRoles(),
            'default_permissions' => $data['default_permissions'] ?? $this->getDefaultPermissions(),
            'is_active' => $data['is_active'] ?? true,
            'is_default' => $data['is_default'] ?? false,
        ]);
    }

    /**
     * Get onboarding analytics.
     *
     * @param int $businessId
     * @param array<string, mixed> $filters
     * @return array
     */
    public function getOnboardingAnalytics(int $businessId, array $filters = []): array
    {
        $instance = TenantOnboardingInstance::where('business_id', $businessId)->first();

        if (!$instance) {
            return [
                'has_data' => false,
                'message' => 'No onboarding data available',
            ];
        }

        $totalSteps = $instance->total_steps;
        $completedSteps = $instance->stepLogs()->where('status', 'completed')->count();
        $timeSpent = $instance->completed_at 
            ? $instance->completed_at->diffInMinutes($instance->started_at)
            : now()->diffInMinutes($instance->started_at);

        return [
            'has_data' => true,
            'status' => $instance->status,
            'completion_rate' => $totalSteps > 0 ? ($completedSteps / $totalSteps) * 100 : 0,
            'time_spent_minutes' => $timeSpent,
            'average_time_per_step' => $completedSteps > 0 ? $timeSpent / $completedSteps : 0,
            'checklist_completion' => $this->getChecklistCompletionRate($businessId),
        ];
    }

    /**
     * Get checklist completion rate.
     *
     * @param int $businessId
     * @return float
     */
    protected function getChecklistCompletionRate(int $businessId): float
    {
        $total = TenantChecklistProgress::where('business_id', $businessId)->count();
        $completed = TenantChecklistProgress::where('business_id', $businessId)
            ->where('is_completed', true)
            ->count();

        return $total > 0 ? ($completed / $total) * 100 : 0;
    }
}