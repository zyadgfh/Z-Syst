<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $company;
    protected $lowStockProducts;

    /**
     * Create a new notification instance.
     */
    public function __construct($company, $lowStockProducts)
    {
        $this->company = $company;
        $this->lowStockProducts = $lowStockProducts;
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
            ->subject("Low Stock Alert - {$this->company->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("The following products are running low on stock and need attention:");

        foreach ($this->lowStockProducts as $branchId => $products) {
            $branch = $products->first()->branch;
            $message->line("**Branch: {$branch->name}**");
            
            foreach ($products->take(10) as $stock) {
                $message->line("- {$stock->product->name} (SKU: {$stock->product->sku}) - Current: {$stock->quantity}, Reorder Level: {$stock->reorder_level}");
            }
            
            if ($products->count() > 10) {
                $message->line("... and {$products->count() - 10} more products");
            }
        }

        $message->action('View Inventory', url('/admin/inventory'))
                ->line('Please review and replenish stock as needed.');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        $totalProducts = $this->lowStockProducts->flatten()->count();
        $affectedBranches = $this->lowStockProducts->keys()->count();

        return [
            'title' => 'Low Stock Alert',
            'message' => "{$totalProducts} products across {$affectedBranches} branches are running low on stock.",
            'type' => 'warning',
            'company_id' => $this->company->id,
            'data' => [
                'total_products' => $totalProducts,
                'affected_branches' => $affectedBranches,
                'branches' => $this->lowStockProducts->map(function ($products, $branchId) {
                    return [
                        'branch_id' => $branchId,
                        'branch_name' => $products->first()->branch->name,
                        'product_count' => $products->count(),
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
        return 'high';
    }
}