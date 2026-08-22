<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Business;
use App\Models\Campaign;
use App\Models\Course;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\JobPost;
use App\Models\LeaveEntitlement;
use App\Models\LeavePeriod;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\OrganogramRole;
use App\Models\PayGrade;
use App\Models\PayrollFormula;
use App\Models\PerformanceCycle;
use App\Models\Project;
use App\Models\Shift;

/**
 * Powers the "Getting Started" checklist on the business dashboard - see
 * GUIDE.md. Every step's done-state is detected live from real data rather
 * than a manually-ticked flag, so it can never drift out of sync with
 * reality: complete the underlying setup action anywhere in the app (e.g.
 * create a department directly, or via import) and the step flips to done
 * the next time this is computed, with no separate "mark complete" step to
 * remember.
 */
class BusinessSetupProgressService
{
    public function hasOrganizationStructure(Business $business): bool
    {
        return OrganogramRole::where('business_id', $business->id)->exists();
    }

    public function hasDepartments(Business $business): bool
    {
        return Department::where('business_id', $business->id)->exists();
    }

    public function hasEmployees(Business $business): bool
    {
        return Employee::where('business_id', $business->id)->exists();
    }

    /**
     * Leave setup is a compound step per GUIDE.md: types and periods exist,
     * holidays/non-working-days have been configured one way or the other,
     * and at least one entitlement has actually been set for an employee.
     */
    public function hasLeaveSetup(Business $business): bool
    {
        return LeaveType::where('business_id', $business->id)->exists()
            && LeavePeriod::where('business_id', $business->id)->exists()
            && $this->hasHolidaysConfigured($business)
            && LeaveEntitlement::where('business_id', $business->id)->exists();
    }

    private function hasHolidaysConfigured(Business $business): bool
    {
        return Holiday::where('business_id', $business->id)->exists()
            || !empty($business->non_working_days);
    }

    public function hasAttendanceSetup(Business $business): bool
    {
        return Shift::where('business_id', $business->id)->exists()
            && Location::where('business_id', $business->id)->exists();
    }

    public function hasPayrollSetup(Business $business): bool
    {
        return PayGrade::where('business_id', $business->id)->exists()
            && PayrollFormula::where('business_id', $business->id)->exists();
    }

    public function hasJobPosts(Business $business): bool
    {
        return JobPost::where('business_id', $business->id)->exists();
    }

    public function hasPerformanceCycle(Business $business): bool
    {
        return PerformanceCycle::where('business_id', $business->id)->exists();
    }

    public function hasCourses(Business $business): bool
    {
        return Course::where('business_id', $business->id)->exists();
    }

    public function hasAssets(Business $business): bool
    {
        return Asset::where('business_id', $business->id)->exists();
    }

    public function hasProjects(Business $business): bool
    {
        return Project::where('business_id', $business->id)->exists();
    }

    public function hasCampaigns(Business $business): bool
    {
        return Campaign::where('business_id', $business->id)->exists();
    }

    /**
     * Steps gated by an optional module the business may or may not have
     * selected - each entry's 'module' is the Module::slug (the same slug
     * Business::hasModule() and EnsureBusinessModuleActive check), so a
     * step only ever appears for a business that actually picked that
     * module. Order here mirrors the order these modules appear in the
     * nav. "Core HR Management" and Time & Attendance are deliberately not
     * represented here - neither is actually gated behind module selection
     * anywhere in the app (every business can reach Employees/Leave/
     * Attendance regardless of subscription), so those 5 steps stay
     * unconditional in progressFor() below rather than being made to
     * depend on a selection that doesn't actually restrict anything.
     */
    private function moduleGatedSteps(Business $business): array
    {
        return [
            [
                'key' => 'payroll',
                'module' => 'payroll-management',
                'label' => 'Payroll setup',
                'description' => 'Set up pay grades and at least one payroll formula.',
                'route' => route('business.pay-grades.index', $business->slug),
                'done' => $this->hasPayrollSetup($business),
            ],
            [
                'key' => 'recruitment',
                'module' => 'recruitment-onboarding',
                'label' => 'Recruitment setup',
                'description' => 'Post your first job to start tracking applicants.',
                'route' => route('business.recruitment.jobs.index', $business->slug),
                'done' => $this->hasJobPosts($business),
            ],
            [
                'key' => 'performance',
                'module' => 'performance-management',
                'label' => 'Performance setup',
                'description' => 'Create an appraisal cycle so employees can set objectives and KPIs.',
                'route' => route('business.performance.setup.index', $business->slug),
                'done' => $this->hasPerformanceCycle($business),
            ],
            [
                'key' => 'learning',
                'module' => 'learning-management',
                'label' => 'Learning setup',
                'description' => 'Add your first course for employees to enroll in.',
                'route' => route('business.learning.index', $business->slug),
                'done' => $this->hasCourses($business),
            ],
            [
                'key' => 'assets',
                'module' => 'asset-management',
                'label' => 'Asset register',
                'description' => 'Register your first company asset.',
                'route' => route('business.assets.index', $business->slug),
                'done' => $this->hasAssets($business),
            ],
            [
                'key' => 'projects',
                'module' => 'project-management',
                'label' => 'Project setup',
                'description' => 'Create your first project and Kanban board.',
                'route' => route('business.projects.index', $business->slug),
                'done' => $this->hasProjects($business),
            ],
            [
                'key' => 'crm',
                'module' => 'crm-integration',
                'label' => 'CRM setup',
                'description' => 'Create your first campaign to start tracking leads.',
                'route' => route('business.crm.campaigns.index', $business->slug),
                'done' => $this->hasCampaigns($business),
            ],
        ];
    }

    /**
     * The ordered checklist for this business - id, label, description,
     * target route, and current done-state for each relevant step. The
     * first 5 steps are always shown (see moduleGatedSteps() docblock);
     * everything after is dynamic, appearing only for a module this
     * specific business has actually selected (Business::hasModule()).
     */
    public function progressFor(Business $business): array
    {
        $steps = [
            [
                'key' => 'organization_structure',
                'label' => 'Organization structure',
                'description' => 'Define roles, reporting lines, and assign the first positions.',
                'route' => route('business.organization-structure.index', $business->slug),
                'done' => $this->hasOrganizationStructure($business),
            ],
            [
                'key' => 'departments',
                'label' => 'Departments',
                'description' => 'Create the departments your employees will belong to.',
                'route' => route('business.departments.index', $business->slug),
                'done' => $this->hasDepartments($business),
            ],
            [
                'key' => 'employees',
                'label' => 'Add employees',
                'description' => 'Add your first employees to the system.',
                'route' => route('business.employees.index', $business->slug),
                'done' => $this->hasEmployees($business),
            ],
            [
                'key' => 'leave',
                'label' => 'Leave setup',
                'description' => 'Set up leave types and periods, configure holidays/non-working days, then set entitlements.',
                'route' => route('business.leave.types', $business->slug),
                'done' => $this->hasLeaveSetup($business),
            ],
            [
                'key' => 'attendance',
                'label' => 'Attendance setup',
                'description' => 'Define shifts and work locations.',
                'route' => route('business.shifts.index', $business->slug),
                'done' => $this->hasAttendanceSetup($business),
            ],
        ];

        foreach ($this->moduleGatedSteps($business) as $step) {
            if ($business->hasModule($step['module'])) {
                unset($step['module']);
                $steps[] = $step;
            }
        }

        return $steps;
    }
}
