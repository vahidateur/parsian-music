# Teacher Hero Skeleton — Phase 1 Design

## Overview

This design establishes the foundational UI architecture for the Teacher Hero component—a responsive, mobile-first Blade component system for displaying teacher profiles. Phase 1 focuses purely on layout structure and component organization using CSS Grid, with no business logic, animations, or real imagery.

**Scope**: Component skeleton, layout grid system, mock data integration, responsive breakpoints.

**Out of Scope**: Backend integration, database queries, images, animations, form submission handlers, professional details, scheduling, reviews.

---

## Architecture

### Component Hierarchy

```
teacher/show.blade.php (View)
  ├─ Base Layout (navbar, breadcrumb)
  └─ <x-ui.teacher.hero :teacher="$teacher" />
      ├─ <x-ui.teacher.portrait />
      ├─ <x-ui.teacher.teacher-info :teacher="$teacher" />
      │   ├─ <x-ui.teacher.experience-badge :experience="$teacher['experience']" />
      │   └─ @foreach($teacher['instruments']) 
      │       <x-ui.teacher.instrument-chip :instrument="$instrument" />
      │       @endforeach
      └─ <x-ui.teacher.cta-button label="Request Class" />
```

### Data Flow

1. **Mock Data Source**: `teacher/show.blade.php` passes hardcoded `$teacher` array
2. **Hero Container**: Receives `$teacher`, distributes to child components via props
3. **Child Components**: Accept specific props (name, title, experience, instruments), render isolated
4. **No State Management**: All data flows one-way from parent to children; no component state

### Design Principles

- **Mobile First**: Base layout is mobile (12 columns stacked). Desktop adds multi-column layout.
- **CSS Grid Only**: No Flexbox. 12-column grid for consistency across all viewports.
- **Semantic HTML**: Buttons are `<button>` elements, not divs. Proper heading hierarchy.
- **Modular Components**: Each component is self-contained and independently testable.
- **No Magic**: Styling is explicit, no implicit behaviors or assumptions.
- **Frozen Layout**: Design is locked after Phase 1 to ensure Phase 2 additions don't break structure.

---

## Components and Interfaces

### 1. Hero Component (`hero.blade.php`)

**Purpose**: Main container orchestrating layout and child component rendering.

**Props**:
- `$teacher` (array): Teacher data object with keys: name, title, experience, instruments

**Responsibilities**:
- Create 12-column CSS Grid container
- Define grid layout strategy (mobile vs. desktop)
- Render all child components
- Pass relevant data to children

**Blade Structure**:
```blade
<div class="grid grid-cols-12 gap-0">
  {{-- Background: 12 cols mobile, 8 cols desktop --}}
  <x-ui.teacher.portrait />
  
  {{-- Info section: 12 cols mobile, 4 cols desktop --}}
  <x-ui.teacher.teacher-info :teacher="$teacher" />
  
  {{-- CTA: 12 cols mobile/desktop (within info section) --}}
  <x-ui.teacher.cta-button />
</div>
```

**CSS Grid Layout**:
- **Mobile (< 768px)**: All components span 12 columns, stacked vertically
- **Desktop (≥ 768px)**: Background 8 cols, Info section 4 cols

---

### 2. Portrait Component (`portrait.blade.php`)

**Purpose**: Placeholder for teacher profile image (Phase 2).

**Props**: None

**Responsibilities**:
- Render empty div with square aspect ratio
- Maintain consistent sizing across breakpoints

**Blade Structure**:
```blade
<div class="aspect-square min-h-[200px] bg-gray-100"></div>
```

**Layout**:
- Mobile: 12 columns (full width)
- Desktop: 4 columns (within info section)

---

### 3. Teacher Info Component (`teacher-info.blade.php`)

**Purpose**: Container for name, title, experience badge, and instrument chips.

**Props**:
- `$teacher` (array): Contains name, title, experience, instruments

**Responsibilities**:
- Display teacher name as heading
- Display teacher title
- Render experience badge
- Render instrument chips via loop
- Structure information hierarchy

