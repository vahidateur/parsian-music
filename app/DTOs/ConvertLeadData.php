<?php

namespace App\DTOs;

use App\Enums\SkillLevelEnum;
use Carbon\Carbon;

readonly class ConvertLeadData
{
    public function __construct(
        /**
         * When provided, a StudentEnrollment is created using the lead's
         * preferred_instrument and preferred_teacher.
         * Omit to convert without creating an enrollment.
         */
        public ?SkillLevelEnum $skillLevel = null,

        /** Enrollment start date — defaults to today when skillLevel is given. */
        public ?Carbon $startDate = null,

        /** Additional notes appended to the new Student record. */
        public ?string $notes = null,
    ) {}

    public function shouldCreateEnrollment(): bool
    {
        return $this->skillLevel !== null;
    }
}
