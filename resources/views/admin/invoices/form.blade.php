{{--
    Invoice form body — shared by create & edit.
    Expects: $action, $method, $submitLabel, $students, $enrollments, $itemsSeed, $taxSeed,
             $studentId, $enrollmentId, $issueDate, $dueDate, $notes.
    Phase: Billing.
--}}
{{-- Loading_State owner of this Record_Form --}}
<x-admin.form-state>
<form method="POST" action="{{ $action }}" class="space-y-6" x-data="invoiceForm(@js($itemsSeed), @js($taxSeed))">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    {{-- Header fields --}}
    <div class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
        <div class="border-b border-gray-800/60 px-6 py-4">
            <h2 class="text-lg font-semibold text-amber-100">{{ __('admin.invoice_details') }}</h2>
        </div>
        <div class="grid grid-cols-1 gap-5 px-6 py-6 sm:grid-cols-2">
            <div>
                <label for="student_id" class="mb-1 block text-xs font-medium text-gray-400">{{ __('admin.student') }}</label>
                <select id="student_id" name="student_id" required class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                    <option value="">{{ __('admin.select_student') }}</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}" {{ (string) old('student_id', $studentId) === (string) $student->id ? 'selected' : '' }}>{{ $student->full_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="enrollment_id" class="mb-1 block text-xs font-medium text-gray-400">
                    {{ __('admin.enrollment') }} <span class="text-gray-600">({{ __('admin.optional') }})</span>
                </label>
                <select id="enrollment_id" name="enrollment_id" class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                    <option value="">{{ __('admin.select_enrollment') }}</option>
                    @foreach ($enrollments as $enrollment)
                        <option value="{{ $enrollment->id }}" {{ (string) old('enrollment_id', $enrollmentId) === (string) $enrollment->id ? 'selected' : '' }}>
                            {{ $enrollment->student?->full_name ?? '—' }} — {{ $enrollment->instrument?->display_name ?? '—' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="issue_date" class="mb-1 block text-xs font-medium text-gray-400">{{ __('admin.issue_date') }}</label>
                <input id="issue_date" name="issue_date" type="date" required value="{{ old('issue_date', $issueDate) }}"
                       class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            </div>

            <div>
                <label for="due_date" class="mb-1 block text-xs font-medium text-gray-400">{{ __('admin.due_date') }}</label>
                <input id="due_date" name="due_date" type="date" required value="{{ old('due_date', $dueDate) }}"
                       class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            </div>

            <div>
                <label for="tax" class="mb-1 block text-xs font-medium text-gray-400">{{ __('admin.tax') }} ({{ __('admin.currency_toman') }})</label>
                <input id="tax" name="tax" type="number" min="0" step="1" x-model.number="tax"
                       class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
            </div>

            <div class="sm:col-span-2">
                <label for="notes" class="mb-1 block text-xs font-medium text-gray-400">{{ __('admin.notes') }}</label>
                <textarea id="notes" name="notes" rows="3" placeholder="{{ __('admin.optional_notes') }}"
                          class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-4 py-2.5 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">{{ old('notes', $notes) }}</textarea>
            </div>
        </div>
    </div>

    {{-- Line items --}}
    <div class="overflow-hidden rounded-2xl border border-gray-800/60 bg-gray-900/50 shadow-xl backdrop-blur-sm">
        <div class="flex items-center justify-between border-b border-gray-800/60 px-6 py-4">
            <h2 class="text-lg font-semibold text-amber-100">{{ __('admin.invoice_items') }}</h2>
            <button type="button" x-on:click="addItem()" class="rounded-lg border border-amber-500/40 px-3 py-1.5 text-xs font-medium text-amber-300 transition hover:bg-amber-500/10">
                {{ __('admin.add_item') }}
            </button>
        </div>

        <div class="space-y-4 px-6 py-6">
            <template x-for="(item, index) in items" :key="index">
                <div class="grid grid-cols-1 gap-4 rounded-xl border border-gray-800/60 bg-gray-900/40 p-4 sm:grid-cols-12">
                    <div class="sm:col-span-4">
                        <label class="mb-1 block text-xs font-medium text-gray-400" :for="'item-title-' + index">{{ __('admin.item_title') }}</label>
                        <input type="text" required maxlength="255"
                               :id="'item-title-' + index"
                               :name="'items[' + index + '][title]'"
                               x-model="item.title"
                               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-3 py-2 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="mb-1 block text-xs font-medium text-gray-400" :for="'item-description-' + index">{{ __('admin.item_description') }}</label>
                        <input type="text" maxlength="500"
                               :id="'item-description-' + index"
                               :name="'items[' + index + '][description]'"
                               x-model="item.description"
                               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-3 py-2 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                    </div>
                    <div class="sm:col-span-1">
                        <label class="mb-1 block text-xs font-medium text-gray-400" :for="'item-quantity-' + index">{{ __('admin.quantity') }}</label>
                        <input type="number" min="1" step="1" required
                               :id="'item-quantity-' + index"
                               :name="'items[' + index + '][quantity]'"
                               x-model.number="item.quantity"
                               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-3 py-2 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-gray-400" :for="'item-price-' + index">{{ __('admin.unit_price') }}</label>
                        <input type="number" min="0" step="1" required
                               :id="'item-price-' + index"
                               :name="'items[' + index + '][unit_price]'"
                               x-model.number="item.unit_price"
                               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-3 py-2 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                    </div>
                    <div class="sm:col-span-1">
                        <label class="mb-1 block text-xs font-medium text-gray-400" :for="'item-discount-' + index">{{ __('admin.discount') }}</label>
                        <input type="number" min="0" step="1"
                               :id="'item-discount-' + index"
                               :name="'items[' + index + '][discount]'"
                               x-model.number="item.discount"
                               class="block w-full rounded-lg border border-gray-700 bg-gray-800/50 px-3 py-2 text-sm text-gray-100 transition focus:border-amber-500/50 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                    </div>
                    <div class="flex items-end justify-between gap-3 sm:col-span-1">
                        <span class="text-xs text-gray-400" x-text="money(lineTotal(item))"></span>
                        <button type="button" x-on:click="removeItem(index)"
                                class="rounded-lg px-2 py-1 text-xs text-red-400 transition hover:text-red-300"
                                :aria-label="'{{ __('admin.remove_item') }} ' + (index + 1)">
                            ✕
                        </button>
                    </div>
                </div>
            </template>

            {{-- Totals --}}
            <div class="flex flex-col items-end gap-1 border-t border-gray-800/60 pt-4 text-sm">
                <div class="flex gap-4">
                    <span class="text-gray-500">{{ __('admin.subtotal') }}</span>
                    <span class="text-gray-200" x-text="money(subtotal)"></span>
                </div>
                <div class="flex gap-4">
                    <span class="text-gray-500">{{ __('admin.discount') }}</span>
                    <span class="text-gray-200" x-text="money(discountTotal)"></span>
                </div>
                <div class="flex gap-4">
                    <span class="text-gray-500">{{ __('admin.total_amount') }}</span>
                    <span class="font-semibold text-amber-200" x-text="money(total)"></span>
                </div>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-4">
        <x-admin.submit-button :label="$submitLabel" class="shadow-amber-500/10" />
        <a href="{{ route('admin.invoices.index') }}" class="rounded-lg border border-gray-700 px-6 py-2.5 text-sm font-medium text-gray-400 transition hover:border-gray-600 hover:text-gray-200">
            {{ __('admin.cancel') }}
        </a>
    </div>
</form>
</x-admin.form-state>
