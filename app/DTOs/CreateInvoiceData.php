<?php

namespace App\DTOs;

use Carbon\Carbon;

readonly class CreateInvoiceData
{
    public function __construct(
        public Carbon  $issueDate,
        public Carbon  $dueDate,
        public ?int    $enrollmentId = null,
        public float   $tax          = 0,
        public string  $currency     = 'IRR',
        public ?string $notes        = null,
    ) {}
}
