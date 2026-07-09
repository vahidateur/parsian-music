# Student Subscription Module - Design Document

## Overview

The Student Subscription Module implements a monthly subscription system where each student-teacher-instrument combination has its own subscription record. Sessions are deducted from allocated counts with permissive overage tracking. Payments are managed manually with renewal resetting session counts.

### Key Design Principles

- **Flexible Overage**: Sessions can exceed allocation; overages are tracked but not prevented
- **Subscription-Centric**: Subscriptions, not enrollments, drive session creation validation
- **Permissive Creation**: Session form allows manual selection with validation after selection
- **Manual Payment Flow**: Admin explicitly marks payments and renews subscriptions
- **Immutable Defaults**: New subscriptions always start at 4 sessions, 3,000,000 toman, unpaid status

---

## Architecture

### High-Level Flow

```
Student Creation → Enrollment → Subscription Record
                                    ↓
                     Session Form (autocomplete)
                                    ↓
                  Validate Subscription Exists
                                    ↓
                    Decrement sessions_used
                          (allow overage)
                                    ↓
                     Record overage if applicable
                                    ↓
                     Display in Student Profile
                                    ↓
                    Admin Marks Payment Status
                          (paid/unpaid)
                                    ↓
                   Overdue Check (date-based)
                          ↓
                    Manual Renewal
                  (reset count, update date)
```

### Layers

- **Model Layer**: Subscription model with relationships to Student, Teacher, Instrument
- **Session Layer**: ClassSession updates to support subscription deduction
- **Form Layer**: Session form with autocomplete and subscription validation
- **Display Layer**: Student profile subscription summary, admin subscription list
- **Background**: Overdue status detection based on renewal_date

---

## Data Models

### Subscription Model

**Table: subscriptions**

```
id                    : bigint unsigned, primary
student_id            : bigint unsigned, foreign key → students.id
teacher_id            : bigint unsigned, foreign key → teachers.id
instrument_id         : bigint unsigned, foreign key → instruments.id
sessions_allocated    : int (default: 4)
sessions_used         : int (default: 0)
monthly_fee           : int in toman (default: 3,000,000)
payment_status        : enum('paid', 'unpaid', 'overdue') default 'unpaid'
renewal_date          : date (current_date + 30 days at creation)
notes                 : longtext nullable (tracks overage history, renewal logs)
created_at            : timestamp
updated_at            : timestamp
unique index on (student_id, teacher_id, instrument_id)
```

### Relationships

```
Subscription
  ├─ belongs_to Student
  ├─ belongs_to Teacher
  └─ belongs_to Instrument

Student
  └─ has_many Subscription

Teacher
  └─ has_many Subscription

Instrument
  └─ has_many Subscription

ClassSession
  ├─ add: subscription_overage_notes (nullable text)
  └─ relationships unchanged
```

---

## Components and Interfaces

### 1. Session Creation Form (Redesigned)

**Location**: ClassSession create form

**Fields**:

```
┌─────────────────────────────────────────────┐
│ New Class Session                           │
├─────────────────────────────────────────────┤
│                                             │
│ Student: [autocomplete search box]          │
│          Search by name or ID               │
│                                             │
│ Teacher: [dropdown - populated after]       │
│          Select from available teachers     │
│                                             │
│ Instrument: [dropdown - populated after]    │
│            Select from available instruments│
│                                             │
│ Room: [dropdown - fixed set]                │
│       ⚪ A101                                │
│       ⚪ A102                                │
│       ⚪ A103                                │
│                                             │
│ Session Date: [date picker]                 │
│                                             │
│ Start Time: [time picker]                   │
│                                             │
│ Duration: [number input] minutes            │
│                                             │
│ ⚠️ [Warning if double-booking]              │
│ [Override checkbox if needed]               │
│                                             │
│ ⚠️ [Warning if overage]                     │
│ Sessions exceeded. Session will be marked.  │
│                                             │
│ Notes: [text area]                          │
│        Reason for overage (optional)        │
│                                             │
│ [Cancel] [Create Session]                   │
└─────────────────────────────────────────────┘
```

**Behavior**:

