# Student Subscription Module - MVP (5 + 1 Task)

**Total Effort**: ~4 hours  
**Task Count**: 5 implementation + 1 testing task  
**Execution**: Linear (1 → 2 → 3 → 4 → 5 → 6)  
**Schema Changes**: 1 migration only (subscriptions table)  
**Overage Tracking**: UI-only, no DB schema expansion

---

## Quick Reference

| # | Task | Time | Status |
|---|------|------|--------|
| 1 | Subscription migration + model | 45m | [ ] |
| 2 | Relationships wiring | 30m | [ ] |
| 3 | Session form redesign | 60m | [ ] |
| 4 | ClassSessionController deduction | 45m | [ ] |
| 5 | Student profile subscription card | 45m | [ ] |
| 6 | End-to-end tests (integrated) | 60m | [ ] |

**Total**: ~285 min (~4.5 hours)

---

## Task 1: Subscription Migration & Model (45 min)

Create subscriptions table migration and Subscription model.

**Validates**: Requirements 1.1, 1.2, 1.4, 10.1, 10.3

### Subtasks

- [x] 1.1 Generate migration: `php artisan make:migration create_subscriptions_table`

- [x] 1.2 Create subscriptions table with schema:
  ```
  id (PK)
  student_id (FK → students)
  teacher_id (FK → teachers)
  instrument_id (FK → instruments)
  sessions_allocated (int, default 4)
  sessions_used (int, default 0)
  monthly_fee (int, default 3000000)
  payment_status (enum: paid/unpaid/overdue, default 'unpaid')
  renewal_date (date, default today + 30 days)
  notes (longtext nullable)
  created_at, updated_at (timestamps)
  ```

- [x] 1.3 Add unique index: `unique(['student_id', 'teacher_id', 'instrument_id'])`

- [x] 1.4 Add foreign key constraints with cascade delete

- [x] 1.5 Create `app/Models/Subscription.php`:
  ```php
  class Subscription extends Model
  {
      protected $fillable = [
          'student_id', 'teacher_id', 'instrument_id',
          'sessions_allocated', 'sessions_used', 'monthly_fee',
          'payment_status', 'renewal_date', 'notes'
      ];
      
      protected $casts = [
          'payment_status' => 'string',
          'renewal_date' => 'date',
      ];
  }
  ```

- [x] 1.6 Run migration: `php artisan migrate`

**Success Criteria**:
- ✅ subscriptions table created with correct schema
- ✅ unique constraint enforced on (student_id, teacher_id, instrument_id)
- ✅ Subscription model instantiable
- ✅ Migration is reversible

---

## Task 2: Relationships Wiring (30 min)

Add hasMany relationships to Student/Teacher/Instrument models and belongsTo to Subscription.

**Validates**: Requirements 1.1, 1.3, 8.2

### Subtasks

- [x] 2.1 Update `app/Models/Student.php`:
  ```php
  public function subscriptions()
  {
      return $this->hasMany(Subscription::class);
  }
  ```

- [x] 2.2 Update `app/Models/Teacher.php`:
  ```php
  public function subscriptions()
  {
      return $this->hasMany(Subscription::class);
  }
  ```

- [x] 2.3 Update `app/Models/Instrument.php`:
  ```php
  public function subscriptions()
  {
      return $this->hasMany(Subscription::class);
  }
  ```

- [x] 2.4 Add relationships to `app/Models/Subscription.php`:
  ```php
  public function student()
  {
      return $this->belongsTo(Student::class);
  }
  
  public function teacher()
  {
      return $this->belongsTo(Teacher::class);
  }
  
  public function instrument()
  {
      return $this->belongsTo(Instrument::class);
  }
  ```

- [x] 2.5 Test relationships work:
  ```php
  $subscription = Subscription::first();
  $subscription->student; // returns Student
  $subscription->teacher; // returns Teacher
  $subscription->instrument; // returns Instrument
  ```

**Success Criteria**:
- ✅ All relationships configured correctly
- ✅ Can access related models via Subscription/Student/Teacher/Instrument
- ✅ Eager-loading works with `with()` method

**Note**: NO new DB fields added. Relationships only.

---

## Task 3: Session Form Redesign (60 min)

Update session creation form: remove active_enrollment, add student autocomplete, populate teacher/instrument/room from subscriptions.

