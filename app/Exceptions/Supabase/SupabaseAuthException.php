<?php

namespace App\Exceptions\Supabase;

class SupabaseAuthException extends SupabaseException
{
    public function __construct(string $message = 'Supabase authentication failed', array $context = [], Exception $previous = null)
    {
        parent::__construct($message, $context, $previous);
    }
}