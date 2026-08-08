<?php

namespace App\Helpers;

use App\Exceptions\RenderableException;
use App\Exceptions\TransactionException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class TransactionHelper
{
    /**
     * Execute a callback within a database transaction with proper error handling.
     *
     * @param  callable  $callback  The business logic to execute
     * @param  string  $operation  A descriptive name of the operation
     * @param  array  $context  Additional context for logging
     *
     * @throws TransactionException|RenderableException
     */
    public static function run(callable $callback, string $operation, array $context = []): mixed
    {
        DB::beginTransaction();

        try {
            $result = $callback();

            DB::commit();

            return $result;

        } catch (QueryException $e) {
            DB::rollBack();

            throw new TransactionException($operation, array_merge($context, [
                'sql_error' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
            ]), $e);

        } catch (\PDOException $e) {
            DB::rollBack();

            throw new TransactionException($operation, array_merge($context, [
                'pdo_error' => $e->getMessage(),
            ]), $e);

        } catch (\Throwable $e) {
            DB::rollBack();

            // Re-throw if it's already our custom exception
            if ($e instanceof RenderableException) {
                throw $e;
            }

            throw new TransactionException($operation, array_merge($context, [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]), $e);
        }
    }
}
