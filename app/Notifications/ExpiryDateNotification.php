<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Carbon\Carbon;

class ExpiryDateNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $company;
    protected $expiringProducts;

    /**
     * Create a new notification instance.
     */
    public function __construct($company, $expiringProducts)
    {
        $this->company = $company;
        $this->expiringProducts = $expiringProducts;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Product Expiry Alert - {$this->company->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("The following products are expiring within the next 30 days and need attention:");

        foreach ($this->expiringProducts as $branchId => $products) {
            $branch = $products->first()->branch;
            $message->line("**Branch: {$branch->name}**");
            
            foreach ($products->take(10) as $stock) {
                $daysUntilExpiry = Carbon::now()->diffInDays($stock->expiry_date, false);
                $urgency = $daysUntilExpiry <= 7 ? 'URGENT' : 'Warning';
                $message->line("- {$stock->product->name} (Batch: {$stock->batch_number}) - Expires: {$stock->expiry_date->format('Y-m-d')} ({$daysUntilExpiry} days) [{$urgency}]");
            }
            
            if ($products->count() > 10) {
                $message->line("... and {$products->count() - 10} more products");
            }
        }

        $message->action('View Expiring Products', url('/admin/inventory/expiring'))
                ->line('Please review and take appropriate action (discount, return, or dispose).');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        $totalProducts = $this->expiringProducts->flatten()->count();
        $affectedBranches = $this->expiringProducts->keys()->count();
        
        // Count urgent items (expiring within 7 days)
        $urgentCount = $this->expiringProducts->flatten()->filter(function ($stock) {
            return Carbon::now()->diffInDays($stock->expiry_date, false) <= 7;
        })->count();

        return [
            'title' => 'Product Expiry Alert',
            'message' => "{$totalProducts} products across {$affectedBranches} branches are expiring soon ({$urgentCount} urgent).",
            'type' => $urgentCount > 0 ? 'critical' : 'warning',
            'company_id' => $this->company->id,
            'data' => [
                'total_products' => $totalProducts,
                'urgent_count' => $urgentCount,
                'affected_branches' => $affectedBranches,
                'branches' => $this->expiringProducts->map(function ($products, $branchId) {
                    $urgentInBranch = $products->filter(function ($stock) {
                        return Carbon::now()->diffInDays($stock->expiry_date, false) <= 7;
                    })->count();
                    
                    return [
                        'branch_id' => $branchId,
                        'branch_name' => $products->first()->branch->name,
                        'product_count' => $products->count(),
                        'urgent_count' => $urgentInBranch,
                    ];
                })->values(),
            ],
        ];
    }

    /**
     * Get the notification's priority level.
     */
    public function priority(): string
    {
        $urgentCount = $this->expiringProducts->flatten()->filter(function ($stock) {
            return Carbon::now()->diffInDays($stock->expiry_date, false) <= 7;
        })->count();

        return $urgentCount > 0 ? 'critical' : 'high';
    }
}