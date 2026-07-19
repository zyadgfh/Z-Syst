<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Prescription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class PrescriptionReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Prescription $prescription)
    {
    }

    /**
     * Get the notification channels.
     */
    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'type' => 'prescription_ready',
            'prescription_id' => $this->prescription->id,
            'prescription_number' => $this->prescription->prescription_number,
            'patient_name' => $this->prescription->patient->name ?? 'Unknown',
            'doctor_name' => $this->prescription->doctor->name ?? 'Unknown',
            'items_count' => $this->prescription->items()->count(),
            'created_at' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast($notifiable): array
    {
        return [
            'type' => 'prescription_ready',
            'prescription_id' => $this->prescription->id,
            'prescription_number' => $this->prescription->prescription_number,
            'patient_name' => $this->prescription->patient->name ?? 'Unknown',
            'doctor_name' => $this->prescription->doctor->name ?? 'Unknown',
            'items_count' => $this->prescription->items()->count(),
            'created_at' => now()->toDateTimeString(),
        ];
    }
}