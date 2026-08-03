<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Resources;

use App\DTOs\BulkItemResultData;
use App\DTOs\BulkResultData;
use App\Http\Resources\BulkResultResource;
use Illuminate\Http\Request;
use Tests\TestCase;

final class BulkResultResourceTest extends TestCase
{
    public function test_result_without_item_details_preserves_selection_metadata(): void
    {
        $result = new BulkResultData(
            entity: 'teacher',
            action: 'deactivate',
            mode: 'all_filtered',
            total: 0,
            succeeded: 0,
            skipped: 0,
            failed: 0,
            selection_reference: 'selection-opaque',
            context_fingerprint: 'context-sha256',
        );

        $payload = BulkResultResource::make($result)->resolve(Request::create('/admin/teachers/bulk'));

        self::assertSame('teacher', $payload['entity']);
        self::assertSame('deactivate', $payload['action']);
        self::assertSame('all_filtered', $payload['mode']);
        self::assertSame('selection-opaque', $payload['selection_reference']);
        self::assertSame('context-sha256', $payload['context_fingerprint']);
        self::assertSame(0, $payload['total']);
        self::assertSame('complete_success', $payload['outcome']);
        self::assertSame([], $payload['items']);
    }

    public function test_partial_items_are_conserved_and_reasons_are_localized_once_per_id(): void
    {
        app()->setLocale('fa');
        $result = new BulkResultData(
            entity: 'student',
            action: 'delete',
            mode: 'current_page',
            total: 2,
            succeeded: 1,
            skipped: 0,
            failed: 1,
            items: [
                new BulkItemResultData(12, 'succeeded'),
                new BulkItemResultData(13, 'failed', 'protected_dependency', 'ignored source text', 'protected_dependency'),
            ],
            selection_reference: 'selection-12-13',
        );

        $resource = BulkResultResource::make($result);
        $payload = $resource->resolve(Request::create('/admin/students/bulk'));
        $response = $resource->response()->getData(true);

        self::assertSame(2, $payload['total']);
        self::assertSame(2, $payload['succeeded'] + $payload['failed'] + $payload['skipped']);
        self::assertSame('partial_success', $payload['outcome']);
        self::assertCount(2, $payload['items']);
        self::assertSame([12, 13], array_column($payload['items'], 'id'));
        self::assertSame('وابستگی محافظت‌شده', $payload['items'][1]['reason']['category']);
        self::assertSame('این رکورد دارای وابستگی‌های محافظت‌شده است.', $payload['items'][1]['reason']['message']);
        self::assertSame('protected_dependency', $payload['items'][1]['reason']['identifier']);
        self::assertSame($payload, $response['data']);
    }
}
