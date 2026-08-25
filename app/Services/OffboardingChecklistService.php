<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeContractAction;
use App\Models\OffboardingChecklist;

class OffboardingChecklistService
{
    private const DEFAULT_TASKS = [
        ['task_key' => 'asset_return', 'name' => 'Company Asset Return'],
        ['task_key' => 'access_revocation', 'name' => 'System & Access Revocation'],
        ['task_key' => 'exit_interview', 'name' => 'Exit Interview'],
        ['task_key' => 'final_settlement', 'name' => 'Final Settlement / Clearance'],
        ['task_key' => 'knowledge_handover', 'name' => 'Knowledge Handover'],
    ];

    public function createForTermination(Employee $employee, EmployeeContractAction $contractAction): OffboardingChecklist
    {
        $checklist = OffboardingChecklist::create([
            'business_id' => $contractAction->business_id,
            'employee_id' => $employee->id,
            'contract_action_id' => $contractAction->id,
            'status' => 'in_progress',
            'initiated_at' => now(),
        ]);

        foreach (self::DEFAULT_TASKS as $i => $task) {
            $checklist->tasks()->create([
                'business_id' => $contractAction->business_id,
                'task_key' => $task['task_key'],
                'name' => $task['name'],
                'sequence_order' => $i + 1,
            ]);
        }

        return $checklist;
    }
}
