<?php

namespace App\Core\Abstracts;

/**
 * Base Service Class
 * 
 * Provides common service operations and dependency injection.
 * All domain services should extend this class.
 */
abstract class AbstractService
{
    /**
     * The repository instance
     */
    protected ?AbstractRepository $repository = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->initializeRepository();
    }

    /**
     * Initialize the repository (to be overridden by child classes)
     */
    protected function initializeRepository(): void
    {
        // Override in child classes
    }

    /**
     * Get the repository instance
     */
    public function getRepository(): ?AbstractRepository
    {
        return $this->repository;
    }

    /**
     * Log activity
     */
    protected function log(string $action, array $data = []): void
    {
        // TODO: Implement activity logging
        // activity()
        //     ->causedBy(auth()->user())
        //     ->performedOn($this->getRepository()->getModel() ?? null)
        //     ->withProperties($data)
        //     ->log($action);
    }

    /**
     * Dispatch event
     */
    protected function dispatchEvent(string $eventClass, $model = null): void
    {
        if ($model) {
            event(new $eventClass($model));
        }
    }
}
