<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            ['name' => 'Comming Soon', 'icon' => 'uploads/24/04/1713263132-74.png', 'status' => '1', 'created_at' => '2024-04-16 16:25:32', 'updated_at' => '2024-04-16 16:25:32'],
            ['name' => 'Portuguese (BR)', 'icon' => 'uploads/24/04/1713263352-609.png', 'status' => '1', 'created_at' => '2024-04-16 16:29:12', 'updated_at' => '2024-04-16 16:29:12'],
            ['name' => 'Chinese (TW)', 'icon' => 'uploads/24/04/1713263390-316.png', 'status' => '1', 'created_at' => '2024-04-16 16:29:50', 'updated_at' => '2024-04-16 16:29:50'],
            ['name' => 'Chinese (CN)', 'icon' => 'uploads/24/04/1713263431-511.png', 'status' => '1', 'created_at' => '2024-04-16 16:30:31', 'updated_at' => '2024-04-16 16:30:31'],
            ['name' => 'Azerbaijani', 'icon' => 'uploads/24/04/1713263469-306.png', 'status' => '1', 'created_at' => '2024-04-16 16:31:09', 'updated_at' => '2024-04-16 16:31:09'],
            ['name' => 'Kazakhastan', 'icon' => 'uploads/24/04/1713263502-600.png', 'status' => '1', 'created_at' => '2024-04-16 16:31:42', 'updated_at' => '2024-04-16 16:31:42'],
            ['name' => 'Tigrinya', 'icon' => 'uploads/24/04/1713263543-203.png', 'status' => '1', 'created_at' => '2024-04-16 16:32:23', 'updated_at' => '2024-04-16 16:32:23'],
            ['name' => 'Burmuse', 'icon' => 'uploads/24/04/1713263573-197.png', 'status' => '1', 'created_at' => '2024-04-16 16:32:53', 'updated_at' => '2024-04-16 16:32:53'],
            ['name' => 'Swahili', 'icon' => 'uploads/24/04/1713263661-746.png', 'status' => '1', 'created_at' => '2024-04-16 16:33:31', 'updated_at' => '2024-04-16 16:34:21'],
            ['name' => 'Slovak', 'icon' => 'uploads/24/04/1713263705-676.png', 'status' => '1', 'created_at' => '2024-04-16 16:35:05', 'updated_at' => '2024-04-16 16:35:05'],
            ['name' => 'Albanian', 'icon' => 'uploads/24/04/1713263748-483.png', 'status' => '1', 'created_at' => '2024-04-16 16:35:48', 'updated_at' => '2024-04-16 16:35:48'],
            ['name' => 'Urdu', 'icon' => 'uploads/24/04/1713263785-829.png', 'status' => '1', 'created_at' => '2024-04-16 16:36:25', 'updated_at' => '2024-04-16 16:36:25'],
            ['name' => 'Danish', 'icon' => 'uploads/24/04/1713263817-546.png', 'status' => '1', 'created_at' => '2024-04-16 16:36:57', 'updated_at' => '2024-04-16 16:36:57'],
            ['name' => 'Swedish', 'icon' => 'uploads/24/04/1713263851-61.png', 'status' => '1', 'created_at' => '2024-04-16 16:37:31', 'updated_at' => '2024-04-16 16:37:31'],
            ['name' => 'Marathi', 'icon' => 'uploads/24/04/1713263883-87.png', 'status' => '1', 'created_at' => '2024-04-16 16:38:03', 'updated_at' => '2024-04-16 16:38:03'],
            ['name' => 'Kannada', 'icon' => 'uploads/24/04/1713263922-810.png', 'status' => '1', 'created_at' => '2024-04-16 16:38:42', 'updated_at' => '2024-04-16 16:38:42'],
            ['name' => 'Czech', 'icon' => 'uploads/24/04/1713263954-737.png', 'status' => '1', 'created_at' => '2024-04-16 16:39:14', 'updated_at' => '2024-04-16 16:39:14'],
            ['name' => 'Русский', 'icon' => 'uploads/24/04/1713263985-750.png', 'status' => '1', 'created_at' => '2024-04-16 16:39:45', 'updated_at' => '2024-04-16 16:39:45'],
            ['name' => 'Lao', 'icon' => 'uploads/24/04/1713264024-299.png', 'status' => '1', 'created_at' => '2024-04-16 16:40:24', 'updated_at' => '2024-04-16 16:40:24'],
            ['name' => 'Ukrainian', 'icon' => 'uploads/24/04/1713264051-664.png', 'status' => '1', 'created_at' => '2024-04-16 16:40:51', 'updated_at' => '2024-04-16 16:40:51'],
            ['name' => 'Khmer', 'icon' => 'uploads/24/04/1713264076-901.png', 'status' => '1', 'created_at' => '2024-04-16 16:41:16', 'updated_at' => '2024-04-16 16:41:16'],
            ['name' => 'Serbian', 'icon' => 'uploads/24/04/1713264104-342.png', 'status' => '1', 'created_at' => '2024-04-16 16:41:44', 'updated_at' => '2024-04-16 16:41:44'],
            ['name' => 'Turkish', 'icon' => 'uploads/24/04/1713264131-167.png', 'status' => '1', 'created_at' => '2024-04-16 16:42:11', 'updated_at' => '2024-04-16 16:42:11'],
            ['name' => 'Persian', 'icon' => 'uploads/24/04/1713264160-560.png', 'status' => '1', 'created_at' => '2024-04-16 16:42:40', 'updated_at' => '2024-04-16 16:42:40'],
            ['name' => 'Indonesian', 'icon' => 'uploads/24/04/1713264189-370.png', 'status' => '1', 'created_at' => '2024-04-16 16:43:09', 'updated_at' => '2024-04-16 16:43:09'],
            ['name' => 'Malay', 'icon' => 'uploads/24/04/1713264218-608.png', 'status' => '1', 'created_at' => '2024-04-16 16:43:38', 'updated_at' => '2024-04-16 16:43:38'],
            ['name' => 'Korean', 'icon' => 'uploads/24/04/1713264250-943.png', 'status' => '1', 'created_at' => '2024-04-16 16:44:10', 'updated_at' => '2024-04-16 16:44:10'],
            ['name' => 'Greek', 'icon' => 'uploads/24/04/1713264276-755.png', 'status' => '1', 'created_at' => '2024-04-16 16:44:37', 'updated_at' => '2024-04-16 16:44:37'],
            ['name' => 'Finland', 'icon' => 'uploads/24/04/1713264306-829.png', 'status' => '1', 'created_at' => '2024-04-16 16:45:06', 'updated_at' => '2024-04-16 16:45:06'],
            ['name' => 'Hungarian', 'icon' => 'uploads/24/04/1713264331-326.png', 'status' => '1', 'created_at' => '2024-04-16 16:45:31', 'updated_at' => '2024-04-16 16:45:31'],
            ['name' => 'Polish', 'icon' => 'uploads/24/04/1713264358-886.png', 'status' => '1', 'created_at' => '2024-04-16 16:45:58', 'updated_at' => '2024-04-16 16:45:58'],
            ['name' => 'Bengali', 'icon' => 'uploads/24/04/1713264388-157.png', 'status' => '1', 'created_at' => '2024-04-16 16:46:28', 'updated_at' => '2024-04-16 16:46:28'],
            ['name' => 'Portuguese', 'icon' => 'uploads/24/04/1713264423-206.png', 'status' => '1', 'created_at' => '2024-04-16 16:47:03', 'updated_at' => '2024-04-16 16:47:03'],
            ['name' => 'Hebrew', 'icon' => 'uploads/24/04/1713264450-677.png', 'status' => '1', 'created_at' => '2024-04-16 16:47:30', 'updated_at' => '2024-04-16 16:47:30'],
            ['name' => 'Dutch', 'icon' => 'uploads/24/04/1713264476-832.png', 'status' => '1', 'created_at' => '2024-04-16 16:47:56', 'updated_at' => '2024-04-16 16:47:56'],
            ['name' => 'Bosnian', 'icon' => 'uploads/24/04/1713264505-83.png', 'status' => '1', 'created_at' => '2024-04-16 16:48:25', 'updated_at' => '2024-04-16 16:48:25'],
            ['name' => 'Thai', 'icon' => 'uploads/24/04/1713264534-163.png', 'status' => '1', 'created_at' => '2024-04-16 16:48:54', 'updated_at' => '2024-04-16 16:48:54'],
            ['name' => 'Italian', 'icon' => 'uploads/24/04/1713264559-834.png', 'status' => '1', 'created_at' => '2024-04-16 16:49:19', 'updated_at' => '2024-04-16 16:49:19'],
            ['name' => 'Vietnamese', 'icon' => 'uploads/24/04/1713264586-161.png', 'status' => '1', 'created_at' => '2024-04-16 16:49:46', 'updated_at' => '2024-04-16 16:49:46'],
            ['name' => 'German', 'icon' => 'uploads/24/04/1713264610-223.png', 'status' => '1', 'created_at' => '2024-04-16 16:50:10', 'updated_at' => '2024-04-16 16:50:10'],
            ['name' => 'Romanian', 'icon' => 'uploads/24/04/1713264637-599.png', 'status' => '1', 'created_at' => '2024-04-16 16:50:37', 'updated_at' => '2024-04-16 16:50:37'],
            ['name' => 'Arabic', 'icon' => 'uploads/24/04/1713264667-831.png', 'status' => '1', 'created_at' => '2024-04-16 16:51:07', 'updated_at' => '2024-04-16 16:51:07'],
            ['name' => 'Japanese', 'icon' => 'uploads/24/04/1713264693-992.png', 'status' => '1', 'created_at' => '2024-04-16 16:51:33', 'updated_at' => '2024-04-16 16:51:33'],
            ['name' => 'Spanish', 'icon' => 'uploads/24/04/1713264720-829.png', 'status' => '1', 'created_at' => '2024-04-16 16:52:00', 'updated_at' => '2024-04-16 16:52:00'],
            ['name' => 'French', 'icon' => 'uploads/24/04/1713264745-349.png', 'status' => '1', 'created_at' => '2024-04-16 16:52:25', 'updated_at' => '2024-04-16 16:52:25'],
            ['name' => 'Hindi', 'icon' => 'uploads/24/04/1713264770-181.png', 'status' => '1', 'created_at' => '2024-04-16 16:52:50', 'updated_at' => '2024-04-16 16:52:50'],
            ['name' => 'Chinese', 'icon' => 'uploads/24/04/1713264810-300.png', 'status' => '1', 'created_at' => '2024-04-16 16:53:30', 'updated_at' => '2024-04-16 16:53:30'],
            ['name' => 'English', 'icon' => 'uploads/24/04/1713264836-549.png', 'status' => '1', 'created_at' => '2024-04-16 16:53:56', 'updated_at' => '2024-04-16 16:53:56'],
        ];

        Language::insert($languages);
    }
}
