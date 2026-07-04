# Requirements Document

## UI Phase 5A: Premium Modern UI

## Introduction

This document specifies UI/UX improvements for the Parsian Music admin panel, transforming the existing dashboard with professional, modern aesthetics. Phase 5A focuses exclusively on visual enhancements through CSS utilities, Typography, and component styling—no backend changes, database modifications, or new routes. All changes go into resources/css/app.css with Tailwind utilities; no separate CSS modules.

## Glossary

- **Vazirmatn Font**: Open-source Persian typeface designed for screen readability; supports weights 300, 400, 600, 700
- **Glass Effect**: Semi-transparent background with backdrop blur creating a layered depth visual
- **RTL**: Right-to-Left layout for Persian/Arabic content
- **Stat Card**: Dashboard KPI display showing metric, trend, and visual progress indicator
- **Zebra Styling**: Alternating row colors in tables for improved readability
- **8px Grid**: Spacing system where all padding/margin values align to 8px multiples
- **Blade Template**: Laravel's templating engine for view files
- **Alpine.js**: Lightweight JavaScript framework for reactive components
- **Tailwind CSS**: Utility-first CSS framework

## Requirements

### Requirement 1: Vazirmatn Font Integration

**User Story:** As a user viewing the admin panel, I want all text rendered in Vazirmatn font with proper Persian rendering, so that the interface feels professional and readable in my language.

#### Acceptance Criteria

1. WHEN the dashboard loads, THE Font_System SHALL load Vazirmatn from CDN with weights 300, 400, 600, 700 without blocking page render
2. WHEN any text element displays, THE Font_System SHALL apply Vazirmatn as the primary font family with graceful fallback to system fonts
3. WHEN Persian content displays, THE Font_System SHALL enforce line-height of 1.8+ for optimal Persian text spacing
4. WHEN Persian text renders, THE Font_System SHALL apply letter-spacing of 0.5–0.75px for Persian-specific typography adjustments
5. WHILE Persian headings display, THE Typography_System SHALL apply font-weight 700 and line-height 1.6 for visual hierarchy
6. IF a font fails to load, THEN THE Font_System SHALL fall back to system fonts without breaking layout
7. WHERE Persian numerals are marked with `.persian-numerals` class, THE Font_System SHALL render Persian digit variants using font-feature-settings

### Requirement 2: Dashboard Visual Redesign

**User Story:** As an admin, I want the dashboard stat cards to feel modern and elevated with visual depth, so that the interface conveys professionalism and encourages engagement.

#### Acceptance Criteria

1. WHEN the dashboard loads, THE Stat_Card_System SHALL render all KPI cards with glass effect (semi-transparent background + backdrop blur)
2. WHEN a user hovers over a stat card, THE Stat_Card_System SHALL apply subtle elevation (translate-y -1px) and enhanced glow effect
3. WHEN stat cards render, THE Visual_Hierarchy_System SHALL apply rounded corners of 2xl (16px) and soft shadows (shadow-xl with 20px blur)
4. WHEN a stat card displays, THE Stat_Card_System SHALL show a trend indicator (up/down arrow or percentage) in the top-right corner
5. WHEN stat cards display on mobile, THE Responsive_System SHALL stack in 1 column; on tablet 2 columns; on desktop 4 columns (grid-cols-1 sm:grid-cols-2 xl:grid-cols-4)
6. WHILE a card hovers, THE Animation_System SHALL transition card state over 300ms with ease-in-out timing
7. IF a trend value is positive, THEN THE Visual_Indicator_System SHALL display an up arrow in green; if negative, display down arrow in red

### Requirement 3: Sidebar Polish

**User Story:** As a user navigating the panel, I want smooth sidebar interactions and clear visual feedback, so that navigation feels responsive and modern.

#### Acceptance Criteria

1. WHEN the sidebar collapses/expands, THE Sidebar_System SHALL animate state change over 300ms with ease-in-out timing
2. WHEN a user hovers over a nav item, THE Navigation_System SHALL apply hover state with background highlight and text color change to amber-300
3. WHEN a nav item is active, THE Navigation_System SHALL display with ring (ring-1 ring-amber-500/20) and amber background
4. WHEN the sidebar is collapsed, THE Icon_Contrast_System SHALL ensure icons remain visible with sufficient color contrast (amber-400)
5. WHILE the sidebar is expanded, THE Collapse_Control_System SHALL display a collapse button that toggles the `collapsed` state via Alpine.js
6. WHERE the sidebar persists state, THE State_System SHALL save collapse state to localStorage as `sidebarCollapsed` boolean
7. IF sidebar is collapsed on mobile < 1024px, THEN THE Layout_System SHALL hide sidebar entirely; on desktop ≥ 1024px, THE Layout_System SHALL show sidebar with width from CSS variable `--sidebar-width`

