<?php

namespace Tests\Unit\Services;

use App\DTOs\ListContextDefinition;
use App\DTOs\ListFilterDefinition;
use App\Services\Lists\ListContextNormalizer;
use App\Services\SelectionContextService;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SelectionContextServiceTest extends TestCase
{
    private SelectionContextService $service;
    private ListContextDefinition $definition;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SelectionContextService(new ListContextNormalizer(), 'selection-test-key', 900);
        $this->definition = new ListContextDefinition(
            entity: 'teachers',
            sortable: ['full_name', 'created_at'],
            default_sort: 'full_name',
            default_direction: 'asc',
            filters: [new ListFilterDefinition('status', ListFilterDefinition::TYPE_STRING, ['active', 'inactive'])],
        );
    }

    public function test_context_fingerprint_and_signature_exclude_page(): void
    {
        $now = new DateTimeImmutable('2026-01-01T12:00:00Z');
        $first = $this->service->create(
            (new ListContextNormalizer())->normalize($this->definition, ['status' => 'active', 'page' => 1]),
            $now,
        );
        $laterPage = $this->service->create(
            (new ListContextNormalizer())->normalize($this->definition, ['status' => 'active', 'page' => 7]),
            $now,
        );

        $this->assertSame($first->context_fingerprint, $laterPage->context_fingerprint);
        $this->assertSame($first->signature, $laterPage->signature);
        $this->assertArrayNotHasKey('page', $first->toArray());
    }

    public function test_tamper_expiry_and_entity_mismatch_are_rejected(): void
    {
        $now = new DateTimeImmutable('2026-01-01T12:00:00Z');
        $context = $this->service->create(
            (new ListContextNormalizer())->normalize($this->definition, ['status' => 'active']),
            $now,
        );

        $tampered = $context->toArray();
        $tampered['filters']['status'] = 'inactive';
        $this->expectException(InvalidArgumentException::class);
        $this->service->verify($tampered, 'teachers', $now);
    }

    public function test_expired_and_wrong_entity_contexts_are_rejected(): void
    {
        $now = new DateTimeImmutable('2026-01-01T12:00:00Z');
        $context = $this->service->create(
            (new ListContextNormalizer())->normalize($this->definition, []),
            $now,
        );

        $this->assertFalse($this->service->isValid($context, 'teachers', $now->modify('+901 seconds')));
        $this->assertFalse($this->service->isValid($context, 'students', $now));
    }

    public function test_reconstruction_rejects_a_signed_context_that_is_no_longer_reproducible(): void
    {
        $now = new DateTimeImmutable('2026-01-01T12:00:00Z');
        $context = $this->service->create(
            (new ListContextNormalizer())->normalize($this->definition, ['status' => 'active']),
            $now,
        );
        $changedDefinition = new ListContextDefinition(
            entity: 'teachers',
            sortable: ['full_name', 'created_at'],
            default_sort: 'full_name',
            default_direction: 'asc',
            filters: [new ListFilterDefinition('status', ListFilterDefinition::TYPE_STRING, ['inactive'])],
        );

        $this->expectException(InvalidArgumentException::class);
        $this->service->reconstruct($context, $changedDefinition, $now);
    }

    public function test_reconstruction_is_canonical_and_always_starts_at_page_one(): void
    {
        $now = new DateTimeImmutable('2026-01-01T12:00:00Z');
        $context = $this->service->create(
            (new ListContextNormalizer())->normalize($this->definition, [
                'search' => '  علی  ', 'status' => 'active', 'sort' => 'created_at',
                'direction' => 'desc', 'page' => 9,
            ]),
            $now,
        );

        $reconstructed = $this->service->reconstruct($context, $this->definition, $now);

        $this->assertSame(1, $reconstructed->page);
        $this->assertSame('علی', $reconstructed->search);
        $this->assertSame(['status' => 'active'], $reconstructed->filters);
        $this->assertSame('created_at', $reconstructed->sort);
        $this->assertSame('desc', $reconstructed->direction);
    }
}
