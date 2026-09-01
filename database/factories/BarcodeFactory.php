<?php

namespace Database\Factories;

use App\Models\Barcode;
use App\Models\Business;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BarcodeFactory extends Factory
{
    protected $model = Barcode::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'batch_id' => null,
            'barcode_number' => strtoupper(fake()->unique()->bothify('??-#########')),
            'barcode_type' => Barcode::TYPE_CODE128,
            'barcode_image' => null,
            'print_status' => Barcode::STATUS_NOT_PRINTED,
            'printed_at' => null,
            'printed_by' => null,
            'print_count' => 0,
            'size' => Barcode::SIZE_STANDARD,
            'print_settings' => null,
            'is_active' => true,
            'business_id' => Business::factory(),
            'branch_id' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function ean13(): static
    {
        return $this->state(fn (array $attributes) => [
            'barcode_type' => Barcode::TYPE_EAN13,
            'barcode_number' => self::generateEAN13(),
        ]);
    }

    public function upc(): static
    {
        return $this->state(fn (array $attributes) => [
            'barcode_type' => Barcode::TYPE_UPC,
            'barcode_number' => self::generateUPC(),
        ]);
    }

    public function printed(): static
    {
        return $this->state(fn (array $attributes) => [
            'print_status' => Barcode::STATUS_PRINTED,
            'printed_at' => now(),
            'print_count' => 1,
        ]);
    }

    public function reprinted(): static
    {
        return $this->state(fn (array $attributes) => [
            'print_status' => Barcode::STATUS_REPRINTED,
            'printed_at' => now(),
            'print_count' => 2,
        ]);
    }

    private static function generateEAN13(): string
    {
        $prefix = '600';
        $random = str_pad(rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);
        $base = $prefix . $random;
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $base[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }
        return $base . ((10 - ($sum % 10)) % 10);
    }

    private static function generateUPC(): string
    {
        $random = str_pad(rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);
        $sum = 0;
        for ($i = 0; $i < 11; $i++) {
            $digit = (int) $random[$i];
            $sum += ($i % 2 === 0) ? $digit * 3 : $digit;
        }
        return $random . ((10 - ($sum % 10)) % 10);
    }
}
