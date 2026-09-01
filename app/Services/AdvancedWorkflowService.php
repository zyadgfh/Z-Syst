<?php

namespace App\Services;

use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use App\Models\WorkflowApproval;
use App\Models\WorkflowHistory;
use App\Models\WorkflowDelegation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdvancedWorkflowService
{
    /**
     * Create a new workflow instance
     */
    public function createInstance(
        string $entityType,
        int $entityId,
        int $businessId,
        int $initiatedBy,
        ?string $code = null,
        ?array $metadata = null
    ): WorkflowInstance {
        return DB::transaction(function () use ($entityType, $entityId, $businessId, $initiatedBy, $code, $metadata) {
            // Find workflow definition
            $definition = $this->getActiveDefinition($entityType, $businessId, $code);
            
            if (!$definition) {
                throw new \Exception("No active workflow definition found for {$entityType}");
            }

            // Create workflow instance
            $instance = WorkflowInstance::create([
                'business_id' => $businessId,
                'workflow_definition_id' => $definition->id,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'status' => 'pending',
                'initiated_by' => $initiatedBy,
                'initiated_at' => now(),
                'metadata' => $metadata,
            ]);

            // Get first step
            $firstStep = $definition->steps()->ordered()->first();
            
            if ($firstStep) {
                $instance->update([
                    'current_step_id' => $firstStep->id,
                    'status' => 'in_progress',
                ]);

                // Create pending approvals for the first step
                $this->createStepApprovals($instance, $firstStep);
            } else {
                // No steps required, auto-approve
                $instance->update([
                    'status' => 'approved',
                    'completed_at' => now(),
                ]);
            }

            // Log history
            $this->logHistory($instance, 'initiated', 'Workflow instance created', $initiatedBy);

            return $instance->load(['definition', 'currentStep', 'initiatedBy']);
        });
    }

    /**
     * Approve a workflow step
     */
    public function approveStep(
        WorkflowInstance $instance,
        int $stepId,
        int $userId,
        ?string $comments = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): WorkflowInstance {
        return DB::transaction(function () use ($instance, $stepId, $userId, $comments, $ipAddress, $userAgent) {
            $step = WorkflowStep::findOrFail($stepId);
            
            // Verify this is the current step
            if ($instance->current_step_id !== $step->id) {
                throw new \Exception("Cannot approve a step that is not the current step");
            }

            // Create or update approval
            $approval = WorkflowApproval::updateOrCreate(
                [
                    'workflow_instance_id' => $instance->id,
                    'workflow_step_id' => $step->id,
                    'user_id' => $userId,
                ],
                [
                    'status' => 'approved',
                    'comments' => $comments,
                    'approved_at' => now(),
                ]
            );

            // Check if step is fully approved based on approval type
            $stepFullyApproved = $this->checkStepApproval($instance, $step);

            if ($stepFullyApproved) {
                // Check if this is the final step
                if ($step->is_final) {
                    $instance->update([
                        'status' => 'approved',
                        'completed_at' => now(),
                        'current_step_id' => null,
                    ]);

                    // Execute final actions
                    $this->executeStepActions($instance, $step);

                    $this->logHistory($instance, 'approved', 'Workflow instance approved', $userId, $ipAddress, $userAgent);
                } else {
                    // Move to next step
                    $nextStep = $instance->getNextStep();
                    
                    if ($nextStep) {
                        $instance->update([
                            'current_step_id' => $nextStep->id,
                        ]);

                        // Create approvals for next step
                        $this->createStepApprovals($instance, $nextStep);

                        // Execute step actions
                        $this->executeStepActions($instance, $step);

                        $this->logHistory($instance, 'step_changed', "Moved to step: {$nextStep->name}", $userId, $ipAddress, $userAgent);
                    } else {
                        // No more steps, approve
                        $instance->update([
                            'status' => 'approved',
                            'completed_at' => now(),
                            'current_step_id' => null,
                        ]);

                        $this->logHistory($instance, 'approved', 'Workflow instance approved (no more steps)', $userId, $ipAddress, $userAgent);
                    }
                }
            }

            return $instance->fresh()->load(['definition', 'currentStep', 'approvals']);
        });
    }

    /**
     * Reject a workflow instance
     */
    public function rejectInstance(
        WorkflowInstance $instance,
        int $userId,
        string $reason,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): WorkflowInstance {
        return DB::transaction(function () use ($instance, $userId, $reason, $ipAddress, $userAgent) {
            if ($instance->isCompleted()) {
                throw new \Exception("Cannot reject a completed workflow instance");
            }

            // Update current step approval to rejected
            if ($instance->currentStep) {
                WorkflowApproval::updateOrCreate(
                    [
                        'workflow_instance_id' => $instance->id,
                        'workflow_step_id' => $instance->currentStep->id,
                        'user_id' => $userId,
                    ],
                    [
                        'status' => 'rejected',
                        'comments' => $reason,
                        'rejected_at' => now(),
                    ]
                );
            }

            $instance->update([
                'status' => 'rejected',
                'completed_at' => now(),
            ]);

            $this->logHistory($instance, 'rejected', $reason, $userId, $ipAddress, $userAgent);

            return $instance->fresh();
        });
    }

    /**
     * Cancel a workflow instance
     */
    public function cancelInstance(
        WorkflowInstance $instance,
        int $userId,
        ?string $reason = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): WorkflowInstance {
        return DB::transaction(function () use ($instance, $userId, $reason, $ipAddress, $userAgent) {
            if ($instance->isCompleted()) {
                throw new \Exception("Cannot cancel a completed workflow instance");
            }

            $instance->update([
                'status' => 'cancelled',
                'completed_at' => now(),
            ]);

            $this->logHistory($instance, 'cancelled', $reason ?? 'Workflow cancelled', $userId, $ipAddress, $userAgent);

            return $instance->fresh();
        });
    }

    /**
     * Get pending workflows for a business
     */
    public function getPendingWorkflows(int $businessId)
    {
        return WorkflowInstance::forBusiness($businessId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->with(['definition', 'currentStep', 'initiatedBy', 'approvals'])
            ->latest('initiated_at')
            ->get();
    }

    /**
     * Get workflows requiring approval for a user
     */
    public function getWorkflowsForApproval(int $userId, int $businessId)
    {
        // Check for active delegations
        $delegations = WorkflowDelegation::active()
            ->forBusiness($businessId)
            ->where('to_user_id', $userId)
            ->get();

        $delegatedUserIds = $delegations->pluck('from_user_id')->push($userId);

        return WorkflowInstance::forBusiness($businessId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereHas('currentStep.approvers', function ($query) use ($delegatedUserIds) {
                $query->whereIn('user_id', $delegatedUserIds);
            })
            ->whereDoesntHave('approvals', function ($query) use ($delegatedUserIds) {
                $query->whereIn('user_id', $delegatedUserIds)
                    ->where('workflow_step_id', DB::raw('workflow_instances.current_step_id'));
            })
            ->with(['definition', 'currentStep', 'initiatedBy'])
            ->latest('initiated_at')
            ->get();
    }

    /**
     * Create a workflow delegation
     */
    public function createDelegation(
        int $businessId,
        int $fromUserId,
        int $toUserId,
        string $startDate,
        ?string $endDate = null,
        ?string $reason = null
    ): WorkflowDelegation {
        return WorkflowDelegation::create([
            'business_id' => $businessId,
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => $reason,
            'is_active' => true,
        ]);
    }

    /**
     * Get active workflow definition
     */
    protected function getActiveDefinition(string $entityType, int $businessId, ?string $code = null): ?WorkflowDefinition
    {
        $query = WorkflowDefinition::active()
            ->forBusiness($businessId)
            ->forEntityType($entityType);

        if ($code) {
            $query->where('code', $code);
        }

        return $query->first();
    }

    /**
     * Create pending approvals for a step
     */
    protected function createStepApprovals(WorkflowInstance $instance, WorkflowStep $step): void
    {
        $approvers = $step->approvers()->ordered()->get();

        foreach ($approvers as $approver) {
            WorkflowApproval::create([
                'workflow_instance_id' => $instance->id,
                'workflow_step_id' => $step->id,
                'user_id' => $approver->user_id,
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Check if a step is fully approved based on approval type
     */
    protected function checkStepApproval(WorkflowInstance $instance, WorkflowStep $step): bool
    {
        $approvals = $instance->approvals()
            ->where('workflow_step_id', $step->id)
            ->get();

        $requiredApprovers = $step->approvers()->required()->count();
        $approvedCount = $approvals->where('status', 'approved')->count();

        switch ($step->approval_type) {
            case 'single':
                return $approvedCount >= 1;
            case 'any':
                return $approvedCount >= 1;
            case 'all':
                return $approvedCount >= $requiredApprovers;
            case 'majority':
                return $approvedCount > ($requiredApprovers / 2);
            default:
                return $approvedCount >= 1;
        }
    }

    /**
     * Execute step actions
     */
    protected function executeStepActions(WorkflowInstance $instance, WorkflowStep $step): void
    {
        if ($step->actions) {
            foreach ($step->actions as $action) {
                try {
                    $this->executeAction($instance, $action);
                } catch (\Exception $e) {
                    Log::error("Failed to execute workflow action", [
                        'instance_id' => $instance->id,
                        'step_id' => $step->id,
                        'action' => $action,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Execute a single action
     */
    protected function executeAction(WorkflowInstance $instance, array $action): void
    {
        $type = $action['type'] ?? null;
        
        switch ($type) {
            case 'notify':
                // Send notification
                $this->sendNotification($instance, $action);
                break;
            case 'update_entity':
                // Update entity status
                $this->updateEntityStatus($instance, $action);
                break;
            case 'create_task':
                // Create task
                $this->createTask($instance, $action);
                break;
            default:
                Log::warning("Unknown workflow action type: {$type}");
        }
    }

    /**
     * Send notification
     */
    protected function sendNotification(WorkflowInstance $instance, array $action): void
    {
        // Implementation depends on notification system
        Log::info("Sending notification for workflow instance {$instance->id}");
    }

    /**
     * Update entity status
     */
    protected function updateEntityStatus(WorkflowInstance $instance, array $action): void
    {
        $status = $action['status'] ?? null;
        $entityClass = $this->getEntityClass($instance->entity_type);
        
        if ($entityClass && $status) {
            $entity = $entityClass::find($instance->entity_id);
            if ($entity && isset($entity->status)) {
                $entity->update(['status' => $status]);
            }
        }
    }

    /**
     * Create task
     */
    protected function createTask(WorkflowInstance $instance, array $action): void
    {
        // Implementation depends on task system
        Log::info("Creating task for workflow instance {$instance->id}");
    }

    /**
     * Get entity class from entity type
     */
    protected function getEntityClass(string $entityType): ?string
    {
        $mapping = [
            'purchase_order' => \App\Models\Purchase::class,
            'sale' => \App\Models\Sale::class,
            'prescription' => \App\Models\Prescription::class,
        ];

        return $mapping[$entityType] ?? null;
    }

    /**
     * Log workflow history
     */
    protected function logHistory(
        WorkflowInstance $instance,
        string $action,
        string $description,
        ?int $userId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        WorkflowHistory::create([
            'workflow_instance_id' => $instance->id,
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }
}
