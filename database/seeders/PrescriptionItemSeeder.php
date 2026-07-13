<?php

namespace Database\Seeders;

use App\Models\PrescriptionItem;
use Illuminate\Database\Seeder;

class PrescriptionItemSeeder extends Seeder
{
    public function run(): void
    {
        PrescriptionItem::factory()->count(20)->create();
    }
}
