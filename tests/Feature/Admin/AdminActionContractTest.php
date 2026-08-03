<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Actions\Admin\InvoiceAction;
use App\Enums\InvoiceStatusEnum;
use App\Enums\PaymentMethodEnum;
use App\Enums\RoleEnum;
use App\Models\Instrument;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Contract of the admin Action layer: the normalization applied during validation
 * is the normalization that reaches the database, a multi-record change rolls back
 * completely, and a missing record aborts with 404 without creating a substitute.
 *
 * Requirements: 6.4, 6.6, 6.8, 6.9, 6.10, 6.13
 */
class AdminActionContractTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => RoleEnum::SUPER_ADMIN]);
    }

    public function test_record_form_values_are_normalized_before_persistence_and_redisplayed(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.teachers.store'), [
                'full_name' => "  علي   كريمي \t",
                'phone' => ' ۰۹۱۲۳۴۵۶۷۸۹ ',
                'bio' => "  سابقه   تدريس \n  ده سال  ",
            ])
            ->assertRedirect(route('admin.teachers.index'))
            ->assertSessionHas('success');

        $teacher = Teacher::sole();

        $this->assertSame('علی کریمی', $teacher->full_name);
        $this->assertSame('09123456789', $teacher->phone);
        $this->assertSame("سابقه تدریس\nده سال", $teacher->bio);

        $this->actingAs($this->admin)
            ->get(route('admin.teachers.edit', $teacher))
            ->assertOk()
            ->assertSee('علی کریمی')
            ->assertSee('09123456789');
    }

    public function test_instrument_creation_derives_a_unique_slug_from_normalized_input(): void
    {
        $submissions = [
            ['name_fa' => ' كمانچه ', 'name' => ' Kamancheh '],
            // A different English name that slugifies to the same value.
            ['name_fa' => ' كمانچه بزرگ ', 'name' => ' Kamancheh! '],
        ];

        foreach ($submissions as $payload) {
            $this->actingAs($this->admin)
                ->post(route('admin.instruments.store'), $payload)
                ->assertSessionHasNoErrors();
        }

        $slugs = Instrument::orderBy('id')->pluck('slug')->all();

        $this->assertSame(['kamancheh', 'kamancheh-1'], $slugs);
        $this->assertSame('کمانچه', Instrument::orderBy('id')->first()?->name_fa);
    }

    public function test_an_instrument_still_in_use_is_never_deleted(): void
    {
        $instrument = Instrument::factory()->create();
        $teacher = Teacher::factory()->create();
        $teacher->instruments()->attach($instrument->id, ['skill_level' => 'expert', 'is_primary' => true]);

        $this->actingAs($this->admin)
            ->delete(route('admin.instruments.destroy', $instrument))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('instruments', ['id' => $instrument->id]);
    }

    public function test_a_failed_invoice_update_rolls_back_every_write(): void
    {
        $invoice = $this->draftInvoiceWithOneItem();
        $originalItem = $invoice->items()->sole();

        // A real model event fails the second insert, after the items were replaced.
        InvoiceItem::created(function (InvoiceItem $item): void {
            if ($item->sort_order === 1) {
                throw new RuntimeException('write failed halfway through the transaction');
            }
        });

        try {
            app(InvoiceAction::class)->update($invoice, [
                'student_id' => $invoice->student_id,
                'issue_date' => now()->format('Y-m-d'),
                'due_date' => now()->addMonth()->format('Y-m-d'),
                'notes' => 'یادداشت تازه',
                'items' => [
                    ['title' => 'ردیف اول', 'quantity' => 1, 'unit_price' => 100000],
                    ['title' => 'ردیف دوم', 'quantity' => 2, 'unit_price' => 200000],
                ],
            ]);

            $this->fail('The invoice update was expected to fail.');
        } catch (RuntimeException) {
            // expected
        }

        $invoice->refresh()->load('items');

        $this->assertNull($invoice->notes);
        $this->assertCount(1, $invoice->items);
        $this->assertSame($originalItem->id, $invoice->items->first()?->id);
    }

    public function test_deleting_a_payment_of_another_invoice_returns_404_and_writes_nothing(): void
    {
        $invoice = $this->issuedInvoice();
        $other = $this->issuedInvoice();

        $payment = $invoice->payments()->create([
            'amount' => 100000,
            'paid_at' => now(),
            'method' => PaymentMethodEnum::Cash,
            'status' => \App\Enums\PaymentStatusEnum::Completed,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.invoices.payments.destroy', [$other, $payment]))
            ->assertNotFound();

        $this->assertDatabaseHas('invoice_payments', ['id' => $payment->id]);
        $this->assertSame(InvoiceStatusEnum::Issued, $other->refresh()->status);
    }

    public function test_updating_a_missing_record_returns_404_without_creating_a_substitute(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.teachers.update', 9999), [
                'full_name' => 'استاد ناموجود',
                'phone' => '09120000000',
            ])
            ->assertNotFound();

        $this->assertSame(0, Teacher::count());
    }

    private function draftInvoiceWithOneItem(): Invoice
    {
        $invoice = Invoice::factory()->for(Student::factory())->create([
            'status' => InvoiceStatusEnum::Draft,
            'notes' => null,
        ]);

        $invoice->items()->create([
            'title' => 'ردیف اصلی',
            'quantity' => 1,
            'unit_price' => 500000,
            'discount' => 0,
            'sort_order' => 0,
        ]);

        $invoice->recalculate();

        return $invoice->fresh(['items']);
    }

    private function issuedInvoice(): Invoice
    {
        return Invoice::factory()->for(Student::factory())->create([
            'status' => InvoiceStatusEnum::Issued,
        ]);
    }
}
