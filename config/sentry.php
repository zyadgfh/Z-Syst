<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sentry DSN
    |--------------------------------------------------------------------------
    |
    | The DSN tells the Sentry SDK where to send events. If this value is
    | empty, the Sentry SDK will not be initialized.
    |
    */
    'dsn' => env('SENTRY_DSN'),

    /*
    |--------------------------------------------------------------------------
    | Capture Release
    |--------------------------------------------------------------------------
    |
    | Capture the release version of the application.
    |
    */
    'capture_release' => true,

    /*
    |--------------------------------------------------------------------------
    | Breadcrumbs
    |--------------------------------------------------------------------------
    |
    | Configure which breadcrumbs to capture.
    |
    */
    'breadcrumbs' => [
        'sql_queries' => true,
        'sql_bindings' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracing
    |--------------------------------------------------------------------------
    |
    | Configure tracing options.
    |
    */
    'traces_sample_rate' => 1.0,
    'profiles_sample_rate' => 1.0,

    /*
    |--------------------------------------------------------------------------
    | User Feedback
    |--------------------------------------------------------------------------
    |
    | Configure user feedback options.
    |
    */
    'user_feedback' => true,

    /*
    |--------------------------------------------------------------------------
    | Context
    |--------------------------------------------------------------------------
    |
    | Additional context to send with events.
    |
    */
    'context' => [
        'app' => true,
        'os' => true,
        'php' => true,
        'server' => true,
        'user' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Exceptions
    |--------------------------------------------------------------------------
    |
    | Exceptions that should not be reported to Sentry.
    |
    */
    'excluded_exceptions' => [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Validation\ValidationException::class,
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
        \Illuminate\Http\Exceptions\ThrottleRequestsException::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Before Send
    |--------------------------------------------------------------------------
    |
    | A callback to modify events before they are sent to Sentry.
    |
    */
    'before_send' => function (\Sentry\Event $event): \Sentry\Event {
        // Don't send events from local environment
        if (app()->environment('local')) {
            return null;
        }

        return $event;
    },

    /*
    |--------------------------------------------------------------------------
    | Options
    |--------------------------------------------------------------------------
    |
    | Additional options to pass to the Sentry SDK.
    |
    */
    'options' => [
        'attach_stacktrace' => true,
        'send_default_pii' => true,
    ],
];