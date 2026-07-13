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
    <input type="text" name="full_name" value="{{ old('full_name', $lead?->full_name) }}" required
           class="{{ $inputClass }}" placeholder="{{ __('admin.lead_full_name_placeholder') }}">
    @error('full_name')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
</div>

{{-- phone --}}
<div>
    <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.phone') }}</label>
    <input type="tel" name="phone" value="{{ old('phone', $lead?->phone) }}" required
           class="{{ $inputClass }}" placeholder="09123456789">
    @error('phone')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
</div>

{{-- email --}}
<div>
    <label class="mb-1.5 block text-sm font-medium text-gray-300">
        {{ __('admin.email') }} <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
    </label>
    <input type="email" name="email" value="{{ old('email', $lead?->email) }}" class="{{ $inputClass }}">
    @error('email')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
</div>

{{-- age --}}
<div>
    <label class="mb-1.5 block text-sm font-medium text-gray-300">
        {{ __('admin.age') }} <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
    </label>
    <input type="number" name="age" min="1" max="120" value="{{ old('age', $lead?->age) }}" class="{{ $inputClass }}">
    @error('age')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
</div>

{{-- source --}}
<div>
    <label class="mb-1.5 block text-sm font-medium text-gray-300">{{ __('admin.source') }}</label>
    @php $currentSource = old('source', $lead?->source?->value); @endphp
    <select name="source" required class="{{ $inputClass }}">
        <option value="">{{ __('admin.select') }}</option>
        @foreach (\App\Enums\LeadSourceEnum::cases() as $src)
            <option value="{{ $src->value }}" {{ $currentSource === $src->value ? 'selected' : '' }}>{{ $src->label() }}</option>
        @endforeach
    </select>
    @error('source')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
</div>

{{-- priority --}}
<div>
    <label class="mb-1.5 block text-sm font-medium text-gray-300">
        {{ __('admin.priority') }} <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
    </label>
    @php $currentPriority = old('priority', $lead?->priority?->value ?? 'medium'); @endphp
    <select name="priority" class="{{ $inputClass }}">
        @foreach (\App\Enums\LeadPriorityEnum::cases() as $p)
            <option value="{{ $p->value }}" {{ $currentPriority === $p->value ? 'selected' : '' }}>{{ $p->label() }}</option>
        @endforeach
    </select>
    @error('priority')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
</div>

{{-- preferred_instrument_id --}}
<div>
    <label class="mb-1.5 block text-sm font-medium text-gray-300">
        {{ __('admin.preferred_instrument') }} <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
    </label>
    @php $currentInstrument = old('preferred_instrument_id', $lead?->preferred_instrument_id); @endphp
    <select name="preferred_instrument_id" class="{{ $inputClass }}">
        <option value="">{{ __('admin.select_instrument') }}</option>
        @foreach ($instruments as $instrument)
            <option value="{{ $instrument->id }}" {{ (string) $currentInstrument === (string) $instrument->id ? 'selected' : '' }}>{{ $instrument->display_name }}</option>
        @endforeach
    </select>
    @error('preferred_instrument_id')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
</div>

{{-- preferred_teacher_id --}}
<div>
    <label class="mb-1.5 block text-sm font-medium text-gray-300">
        {{ __('admin.preferred_teacher') }} <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
    </label>
    @php $currentTeacher = old('preferred_teacher_id', $lead?->preferred_teacher_id); @endphp
    <select name="preferred_teacher_id" class="{{ $inputClass }}">
        <option value="">{{ __('admin.select_teacher') }}</option>
        @foreach ($teachers as $teacher)
            <option value="{{ $teacher->id }}" {{ (string) $currentTeacher === (string) $teacher->id ? 'selected' : '' }}>{{ $teacher->full_name }}</option>
        @endforeach
    </select>
    @error('preferred_teacher_id')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
</div>

{{-- assigned_to --}}
<div>
    <label class="mb-1.5 block text-sm font-medium text-gray-300">
        {{ __('admin.assigned_admin') }} <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
    </label>
    @php $currentAssignee = old('assigned_to', $lead?->assigned_to); @endphp
    <select name="assigned_to" class="{{ $inputClass }}">
        <option value="">{{ __('admin.unassigned') }}</option>
        @foreach ($assignees as $admin)
            <option value="{{ $admin->id }}" {{ (string) $currentAssignee === (string) $admin->id ? 'selected' : '' }}>{{ $admin->full_name }}</option>
        @endforeach
    </select>
    @error('assigned_to')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
</div>

{{-- next_follow_up_at --}}
<div>
    <label class="mb-1.5 block text-sm font-medium text-gray-300">
        {{ __('admin.next_follow_up') }} <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
    </label>
    @php
        $currentFollowUp = old('next_follow_up_at', $lead?->next_follow_up_at?->format('Y-m-d\TH:i'));
    @endphp
    <input type="datetime-local" name="next_follow_up_at" value="{{ $currentFollowUp }}" class="{{ $inputClass }}">
    @error('next_follow_up_at')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
</div>

{{-- notes --}}
<div>
    <label class="mb-1.5 block text-sm font-medium text-gray-300">
        {{ __('admin.notes') }} <span class="text-gray-500 text-xs">({{ __('admin.optional') }})</span>
    </label>
    <textarea name="notes" rows="3" class="{{ $inputClass }}" placeholder="{{ __('admin.optional_notes') }}">{{ old('notes', $lead?->notes) }}</textarea>
    @error('notes')<p class="mt-1 text-sm text-red-400">{{ $message }}</p>@enderror
</div>