**Validates**: Requirements 2.1, 2.2, 2.3, 3.1, 3.2, 3.3, 4.1

### Subtasks

- [x] 3.1 Locate session creation form component (typically `resources/views/admin/class-sessions/create.blade.php` or Livewire component)

- [x] 3.2 Remove `active_enrollment` field from form UI:
  - Delete enrollment_id input
  - Delete enrollment validation

- [x] 3.3 Add student autocomplete field:
  - Text input with search/debounce (Livewire or Alpine)
  - Wire to `searchStudents($query)` method
  - Display: name + id
  - On select: populate `student_id`, trigger teacher/instrument load

- [x] 3.4 Add teacher dropdown:
  - Initially empty/disabled
  - After student selected, load:
    ```php
    $student->subscriptions()->distinct()->pluck('teacher.name', 'teacher.id')
    ```
  - On change: trigger instrument load

- [x] 3.5 Add instrument dropdown:
  - Initially empty/disabled
  - After teacher selected, load:
    ```php
    $student->subscriptions()
        ->where('teacher_id', $teacherId)
        ->distinct()
        ->pluck('instrument.name', 'instrument.id')
    ```

- [x] 3.6 Add room dropdown:
  - Static options: A101, A102, A103
  - Always enabled

- [x] 3.7 Add subscription validation before submission:
  ```php
  $subscription = Subscription::where([
      'student_id' => $request->student_id,
      'teacher_id' => $request->teacher_id,
      'instrument_id' => $request->instrument_id,
  ])->exists();
  
  if (!$subscription) {
      throw ValidationException::withMessages([
          'form' => 'No subscription exists for this combination'
      ]);
  }
  ```

- [x] 3.8 Add over-quota warning UI (UI-only, no DB tracking):
  ```blade
  @if ($subscription->sessions_used >= $subscription->sessions_allocated)
    <div class="bg-amber-500/10 border border-amber-400 rounded p-3">
      <p class="text-amber-400">⚠️ Sessions exceeded. Session will be marked as overage.</p>
      <p class="text-sm mt-2">Optional reason for overage:</p>
      <input type="text" name="notes" placeholder="Reason..." maxlength="255">
    </div>
  @endif
  ```

- [x] 3.9 Test form:
  - Verify autocomplete searches
  - Verify dropdowns populate after selection
  - Verify overage warning displays
  - Verify form rejects invalid subscription
  - Verify form submits with valid data

**Success Criteria**:
- ✅ Form renders without active_enrollment field
- ✅ Student autocomplete works (search by name/id)
- ✅ Teacher dropdown populates from subscriptions
- ✅ Instrument dropdown populates from subscriptions
- ✅ Room dropdown shows fixed options (A101, A102, A103)
- ✅ Over-quota warning displays when sessions_used >= sessions_allocated
- ✅ Form validates subscription exists before submission
- ✅ Form rejects submission if subscription doesn't exist

**Note**: NO subscription_overage_notes field. Overage warning is UI-only.

---

## Task 4: ClassSessionController Deduction Logic (45 min)

Implement session deduction in ClassSessionController store() method.

**Validates**: Requirements 2.5, 3.3, 3.4, 5.1, 5.2, 5.3

### Subtasks

- [x] 4.1 Locate `ClassSessionController::store()` method

- [x] 4.2 Add subscription lookup at start:
  ```php
  $subscription = Subscription::where([
      'student_id' => $request->student_id,
      'teacher_id' => $request->teacher_id,
      'instrument_id' => $request->instrument_id,
  ])->firstOrFail(); // throws 404 if not found
  ```

- [x] 4.3 Create ClassSession record (standard fields only, no overage_notes):
  ```php
  $session = ClassSession::create([
      'student_id' => $request->student_id,
      'teacher_id' => $request->teacher_id,
      'instrument_id' => $request->instrument_id,
      'room_id' => $request->room,
      'session_date' => $request->session_date,
      'start_time' => $request->start_time,
      'duration_minutes' => $request->duration_minutes,
      // ... other existing fields ...
  ]);
  ```

- [x] 4.4 Decrement subscription.sessions_used by 1:
  ```php
  $subscription->sessions_used += 1;
  $subscription->save();
  ```

