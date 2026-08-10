<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_enabled',
        'title',
        'message',
        'estimated_completion',
        'allowed_ips',
        'allowed_users',
        'started_at',
        'scheduled_for',
        'ended_at',
        'created_by',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'allowed_ips' => 'array',
        'allowed_users' => 'array',
        'started_at' => 'datetime',
        'scheduled_for' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * Relationship with the user who created the maintenance
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if maintenance mode is currently active
     */
    public function isActive(): bool
    {
        return $this->is_enabled && 
               (!$this->ended_at || $this->ended_at > now());
    }

    /**
     * Check if maintenance mode is scheduled
     */
    public function isScheduled(): bool
    {
        return $this->scheduled_for && 
               $this->scheduled_for > now() && 
               !$this->is_enabled;
    }

    /**
     * Get the duration of maintenance in minutes
     */
    public function getDurationInMinutes(): ?int
    {
        if (!$this->started_at || !$this->ended_at) {
            return null;
        }

        return $this->started_at->diffInMinutes($this->ended_at);
    }

    /**
     * Check if current user/IP is allowed during maintenance
     */
    public function isAllowed(string $ip, ?int $userId = null): bool
    {
        if (!$this->is_enabled) {
            return true;
        }

        // Check if IP is allowed
        if ($this->allowed_ips && in_array($ip, $this->allowed_ips)) {
            return true;
        }

        // Check if user is allowed
        if ($userId && $this->allowed_users && in_array($userId, $this->allowed_users)) {
            return true;
        }

        return false;
    }

    /**
     * Activate maintenance mode
     */
    public function activate(array $data = []): self
    {
        $this->update([
            'is_enabled' => true,
            'started_at' => now(),
            'ended_at' => $data['ended_at'] ?? null,
            'title' => $data['title'] ?? 'System Maintenance',
            'message' => $data['message'] ?? null,
            'estimated_completion' => $data['estimated_completion'] ?? null,
            'allowed_ips' => $data['allowed_ips'] ?? null,
            'allowed_users' => $data['allowed_users'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return $this;
    }

    /**
     * Deactivate maintenance mode
     */
    public function deactivate(): self
    {
        $this->update([
            'is_enabled' => false,
            'ended_at' => now(),
        ]);

        return $this;
    }

    /**
     * Schedule maintenance mode
     */
    public function schedule(\DateTime $scheduledFor, array $data = []): self
    {
        $this->update([
            'is_enabled' => false,
            'scheduled_for' => $scheduledFor,
            'title' => $data['title'] ?? 'System Maintenance',
            'message' => $data['message'] ?? null,
            'estimated_completion' => $data['estimated_completion'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return $this;
    }
}