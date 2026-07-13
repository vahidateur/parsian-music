{{--
/**
 * Form Field Component
 * 
 * Complete form field wrapper with label, input container, validation, and hint.
 * Generic component used across all forms in the academy.
 * 
 * Architecture:
 * FormField (this component)
 *   ├── Label
 *   ├── InputContainer
 *   │    ├── Input (slot)
 *   │    └── Icon (optional slot)
 *   ├── Validation Message (error)
 *   └── Hint (helper text)
 * 
 * @props
 * - name: Field name (required, used for label/input association)
 * - label: Label text (optional)
 * - hint: Helper text below input (optional)
 * - error: Error message (optional)
 * - required: Boolean, shows required indicator (default: false)
 * - dir: Text direction (default: 'rtl')
 * - class: Additional CSS classes for wrapper
 * 
 * @slots
 * - default: Input element (required)
 * - icon: Icon element (optional, positioned absolutely)
 * 
 * @example
 * x:ui.form-field name="phone" label="شماره موبایل" required>
 *     x:ui.input name="phone" type="tel" placeholder="09123456789" />
 *     x:slot:icon>
 *         <i data-lucide="phone" class="w-5 h-5"></i>
 *     </x-slot:icon>
 * </x-ui.form-field>
 * 
 * x:ui.form-field 
 *     name="email" 
 *     label="ایمیل" 
 *     hint="برای بازیابی رمز عبور استفاده می‌شود"
 *     error="{{ $errors->first('email') }}"
 * >
 *     x:ui.input name="email" type="email" />
 * </x-ui.form-field>
 * 
 * @accessibility
 * - Label associated with input via for/id
 * - Required fields marked visually and semantically
 * - Error messages linked with aria-describedby
 * - Hint text provides additional context
 */
--}}

@props([
    'name',
    'label' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'dir' => 'rtl',
])

<div {{ $attributes->merge(['class' => 'flex flex-col [gap:var(--space-2)]']) }} dir="{{ $dir }}">
    
    {{-- Label --}}
    @if($label)
        <label 
            for="{{ $name }}"
            class="[font-size:var(--text-sm)] font-medium text-[var(--text-secondary)]"
        >
            {{ $label }}
            @if($required)
                <span class="text-[var(--error-500)]" aria-label="required">*</span>
            @endif
        </label>
    @endif
    
    {{-- Input Container (for icon positioning) --}}
    <div class="relative flex items-center">
        
        {{-- Input (slot) --}}
        {{ $slot }}
        
        {{-- Icon (optional slot) --}}
        @isset($icon)
            <span class="absolute right-5 text-[var(--icon-default)] pointer-events-none">
                {{ $icon }}
            </span>
        @endisset
        
    </div>
    
    {{-- Error Message --}}
    @if($error)
        <p 
            class="[font-size:var(--text-sm)] text-[var(--error-500)] flex items-center [gap:var(--space-1)]"
            id="{{ $name }}-error"
            role="alert"
        >
            <i data-lucide="alert-circle" class="[width:var(--icon-sm)] [height:var(--icon-sm)]"></i>
            <span>{{ $error }}</span>
        </p>
    @endif
    
    {{-- Hint --}}
    @if($hint && !$error)
        <p 
            class="[font-size:var(--text-sm)] text-[var(--text-tertiary)]"
            id="{{ $name }}-hint"
        >
            {{ $hint }}
        </p>
    @endif
    
</div>
