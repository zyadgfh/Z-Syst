<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\EmployeeAttendance;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeCheckedOut
{
    use Dispatchable, SerializesModels;

    public EmployeeAttendance $attendance;

    public function __construct(EmployeeAttendance $attendance)
    {
        $this->attendance = $attendance;
    }
}