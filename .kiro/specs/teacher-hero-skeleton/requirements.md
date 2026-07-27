# Teacher Hero Skeleton — Phase 1 Requirements

## Introduction

The Teacher Hero Skeleton is the foundational UI component for displaying teacher profiles in the Parsian Music platform. Phase 1 establishes the mobile-first responsive layout and reusable Blade components with mock data only. No database queries, business logic, or animations are included in this phase.

## Glossary

- **Hero Section**: The primary visual area at the top of a teacher profile page containing background, portrait, information, and CTA
- **Blade Component**: Laravel's reusable view template system for modular UI elements
- **Mock Data**: Hardcoded test data passed directly to components, not from a database or controller
- **CSS Grid Layout**: 12-column grid system for responsive layout (not Flexbox)
- **Mobile First**: Design approach where mobile layout is the base, enhanced with larger breakpoints
- **Placeholder**: Empty div element representing future image or content areas
- **CTA Button**: Call-to-action button ("Request Class")
- **Experience Badge**: Visual indicator displaying teacher's years of experience
- **Instrument Chip**: Tag-style component listing teacher's teaching instruments
- **Portrait**: Placeholder area for teacher's profile image (phase 2+)
- **Background**: Placeholder area for hero section background visual (phase 2+)
- **Breadcrumb**: Navigation trail inherited from base layout
- **Navbar**: Navigation bar inherited from base layout

## Requirements

### Requirement 1: Hero Section Container

**User Story:** As a student, I want to see the teacher hero section displayed, so that I can quickly access key teacher information and profile action.

#### Acceptance Criteria

1. WHEN the teacher profile page loads, THE Hero_Section SHALL render a container with the teacher hero content
2. THE Hero_Section SHALL use CSS Grid with 12 columns as the layout engine
3. THE Hero_Section SHALL accept $teacher mock data as input
4. THE Hero_Section SHALL render all child components (Background, Portrait, Teacher_Info, CTA_Button)
5. WHERE mobile viewport (< 768px), THE Hero_Section SHALL stack components vertically in full width
6. WHERE desktop viewport (≥ 768px), THE Hero_Section SHALL display Background in 8 columns and info section in 4 columns

### Requirement 2: Background Placeholder

**User Story:** As a designer, I want a placeholder for the hero background, so that I can add background imagery in Phase 2.

#### Acceptance Criteria

1. WHEN the Hero_Section renders, THE Background_Placeholder SHALL display as an empty div
2. THE Background_Placeholder SHALL span 8 columns on desktop and 12 columns on mobile
3. THE Background_Placeholder SHALL have a minimum height of 400px
4. THE Background_Placeholder SHALL use CSS Grid placement without absolute positioning

### Requirement 3: Portrait Placeholder

**User Story:** As a designer, I want a placeholder for the teacher portrait, so that I can add profile imagery in Phase 2.

#### Acceptance Criteria

1. WHEN the Hero_Section renders, THE Portrait_Placeholder SHALL display as an empty div
2. THE Portrait_Placeholder SHALL be positioned within the info section
3. THE Portrait_Placeholder SHALL be visible on mobile viewports before teacher information
4. THE Portrait_Placeholder SHALL have a square aspect ratio with minimum dimension of 200px

### Requirement 4: Teacher Information Display

**User Story:** As a student, I want to see teacher name, title, experience, and instruments, so that I can quickly assess the teacher's qualifications.

#### Acceptance Criteria

1. WHEN the Hero_Section renders with $teacher data, THE Teacher_Info_Component SHALL display the teacher's name
2. WHEN the Hero_Section renders with $teacher data, THE Teacher_Info_Component SHALL display the teacher's title
3. WHEN the Hero_Section renders with $teacher data, THE Teacher_Info_Component SHALL display the experience badge
4. WHEN the Hero_Section renders with $teacher data, THE Teacher_Info_Component SHALL display instrument chips
5. THE Teacher_Info_Component SHALL receive name, title, experience, and instruments from $teacher mock data
6. THE Teacher_Info_Component SHALL render on mobile in full width below the portrait
7. THE Teacher_Info_Component SHALL render on desktop in the 4-column info section

### Requirement 5: Experience Badge Display

**User Story:** As a student, I want to see the teacher's years of experience, so that I can evaluate their expertise.

#### Acceptance Criteria

1. WHEN Teacher_Info_Component renders, THE Experience_Badge SHALL display the experience value from $teacher['experience']
2. THE Experience_Badge SHALL render as a distinct visual component
3. THE Experience_Badge SHALL contain no images or animations
4. THE Experience_Badge SHALL display the raw experience text (e.g., "۱۰ سال تجربه")

### Requirement 6: Instrument Chips Display

**User Story:** As a student, I want to see which instruments the teacher teaches, so that I can determine if they match my learning goals.

#### Acceptance Criteria