### Requirement 4: Table UX Improvements

**User Story:** As an admin reviewing data tables, I want enhanced table styling and interactions, so that data is easy to scan and understand.

#### Acceptance Criteria

1. WHEN a table renders, THE Table_System SHALL apply sticky positioning to thead so headers remain visible on scroll
2. WHEN table rows display, THE Zebra_Styling_System SHALL apply alternating background colors (even rows: bg-gray-800/30, odd rows: transparent)
3. WHEN a user hovers over a table row, THE Row_Highlight_System SHALL apply hover background (hover:bg-gray-800/20) with smooth transition
4. WHEN a table displays a paginated list, THE Pagination_UI SHALL render with buttons styled consistently with dashboard button patterns
5. WHEN a search input displays in tables, THE Search_Input_System SHALL apply consistent styling matching other form inputs with amber accents
6. WHERE column headers are sortable, THE Sort_Indicator_System SHALL display sort arrows (↑ or ↓) indicating sort direction
7. IF a table is empty, THEN THE Empty_State_Display SHALL show a placeholder message or redirect to empty-state component
8. WHILE tables are enhanced, THE Audit_System SHALL verify which table views are actually used in the application before applying styling to avoid unused component modifications

### Requirement 5: Empty State Components

**User Story:** As a user, I want to see helpful empty states when no data is available, so that I understand what to do next.

#### Acceptance Criteria

1. WHEN a table/list has no data, THE Empty_State_Component SHALL render with icon placeholder (SVG or Unicode icon)
2. WHEN an empty state displays, THE Empty_State_Component SHALL show a clear, concise message describing the empty condition
3. WHEN an empty state shows, THE Empty_State_Component SHALL optionally display a call-to-action button styled with primary colors (amber gradient)
4. WHEN empty states display, THE Style_Consistency_System SHALL apply the same glass effect and rounded corners as dashboard cards
5. WHILE empty states display across the app, THE Visual_System SHALL maintain consistent padding (24–32px) and spacing with surrounding components

### Requirement 6: Cards & Components Enhancement

**User Story:** As a designer reviewing the interface, I want all cards and components to have a cohesive modern aesthetic, so that the entire UI feels polished.

#### Acceptance Criteria

1. WHEN a card component renders, THE Card_System SHALL apply glass effect (semi-transparent bg + backdrop-blur-sm)
2. WHEN cards display, THE Border_System SHALL apply subtle borders (border border-gray-800/60) instead of heavy outlines
3. WHEN cards render, THE Corner_System SHALL apply rounded-2xl (16px) border-radius consistently
4. WHEN cards display shadows, THE Shadow_System SHALL use soften shadows (shadow-xl shadow-black/20) with 20px blur for depth
5. WHERE components need visual separation, THE Spacing_System SHALL apply padding and margins aligned to 8px grid (8, 16, 24, 32, 40px)
6. IF a component has hover state, THEN THE Interaction_System SHALL apply subtle translate, scale, or shadow changes over 300ms

### Requirement 7: Spacing & Typography System

**User Story:** As a developer, I want consistent spacing and typography guidelines, so that layouts are predictable and maintainable.

#### Acceptance Criteria

1. WHEN layout spacing is applied, THE Spacing_System SHALL enforce 8px grid multiples (8, 16, 24, 32, 40, 48px padding/margin)
2. WHEN headings render in Persian, THE Typography_System SHALL apply line-height 1.6 and font-weight 700
3. WHEN body text renders in Persian, THE Typography_System SHALL apply line-height 1.8 for improved readability
4. WHEN Persian text displays, THE Typography_System SHALL apply letter-spacing 0.5–0.75px for proper glyph spacing
5. WHILE typography scales across breakpoints, THE Responsive_Typography_System SHALL maintain readability on mobile (base), tablet (sm), and desktop (lg/xl) sizes

### Requirement 8: Shadows & Glass Effects (Subtle, Performance-Conscious)

**User Story:** As a user, I want visual depth and modern aesthetics without sacrificing readability, performance, or device responsiveness, so that the interface feels contemporary yet functional on all hardware.

