<?php

/** Emit the tail of a failed CI step log as GitHub error annotations. */

$file = $argv[1] ?? 'step.log';
$label = $argv[2] ?? 'step output';
if (!is_file($file) || filesize($file) === 0) {
    echo "::error::{$label}: (no output captured)\n";
    exit(0);
}

$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach (array_slice($lines, -30) as $line) {
    echo '::error::'.mb_substr(str_replace("\t", ' ', $label.' | '.$line), 0, 380)."\n";
}
