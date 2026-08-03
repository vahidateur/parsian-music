<?php

namespace App\DTOs;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use JsonSerializable;

/** Immutable, signed selection snapshot; page is intentionally not part of it. */
readonly class FilterContext implements JsonSerializable
{
    /** @param array<string, string|int|bool|null> $filters */
    public function __construct(
        string $entity,
        ?string $search,
        array $filters,
        string $sort,
        string $direction,
        string $context_fingerprint,
        ?DateTimeInterface $expires_at = null,
        ?string $signature = null,
    ) {
        $entity = trim($entity);
        $sort = trim($sort);
        $direction = strtolower(trim($direction));
        if ($entity === '' || $sort === '') {
            throw new InvalidArgumentException('Filter context entity and sort are required.');
        }
        if (! in_array($direction, ['asc', 'desc'], true)) {
            throw new InvalidArgumentException('Filter context direction must be asc or desc.');
        }
        if (trim($context_fingerprint) === '') {
            throw new InvalidArgumentException('Filter context fingerprint is required.');
        }
        foreach ($filters as $name => $value) {
            if (! is_string($name) || trim($name) === '') {
                throw new InvalidArgumentException('Filter names must be non-empty strings.');
            }
            if (in_array($name, ['page', 'per_page'], true)) {
                throw new InvalidArgumentException('Filter context cannot contain pagination fields.');
            }
            if (! is_string($value) && ! is_int($value) && ! is_bool($value) && $value !== null) {
                throw new InvalidArgumentException('Filter values must be scalar or null.');
            }
        }
        $this->entity = $entity;
        $this->sort = $sort;
        $this->direction = $direction;
        $this->context_fingerprint = trim($context_fingerprint);
        $this->filters = $filters;
        $this->expires_at = $expires_at === null ? null : DateTimeImmutable::createFromInterface($expires_at);
        $this->search = $search === null ? null : trim($search);
        $this->signature = $signature === null ? null : trim($signature);
    }

    public readonly string $entity;
    public readonly ?string $search;
    /** @var array<string, string|int|bool|null> */
    public readonly array $filters;
    public readonly string $sort;
    public readonly string $direction;
    public readonly string $context_fingerprint;
    public readonly ?DateTimeImmutable $expires_at;
    public readonly ?string $signature;

    public function isExpired(?DateTimeInterface $now = null): bool
    {
        return $this->expires_at !== null
            && $this->expires_at <= ($now === null ? new DateTimeImmutable() : DateTimeImmutable::createFromInterface($now));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'entity' => $this->entity,
            'search' => $this->search,
            'filters' => $this->filters,
            'sort' => $this->sort,
            'direction' => $this->direction,
            'context_fingerprint' => $this->context_fingerprint,
            'expires_at' => $this->expires_at?->format(DateTimeInterface::ATOM),
            'signature' => $this->signature,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