**Blade Structure**:
```blade
<div class="flex flex-col gap-4">
  <h1>{{ $teacher['name'] }}</h1>
  <p>{{ $teacher['title'] }}</p>
  
  <x-ui.teacher.experience-badge :experience="$teacher['experience']" />
  
  <div class="flex flex-wrap gap-2">
    @foreach($teacher['instruments'] as $instrument)
      <x-ui.teacher.instrument-chip :instrument="$instrument" />
    @endforeach
  </div>
</div>
```

**Layout**:
- Mobile: 12 columns (full width below portrait)
- Desktop: 4 columns (within info section)

---

### 4. Experience Badge Component (`experience-badge.blade.php`)

**Purpose**: Display teacher's years of experience as a distinct visual element.

**Props**:
- `$experience` (string): Experience text (e.g., "۱۰ سال تجربه")

**Responsibilities**:
- Render experience text
- Apply consistent styling
- No icon or image required

**Blade Structure**:
```blade
<div class="badge badge-lg">
  {{ $experience }}
</div>
```

---

### 5. Instrument Chip Component (`instrument-chip.blade.php`)

**Purpose**: Display individual instrument as a tag/chip.

**Props**:
- `$instrument` (string): Instrument name (e.g., "ویولن")

**Responsibilities**:
- Render instrument text
- Apply consistent chip styling
- No icon or image required

**Blade Structure**:
```blade
<span class="chip">
  {{ $instrument }}
</span>
```

---

### 6. CTA Button Component (`cta-button.blade.php`)

**Purpose**: Primary call-to-action button for requesting a class.

**Props**:
- `$label` (string, default: "Request Class"): Button text

**Responsibilities**:
- Render semantic button element
- Accept customizable label
- No click handler in Phase 1
- Full width on mobile, contained on desktop

**Blade Structure**:
```blade
<button type="button" class="btn btn-primary w-full">
  {{ $label }}
</button>
```

---

## Data Models

### Teacher Mock Data

```php
$teacher = [
    'name' => 'نازنین حسینی',              // Persian name
    'title' => 'مدرس ویولن',              // Persian job title
    'experience' => '۱۰ سال تجربه',        // Persian experience string
    'instruments' => [
        'ویولن',                          // Violin (Persian)
        'سلفژ',                           // Solfège (Persian)
        'موسیقی کلاسیک'                   // Classical Music (Persian)
    ]
];
```

**Usage**:
- Passed from `teacher/show.blade.php` to `hero.blade.php`
- Distributed to child components as needed
- No transformation or processing
- Mock data only—no database queries

---

## CSS Grid Layout Strategy

### Grid Columns: 12-Column System

All components use a 12-column grid foundation for consistency and scalability.

### Mobile Layout (< 768px)

```
┌─────────────────────────┐
│  Background (12 cols)   │  400px min-height
├─────────────────────────┤
│  Portrait (12 cols)     │  Square, 200px min
├─────────────────────────┤
│  Teacher Info (12 cols) │  Name, title, exp, chips
├─────────────────────────┤
│  CTA Button (12 cols)   │  Full width
└─────────────────────────┘
```

### Desktop Layout (≥ 768px)

```
┌──────────────────────┬──────────────┐
│  Background (8 cols) │  Portrait(4) │  400px min-height
│                      │  Info (4)    │
│                      ├──────────────┤
│                      │  CTA (4)     │
└──────────────────────┴──────────────┘
```

### Responsive Breakpoints

| Breakpoint | Width | Layout | Columns |
|-----------|-------|--------|---------|
| Mobile | < 768px | Stacked vertical | 12 (all full width) |
| Desktop | ≥ 768px | Side-by-side | 8 + 4 split |

### CSS Utility Classes (Tailwind)

- `grid`: Apply grid display
- `grid-cols-12`: 12-column grid
- `col-span-12`: Full width (mobile)
- `col-span-8`: 8 columns (desktop background)
- `col-span-4`: 4 columns (desktop info section)
- `gap-0`: No gap between grid items
- `aspect-square`: Square portrait ratio
- `min-h-[200px]`: Minimum height constraint
- `min-h-[400px]`: Background minimum height
- `w-full`: Full width CTA button

---

## Error Handling

### Data Validation

Components assume correct input shape:
- `$teacher` always contains required keys
- `$teacher['instruments']` is always an array
- No null/empty checks in Phase 1

**Rationale**: Mock data is hardcoded; error handling deferred to Phase 2.

