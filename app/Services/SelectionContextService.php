<?php

namespace App\Services;

use App\DTOs\FilterContext;
use App\DTOs\Filter_Context;
use App\DTOs\ListContext;
use App\DTOs\ListContextDefinition;
use App\Services\Lists\ListContextNormalizer;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Issues and validates immutable selection contexts for all-filtered bulk mode.
 *
 * The signed snapshot contains only entity, search/filters and sort direction;
 * page and per-page are deliberately excluded so a context can be reused over
 * the complete filtered result set and never trusts client pagination.
 */
final class SelectionContextService
{
    public const DEFAULT_TTL_SECONDS = 900;

    public function __construct(
        private readonly ListContextNormalizer $normalizer,
        ?string $signingKey = null,
        private readonly int $ttlSeconds = self::DEFAULT_TTL_SECONDS,
    ) {
        $configuredKey = $signingKey ?? (string) config('app.key', '');
        $this->signingKey = $this->decodeApplicationKey($configuredKey);

        if ($this->signingKey === '') {
            throw new InvalidArgumentException('A signing key is required for selection contexts.');
        }
        if ($this->ttlSeconds <= 0) {
            throw new InvalidArgumentException('Selection context expiry must be positive.');
        }
    }

    private readonly string $signingKey;

    public function create(ListContext $context, ?DateTimeInterface $now = null): Filter_Context
    {
        $expiresAt = $this->clock($now)->modify("+{$this->ttlSeconds} seconds");
        $snapshot = $this->snapshotFromListContext($context);

        return $this->signedContext(
            $snapshot,
            $expiresAt,
            $this->fingerprint($snapshot),
        );
    }

    /** Alias kept explicit for callers at the list/query boundary. */
    public function issue(ListContext $context, ?DateTimeInterface $now = null): Filter_Context
    {
        return $this->create($context, $now);
    }

    /**
     * Verify a DTO, serialized payload or opaque token before any query uses it.
     *
     * @param FilterContext|array<string, mixed>|string $context
     */
    public function verify(
        FilterContext|array|string $context,
        ?string $expectedEntity = null,
        ?DateTimeInterface $now = null,
    ): Filter_Context {
        $verified = $this->toContext($context);
        $snapshot = $this->snapshotFromContext($verified);

        if ($expectedEntity !== null && $verified->entity !== trim($expectedEntity)) {
            throw new InvalidArgumentException('Selection context entity is invalid.');
        }
        if ($verified->context_fingerprint !== $this->fingerprint($snapshot)) {
            throw new InvalidArgumentException('Selection context fingerprint is invalid.');
        }
        if ($verified->signature === null || ! hash_equals(
            $this->signature($snapshot, $verified->expires_at, $verified->context_fingerprint),
            $verified->signature,
        )) {
            throw new InvalidArgumentException('Selection context signature is invalid.');
        }
        if ($verified->isExpired($now)) {
            throw new InvalidArgumentException('Selection context has expired.');
        }

        return new Filter_Context(
            entity: $verified->entity,
            search: $verified->search,
            filters: $snapshot['filters'],
            sort: $verified->sort,
            direction: $verified->direction,
            context_fingerprint: $verified->context_fingerprint,
            expires_at: $verified->expires_at,
            signature: $verified->signature,
        );
    }

