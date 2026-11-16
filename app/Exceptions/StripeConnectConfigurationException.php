<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a required Stripe Connect environment variable is missing or still
 * set to an unconfigured placeholder (such as `<<<NEEDED:...>>>`).
 *
 * Carrying the env variable name and setup guidance lets controllers and views
 * present a clear, actionable message instead of an opaque Stripe auth failure.
 */
class StripeConnectConfigurationException extends RuntimeException
{
    public function __construct(
        public readonly string $envVariable,
        public readonly string $guidance,
        ?string $message = null,
    ) {
        parent::__construct(
            $message ?? "Stripe Connect configuration error: {$envVariable} is not configured. {$guidance}"
        );
    }
}
