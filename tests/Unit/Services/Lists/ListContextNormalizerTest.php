<?php

namespace Tests\Unit\Services\Lists;

use App\DTOs\ListContext;
use App\DTOs\ListContextDefinition;
use App\DTOs\ListFilterDefinition;
use App\Services\Lists\ListContextNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Contract of the canonical List_Context normalization.
 *
 * Covers Requirements 5.2, 5.3, 5.4, 5.5, 5.8, 5.11 and 16.2.
 */
class ListContextNormalizerTest extends TestCase
{
    private ListContextNormalizer $normalizer;

    private ListContextDefinition $definition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = new ListContextNormalizer();

        $this->definition = new ListContextDefinition(
            entity: 'teachers',
            sortable: ['full_name', 'phone', 'status', 'created_at'],
            default_sort: 'full_name',
            default_direction: ListContextDefinition::DIRECTION_ASC,
            filters: [
                new ListFilterDefinition('status', ListFilterDefinition::TYPE_STRING, ['active', 'inactive']),
                new ListFilterDefinition('instrument_id', ListFilterDefinition::TYPE_INT),
                new ListFilterDefinition('archived', ListFilterDefinition::TYPE_BOOL, [], false),
            ],
            perPageAllowList: [20, 50],
        );
    }

    public function test_empty_input_produces_documented_defaults(): void
    {
        $context = $this->normalizer->normalize($this->definition, []);

        $this->assertSame('teachers', $context->entity);
        $this->assertNull($context->search);
        $this->assertSame(['archived' => false], $context->filters);
        $this->assertSame('full_name', $context->sort);
        $this->assertSame('asc', $context->direction);
        $this->assertSame(1, $context->page);
        $this->assertSame(20, $context->per_page);
        $this->assertSame([], $context->normalized_query);
        $this->assertNotSame('', $context->context_fingerprint);
    }

    public function test_search_is_trimmed_and_persian_arabic_equivalents_are_canonical(): void
    {
        $arabic = $this->normalizer->normalize($this->definition, ['search' => "  علي  كريمي\t ٠٩١٢ "]);
        $persian = $this->normalizer->normalize($this->definition, ['search' => 'علی کریمی 0912']);

        $this->assertSame('علی کریمی 0912', $arabic->search);
        $this->assertSame($persian->search, $arabic->search);
        $this->assertSame($persian->context_fingerprint, $arabic->context_fingerprint);
    }

    public function test_distinct_persian_letters_are_preserved(): void
    {
        $context = $this->normalizer->normalize($this->definition, ['search' => 'آرش مسئول مؤسسه']);

        $this->assertSame('آرش مسئول مؤسسه', $context->search);
    }

    public function test_search_is_truncated_to_one_hundred_characters(): void
    {
        $context = $this->normalizer->normalize($this->definition, ['search' => str_repeat('ب', 150)]);

        $this->assertSame(100, mb_strlen((string) $context->search));
    }

    public function test_blank_search_becomes_null_and_is_absent_from_query(): void
    {
        $context = $this->normalizer->normalize($this->definition, ['search' => "   \n ", 'page' => '3']);

        $this->assertNull($context->search);
        $this->assertSame(['page' => 3], $context->normalized_query);
    }

    public function test_unknown_and_invalid_filters_are_ignored_and_defaults_applied(): void
    {
        $context = $this->normalizer->normalize($this->definition, [
            'status' => 'deleted',
            'instrument_id' => '7; drop table teachers',
            'secret_flag' => '1',
            'archived' => 'yes',
        ]);

        $this->assertSame(['archived' => true], $context->filters);
        $this->assertArrayNotHasKey('secret_flag', $context->normalized_query);
        $this->assertArrayNotHasKey('status', $context->normalized_query);
        $this->assertArrayNotHasKey('instrument_id', $context->normalized_query);
    }

    public function test_valid_filters_are_typed_and_preserved_in_query(): void
    {
        $context = $this->normalizer->normalize($this->definition, [
            'status' => 'inactive',
            'instrument_id' => '12',
        ]);

        $this->assertSame(['archived' => false, 'instrument_id' => 12, 'status' => 'inactive'], $context->filters);
        $this->assertSame(['instrument_id' => 12, 'status' => 'inactive'], $context->normalized_query);
    }

    public function test_sort_column_outside_whitelist_falls_back_to_default(): void
    {
        $context = $this->normalizer->normalize($this->definition, [
            'sort' => 'teachers.password) --',
            'direction' => 'DROP',
        ]);

        $this->assertSame('full_name', $context->sort);
        $this->assertSame('asc', $context->direction);
        $this->assertSame([], $context->normalized_query);
    }

    public function test_whitelisted_sort_and_direction_are_accepted_case_insensitively(): void
    {
        $context = $this->normalizer->normalize($this->definition, [
            'sort' => 'created_at',
            'direction' => 'DESC',
        ]);

        $this->assertSame('created_at', $context->sort);
        $this->assertSame('desc', $context->direction);
        $this->assertSame(['direction' => 'desc', 'sort' => 'created_at'], $context->normalized_query);
    }

    public function test_page_and_per_page_fall_back_to_contract_values(): void
    {
        $invalid = $this->normalizer->normalize($this->definition, ['page' => '-4', 'per_page' => '999']);

        $this->assertSame(1, $invalid->page);
        $this->assertSame(20, $invalid->per_page);

        $allowed = $this->normalizer->normalize($this->definition, ['page' => '3', 'per_page' => '50']);

        $this->assertSame(3, $allowed->page);
        $this->assertSame(50, $allowed->per_page);
        $this->assertSame(['page' => 3, 'per_page' => 50], $allowed->normalized_query);
    }

    public function test_normalized_query_round_trips_to_the_same_context(): void
    {
        $context = $this->normalizer->normalize($this->definition, [
            'search' => '  كريمي ',
            'status' => 'active',
            'sort' => 'phone',
            'direction' => 'desc',
            'page' => '2',
            'per_page' => '50',
        ]);

        $reparsed = $this->normalizer->normalize($this->definition, $context->normalized_query);

        $this->assertEquals($context, $reparsed);
        $this->assertSame($context->context_fingerprint, $reparsed->context_fingerprint);
    }

    public function test_context_is_immutable_and_exposes_query_parameters(): void
    {
        $context = $this->normalizer->normalize($this->definition, ['status' => 'active']);

        $this->assertInstanceOf(ListContext::class, $context);
        $this->assertSame($context->normalized_query, $context->queryParameters());
        $this->assertTrue((new \ReflectionClass(ListContext::class))->isReadOnly());
    }

    public function test_definition_rejects_a_default_sort_outside_the_whitelist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ListContextDefinition(
            entity: 'rooms',
            sortable: ['name'],
            default_sort: 'created_at',
        );
    }
}