    public function isValid(
        FilterContext|array|string $context,
        ?string $expectedEntity = null,
        ?DateTimeInterface $now = null,
    ): bool {
        try {
            $this->verify($context, $expectedEntity, $now);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Rebuild the canonical list context at page one for server-side resolution.
     * The definition remains the authority for filter types and sort whitelist.
     *
     * @param FilterContext|array<string, mixed>|string $context
     */
    public function reconstruct(
        FilterContext|array|string $context,
        ListContextDefinition $definition,
        ?DateTimeInterface $now = null,
    ): ListContext {
        $verified = $this->verify($context, $definition->entity, $now);
        $reconstructed = $this->normalizer->normalize($definition, $this->queryInput($verified));

        // A signed snapshot must still describe the same canonical query when
        // the server rebuilds it. If a filter/sort was removed or changed
        // since issuance, normalizing it away would otherwise broaden or alter
        // an all-filtered operation instead of rejecting the stale context.
        if ($this->snapshotFromListContext($reconstructed) !== $this->snapshotFromContext($verified)) {
            throw new InvalidArgumentException('Selection context is no longer reproducible.');
        }

        return $reconstructed;
    }

    /**
     * Query input for an all-filtered operation; it intentionally has no page.
     *
     * @return array<string, string|int|bool>
     */
    public function queryInput(FilterContext $context): array
    {
        $input = [];
        if ($context->search !== null) {
            $input[ListContextNormalizer::SEARCH_KEY] = $context->search;
        }
        foreach ($context->filters as $name => $value) {
            if ($value !== null) {
                $input[$name] = $value;
            }
        }
        $input[ListContextNormalizer::SORT_KEY] = $context->sort;
        $input[ListContextNormalizer::DIRECTION_KEY] = $context->direction;
        ksort($input);

        return $input;
    }

    /** Return an opaque transport representation suitable for a request field. */
    public function token(FilterContext $context): string
    {
        $verified = $this->verify($context, null);
        $payload = $verified->toArray();
        unset($payload['signature']);
        $payload['signature'] = $verified->signature;

        return $this->base64UrlEncode($this->json($payload));
    }

    /** @param FilterContext|array<string, mixed>|string $context */
    private function toContext(FilterContext|array|string $context): FilterContext
    {
        if ($context instanceof FilterContext) {
            return $context;
        }
        if (is_string($context)) {
            $decoded = $this->base64UrlDecode($context);
            $context = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
        }
        if (! is_array($context)) {
            throw new InvalidArgumentException('Selection context payload is invalid.');
        }

        $expiresAt = $context['expires_at'] ?? null;
        return new Filter_Context(
            entity: (string) ($context['entity'] ?? ''),
            search: isset($context['search']) ? (string) $context['search'] : null,
            filters: is_array($context['filters'] ?? null) ? $context['filters'] : [],
            sort: (string) ($context['sort'] ?? ''),
            direction: (string) ($context['direction'] ?? ''),
            context_fingerprint: (string) ($context['context_fingerprint'] ?? ''),
            expires_at: $expiresAt === null ? null : new DateTimeImmutable((string) $expiresAt),
            signature: isset($context['signature']) ? (string) $context['signature'] : null,
        );
    }

    /** @return array{entity: string, search: ?string, filters: array<string, string|int|bool>, sort: string, direction: string} */
    private function snapshotFromListContext(ListContext $context): array
    {
        return $this->canonicalSnapshot(
            $context->entity,
            $context->search,
            $context->filters,
            $context->sort,
            $context->direction,
        );
    }

    /** @return array{entity: string, search: ?string, filters: array<string, string|int|bool>, sort: string, direction: string} */
    private function snapshotFromContext(FilterContext $context): array
    {
        return $this->canonicalSnapshot(
            $context->entity,
            $context->search,
            $context->filters,
            $context->sort,
            $context->direction,
        );
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{entity: string, search: ?string, filters: array<string, string|int|bool>, sort: string, direction: string}
     */
    private function canonicalSnapshot(
        string $entity,
        ?string $search,
        array $filters,
        string $sort,
        string $direction,
    ): array {
        $canonicalFilters = [];
        foreach ($filters as $name => $value) {
            if (! is_string($name) || in_array($name, ['page', 'per_page'], true)) {
                continue;
            }
            if (is_string($value) || is_int($value) || is_bool($value)) {
                $canonicalFilters[trim($name)] = $value;
            }
        }
        ksort($canonicalFilters);

        return [
            'entity' => trim($entity),
            'search' => $search === null ? null : trim($search),
            'filters' => $canonicalFilters,
            'sort' => trim($sort),
            'direction' => strtolower(trim($direction)),
        ];
    }

    /** @param array{entity: string, search: ?string, filters: array<string, string|int|bool>, sort: string, direction: string} $snapshot */
    private function fingerprint(array $snapshot): string
    {
        return hash('sha256', $this->json($snapshot));
    }

    /** @param array{entity: string, search: ?string, filters: array<string, string|int|bool>, sort: string, direction: string} $snapshot */
    private function signedContext(array $snapshot, DateTimeImmutable $expiresAt, string $fingerprint): Filter_Context
    {
        return new Filter_Context(
            entity: $snapshot['entity'],
            search: $snapshot['search'],
            filters: $snapshot['filters'],
            sort: $snapshot['sort'],
            direction: $snapshot['direction'],
            context_fingerprint: $fingerprint,
            expires_at: $expiresAt,
            signature: $this->signature($snapshot, $expiresAt, $fingerprint),
        );
    }

    /** @param array{entity: string, search: ?string, filters: array<string, string|int|bool>, sort: string, direction: string} $snapshot */
    private function signature(array $snapshot, ?DateTimeInterface $expiresAt, string $fingerprint): string
    {
        return hash_hmac(
            'sha256',
            $this->json([
                'snapshot' => $snapshot,
                'context_fingerprint' => $fingerprint,
                'expires_at' => $expiresAt?->format(DateTimeInterface::ATOM),
            ]),
            $this->signingKey,
        );
    }

    private function clock(?DateTimeInterface $now): DateTimeImmutable
    {
        return $now === null ? new DateTimeImmutable() : DateTimeImmutable::createFromInterface($now);
    }

    private function decodeApplicationKey(string $key): string
    {
        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            return $decoded === false ? '' : $decoded;
        }

        return $key;
    }

    /** @param mixed $value */
    private function json($value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $decoded = base64_decode(strtr($value . str_repeat('=', (4 - strlen($value) % 4) % 4), '-_', '+/'), true);
        if ($decoded === false) {
            throw new InvalidArgumentException('Selection context token is invalid.');
        }

        return $decoded;
    }
}
