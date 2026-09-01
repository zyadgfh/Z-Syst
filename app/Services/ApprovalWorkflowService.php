<?php

namespace App\Services;

use App\Models\ApprovalStep;
use App\Models\ApprovalTemplate;
use App\Models\ApprovalWorkflow;
use Illuminate\Support\Facades\DB;

class ApprovalWorkflowService
{
    public function createWorkflow(string $type, int $entityId, int $businessId, int $createdBy): ApprovalWorkflow
    {
        return DB::transaction(function () use ($type, $entityId, $businessId, $createdBy) {
            $template = ApprovalTemplate::where('type', $type)
                ->where('business_id', $businessId)
                ->where('is_default', true)
                ->first();

            $workflow = ApprovalWorkflow::create([
                'type' => $type,
                'entity_id' => $entityId,
                'business_id' => $businessId,
                'current_step' => 1,
                'status' => 'pending',
                'created_by' => $createdBy,
            ]);

            if ($template) {
                $steps = json_decode($template->steps_config, true);
                foreach ($steps as $stepConfig) {
                    ApprovalStep::create([
                        'workflow_id' => $workflow->id,
                        'step_number' => $stepConfig['step_number'],
                        'approver_role' => $stepConfig['approver_role'],
                        'status' => 'pending',
                    ]);
                }
            }

            return $workflow;
        });
    }

    public function approveStep(ApprovalWorkflow $workflow, int $stepNumber, int $approverId, ?string $notes = null): ApprovalWorkflow
    {
        return DB::transaction(function () use ($workflow, $stepNumber, $approverId, $notes) {
            $step = $workflow->steps()->where('step_number', $stepNumber)->first();
            $step->update([
                'approver_id' => $approverId,
                'status' => 'approved',
                'approved_at' => now(),
                'notes' => $notes,
            ]);

            $nextStep = $workflow->steps()->where('step_number', '>', $stepNumber)->orderBy('step_number')->first();

            if ($nextStep) {
                $workflow->update(['current_step' => $nextStep->step_number]);
            } else {
                $workflow->update(['status' => 'approved']);
            }

            return $workflow->fresh();
        });
    }

    public function rejectStep(ApprovalWorkflow $workflow, int $stepNumber, int $approverId, string $reason): ApprovalWorkflow
    {
        return DB::transaction(function () use ($workflow, $stepNumber, $approverId, $reason) {
            $step = $workflow->steps()->where('step_number', $stepNumber)->first();
            $step->update([
                'approver_id' => $approverId,
                'status' => 'rejected',
                'notes' => $reason,
            ]);

            $workflow->update(['status' => 'rejected']);

            return $workflow->fresh();
        });
    }

    public function getPendingWorkflows(int $businessId)
    {
        return ApprovalWorkflow::forBusiness($businessId)
            ->pending()
            ->with(['steps', 'createdBy'])
            ->latest()
            ->get();
    }
}
