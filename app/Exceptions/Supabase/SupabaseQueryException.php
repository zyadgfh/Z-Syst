<?php

namespace App\Exceptions\Supabase;

class SupabaseQueryException extends SupabaseException
{
    public function __construct(string $message = 'Supabase query execution failed', array $context = [], Exception $previous = null)
    {
        parent::__construct($message, $context, $previous);
    }
}