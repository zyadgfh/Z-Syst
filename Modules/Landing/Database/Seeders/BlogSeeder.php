<?php

namespace Modules\Landing\Database\Seeders;

use Modules\Landing\App\Models\Blog;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $blogs = array(
            array('user_id' => '1','title' => 'How can pharmacy management softwares improve daily.','slug' => 'how-can-pharmacy-management-softwares-improve-daily','image' => 'uploads/25/02/1738836201-27.svg','status' => '1','descriptions' => 'Blessing welcomed ladyship she met humo ured sir breeding her. Six curiosity day assurance bed necessary','tags' => '["Six curiosity","Customer Service in Retail"]','meta' => '{"title":"Good Customer Service in Retail: 9 Characteristics","description":"Blessing welcomed ladyship she met humo ured sir breeding her. Six curiosity day assurance bed necessary"}','created_at' => '2024-04-16 17:11:24','updated_at' => '2025-02-06 16:03:21'),
            array('user_id' => '1','title' => 'How can pharmacy management software improve daily operation?','slug' => 'how-can-pharmacy-management-software-improve-daily-operation','image' => 'uploads/25/02/1738836190-853.svg','status' => '1','descriptions' => 'Blessing welcomed ladyship she met humo ured sir breeding her. Six curiosity day assurance bed necessary','tags' => '["Risks of Inventory"]','meta' => '{"title":"What Are the 10 Risks of Inventory Transfer?","description":"Blessing welcomed ladyship she met humo ured sir breeding her. Six curiosity day assurance bed necessary"}','created_at' => '2024-04-16 17:12:53','updated_at' => '2025-02-06 16:03:10'),
            array('user_id' => '1','title' => 'How can pharmacy managements software improve daily','slug' => 'how-can-pharmacy-managements-software-improve-daily','image' => 'uploads/25/02/1738836177-837.svg','status' => '1','descriptions' => 'Blessing welcomed ladyship she met humo ured sir breeding her. Six curiosity day assurance bed necessary','tags' => '["Trends to Watch","Grocery"]','meta' => '{"title":"What is the Store of the Future? 8 Trends to Watch Out For","description":"Blessing welcomed ladyship she met humo ured sir breeding her. Six curiosity day assurance bed necessary"}','created_at' => '2024-04-16 17:15:42','updated_at' => '2025-02-06 16:02:57'),
            array('user_id' => '1','title' => 'How can pharmacy managements software improve daily operations?','slug' => 'how-can-pharmacy-managements-software-improve-daily-operations','image' => 'uploads/25/02/1738836164-901.svg','status' => '1','descriptions' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been .','tags' => '["payslip", "payment", "Inventory"]','meta' => '{"title":"Provident quis nequ","description":"Lorem Ipsum is simply dummy"}','created_at' => '2025-01-08 10:57:31','updated_at' => '2025-02-06 16:02:44'),
            array('user_id' => '1','title' => 'How can pharmacy management software improve daily','slug' => 'how-can-pharmacy-management-software-improve-daily','image' => 'uploads/25/02/1738836151-567.svg','status' => '1','descriptions' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been .','tags' => '["Shop", "Grocery"]','meta' => '{"title":"Aut fugit officia v","description":"Suscipit non volupta"}','created_at' => '2025-01-08 10:59:01','updated_at' => '2025-02-06 16:02:31'),
            array('user_id' => '1','title' => 'How can pharmacy management software improve daily operations?','slug' => 'how-can-pharmacy-management-software-improve-daily-operations','image' => 'uploads/25/02/1738836135-31.svg','status' => '1','descriptions' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been .','tags' => '["Meat", "Pos Sale"]','meta' => '{"title":"Sunt et reprehenderi","description":"Soluta aliquip quam"}','created_at' => '2025-01-08 11:00:42','updated_at' => '2025-02-06 16:02:15')
          );

        Blog::insert($blogs);
    }
}
