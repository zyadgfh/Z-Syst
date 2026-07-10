<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = array(
            array('business_id' => '1', 'brandName' => 'Tesla', 'description' => 'Innovative electric vehicles and clean energy solutions.', 'icon' => NULL, 'status' => '1', 'created_at' => '2024-11-05 15:38:10', 'updated_at' => '2024-11-05 15:38:10'),
            array('business_id' => '1', 'brandName' => 'Bugatti', 'description' => 'Luxury sports cars with unmatched speed and performance.', 'icon' => NULL, 'status' => '1', 'created_at' => '2024-11-05 18:58:00', 'updated_at' => '2024-11-05 18:58:00'),
            array('business_id' => '1', 'brandName' => 'Addidas', 'description' => 'Global sportswear brand delivering comfort and style.', 'icon' => NULL, 'status' => '1', 'created_at' => '2024-11-28 21:27:25', 'updated_at' => '2024-11-28 21:27:25'),
            array('business_id' => '1', 'brandName' => 'Puma', 'description' => 'Sport-inspired fashion and high-performance footwear.', 'icon' => 'uploads/24/11/1732786079-799.png', 'status' => '1', 'created_at' => '2024-11-28 21:27:59', 'updated_at' => '2024-11-28 21:27:59'),
            array('business_id' => '1', 'brandName' => 'Levi\'s', 'description' => 'Iconic denim and fashion brand trusted worldwide.', 'icon' => 'uploads/24/11/1732786106-281.png', 'status' => '1', 'created_at' => '2024-11-28 21:28:26', 'updated_at' => '2024-11-28 21:28:26'),
            array('business_id' => '1', 'brandName' => 'H&M', 'description' => 'Trendy clothing and accessories at affordable prices.', 'icon' => 'uploads/24/11/1732786127-117.png', 'status' => '1', 'created_at' => '2024-11-28 21:28:47', 'updated_at' => '2024-11-28 21:28:47'),
            array('business_id' => '1', 'brandName' => 'Rolex', 'description' => 'Prestigious Swiss watches symbolizing luxury and precision.', 'icon' => 'uploads/24/11/1732786146-95.png', 'status' => '1', 'created_at' => '2024-11-28 21:29:06', 'updated_at' => '2024-11-28 21:29:06'),
            array('business_id' => '1', 'brandName' => 'Apple', 'description' => 'Cutting-edge technology and innovative smart devices.', 'icon' => 'uploads/24/11/1732786166-518.png', 'status' => '1', 'created_at' => '2024-11-28 21:29:26', 'updated_at' => '2024-11-28 21:29:26'),
            array('business_id' => '1', 'brandName' => 'Schnell', 'description' => 'High-quality German-engineered household and lifestyle products.', 'icon' => 'uploads/24/11/1732786190-544.png', 'status' => '1', 'created_at' => '2024-11-28 21:29:50', 'updated_at' => '2024-11-28 21:29:50'),
            array('business_id' => '1', 'brandName' => 'Gucci', 'description' => 'Italian luxury fashion house known for bold designs.', 'icon' => 'uploads/24/11/1732786229-315.png', 'status' => '1', 'created_at' => '2024-11-28 21:30:05', 'updated_at' => '2024-11-28 21:30:29'),
            array('business_id' => '1', 'brandName' => 'Zara', 'description' => 'Fast-fashion retailer with modern, stylish collections.', 'icon' => 'uploads/24/11/1732786248-250.png', 'status' => '1', 'created_at' => '2024-11-28 21:30:48', 'updated_at' => '2024-11-28 21:30:48'),
            array('business_id' => '1', 'brandName' => 'Nike', 'description' => 'World-leading sportswear brand inspiring athletes globally.', 'icon' => 'uploads/24/11/1732786269-552.png', 'status' => '1', 'created_at' => '2024-11-28 21:31:10', 'updated_at' => '2024-11-28 21:31:10'),
            array('business_id' => '1', 'brandName' => 'Gillette', 'description' => 'Trusted grooming products for men’s shaving and care.', 'icon' => 'uploads/24/11/1732786288-65.png', 'status' => '1', 'created_at' => '2024-11-28 21:31:28', 'updated_at' => '2024-11-28 21:31:28'),
            array('business_id' => '1', 'brandName' => 'Accenture', 'description' => 'Global consulting and professional services company.', 'icon' => 'uploads/24/11/1732786307-528.png', 'status' => '1', 'created_at' => '2024-11-28 21:31:47', 'updated_at' => '2024-11-28 21:31:47'),
            array('business_id' => '1', 'brandName' => 'Nescafe', 'description' => 'World-famous coffee brand bringing rich taste and aroma.', 'icon' => 'uploads/24/11/1732786332-860.png', 'status' => '1', 'created_at' => '2024-11-28 21:32:12', 'updated_at' => '2024-11-28 21:32:12'),
            array('business_id' => '1', 'brandName' => 'Loreal', 'description' => 'Global leader in beauty, cosmetics, and skincare.', 'icon' => 'uploads/24/11/1732786349-739.png', 'status' => '1', 'created_at' => '2024-11-28 21:32:29', 'updated_at' => '2024-11-28 21:32:29'),
            array('business_id' => '1', 'brandName' => 'Samsung', 'description' => 'Innovative electronics and smart home technology.', 'icon' => 'uploads/25/08/1754890081-674.png', 'status' => '1', 'created_at' => '2025-08-11 17:28:01', 'updated_at' => '2025-08-11 17:28:01'),
            array('business_id' => '1', 'brandName' => 'Nike', 'description' => 'High-performance footwear and apparel for athletes.', 'icon' => 'uploads/25/08/1754890402-214.png', 'status' => '1', 'created_at' => '2025-08-11 17:28:31', 'updated_at' => '2025-08-11 17:33:22'),
            array('business_id' => '1', 'brandName' => 'Apple', 'description' => 'Premium electronics and innovative digital experiences.', 'icon' => 'uploads/25/08/1754890453-456.png', 'status' => '1', 'created_at' => '2025-08-11 17:28:44', 'updated_at' => '2025-08-11 17:34:13'),
            array('business_id' => '1', 'brandName' => 'Philips', 'description' => 'Reliable electronics, lighting, and healthcare technology.', 'icon' => 'uploads/25/08/1754890493-437.png', 'status' => '1', 'created_at' => '2025-08-11 17:28:59', 'updated_at' => '2025-08-11 17:34:53'),
            array('business_id' => '1', 'brandName' => 'Kellogg’s', 'description' => 'Trusted cereal and breakfast food brand loved worldwide.', 'icon' => 'uploads/25/08/1754890574-990.png', 'status' => '1', 'created_at' => '2025-08-11 17:29:36', 'updated_at' => '2025-08-11 17:36:14'),
            array('business_id' => '1', 'brandName' => 'McCain Foods', 'description' => 'Frozen food products delivering taste and convenience.', 'icon' => 'uploads/25/08/1754890617-678.png', 'status' => '1', 'created_at' => '2025-08-11 17:29:50', 'updated_at' => '2025-08-11 17:36:57')
        );

        Brand::insert($brands);
    }
}
