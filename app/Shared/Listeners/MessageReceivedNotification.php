<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class MessageReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Message $message)
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
            'type' => 'message_received',
            'message_id' => $this->message->id,
            'subject' => $this->message->subject,
            'content_preview' => \Str::limit($this->message->content, 100),
            'message_type' => $this->message->type,
            'sender_id' => $this->message->sender_id,
            'metadata' => $this->message->metadata,
            'created_at' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast($notifiable): array
    {
        return [
            'id' => $this->message->id,
            'type' => $this->message->type,
            'subject' => $this->message->subject,
            'content_preview' => \Str::limit($this->message->content, 100),
            'sender_id' => $this->message->sender_id,
            'created_at' => now()->toDateTimeString(),
        ];
    }
}