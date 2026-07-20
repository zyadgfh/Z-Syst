<?php

namespace App\Modules\Auth\Domain\Contracts;

use App\Models\User;

interface UserRepositoryInterface
{
    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Create a new user.
     */
    public function createUser(array $data): User;

    /**
     * Update user.
     */
    public function updateUser(User $user, array $data): User;

    /**
     * Delete user.
     */
    public function deleteUser(User $user): bool;
}
