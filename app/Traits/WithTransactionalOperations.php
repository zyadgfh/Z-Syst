<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

trait WithTransactionalOperations
{
    /**
     * Execute a closure within a database transaction.
     *
     * @param callable $operation
     * @return mixed
     * @throws Exception
     */
    protected function executeTransaction(callable $operation)
    {
        try {
            DB::beginTransaction();
            $result = $operation();
            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollback();
            throw $this->handleException($e);
        }
    }

    /**
     * Handle exceptions during transaction.
     *
     * @param Exception $e
     * @return Exception
     */
    protected function handleException(Exception $e): Exception
    {
        Log::error('Transaction failed', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        return $e;
    }

    /**
     * Execute multiple operations in a single transaction.
     *
     * @param array $operations
     * @return array
     * @throws Exception
     */
    protected function executeMultipleTransactions(array $operations): array
    {
        return $this->executeTransaction(function () use ($operations) {
            $results = [];
            foreach ($operations as $operation) {
                $results[] = $operation();
            }
            return $results;
        });
    }
}