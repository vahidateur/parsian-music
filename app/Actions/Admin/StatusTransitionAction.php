<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\DTOs\BulkItemResultData;
use App\Enums\BulkActionEnum;
use App\Enums\BulkItemResultStatusEnum;
use App\Enums\StudentStatusEnum;
use App\Enums\TeacherStatusEnum;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/** Executes enum-backed teacher and student status transitions. */
final class StatusTransitionAction
{
    public function execute(Teacher|Student $record, BulkActionEnum|string $action): BulkItemResultData
    {
        $action = $action instanceof BulkActionEnum ? $action : BulkActionEnum::tryFrom(trim($action));

        if (! $action instanceof BulkActionEnum || $action === BulkActionEnum::Delete) {
            return $this->failure($record, 'invalid_action', 'The requested status action is invalid.');
        }

        $enumClass = $record instanceof Teacher ? TeacherStatusEnum::class : StudentStatusEnum::class;
        $rawStatus = $record->getRawOriginal('status');
        $rawStatus = $rawStatus instanceof \BackedEnum ? $rawStatus->value : $rawStatus;
        $current = is_string($rawStatus) ? $enumClass::tryFrom($rawStatus) : null;

        if ($current === null) {
            return $this->failure($record, 'invalid_status', 'The stored status is not a valid entity status.');
        }

        $target = $action === BulkActionEnum::Activate
            ? ($enumClass === TeacherStatusEnum::class ? TeacherStatusEnum::Active : StudentStatusEnum::Active)
            : ($enumClass === TeacherStatusEnum::class ? TeacherStatusEnum::Inactive : StudentStatusEnum::Inactive);

        if ($record instanceof Student && in_array($current, [StudentStatusEnum::Paused, StudentStatusEnum::Graduated], true)) {
            return $this->failure($record, 'invalid_transition', 'This student lifecycle status cannot be changed by this action.');
        }

        // A same-state request is still a successful persisted operation. Eloquent's
        // save() skips an UPDATE when the cast value is not dirty, so write the
        // requested enum value explicitly and keep the in-memory model in sync.
        $record->forceFill(['status' => $target]);
        $attributes = ['status' => $target->value];

        if ($record->usesTimestamps()) {
            $timestamp = $record->freshTimestamp();
            $record->setUpdatedAt($timestamp);
            $attributes['updated_at'] = $record->fromDateTime($timestamp);
        }

        $updated = $record->newQuery()
            ->whereKey($record->getKey())
            ->update($attributes);

        if ($updated !== 1) {
            throw new RuntimeException('The status transition could not be persisted.');
        }

        $record->syncOriginal();

        return new BulkItemResultData($record->getKey(), BulkItemResultStatusEnum::Succeeded);
    }

    public function transition(Teacher|Student $record, BulkActionEnum|string $action): BulkItemResultData
    {
        return $this->execute($record, $action);
    }

    private function failure(Model $record, string $category, string $message): BulkItemResultData
    {
        return new BulkItemResultData(
            $record->getKey(),
            BulkItemResultStatusEnum::Failed,
            $category,
            $message,
            $category,
        );
    }
}