- Student autocomplete: Search by name/id, returns matching students
- After student selected:
  - Load teacher dropdown (teachers from student's active subscriptions)
  - Load instrument dropdown (instruments from student's active subscriptions)
- After teacher+instrument selected:
  - Validate subscription exists for (student, teacher, instrument) triple
  - If no subscription: show error, disable submit
- After room+date/time selected:
  - Check for double-booking conflicts in that room at that time
  - If conflict detected: show warning, offer override
- After submission:
  - Validate subscription still exists
  - Check sessions_used vs sessions_allocated
  - If sessions_used >= sessions_allocated:
    - Show overage indicator
    - Enable notes field for reason
  - Decrement sessions_used by 1
  - Store overage_notes if provided
  - Create ClassSession

**Removed**:

- active_enrollment field (no longer used)
- enrollment_id relationship on form (still stored in model for backward compat, but not validated)

### 2. Student Profile - Subscription Summary Section

**Location**: Student profile dashboard

**Section**:

```
┌────────────────────────────────────────────────────────────────┐
│ Subscription Summary                                           │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│ Teacher  | Instrument | Remaining | Status    | Renewal    │
│          |            | Sessions  | Badge     | Date       │
│──────────┼────────────┼───────────┼───────────┼────────────│
│ Ahmed    | Piano      | 2/4       | PAID ✓    | 2024-02-15 │
│          |            |           | (green)   |            │
│──────────┼────────────┼───────────┼───────────┼────────────│
│ Fatima   | Violin     | -1/4      | UNPAID ⚠️ | 2024-02-10 │
│          |            | (1 over)  | (amber)   |            │
│──────────┼────────────┼───────────┼───────────┼────────────│
│ Hassan   | Oud        | 0/4       | OVERDUE🔴 | 2024-01-20 │
│          |            | (4 over)  | (red)     | [LATE]     │
│──────────┼────────────┼───────────┼───────────┼────────────│
│ Monthly Fee | Paid Status | Renewal Date |                 │
│ 3M Toman    | Status      | Date         |                 │
│             |             |              |                 │
│ Legend:                                                     │
│ • Remaining Sessions = sessions_allocated - sessions_used  │
│ • Overage indicated when sessions_used > sessions_allocated│
│ • PAID: green badge (bg-emerald-500/10 text-emerald-400)  │
│ • UNPAID: amber badge (bg-amber-500/10 text-amber-400)    │
│ • OVERDUE: red badge (bg-red-500/10 text-red-400)         │
└────────────────────────────────────────────────────────────────┘
```

**Computed Fields**:

- Remaining Sessions: `sessions_allocated - sessions_used`
- Overage Count: `max(0, sessions_used - sessions_allocated)`
- Is Overdue: `payment_status === 'overdue' OR (renewal_date < today AND payment_status !== 'paid')`

**RTL Considerations**:

- Text direction: right-to-left in Arabic context
- Badge positioning: right side preferred
- Column alignment: reversed for RTL
- Status icons positioned at right edge

### 3. Admin Subscription Management List

**Location**: Admin panel, new Subscription resource

**Table**:

```
┌─────────────────────────────────────────────────────────────┐
│ Manage Subscriptions                              [+Create] │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Student | Teacher | Instrument | Remaining | Status | ...  │
│─────────┼─────────┼────────────┼───────────┼────────┼──── │
│ Ahmed   | Fatima  | Piano      | 2/4       | PAID ✓ │ ...  │
│ Ahmed   | Hassan  | Violin     | 1/4       | UNPAID │ ...  │
│ Layla   | Mariam  | Oud        | 4/4       | OVERDUE│ ...  │
│                                                             │
│ Actions per row:                                            │
│ • Mark Paid (if status != paid)                             │
│ • Mark Unpaid (if status != unpaid)                         │
│ • Renew (manual renewal)                                    │
│ • View (details & history)                                  │
│ • Edit Notes                                                │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**Action: Mark Paid**

```
Confirmation Dialog:
  "Mark this subscription as paid?"
  Student: Ahmed
  Teacher: Fatima
  Instrument: Piano
  Payment Status will change: unpaid → paid
  [Cancel] [Confirm]
```

**Action: Renew**

```
Confirmation Dialog:
  "Renew this subscription?"
  Student: Ahmed
  Teacher: Fatima
  Instrument: Piano
  
  Changes:
  • sessions_used: reset to 0
  • renewal_date: 2024-03-15 (today + 30 days)
  • payment_status: reset to unpaid
  • notes: append "[Renewed: 2024-02-15] sessions reset, next renewal 2024-03-15"
  
  [Cancel] [Confirm Renewal]
```

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Subscription Creation Uniqueness

*For any* student-teacher-instrument triple, at most one active subscription should exist for that combination.

**Validates: Requirements 1.1, 1.3**

### Property 2: Subscription Default Values

*For any* newly created subscription, sessions_allocated SHALL equal 4, monthly_fee SHALL equal 3,000,000 toman, and payment_status SHALL equal 'unpaid'.

**Validates: Requirements 1.2**

### Property 3: Available Combinations Load

*For any* student selection, the system SHALL return all teacher-instrument-room combinations for which an active subscription exists for that student.

**Validates: Requirements 2.2**

### Property 4: Room Options Constraint

*For any* room selection field, available options SHALL be restricted to exactly: A101, A102, A103.

**Validates: Requirements 2.3**

### Property 5: Session Creation Deduction

*For any* class session creation, the associated subscription's sessions_used value SHALL increment by exactly 1.

**Validates: Requirements 2.5, 5.1**

### Property 6: Double-booking Conflict Detection

*For any* room and date/time combination, the system SHALL detect if any existing session occupies that room during an overlapping time period.

**Validates: Requirements 2.4, 4.2**

### Property 7: Triple Validation on Session Create

*For any* manually selected student-teacher-instrument triple, the system SHALL validate that a subscription exists for that combination before allowing session creation.

**Validates: Requirements 3.3, 3.4, 9.3**

### Property 8: Overage Note Recording

*For any* session created when sessions_used >= sessions_allocated, the system SHALL record an overage note with timestamp and optional reason.

**Validates: Requirements 4.4, 5.2, 5.3**

### Property 9: Subscription Default Payment Status

*For any* newly created subscription, payment_status SHALL be set to 'unpaid'.

**Validates: Requirements 6.2**

### Property 10: Overdue Status Determination

*For any* subscription where renewal_date has passed and payment_status is not 'paid', the system SHALL mark payment_status as 'overdue'.

**Validates: Requirements 6.4**

### Property 11: Renewal State Reset

*For any* subscription renewal, the system SHALL reset sessions_used to 0, set renewal_date to 30 days from today, and reset payment_status to 'unpaid'.

**Validates: Requirements 7.3**

### Property 12: Renewal Audit Logging

*For any* subscription renewal, the system SHALL record the renewal action with timestamp in the notes field.

**Validates: Requirements 7.4**

### Property 13: Renewal Date Calculation

*For any* subscription created or renewed, renewal_date SHALL be exactly 30 days from creation or renewal date.

**Validates: Requirements 10.3**

### Property 14: Student Subscription List

*For any* student, when their subscriptions are queried, the system SHALL return all subscriptions where student_id equals that student's id.

**Validates: Requirements 8.2**

### Property 15: Subscription Display Completeness

*For any* subscription displayed in the student profile, the system SHALL show: teacher name, instrument name, remaining sessions (sessions_allocated - sessions_used), payment status, renewal date, and monthly fee.

**Validates: Requirements 8.3**

### Property 16: Overage Count Calculation

*For any* subscription with sessions_used > sessions_allocated, the system SHALL display overage count as (sessions_used - sessions_allocated).

**Validates: Requirements 5.4, 8.4**

---

## Error Handling

### Session Creation Errors

| Error | Cause | Response |
|-------|-------|----------|
| Subscription not found | No subscription exists for (student, teacher, instrument) | Prevent session creation, display error: "No subscription exists for this combination" |
| Student not found | Invalid student ID in autocomplete | Display validation error, disable form submission |
| Teacher/Instrument mismatch | Selected pair has no subscription for student | Display error before form submission |
| Room conflict detected | Existing session occupies selected room+time | Show warning, allow override checkbox |
| Missing required fields | Student, teacher, instrument, room, date, time not provided | Highlight fields, prevent submission |

### Renewal Errors

| Error | Cause | Response |
|-------|-------|----------|
| Subscription not found | ID invalid or subscription deleted | Display error, return to list |
| Database transaction failure | Update fails atomically | Rollback, show error: "Renewal failed, please try again" |
| Concurrent modification | Subscription modified between load and update | Show error with current state, prompt refresh |

### Payment Status Errors

| Error | Cause | Response |
|-------|-------|----------|
| Invalid status value | Status not in [paid, unpaid, overdue] | Reject at validation layer |
| Subscription not found | Update target doesn't exist | Display error, return to list |

---

## API & Controller Changes

### New: SubscriptionController

**Endpoints**:

```
GET    /api/subscriptions                 → List all subscriptions (admin)
GET    /api/subscriptions/{id}            → View subscription details
POST   /api/subscriptions                 → Create subscription (rarely used; typically auto-created)
PUT    /api/subscriptions/{id}            → Update subscription (notes, manual fee adjustment)
PUT    /api/subscriptions/{id}/mark-paid  → Mark payment as paid
PUT    /api/subscriptions/{id}/renew      → Manual renewal (reset sessions, update date)
DELETE /api/subscriptions/{id}            → Soft delete subscription (rare)

GET    /api/students/{id}/subscriptions   → Get all subscriptions for a student (public)
GET    /api/subscriptions/search?query=   → Search by student/teacher/instrument
```

### Modified: ClassSessionController

**store() method changes**:

```php
// OLD:
$session = ClassSession::create([
    'enrollment_id' => $request->enrollment_id,
    'student_id' => ...,
    ...
]);

// NEW:
$subscription = Subscription::where([
    'student_id' => $request->student_id,
    'teacher_id' => $request->teacher_id,
    'instrument_id' => $request->instrument_id,
])->firstOrFail(); // throws 404 if not found

$session = ClassSession::create([
    'student_id' => $request->student_id,
    'teacher_id' => $request->teacher_id,
    'instrument_id' => $request->instrument_id,
    ...
    'subscription_overage_notes' => null, // set conditionally below
]);

// Decrement and track overage
$wasOverage = $subscription->sessions_used >= $subscription->sessions_allocated;
$subscription->sessions_used += 1;
$subscription->save();

if ($wasOverage || $subscription->sessions_used > $subscription->sessions_allocated) {
    $session->subscription_overage_notes = "Overage session - " . now() . 
        ($request->notes ? " - Reason: " . $request->notes : "");
    $session->save();
}
```

### Modified: StudentController

**show() method changes**:

```php
// Add to response:
$student->subscriptions = $student->subscriptions()
    ->with(['teacher', 'instrument'])
    ->get()
    ->map(function ($sub) {
        return [
            'id' => $sub->id,
            'teacher_name' => $sub->teacher->name,
            'instrument_name' => $sub->instrument->name,
            'sessions_allocated' => $sub->sessions_allocated,
            'sessions_used' => $sub->sessions_used,
            'remaining_sessions' => $sub->sessions_allocated - $sub->sessions_used,
            'overage_count' => max(0, $sub->sessions_used - $sub->sessions_allocated),
            'monthly_fee' => $sub->monthly_fee,
            'payment_status' => $sub->payment_status,
            'renewal_date' => $sub->renewal_date,
            'is_overdue' => $sub->payment_status === 'overdue' || 
                           ($sub->renewal_date < today() && $sub->payment_status !== 'paid'),
        ];
    });
```

---

## Validation Rules

### Subscription Creation/Update

- `student_id`: Required, must exist in students table
- `teacher_id`: Required, must exist in teachers table
- `instrument_id`: Required, must exist in instruments table
- `sessions_allocated`: Integer >= 1, default 4
- `sessions_used`: Integer >= 0, default 0, must be <= sessions_allocated at creation (enforced at session creation, not subscription)
- `monthly_fee`: Integer > 0, default 3,000,000
- `payment_status`: One of [paid, unpaid, overdue]
- `renewal_date`: Date >= today, default today + 30 days
- `notes`: Text, optional
- Unique constraint: (student_id, teacher_id, instrument_id) with soft deletes consideration

### Session Creation with Subscription

- `student_id`, `teacher_id`, `instrument_id`: Required
- Subscription must exist for this triple
- `room`: Must be one of [A101, A102, A103]
- `session_date`, `start_time`, `duration_minutes`: Required, valid date/time
- Double-booking check: No existing session in same room during overlapping time (warning only)

### Renewal Action

- Subscription must exist and not be soft-deleted
- Sets: sessions_used = 0, renewal_date = today + 30 days, payment_status = unpaid
- Appends audit note

---

## Testing Strategy

### Unit Tests

**Subscription Model Tests**:
- Create subscription with defaults
- Unique constraint on (student_id, teacher_id, instrument_id)
- Relationships to Student, Teacher, Instrument
- Renewal action updates state correctly
- Payment status updates
- Date calculations

**ClassSession Model Tests**:
- Session creation decrements subscription.sessions_used
- Overage detection and note recording
- Room conflict detection (via service)
- Backward compatibility with enrollment_id field

**Subscription Service Tests**:
- Renewal resets sessions_used to 0
- Renewal sets renewal_date to 30 days future
- Renewal resets payment_status to unpaid
- Renewal records audit note
- Overdue detection based on renewal_date + payment_status

### Property-Based Tests

Property tests will verify:

1. Subscription uniqueness (for generated student-teacher-instrument triples)
2. Session deduction (for generated sessions, verify sessions_used increments)
3. Overage tracking (for sessions exceeding allocation, verify overage flag and notes)
4. Renewal atomicity (for any subscription, renewal updates all fields correctly)
5. Overdue detection (for subscriptions with past renewal_date and non-paid status)
6. Display completeness (for any subscription, all required fields present and calculated)

**Minimum iterations per property**: 100

**Tag format**: `Feature: student-subscription, Property N: [property description]`

### Integration Tests

- Session creation flow: Student autocomplete → teacher selection → subscription validation → session deduction
- Renewal flow: Admin clicks renew → confirmation → state reset → audit log
- Payment status updates: Admin marks paid → status changes → display updates
- Overdue detection: Cron or background task updates status for past-due subscriptions
- Student profile load: Subscriptions render with all fields correctly calculated

### Manual Testing (Non-automatable)

- Session form UX: Autocomplete responsiveness, dropdown population, warning displays
- RTL rendering: Text direction, icon positioning, badge alignment in Arabic context
- Overage warning: Displays correctly when creating session that exceeds allocation
- Double-booking warning: Shows conflict and offers override

---

## Design Decisions

### Why subscription instead of modifying enrollment?

Enrollments can be temporary or educational tracking; subscriptions are billing artifacts. Separating them allows flexible payment models independent of enrollment status.

### Why permissive overage?

Academy may want flexibility to accommodate students beyond allocated sessions while tracking overages for billing negotiations.

### Why manual renewal instead of auto-renewal?

Manual renewal provides control for contract negotiations, allows payment collection before auto-resetting, and maintains audit trail of deliberate actions.

### Why 30-day renewal period?

Default monthly subscription model; easily configurable per subscription if needed.

### Why session form validates subscription after selection?

Provides better UX: users see dropdown options before validation fails, can understand why options are limited.

### Why allow double-booking override?

Respects admin judgment; some rooms may have shared access or timing may work despite overlap detection.

---

## RTL Considerations

For Arabic (right-to-left) UI:

- Text direction: `dir="rtl"` on parent containers
- Badge positioning: Right side of cells (flex `flex-row-reverse` or `justify-end`)
- Form labels: Right-aligned above inputs
- Status icons: Right margin (becomes left margin in RTL)
- Table columns: Reversed order (Student | Teacher | Instrument becomes Instrument | Teacher | Student conceptually, but CSS handles visual flip)
- Modal/dialog buttons: [Confirm] [Cancel] reads right-to-left as intended

---

## Summary of Changes

**New**:
- Subscription table, model, controller
- SubscriptionController with renew/mark-paid actions
- Subscription relationships in Student, Teacher, Instrument models
- Session form autocomplete and subscription validation

**Modified**:
- ClassSession model: add subscription_overage_notes field
- ClassSessionController: update store() to validate subscription, decrement sessions_used
- StudentController: add subscription summary to profile response
- Session form UI: remove active_enrollment field, add student autocomplete

**Removed**:
- active_enrollment field from session form (model field kept for compatibility)

---

## Next Steps (Phase 2 - Tasks)

1. Create Subscription migration
2. Create Subscription model with relationships
3. Create SubscriptionController with actions
4. Update ClassSession migration (add subscription_overage_notes)
5. Update ClassSessionController store() logic
6. Update session form Livewire component for autocomplete + subscription validation
7. Update StudentController profile response
8. Add admin subscription list/management UI
9. Implement overdue detection (scheduled task or query scope)
10. Write property-based tests
11. Write integration tests
