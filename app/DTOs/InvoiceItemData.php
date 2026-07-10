<?php

namespace App\DTOs;

readonly class InvoiceItemData
{
    public function __construct(
        public string  $title,
        public int     $quantity,
        public float   $unitPrice,
        public float   $discount    = 0,
        public ?string $description = null,
        public int     $sortOrder   = 0,
    ) {}
}