### Missing Properties

If a property is missing, Blade will render empty strings (no errors):
```blade
{{ $teacher['name'] }}  {{-- Outputs empty if missing --}}
```

---

## Testing Strategy

### Unit Testing (Example-Based)

Test specific component scenarios with concrete mock data:

| Component | Test Cases |
|-----------|-----------|
| Hero | Renders all children, applies grid classes |
| Portrait | Renders square div with min-height |
| Teacher Info | Displays name, title, experience, instruments |
| Experience Badge | Renders experience text correctly |
| Instrument Chip | Renders instrument name correctly |
| Chip Loop | Multiple instruments render multiple chips |
| CTA Button | Renders button with default label, semantic HTML |

**Framework**: Laravel's Blade testing or Pest/PHPUnit with mock data.

**Test Example**:
```php
test('hero component renders all children', function () {
    $view = view('components.ui.teacher.hero', [
        'teacher' => [
            'name' => 'Test Teacher',
            'title' => 'Test Title',
            'experience' => '5 years',
            'instruments' => ['Piano', 'Guitar']
        ]
    ]);
    
    $this->assertStringContainsString('Test Teacher', $view->render());
    $this->assertStringContainsString('Piano', $view->render());
});
```

### Integration Testing

Test layout responsiveness and component interaction:

| Test | Assertion |
|------|-----------|
| Mobile layout | Components stack vertically at < 768px |
| Desktop layout | Background 8 cols, info 4 cols at ≥ 768px |
| Full-page render | Navbar, breadcrumb, hero render together |
| Mock data flow | Data passes through component hierarchy correctly |

### Snapshot Testing (Optional)

Capture rendered HTML snapshots for regression detection:
```php
test('hero snapshot', function () {
    $view = view('components.ui.teacher.hero', ['teacher' => $mockTeacher]);
    expect($view->render())->toMatchSnapshot();
});
```

### Visual Regression Testing (Phase 2)

- Screenshot tests at multiple breakpoints (390px, 768px, 1920px)
- Automated diff detection for layout changes

---

## Implementation Checklist

### Phase 1 Deliverables

- [ ] `resources/views/components/ui/teacher/hero.blade.php`
- [ ] `resources/views/components/ui/teacher/portrait.blade.php`
- [ ] `resources/views/components/ui/teacher/teacher-info.blade.php`
- [ ] `resources/views/components/ui/teacher/experience-badge.blade.php`
- [ ] `resources/views/components/ui/teacher/instrument-chip.blade.php`
- [ ] `resources/views/components/ui/teacher/cta-button.blade.php`
- [ ] `resources/views/teacher/show.blade.php` (with mock data)
- [ ] CSS: 12-column grid system + responsive classes
- [ ] Unit tests for each component
- [ ] Design frozen (no further changes)

### Phase 2+ Enhancements (Out of Scope)

- [ ] Replace portrait placeholder with real image upload
- [ ] Add background image support
- [ ] Implement click handler for CTA button
- [ ] Add animations/transitions
- [ ] Fetch teacher data from database
- [ ] Add professional details section
- [ ] Add schedule/availability display
- [ ] Add student reviews section

---

## Why Property-Based Testing Does NOT Apply

This feature is a **UI component skeleton** with mock data and no transformation logic:

1. **No Algorithms**: No mathematical operations, parsing, or complex logic to verify universally.
2. **No Input Variation Logic**: Components render static data; input variation doesn't reveal edge cases.
3. **No Pure Functions**: Blade components are rendering functions, not pure logic functions.
4. **Layout-Based**: CSS Grid layout is declarative; testing layout requires visual/snapshot tests, not property tests.
5. **Mock Data Only**: No serialization, validation, or transformation that benefits from 100+ iterations.
6. **No State Management**: Stateless components with no invariants to verify across executions.

**Testing Approach**: Unit tests with specific examples + integration tests for layout + snapshot tests for regression. This is appropriate and sufficient for Phase 1.

---

## Summary

The Teacher Hero Skeleton design is a modular, mobile-first Blade component system using a 12-column CSS Grid. Components are stateless, composable, and driven by mock data only. The layout strategy ensures mobile-first responsiveness with a clean desktop experience. Phase 1 is strictly structural—no backend integration, imagery, or animations.
