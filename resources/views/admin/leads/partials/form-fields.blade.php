{{--
    Shared Lead form fields.
    Expects: $lead (Lead|null), $instruments, $teachers, $assignees
--}}
@php
    $inputClass = "block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-3 text-sm text-gray-100 placeholder-gray-500 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20";
@endphp

{{-- full_name --}}
<div>
    <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.full_name') }}</label>
    <input type="text" name="full_name" {{ feedback_field_attributes('full_name') }} value="{{ old('full_name', $lead?->full_name) }}" required
           class="{{ $inputClass }}" placeholder="{{ __('admin.lead_full_name_placeholder') }}">
    <x-admin.feedback field="full_name" />
</div>

{{-- phone --}}
<div>
    <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.phone') }}</label>
    <input type="tel" name="phone" {{ feedback_field_attributes('phone') }} value="{{ old('phone', $lead?->phone) }}" required
           class="{{ $inputClass }}" placeholder="09123456789">
    <x-admin.feedback field="phone" />
</div>

{{-- email --}}
<div>
    <label class="mb-1.5 block text-sm font-medium text-gray-300">
        {{ __('admin.email') }} <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
    </label>
    <input type="email" name="email" {{ feedback_field_attributes('email') }} value="{{ old('email', $lead?->email) }}" class="{{ $inputClass }}">
    <x-admin.feedback field="email" />
</div>

{{-- age --}}
<div>
    <label class="mb-1.5 block text-sm font-medium text-gray-300">
        {{ __('admin.age') }} <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
    </label>
    <input type="number" name="age" {{ feedback_field_attributes('age') }} min="1" max="120" value="{{ old('age', $lead?->age) }}" class="{{ $inputClass }}">
    <x-admin.feedback field="age" />
</div>

{{-- source --}}
<div>
    <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.source') }}</label>
    @php $currentSource = old('source', $lead?->source?->value); @endphp
    <select name="source" {{ feedback_field_attributes('source') }} required class="{{ $inputClass }}">
        <option value="">{{ __('admin.select') }}</option>
        @foreach (\App\Enums\LeadSourceEnum::cases() as $src)
            <option value="{{ $src->value }}" {{ $currentSource === $src->value ? 'selected' : '' }}>{{ $src->label() }}</option>
        @endforeach
    </select>
    <x-admin.feedback field="source" />
</div>

{{-- priority --}}
<div>
    <label class="mb-1.5 block text-sm font-medium text-gray-300">
        {{ __('admin.priority') }} <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
    </label>
    @php $currentPriority = old('priority', $lead?->priority?->value ?? 'medium'); @endphp
    <select name="priority" {{ feedback_field_attributes('priority') }} class="{{ $inputClass }}">
        @foreach (\App\Enums\LeadPriorityEnum::cases() as $p)
            <option value="{{ $p->value }}" {{ $currentPriority === $p->value ? 'selected' : '' }}>{{ $p->label() }}</option>
        @endforeach
    </select>
    <x-admin.feedback field="priority" />
</div>

{{-- preferred_instrument_id --}}
<div>
    <label class="mb-1.5 block text-sm font-medium text-gray-300">
        {{ __('admin.preferred_instrument') }} <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
    </label>
    @php $currentInstrument = old('preferred_instrument_id', $lead?->preferred_instrument_id); @endphp
    <select name="preferred_instrument_id" {{ feedback_field_attributes('preferred_instrument_id') }} class="{{ $inputClass }}">
        <option value="">{{ __('admin.select_instrument') }}</option>
        @foreach ($instruments as $instrument)
            <option value="{{ $instrument->id }}" {{ (string) $currentInstrument === (string) $instrument->id ? 'selected' : '' }}>{{ $instrument->display_name }}</option>
        @endforeach
    </select>
    <x-admin.feedback field="preferred_instrument_id" />
</div>

{{-- preferred_teacher_id --}}
<div>
    <label class="mb-1.5 block text-sm font-medium text-gray-300">
        {{ __('admin.preferred_teacher') }} <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
    </label>
    @php $currentTeacher = old('preferred_teacher_id', $lead?->preferred_teacher_id); @endphp
    <select name="preferred_teacher_id" {{ feedback_field_attributes('preferred_teacher_id') }} class="{{ $inputClass }}">
        <option value="">{{ __('admin.select_teacher') }}</option>
        @foreach ($teachers as $teacher)
            <option value="{{ $teacher->id }}" {{ (string) $currentTeacher === (string) $teacher->id ? 'selected' : '' }}>{{ $teacher->full_name }}</option>
        @endforeach
    </select>
    <x-admin.feedback field="preferred_teacher_id" />
</div>

{{-- assigned_to --}}
<div>
    <label class="mb-1.5 block text-sm font-medium text-gray-300">
        {{ __('admin.assigned_admin') }} <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
    </label>
    @php $currentAssignee = old('assigned_to', $lead?->assigned_to); @endphp
    <select name="assigned_to" {{ feedback_field_attributes('assigned_to') }} class="{{ $inputClass }}">
        <option value="">{{ __('admin.unassigned') }}</option>
        @foreach ($assignees as $admin)
            <option value="{{ $admin->id }}" {{ (string) $currentAssignee === (string) $admin->id ? 'selected' : '' }}>{{ $admin->full_name }}</option>
        @endforeach
    </select>
    <x-admin.feedback field="assigned_to" />
</div>

{{-- next_follow_up_at --}}
<div>
    <label class="mb-1.5 block text-sm font-medium text-gray-300">
        {{ __('admin.next_follow_up') }} <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
    </label>
    @php
        $currentFollowUp = old('next_follow_up_at', $lead?->next_follow_up_at?->format('Y-m-d\TH:i'));
    @endphp
    <input type="datetime-local" name="next_follow_up_at" {{ feedback_field_attributes('next_follow_up_at') }} value="{{ $currentFollowUp }}" class="{{ $inputClass }}">
    <x-admin.feedback field="next_follow_up_at" />
</div>

{{-- notes --}}
<div>
    <label class="mb-1.5 block text-sm font-medium text-gray-300">
        {{ __('admin.notes') }} <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
    </label>
    <textarea name="notes" {{ feedback_field_attributes('notes') }} rows="3" class="{{ $inputClass }}" placeholder="{{ __('admin.optional_notes') }}">{{ old('notes', $lead?->notes) }}</textarea>
    <x-admin.feedback field="notes" />
</div>