#### Acceptance Criteria

1. WHEN glass effects apply, THE Glass_System SHALL render with minimal semi-transparency (backdrop-blur-sm only; blur-sm = 4px maximum)
2. WHEN glass effects render, THE Blur_System SHALL never exceed backdrop-blur-sm (4px); NO backdrop-blur, backdrop-blur-md, or backdrop-blur-lg values allowed
3. WHEN shadows display, THE Shadow_System SHALL use soft, minimal blur values (shadow-xl maximum with black/10–15 opacity); NO stacked shadows or heavy blur-radius
4. WHEN animations execute, THE Animation_System SHALL enforce 300ms maximum duration with ease-in-out timing; NO animations above 300ms
5. WHILE components render, THE Performance_Constraint_System SHALL ensure dashboard remains smooth (60 FPS target) on low-end hardware; no jank or lag
6. WHERE effects apply, THE Readability_Priority_System SHALL always prioritize readability and performance over visual effects; glass effect must enhance, not obscure

### Requirement 9: CSS Organization (Single File)

**User Story:** As a maintainer, I want clear CSS structure without fragmentation, so that styling code is organized and maintainable in a single source.

#### Acceptance Criteria

1. WHEN CSS loads, THE Module_System SHALL define all new styles within resources/css/app.css using @layer directives (base, components, utilities)
2. WHERE glass effects are used, THE Glass_Utilities_System SHALL provide reusable inline Tailwind utilities (backdrop-blur-sm, bg-opacity adjustments) without separate CSS files
3. WHEN typography applies, THE Typography_Module SHALL define Persian-specific styles inline in app.css @layer base with :lang(fa) selectors
4. WHERE RTL layouts render, THE RTL_Module SHALL apply directional fixes inline in app.css using logical properties (margin-inline-start, flex-row-reverse) and the <html dir> attribute
5. WHILE Tailwind processes CSS, THE Build_System SHALL extend tailwind.config.js with custom glass utilities and color variants only if necessary; prefer standard Tailwind utilities

## Properties for Acceptance Testing

### Property 1: Glass Effect Consistency
For ALL elements with `.glass` class, the effective background must be semi-transparent (opacity 0.04–0.12) AND backdrop-blur value must be applied (blur-sm or greater). Test with 50+ random RGB colors to verify glass effect remains readable on all backgrounds.

### Property 2: Typography Rendering
For ALL Persian text elements, line-height MUST be ≥ 1.8 AND letter-spacing MUST be within range [0.5px, 0.75px]. Verify across all font weights (300, 400, 600, 700) and heading levels (h1–h6).

### Property 3: Spacing Grid Alignment
For ALL padding and margin values in modified templates, values MUST be multiples of 8px (8, 16, 24, 32, 40, 48). Audit all new @layer components for spacing compliance.

### Property 4: Hover State Idempotence
For ALL interactive components, applying hover state twice (e.g., mouseenter → mouseleave → mouseenter) MUST produce identical visual result as first hover. Verify state returns to non-hover appearance after leave.

### Property 5: RTL Layout Symmetry
For ALL components, RTL layout (dir="rtl") MUST produce exact visual mirror of LTR layout. Test sidebar collapse, modal positioning, card alignment on both directions.

### Property 6: Card Elevation Round-Trip
For ANY card component: (1) measure initial shadow blur, (2) apply hover state, (3) remove hover—final state MUST match initial shadow exactly (non-destructive hover).

### Property 7: Empty State Accessibility
For ALL empty states, the component MUST contain: (1) icon/visual placeholder, (2) text message, (3) optional CTA button. Verify all three elements present across 20+ different empty-state scenarios.

### Property 8: Responsive Grid Consistency
For stat card grid, on mobile (< 640px) MUST be 1 column, on tablet (640–1023px) MUST be 2 columns, on desktop (≥ 1024px) MUST be 4 columns. Test with 15+ viewport widths.

### Property 9: Animation Timing Consistency
For ALL animations (sidebar toggle, hover states, transitions), timing MUST be exactly 300ms with ease-in-out curve. Audit CSS transitions and Alpine.js animations for uniform timing.

### Property 10: Font Weight Rendering
For Persian headings, font-weight MUST be 700. For body text, font-weight MUST be 400. For secondary text, font-weight MAY be 300 or 400. Verify Vazirmatn loads all required weights (300, 400, 600, 700).