1. WHEN Teacher_Info_Component renders, THE Instrument_Chip_Component SHALL display each instrument from $teacher['instruments'] array
2. WHEN $teacher['instruments'] contains multiple values, THE Instrument_Chip_Component SHALL render multiple chips
3. THE Instrument_Chip_Component SHALL render each chip as a separate component instance
4. THE Instrument_Chip_Component SHALL display the instrument name as text only
5. THE Instrument_Chip_Component SHALL contain no images or animations

### Requirement 7: CTA Button ("Request Class")

**User Story:** As a student, I want to request a class with the teacher, so that I can initiate the booking process.

#### Acceptance Criteria

1. WHEN the Hero_Section renders, THE CTA_Button SHALL display with text "Request Class"
2. THE CTA_Button SHALL be positioned below teacher information on mobile
3. THE CTA_Button SHALL be positioned within the 4-column info section on desktop
4. THE CTA_Button SHALL accept a label property with default value "Request Class"
5. THE CTA_Button SHALL render as a button element with semantic HTML (no divs styled as buttons)
6. THE CTA_Button SHALL contain no navigation href or click handler in Phase 1

### Requirement 8: Reusable Blade Components

**User Story:** As a developer, I want modular Blade components, so that I can reuse them across different teacher UI contexts.

#### Acceptance Criteria

1. THE components SHALL be located in resources/views/components/ui/teacher/ directory
2. THE Hero_Component SHALL be defined in hero.blade.php
3. THE Portrait_Placeholder_Component SHALL be defined in portrait.blade.php
4. THE Teacher_Info_Component SHALL be defined in teacher-info.blade.php
5. THE Instrument_Chip_Component SHALL be defined in instrument-chip.blade.php
6. THE Experience_Badge_Component SHALL be defined in experience-badge.blade.php
7. THE CTA_Button_Component SHALL be defined in cta-button.blade.php
8. EACH component SHALL accept relevant properties via Blade @props directive
9. EACH component SHALL be independent and testable in isolation

### Requirement 9: Mock Data Handling

**User Story:** As a developer, I want to use mock data only, so that I can develop UI without database dependencies.

#### Acceptance Criteria

1. THE teacher profile view (show.blade.php) SHALL pass $teacher mock data array to Hero_Component
2. THE $teacher array SHALL contain keys: name, title, experience, instruments
3. THE $teacher['instruments'] SHALL be an array of string values
4. THE components SHALL NOT query any database or call Eloquent models
5. THE components SHALL NOT call any controller business logic
6. THE components SHALL accept all data directly via Blade component properties

### Requirement 10: Mobile-First Responsive Layout

**User Story:** As a user on mobile, I want the hero section to display correctly, so that I can view teacher information on any device.

#### Acceptance Criteria

1. THE Hero_Section SHALL use 12-column CSS Grid as the layout foundation
2. WHERE viewport < 768px, THE layout SHALL stack all sections vertically (Background, Portrait, Info, CTA each take 12 columns)
3. WHERE viewport ≥ 768px, THE layout SHALL show Background in 8 columns and info section (Portrait, Info, CTA) in 4 columns
4. WHERE viewport < 768px, THE section order SHALL be: Background, Portrait, Teacher_Info, CTA_Button
5. THE CSS Grid approach SHALL support column flexibility for future layout phases without refactoring

### Requirement 11: Navbar and Breadcrumb Inheritance

**User Story:** As a user, I want consistent navigation elements, so that the teacher profile follows site-wide patterns.

#### Acceptance Criteria

1. THE teacher profile page (show.blade.php) SHALL inherit navbar from base layout
2. THE teacher profile page (show.blade.php) SHALL inherit breadcrumb from base layout
3. THE Hero_Section component SHALL NOT render navbar or breadcrumb
4. THE navbar and breadcrumb SHALL display above the Hero_Section

### Requirement 12: No Animations or Images in Phase 1

**User Story:** As a developer, I want Phase 1 to focus on layout and structure, so that animations and images can be added in Phase 2 without rework.

#### Acceptance Criteria

1. THE Hero_Section SHALL NOT include CSS animations
2. THE Hero_Section SHALL NOT include CSS transitions
3. THE Hero_Section components SHALL NOT load or reference external images
4. THE Background_Placeholder and Portrait_Placeholder SHALL display as empty divs with no background images
5. THE Instrument_Chip_Component SHALL NOT include icons or images
6. THE Experience_Badge_Component SHALL NOT include icons or images

## Out of Scope (Phase 2+)

The following features are explicitly excluded from Phase 1:

- About section
- Professional information details
- Teacher quotes
- Schedule/availability display
- Gallery or media showcase
- Student reviews section
- Video content
- Animations and transitions
- Real images and assets
- Database queries or Eloquent models
- Controller business logic
- Form submission or API integration