- [x] 4.5 Return success response with updated counts:
  ```php
  return response()->json([
      'session' => $session,
      'subscription' => [
          'sessions_used' => $subscription->sessions_used,
          'sessions_allocated' => $subscription->sessions_allocated,
          'remaining' => $subscription->sessions_allocated - $subscription->sessions_used,
      ]
  ]);
  ```

- [x] 4.6 Test session creation:
  - Create session with valid subscription → verify sessions_used incremented by 1
  - Create session when sessions_used = 4, sessions_allocated = 4 → verify sessions_used = 5 (overage allowed)
  - Create session without subscription → verify 404 or validation error
  - Verify existing session tests still pass (backward compatible)

**Success Criteria**:
- ✅ Session creation validates subscription exists
- ✅ Session creation increments subscription.sessions_used by exactly 1
- ✅ remaining_sessions can go negative (permissive overage: sessions_used may exceed sessions_allocated)
- ✅ No overage_notes written to DB
- ✅ Response includes updated subscription counts

**Note**: NO subscription_overage_notes field. Overage detection is UI-only.

---

## Task 5: Student Profile Subscription Summary Card (45 min)

Add subscription summary section to student profile.

**Validates**: Requirements 8.1, 8.2, 8.3, 8.4

### Subtasks

- [x] 5.1 Update `StudentController::show($id)`:
  ```php
  $student = Student::with('subscriptions.teacher', 'subscriptions.instrument')
      ->findOrFail($id);
  ```

- [x] 5.2 Create subscription summary component/partial:
  ```blade
  <div class="mt-6">
    <h3 class="text-lg font-semibold">Subscription Summary</h3>
    
    @if ($student->subscriptions->count() > 0)
      <table class="mt-4 w-full">
        <thead>
          <tr>
            <th class="text-left">Teacher</th>
            <th class="text-left">Instrument</th>
            <th class="text-left">Remaining</th>
            <th class="text-left">Status</th>
            <th class="text-left">Renewal</th>
            <th class="text-left">Fee</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($student->subscriptions as $sub)
            @php
              $remaining = $sub->sessions_allocated - $sub->sessions_used;
              $isOverage = $remaining < 0;
            @endphp
            <tr>
              <td>{{ $sub->teacher->name }}</td>
              <td>{{ $sub->instrument->name }}</td>
              <td class="{{ $isOverage ? 'text-red-500 font-semibold' : '' }}">
                {{ $remaining }}/{{ $sub->sessions_allocated }}
                @if ($isOverage)
                  <span class="text-sm">({{ abs($remaining) }} over)</span>
                @endif
              </td>
              <td>
                @if ($sub->payment_status === 'paid')
                  <span class="px-2 py-1 bg-emerald-500/10 text-emerald-400 rounded text-sm">PAID ✓</span>
                @elseif ($sub->payment_status === 'unpaid')
                  <span class="px-2 py-1 bg-amber-500/10 text-amber-400 rounded text-sm">UNPAID</span>
                @else
                  <span class="px-2 py-1 bg-red-500/10 text-red-400 rounded text-sm font-semibold">OVERDUE</span>
                @endif
              </td>
              <td>{{ $sub->renewal_date->format('Y-m-d') }}</td>
              <td>{{ number_format($sub->monthly_fee) }} ت</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @else
      <p class="mt-2 text-gray-500 italic">No active subscriptions</p>
    @endif
  </div>
  ```

- [x] 5.3 Add to student profile view

- [x] 5.4 Verify computed fields:
  - remaining = sessions_allocated - sessions_used
  - overage = max(0, sessions_used - sessions_allocated)
  - Show overage count when remaining < 0

- [x] 5.5 Test:
  - Load student profile with 1+ subscriptions
  - Verify all fields render correctly
  - Verify status badges show correct colors
  - Verify overage indicator displays
  - Verify RTL text direction works

**Success Criteria**:
- ✅ Student profile displays subscription summary table
- ✅ All fields present: teacher, instrument, remaining, status, renewal, fee
- ✅ Remaining sessions calculated correctly (positive and negative)
- ✅ Overage count displayed when sessions_used > sessions_allocated
- ✅ Status badges: green (PAID), amber (UNPAID), red (OVERDUE)
- ✅ RTL layout correct
- ✅ Empty state handled gracefully

---

