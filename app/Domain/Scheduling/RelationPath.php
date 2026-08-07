<?php

declare(strict_types=1);

namespace App\Domain\Scheduling;

use App\DTOs\ResolvedRelationPath;
use InvalidArgumentException;
use JsonSerializable;

/** Canonical direct or enrollment-backed identity tuple, without models. */
final readonly class RelationPath implements JsonSerializable
{
    public function __construct(
        public RelationPathType $type,
        public int|string|null $enrollmentId,
        public int|string $studentId,
        public int|string $teacherId,
        public int|string $instrumentId,
    ) {
        foreach ([$studentId, $teacherId, $instrumentId] as $id) {
            self::assertStableId($id);
        }

        if ($type === RelationPathType::Enrollment && $enrollmentId !== null) {
            self::assertStableId($enrollmentId);
        }

        if (($type === RelationPathType::Enrollment) !== ($enrollmentId !== null)) {
            throw new InvalidArgumentException('The relation path enrollment identity is inconsistent.');
        }
    }

    public static function fromResolved(ResolvedRelationPath $path): self
    {
        return new self(RelationPathType::from($path->path_type), $path->enrollment_id, $path->student_id, $path->teacher_id, $path->instrument_id);
    }

    /** @param array<string, mixed> $path */
    public static function fromArray(array $path): self
    {
        return new self(
            RelationPathType::from((string) ($path['path_type'] ?? '')),
            $path['enrollment_id'] ?? null,
            $path['student_id'] ?? '',
            $path['teacher_id'] ?? '',
            $path['instrument_id'] ?? '',
        );
    }

    public function hasTuple(int|string $studentId, int|string $teacherId, int|string $instrumentId): bool
    {
        return (string) $this->studentId === (string) $studentId
            && (string) $this->teacherId === (string) $teacherId
            && (string) $this->instrumentId === (string) $instrumentId;
    }

    /** @return array<string, int|string|null> */
    public function jsonSerialize(): array
    {
        return [
            'path_type' => $this->type->value,
            'enrollment_id' => $this->enrollmentId,
            'student_id' => $this->studentId,
            'teacher_id' => $this->teacherId,
            'instrument_id' => $this->instrumentId,
        ];
    }

    private static function assertStableId(int|string $id): void
    {
        if ((is_int($id) && $id > 0) || (is_string($id) && ctype_digit($id) && (int) $id > 0)) {
            return;
        }

        throw new InvalidArgumentException('Relation identifiers must be stable.');
    }
}
