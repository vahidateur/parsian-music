{{--
    Admin Navigation — shared list used by desktop sidebar and mobile drawer.
    Props: items (array with route, active, label, icon, children?).
    Phase: 0.5 — Admin Foundation.
    Slots: none.
--}}
@props([
    'items' => [],
])

<ul class="admin-navigation">
    @foreach ($items as $index => $item)
        @php
            $hasChildren = !empty($item['children']);
            $isActive = request()->routeIs($item['active']);
            $isChildActive = false;
            if ($hasChildren) {
                foreach ($item['children'] as $child) {
                    if (request()->routeIs($child['active'])) {
                        $isChildActive = true;
                        break;
                    }
                }
            }
        @endphp
        <li class="admin-navigation__item" @if($hasChildren) x-data="{ open: {{ $isChildActive ? 'true' : 'false' }} }" @endif>
            @if ($hasChildren)
                <button
                    type="button"
                    class="admin-navigation__link{{ ($isActive || $isChildActive) ? ' admin-navigation__link--active' : '' }}"
                    @click="open = !open"
                    :aria-expanded="open.toString()"
                    aria-label="{{ $item['label'] }}"
                    title="{{ $item['label'] }}"
                >
                    <span class="admin-navigation__icon" aria-hidden="true">{{ $item['icon'] }}</span>
                    <span class="admin-navigation__label" x-show="!collapsed || mobileOpen" x-cloak>{{ $item['label'] }}</span>
                    <span class="admin-navigation__chevron" x-show="!collapsed || mobileOpen" x-cloak :class="{ 'admin-navigation__chevron--open': open }" aria-hidden="true">
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                            <path d="M3 4.5L6 7.5L9 4.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </button>

                <ul class="admin-navigation__submenu" x-show="open && (!collapsed || mobileOpen)" x-cloak
                    x-transition:enter="admin-navigation__submenu-enter"
                    x-transition:enter-start="admin-navigation__submenu-enter-start"
                    x-transition:enter-end="admin-navigation__submenu-enter-end"
                    x-transition:leave="admin-navigation__submenu-leave"
                    x-transition:leave-start="admin-navigation__submenu-leave-start"
                    x-transition:leave-end="admin-navigation__submenu-leave-end"
                >
                    @foreach ($item['children'] as $child)
                        @php($isSubActive = request()->routeIs($child['active']))
                        <li class="admin-navigation__subitem">
                            <a
                                href="{{ route($child['route']) }}"
                                class="admin-navigation__sublink{{ $isSubActive ? ' admin-navigation__sublink--active' : '' }}"
                                @if ($isSubActive) aria-current="page" @endif
                            >
                                @if (!empty($child['icon']))
                                    <span class="admin-navigation__subicon" aria-hidden="true">{{ $child['icon'] }}</span>
                                @endif
                                <span class="admin-navigation__sublabel">{{ $child['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                <a
                    href="{{ route($item['route']) }}"
                    class="admin-navigation__link{{ $isActive ? ' admin-navigation__link--active' : '' }}"
                    @if ($isActive) aria-current="page" @endif
                    aria-label="{{ $item['label'] }}"
                    title="{{ $item['label'] }}"
                >
                    <span class="admin-navigation__icon" aria-hidden="true">{{ $item['icon'] }}</span>
                    <span class="admin-navigation__label" x-show="!collapsed || mobileOpen" x-cloak>{{ $item['label'] }}</span>
                </a>
            @endif
        </li>
    @endforeach
</ul>
