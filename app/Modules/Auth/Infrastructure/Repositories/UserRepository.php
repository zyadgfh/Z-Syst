<?php

namespace App\Modules\Auth\Infrastructure\Repositories;

use App\Core\Abstracts\AbstractRepository;
use App\Models\User;
use App\Modules\Auth\Domain\Contracts\UserRepositoryInterface;

class UserRepository extends AbstractRepository implements UserRepositoryInterface
{
    /**
     * Get the model class.
     */
    protected function getModel(): string
    {
        return User::class;
    }

    /**
     * Find user by email.
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Create a new user.
     */
    public function createUser(array $data): User
    {
        return $this->create($data);
    }

    /**
     * Update user.
     */
    public function updateUser(User $user, array $data): User
    {
        return $this->update($user->id, $data);
    }

    /**
     * Delete user.
     */
    public function deleteUser(User $user): bool
    {
        return $this->delete($user->id);
    }
}
