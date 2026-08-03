<?php

namespace Tests\Feature;

use App\Models\ClassAttendance;
use App\Models\ClassSession;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Room;
use App\Models\StudentEnrollment;
use App\Models\Subscription;
use App\Models\TeacherInstrument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissingAdminFactoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_admin_factories_create_persistable_records_with_valid_relations(): void
    {
        $teacherInstrument = TeacherInstrument::factory()->create();
        $enrollment = StudentEnrollment::factory()->create();
        $session = ClassSession::factory()->create()->load('enrollment');
        $attendance = ClassAttendance::factory()->create()->load('classSession');
        $room = Room::factory()->create();
        $subscription = Subscription::factory()->create();
        $invoice = Invoice::factory()->create();
        $item = InvoiceItem::factory()->create();
        $payment = InvoicePayment::factory()->create();

        $this->assertDatabaseHas('teacher_instruments', ['id' => $teacherInstrument->id]);
        $this->assertDatabaseHas('teacher_instruments', [
            'teacher_id' => $enrollment->teacher_id,
            'instrument_id' => $enrollment->instrument_id,
        ]);
        $this->assertSame($session->enrollment->student_id, $session->student_id);
        $this->assertSame($session->enrollment->teacher_id, $session->teacher_id);
        $this->assertSame($session->enrollment->instrument_id, $session->instrument_id);
        $this->assertSame($attendance->classSession->student_id, $attendance->student_id);
        $this->assertDatabaseHas('rooms', ['id' => $room->id]);
        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id]);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
        $this->assertDatabaseHas('invoice_items', ['id' => $item->id]);
        $this->assertDatabaseHas('invoice_payments', ['id' => $payment->id]);
    }
}
