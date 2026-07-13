{{--
/**
 * Login Card Component (Auth Domain)
 * 
 * Complete login card that composes all auth components.
 * This component ONLY arranges domain components - NO custom styling.
 * 
 * @props
 * - class: Additional CSS classes for container wrapper
 * 
 * @composition
 * Uses these components:
 * - x:ui.glass.card /> (UI primitive)
 * - x:auth.login-header /> (Domain component)
 * - x:auth.login-form /> (Domain component)
 * - x:auth.login-social /> (Domain component)
 * - x:auth.login-footer /> (Domain component)
 * 
 * @example
 * x:auth.login-card />
 * 
 * @accessibility
 * - Proper landmark structure
 * - Logical content hierarchy
 * - Focus management within card
 */
--}}

<div {{ $attributes->merge(['class' => 'login-card-container flex items-center justify-center min-h-screen p-4']) }}>
    
    <x-ui.glass.card id="login-card">
        
        <div id="login-card-content" class="flex flex-col h-full">
            
            {{-- Header: Logo, Title, Subtitle --}}
            <x-auth.login-header />
            
            {{-- Spacing --}}
            <div class="[height:var(--space-5)]"></div>
            
            {{-- Form: Inputs, Remember, Submit --}}
            <x-auth.login-form />
            
            {{-- Spacing --}}
            <div class="[height:var(--space-4)]"></div>
            
            {{-- Social Login --}}
            <x-auth.login-social />
            
            {{-- Spacing --}}
            <div class="[height:var(--space-4)]"></div>
            
            {{-- Footer: Quote --}}
            <x-auth.login-footer />
            
        </div>
        
    </x-ui.glass.card>
    
</div>
