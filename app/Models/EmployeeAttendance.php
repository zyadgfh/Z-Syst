<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAttendance extends Model
{
    use HasCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'branch_id',
        'check_in_at',
        'check_out_at',
        'check_in_method',
        'check_in_qr_token',
        'ip_address',
        'location',
        'notes',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Check if attendance is currently open (checked in but not out)
     */
    public function isOpen(): bool
    {
        return $this->check_in_at !== null && $this->check_out_at === null;
    }

    /**
     * Get total working hours
     */
    public function getWorkingHoursAttribute(): ?float
    {
        if ($this->check_out_at === null) {
            return null;
        }

        return $this->check_in_at->diffInHours($this->check_out_at, true);
    }

    /**
     * Generate QR token for check-in
     */
    public static function generateQrToken(int $userId): string
    {
        return 'ATTENDANCE_' . $userId . '_' . time() . '_' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    }
}