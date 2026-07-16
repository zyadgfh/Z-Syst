<?php

// Load Composer autoload
require __DIR__ . '/../vendor/autoload.php';

// Convert PHP deprecations to exceptions so PHPUnit reports precise stack traces
set_error_handler(function (int $severity, string $message, string $file, int $line) {
    if (($severity & (E_DEPRECATED | E_USER_DEPRECATED)) !== 0) {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    }
    // Let other errors fall through to the normal handler
    return false;
});
