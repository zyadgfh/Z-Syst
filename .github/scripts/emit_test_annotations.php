<?php

/** Emit GitHub annotations for failed tests from a JUnit log (CI-only tool). */

$file = $argv[1] ?? 'junit.xml';
if (!is_file($file)) {
    echo "::error::no junit produced\n";
    exit(0);
}

$xml = @simplexml_load_file($file);
if ($xml === false) {
    echo "::error::junit log unreadable: {$file}\n";
    exit(0);
}

$count = 0;
foreach ($xml->xpath('//testcase') as $case) {
    foreach ($case->children() as $child) {
        if (! in_array($child->getName(), ['failure', 'error'], true)) {
            continue;
        }
        $msg = (string) ($child['message'] ?? '');
        $name = (string) $case['name'];
        $class = (string) $case['class'];
        $flat = trim(str_replace(["\r", "\n"], ' ', $class.'::'.$name.' — '.$msg));
        echo '::error::'.mb_substr($flat, 0, 380)."\n";
        if (++$count >= 25) {
            exit(0);
        }
    }
}

echo "reported {$count} failure(s)\n";
