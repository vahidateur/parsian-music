<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\ConvertLeadData;
use App\Enums\LeadPriorityEnum;
use App\Enums\LeadSourceEnum;
use App\Enums\LeadStatusEnum;
use App\Enums\RoleEnum;
use App\Enums\SkillLevelEnum;
use App\Http\Controllers\Controller;
use App\Models\Instrument;
use App\Models\Lead;
use App\Models\Teacher;
use App\Models\User;
use App\Services\LeadService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeadController extends Controller
{
    private const SORTABLE = ['full_name', 'phone', 'status', 'priority', 'source', 'created_at', 'next_follow_up_at'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Lead::class);

        $sortCol = in_array($request->sort, self::SORTABLE, true) ? $request->sort : 'created_at';
        $sortDir = $request->direction === 'asc' ? 'asc' : 'desc';

        $query = Lead::query()->with(['assignedUser', 'preferredInstrument', 'preferredTeacher']);

        if ($request->filled('full_name')) {
            $query->where('full_name', 'like', "%{$request->full_name}%");
        }

        if ($request->filled('phone')) {
            $query->where('phone', 'like', "%{$request->phone}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $leads = $query->orderBy($sortCol, $sortDir)->paginate(15)->withQueryString();

        $assignees = User::whereIn('role', [RoleEnum::ADMIN, RoleEnum::SUPER_ADMIN])->orderBy('full_name')->get();

        return view('admin.leads.index', compact('leads', 'sortCol', 'sortDir', 'assignees'));
    }

    public function kanban(Request $request): View
    {
        $this->authorize('viewAny', Lead::class);

        $leads = Lead::query()
            ->with(['assignedUser', 'preferredInstrument'])
            ->latest()
            ->get()
            ->groupBy(fn (Lead $lead) => $lead->status->value);

        $columns = LeadStatusEnum::cases();

        return view('admin.leads.kanban', compact('leads', 'columns'));
    }

    public function create(): View
    {
        $this->authorize('create', Lead::class);

        [$instruments, $teachers, $assignees] = $this->formOptions();

        return view('admin.leads.create', compact('instruments', 'teachers', 'assignees'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Lead::class);

        $validated = $request->validate($this->rules());

        $validated['status'] = LeadStatusEnum::New->value;
        $validated['priority'] = $validated['priority'] ?? LeadPriorityEnum::Medium->value;

        Lead::create($validated);

        return redirect()->route('admin.leads.index')
            ->with('success', __('admin.lead_created_successfully'));
    }

    public function show(Lead $lead): View
    {
        $this->authorize('view', $lead);

        $lead->load(['assignedUser', 'preferredInstrument', 'preferredTeacher', 'convertedStudent']);

        $assignees = User::whereIn('role', [RoleEnum::ADMIN, RoleEnum::SUPER_ADMIN])->orderBy('full_name')->get();

        return view('admin.leads.show', compact('lead', 'assignees'));
    }

    public function edit(Lead $lead): View
    {
        $this->authorize('update', $lead);

        [$instruments, $teachers, $assignees] = $this->formOptions();

        return view('admin.leads.edit', compact('lead', 'instruments', 'teachers', 'assignees'));
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);

        $validated = $request->validate($this->rules());
        unset($validated['status']);

        $lead->update($validated);

        return redirect()->route('admin.leads.show', $lead)
            ->with('success', __('admin.lead_updated_successfully'));
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $this->authorize('delete', $lead);

        $lead->delete();

        return redirect()->route('admin.leads.index')
            ->with('success', __('admin.lead_deleted_successfully'));
    }

    public function assign(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);

        $validated = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $lead->update($validated);

        return back()->with('success', __('admin.lead_assigned_successfully'));
    }

    public function scheduleFollowUp(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);

        $validated = $request->validate([
            'next_follow_up_at' => ['required', 'date'],
        ]);

        $lead->update($validated);

        return back()->with('success', __('admin.lead_followup_scheduled_successfully'));
    }

    public function updateStatus(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorize('update', $lead);

        $validated = $request->validate([
            'status' => ['required', Rule::in(LeadStatusEnum::values())],
        ]);

        try {
            $lead->transitionTo(LeadStatusEnum::from($validated['status']));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', __('admin.lead_status_updated_successfully'));
    }

    public function convert(Request $request, Lead $lead, LeadService $service): RedirectResponse
    {
        $this->authorize('convert', $lead);

        $validated = $request->validate([
            'skill_level' => ['nullable', Rule::in(SkillLevelEnum::values())],
            'start_date'  => ['nullable', 'date'],
            'notes'       => ['nullable', 'string'],
        ]);

        $data = new ConvertLeadData(
            skillLevel: isset($validated['skill_level']) ? SkillLevelEnum::from($validated['skill_level']) : null,
            startDate: isset($validated['start_date']) ? \Carbon\Carbon::parse($validated['start_date']) : null,
            notes: $validated['notes'] ?? null,
        );

        try {
            $student = $service->convert($lead, $data);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.students.show', $student)
            ->with('success', __('admin.lead_converted_successfully'));
    }

    private function rules(): array
    {
        return [
            'full_name'                => ['required', 'string', 'max:255'],
            'phone'                    => ['required', 'string', 'max:20'],
            'email'                    => ['nullable', 'email', 'max:255'],
            'age'                      => ['nullable', 'integer', 'min:1', 'max:120'],
            'preferred_instrument_id'  => ['nullable', 'exists:instruments,id'],
            'preferred_teacher_id'     => ['nullable', 'exists:teachers,id'],
            'source'                   => ['required', Rule::in(LeadSourceEnum::values())],
            'priority'                 => ['nullable', Rule::in(LeadPriorityEnum::values())],
            'assigned_to'              => ['nullable', 'exists:users,id'],
            'notes'                    => ['nullable', 'string'],
            'next_follow_up_at'        => ['nullable', 'date'],
        ];
    }

    private function formOptions(): array
    {
        $instruments = Instrument::active()->orderBy('name_fa')->orderBy('name')->get();
        $teachers = Teacher::active()->orderBy('full_name')->get();
        $assignees = User::whereIn('role', [RoleEnum::ADMIN, RoleEnum::SUPER_ADMIN])->orderBy('full_name')->get();

        return [$instruments, $teachers, $assignees];
    }
}
