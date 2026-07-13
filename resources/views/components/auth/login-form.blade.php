{{--
/**
 * Login Form Component (Auth Domain)
 * 
 * Composes generic form components for login page.
 * This component ONLY arranges UI primitives - NO custom styling.
 * 
 * @props
 * - action: Form action URL (default: route('login'))
 * - method: Form method (default: 'POST')
 * - class: Additional CSS classes for wrapper
 * 
 * @composition
 * Uses these generic UI components:
 * - x:ui.form-field />
 * - x:ui.input />
 * - x:ui.checkbox />
 * 
 * @example
 * x:auth.login-form />
 * 
 * @accessibility
 * - Semantic form element
 * - Proper label associations
 * - Error messages linked to inputs
 * - Required fields marked
 */
--}}

@props([
    'action' => null,
    'method' => 'POST',
])

<section {{ $attributes->merge(['class' => 'flex flex-col [gap:var(--space-3)]']) }}>
    
    <form 
        action="{{ $action ?? route('login') }}"
        method="{{ $method }}"
        class="flex flex-col [gap:var(--space-3)]"
    >
        @csrf
        
        {{-- Phone Number Field --}}
        <x-ui.form-field 
            name="phone" 
            label="شماره موبایل" 
            :error="$errors->first('phone')"
            required
        >
            <x-ui.input 
                name="phone" 
                type="tel" 
                placeholder="09123456789"
                autocomplete="tel"
                :hasIcon="true"
                :hasError="$errors->has('phone')"
                required
            />
            
            <x-slot:icon>
                <i data-lucide="phone" class="[width:var(--icon-sm)] [height:var(--icon-sm)]"></i>
            </x-slot:icon>
        </x-ui.form-field>
        
        {{-- Password Field --}}
        <x-ui.form-field 
            name="password" 
            label="رمز عبور" 
            :error="$errors->first('password')"
            required
        >
            <x-ui.input 
                name="password" 
                type="password" 
                placeholder="رمز عبور خود را وارد کنید"
                autocomplete="current-password"
                :hasIcon="true"
                :hasError="$errors->has('password')"
                required
            />
            
            <x-slot:icon>
                <i data-lucide="lock" class="[width:var(--icon-sm)] [height:var(--icon-sm)]"></i>
            </x-slot:icon>
        </x-ui.form-field>
        
        {{-- Remember Me & Forgot Password Row --}}
        <div class="flex items-center justify-between">
            
            {{-- Remember Checkbox --}}
            <x-ui.checkbox 
                name="remember" 
                label="مرا به خاطر بسپار"
                :checked="old('remember')"
            />
            
            {{-- Forgot Password Link --}}
            <a 
                href="{{ route('password.request') }}"
                class="[font-size:var(--text-sm)]
                       text-[var(--gold-300)]
                       hover:text-[var(--gold-200)]
                       transition-colors
                       [transition-duration:var(--duration-fast)]"
            >
                فراموشی رمز عبور
            </a>
            
        </div>
        
        {{-- Submit Button --}}
        <x-ui.button 
            variant="primary" 
            type="submit" 
            :fullWidth="true"
            class="mt-2"
        >
            ورود به آموزشگاه
        </x-ui.button>
        
    </form>
    
</section>
