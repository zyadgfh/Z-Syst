<?php

namespace App\Exceptions\Supabase;

use Exception;

class SupabaseException extends Exception
{
    protected array $context = [];

    public function __construct(string $message, array $context = [], Exception $previous = null)
    {
        $this->context = $context;
        parent::__construct($message, 0, $previous);
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function render($request)
    {
        return response()->json([
            'success' => false,
            'error' => $this->getMessage(),
            'context' => $this->getSafeContext(),
        ], 500);
    }

    protected function getSafeContext(): array
    {
        return array_intersect_key($this->context, array_flip([
            'table',
            'operation',
            'resource',
            'code',
        ]));
    }
}