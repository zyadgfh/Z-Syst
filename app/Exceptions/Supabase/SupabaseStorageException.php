<?php

namespace App\Exceptions\Supabase;

class SupabaseStorageException extends SupabaseException
{
    public function __construct(string $message = 'Supabase storage operation failed', array $context = [], Exception $previous = null)
    {
        parent::__construct($message, $context, $previous);
    }
}