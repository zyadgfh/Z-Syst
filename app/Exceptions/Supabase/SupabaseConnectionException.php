<?php

namespace App\Exceptions\Supabase;

class SupabaseConnectionException extends SupabaseException
{
    public function __construct(string $message = 'Failed to connect to Supabase', array $context = [], Exception $previous = null)
    {
        parent::__construct($message, $context, $previous);
    }
}