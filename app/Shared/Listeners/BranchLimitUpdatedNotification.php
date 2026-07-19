<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BranchLimitUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public $company,
        public $type = 'updated'
    ) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $company = $this->company;
        $usagePercentage = $company->branch_usage_percentage;

        return (new MailMessage)
            ->subject('Branch Limit Update Notification')
            ->greeting("Hello {$notifiable->name},")
            ->line("The branch limit for company '{$company->name}' has been updated.")
            ->line("Current Usage: {$usagePercentage}%")
            ->line('Max Branches: '.($company->is_unlimited_branches ? 'Unlimited' : $company->max_branches))
            ->line("Current Branches: {$company->current_branches_count}")
            ->action('View Company', url(route('admin.companies.index')))
            ->line('Thank you for using our application.');
    }

    public function toArray($notifiable)
    {
        return [
            'company_id' => $this->company->id,
            'company_name' => $this->company->name,
            'type' => $this->type,
            'max_branches' => $this->company->max_branches,
            'is_unlimited' => $this->company->is_unlimited_branches,
            'current_branches' => $this->company->current_branches_count,
            'usage_percentage' => $this->company->branch_usage_percentage,
        ];
    }
}
