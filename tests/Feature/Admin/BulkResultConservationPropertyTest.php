<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\DTOs\BulkCommand;
use App\DTOs\BulkItemResultData;
use App\DTOs\BulkResultData;
use App\Enums\BulkActionEnum;
use App\Enums\BulkItemResultStatusEnum;
use App\Services\BulkActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AdminBulkFixtures;
use Tests\TestCase;

// Feature: admin-operational-ux-baseline, Property 4: Bulk Result Conservation and Idempotence
// **Validates: Requirements 9.6, 9.8, 9.11**
final class BulkResultConservationPropertyTest extends TestCase
{
    use RefreshDatabase;

    private const RUNS = 100;

    public function test_generated_bulk_results_conserve_items_classify_outcomes_and_round_trip_serialization(): void
    {
        for ($run = 0; $run < self::RUNS; $run++) {
            $items = $this->generatedItems($run);
            $counts = $this->counts($items);
            $result = new BulkResultData(
                entity: $run % 2 === 0 ? 'student' : 'teacher',
                action: $run % 3 === 0 ? 'activate' : 'deactivate',
                mode: 'current_page',
                total: count($items),
                succeeded: $counts['succeeded'],
                skipped: $counts['skipped'],
                failed: $counts['failed'],
                items: $items,
                selection_reference: 'property-4-'.$run,
            );

            $this->assertSame(count($items), $result->total, 'Generated run '.$run.' must report every resolved item.');
            $this->assertSame(
                $result->total,
                $result->succeeded + $result->skipped + $result->failed,
                'Generated run '.$run.' must conserve result counts.',
            );

            $stableIds = array_map(
                static fn (BulkItemResultData $item): string => get_debug_type($item->id).':'.(string) $item->id,
                $result->items,
            );
            $this->assertCount(count($stableIds), array_unique($stableIds), 'Generated run '.$run.' must contain unique stable IDs.');

            $expectedOutcome = $result->failed > 0 || $result->skipped > 0
                ? 'partial_success'
                : 'complete_success';
            $this->assertSame($expectedOutcome, $result->outcome->value, 'Generated run '.$run.' has the wrong outcome classification.');

            $serialized = json_encode($result, JSON_THROW_ON_ERROR);
            $decoded = json_decode($serialized, true, 512, JSON_THROW_ON_ERROR);
            $rehydrated = $this->rehydrate($decoded);
            $this->assertSame($result->toArray(), $rehydrated->toArray(), 'Generated run '.$run.' must preserve its result contract after serialization.');
            $this->assertSame($serialized, json_encode($rehydrated, JSON_THROW_ON_ERROR), 'Generated run '.$run.' serialization must be idempotent.');
        }
    }

    public function test_same_state_status_actions_are_successful_and_idempotent_through_bulk_action_service(): void
    {
        $actor = AdminBulkFixtures::policyActor();
        $service = app(BulkActionService::class);

        for ($run = 0; $run < self::RUNS; $run++) {
            $initialStatus = $run % 2 === 0 ? 'active' : 'inactive';
            $action = $initialStatus === 'active' ? BulkActionEnum::Activate : BulkActionEnum::Deactivate;
            $student = AdminBulkFixtures::student(['status' => $initialStatus]);
            $command = new BulkCommand(
                entity: 'student',
                action: $action,
                mode: 'current_page',
                ids: [$student->id],
                actor_id: $actor->id,
                request_fingerprint: 'property-4-request-'.$run,
                selection_reference: 'property-4-selection-'.$run,
            );

            $first = $service->execute($command, $actor);
            $stateAfterFirst = $student->refresh()->status->value;
            $second = $service->execute($command, $actor);
            $stateAfterSecond = $student->refresh()->status->value;

            foreach ([$first, $second] as $index => $result) {
                $this->assertSame([1, 1, 0, 0], [$result->total, $result->succeeded, $result->skipped, $result->failed], 'Same-state run '.$run.' attempt '.$index.' must succeed.');
                $this->assertSame('complete_success', $result->outcome->value, 'Same-state run '.$run.' attempt '.$index.' must be complete.');
                $this->assertSame([$student->id], array_map(static fn (BulkItemResultData $item): int|string => $item->id, $result->items));
            }

            $this->assertSame($initialStatus, $stateAfterFirst, 'Same-state run '.$run.' must preserve the first persisted state.');
            $this->assertSame($stateAfterFirst, $stateAfterSecond, 'Same-state run '.$run.' must be idempotent.');
        }
    }

    /** @return array<int, BulkItemResultData> */
    private function generatedItems(int $run): array
    {
        $count = $run % 8;
        $statuses = [
            BulkItemResultStatusEnum::Succeeded,
            BulkItemResultStatusEnum::Skipped,
            BulkItemResultStatusEnum::Failed,
        ];
        $items = [];

        for ($index = 0; $index < $count; $index++) {
            $status = $statuses[($run + $index) % count($statuses)];
            $items[] = match ($status) {
                BulkItemResultStatusEnum::Succeeded => new BulkItemResultData(100_000 + ($run * 10) + $index, $status),
                BulkItemResultStatusEnum::Skipped => new BulkItemResultData(
                    100_000 + ($run * 10) + $index,
                    $status,
                    'not_found',
                    'The selected record is no longer available.',
                    'not_found',
                ),
                BulkItemResultStatusEnum::Failed => new BulkItemResultData(
                    100_000 + ($run * 10) + $index,
                    $status,
                    'processing_error',
                    'The selected record could not be processed.',
                    'processing_error',
                ),
            };
        }

        return $items;
    }

    /** @param array<int, BulkItemResultData> $items @return array{succeeded: int, skipped: int, failed: int} */
    private function counts(array $items): array
    {
        return [
            'succeeded' => count(array_filter($items, static fn (BulkItemResultData $item): bool => $item->status === BulkItemResultStatusEnum::Succeeded)),
            'skipped' => count(array_filter($items, static fn (BulkItemResultData $item): bool => $item->status === BulkItemResultStatusEnum::Skipped)),
            'failed' => count(array_filter($items, static fn (BulkItemResultData $item): bool => $item->status === BulkItemResultStatusEnum::Failed)),
        ];
    }

    /** @param array<string, mixed> $payload */
    private function rehydrate(array $payload): BulkResultData
    {
        $items = array_map(
            static fn (array $item): BulkItemResultData => new BulkItemResultData(
                id: $item['id'],
                status: $item['status'],
                reason_category: $item['reason']['category'] ?? null,
                reason_message: $item['reason']['message'] ?? null,
                reason_identifier: $item['reason']['identifier'] ?? null,
            ),
            $payload['items'],
        );

        return new BulkResultData(
            entity: $payload['entity'],
            action: $payload['action'],
            mode: $payload['mode'],
            total: $payload['total'],
            succeeded: $payload['succeeded'],
            skipped: $payload['skipped'],
            failed: $payload['failed'],
            items: $items,
            selection_reference: $payload['selection_reference'],
            context_fingerprint: $payload['context_fingerprint'],
            outcome: $payload['outcome'],
        );
    }
}