## Task 6: End-to-End Tests (60 min)

Write comprehensive tests covering all 5 tasks.

**Validates**: All core requirements (1, 2, 3, 4, 5, 8)

### Test Categories

**Unit Tests** (Task 6.1-6.4)
- [x] 6.1 Subscription model defaults (sessions_allocated=4, monthly_fee=3M, payment_status='unpaid')
- [x] 6.2 Subscription relationships (belongsTo Student/Teacher/Instrument)
- [x] 6.3 Unique constraint on (student_id, teacher_id, instrument_id)
- [x] 6.4 Subscription computed fields (remaining_sessions, overage_count)

**Core Feature Tests** (Task 6.5-6.8)
- [x] 6.5 Session creation with valid subscription increments sessions_used by 1
- [x] 6.6 Session creation without subscription returns 404
- [x] 6.7 Overage sessions allowed (sessions_used can go negative)
- [x] 6.8 Student profile subscription summary renders with correct data

**Property-Based Tests** (Task 6.9-6.13)
- [x] 6.9 Property 1: Uniqueness - max 1 active subscription per (student, teacher, instrument)
- [x] 6.10 Property 2: Defaults - new subscriptions have correct defaults
- [x] 6.11 Property 5: Deduction - sessions_used increments by 1 per session (100+ iterations)
- [x] 6.12 Property 7: Triple validation - no session without subscription (100+ iterations)
- [x] 6.13 Property 16: Overage calculation - overage = max(0, sessions_used - sessions_allocated) (100+ iterations)

**Integration Tests** (Task 6.14-6.15)
- [x] 6.14 Full flow: student autocomplete → teacher select → subscription validation → session created → sessions_used decremented
- [x] 6.15 Student profile loads with subscription summary, all fields correct

### Implementation

```php
// tests/Feature/StudentSubscriptionTest.php

class StudentSubscriptionTest extends TestCase
{
    public function test_subscription_defaults()
    {
        // Create subscription, verify defaults
    }
    
    public function test_session_creation_deducts_sessions()
    {
        // Create session, verify sessions_used incremented
    }
    
    public function test_session_creation_fails_without_subscription()
    {
        // Try to create session without subscription, expect 404
    }
    
    public function test_student_profile_displays_subscriptions()
    {
        // Load student profile, verify subscription summary renders
    }
    
    // Property tests...
    public function test_property_subscription_uniqueness()
    {
        // Test 100+ generated student-teacher-instrument triples
    }
}
```

### Run Tests

```bash
php artisan test tests/Feature/StudentSubscriptionTest.php
# or
./vendor/bin/pest tests/Feature/StudentSubscriptionTest.php
```

**Success Criteria**:
- ✅ All unit tests pass
- ✅ All feature tests pass
- ✅ All property tests pass (100+ iterations minimum per property)
- ✅ Integration tests verify end-to-end workflow
- ✅ No mocks used (real fixtures/factories)
- ✅ Edge cases covered (empty subscriptions, multiple subscriptions, overages)

---

## Implementation Checklist

- [x] Complete Task 1: Migration + Model (45m)
- [x] Complete Task 2: Relationships (30m)
- [x] Complete Task 3: Form Redesign (60m)
- [x] Complete Task 4: Controller Deduction (45m)
- [x] Complete Task 5: Profile Card (45m)
- [x] Complete Task 6: Tests (60m)

**Total**: ~285 min (4.5 hours)

---

## Key Constraints

✅ **No Overage DB Tracking**: Overage detection is UI-only. No subscription_overage_notes field.  
✅ **No Schema Expansion**: Only 1 migration. No ClassSession schema changes.  
✅ **Permissive Sessions**: remaining_sessions can go negative (sessions_used may exceed sessions_allocated). No prevention.  
✅ **Linear Execution**: Tasks must complete in order (1→2→3→4→5→6).  
✅ **Integrated Tests**: Task 6 combines all test types in single file.

---

## Success = MVP Complete

When all 6 tasks pass:
- ✅ Subscription model created with relationships
- ✅ Session form redesigned with autocomplete
- ✅ Session deduction logic implemented
- ✅ Overage warning displays in UI
- ✅ Student profile shows subscription summary
- ✅ All tests passing
- ✅ Ready for Phase 2 (payment tracking, renewal, admin dashboard)

