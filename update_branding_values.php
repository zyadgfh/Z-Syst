<?php

$path = 'Modules/Landing/Database/Seeders/OptionTableSeeder.php';
$content = file_get_contents($path);
$replacements = [
    'https://www.instagram.com/acnooteam/' => 'https://www.instagram.com/zsyst/',
    'https://www.facebook.com/acnooteam' => 'https://www.facebook.com/zsyst',
    'https:\/\/www.instagram.com\/acnooteam\/' => 'https:\/\/www.instagram.com\/zsyst\/',
    'https:\/\/www.facebook.com\/acnooteam' => 'https:\/\/www.facebook.com\/zsyst',
    'QUIZYS.IN' => 'Z-Syst',
    'Quizys' => 'Z-Syst',
    'Quizys app' => 'Z-Syst app',
    'quizys' => 'z-syst',
    'acnooteam' => 'zsyst',
    'Acnoo' => 'Z-Syst',
    'acnoo' => 'z-syst',
];
foreach ($replacements as $old => $new) {
    $content = str_replace($old, $new, $content);
}
file_put_contents($path, $content);
echo "branding-updated\n";
