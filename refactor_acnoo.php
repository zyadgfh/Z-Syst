<?php

$base_dir = __DIR__;

$dirs_to_check = [
    $base_dir . '/app/Http/Controllers',
    $base_dir . '/Modules/Landing/App/Http/Controllers',
];

$renamed_classes = [];
$files_to_update = [
    $base_dir . '/routes/admin.php',
    $base_dir . '/routes/api.php',
    $base_dir . '/Modules/Landing/routes/admin.php',
];

function getDirContents($dir, &$results = array()) {
    $files = scandir($dir);
    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (!is_dir($path)) {
            $results[] = $path;
        } else if ($value != "." && $value != "..") {
            getDirContents($path, $results);
            $results[] = $path;
        }
    }
    return $results;
}

foreach ($dirs_to_check as $d) {
    if (!is_dir($d)) continue;
    $files = [];
    getDirContents($d, $files);
    
    foreach ($files as $f) {
        if (!is_file($f)) continue;
        if (substr($f, -4) !== '.php') continue;
        
        $files_to_update[] = $f;
        
        $basename = basename($f);
        if (strpos($basename, 'Acnoo') !== false) {
            $new_basename = str_replace('Acnoo', 'ZSyst', $basename);
            $new_path = str_replace($basename, $new_basename, $f);
            
            $old_class = str_replace('.php', '', $basename);
            $new_class = str_replace('.php', '', $new_basename);
            $renamed_classes[$old_class] = $new_class;
            
            $content = file_get_contents($f);
            $content = str_replace("class $old_class", "class $new_class", $content);
            $content = str_replace('acnooFilter', 'zsystFilter', $content);
            
            file_put_contents($new_path, $content);
            unlink($f);
            
            echo "Renamed $old_class to $new_class\n";
            
            // Add new path to update references inside it
            $files_to_update[] = $new_path;
        }
    }
}

$files_to_update = array_unique($files_to_update);

foreach ($files_to_update as $path) {
    if (file_exists($path) && is_file($path)) {
        $orig_content = file_get_contents($path);
        $content = $orig_content;
        
        foreach ($renamed_classes as $old_class => $new_class) {
            $content = str_replace($old_class, $new_class, $content);
        }
        $content = str_replace('acnooFilter', 'zsystFilter', $content);
        
        if ($content !== $orig_content) {
            file_put_contents($path, $content);
            echo "Updated references in " . basename($path) . "\n";
        }
    }
}

$lang_path = $base_dir . '/lang/en.json';
if (file_exists($lang_path)) {
    $content = file_get_contents($lang_path);
    $orig_content = $content;
    
    $content = str_replace('Acnoo Pharmacy Installer', 'Z-Syst Pharmacy Installer', $content);
    $content = str_replace('Acnoo', 'Z-Syst', $content);
    
    if ($content !== $orig_content) {
        file_put_contents($lang_path, $content);
        echo "Updated lang/en.json\n";
    }
}
