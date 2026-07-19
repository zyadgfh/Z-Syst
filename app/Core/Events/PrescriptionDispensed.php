<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Prescription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrescriptionDispensed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Prescription $prescription,
        public readonly array $dispensedItems
    ) {}
}