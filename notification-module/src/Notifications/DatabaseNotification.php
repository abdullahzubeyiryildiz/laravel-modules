<?php

namespace Modules\NotificationModule\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class DatabaseNotification extends Notification
{
    /**
     * Bildirim tipi (info, success, warning, error)
     */
    public string $type;

    /**
     * Bildirim başlığı
     */
    public string $title;

    /**
     * Bildirim mesajı
     */
    public string $message;

    /**
     * Action URL
     */
    public ?string $actionUrl;

    /**
     * Action text
     */
    public ?string $actionText;

    /**
     * Ek veriler
     */
    public ?array $data;

    /**
     * Expires at
     */
    public ?\DateTime $expiresAt;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?string $actionText = null,
        ?array $data = null,
        ?\DateTime $expiresAt = null
    ) {
        $this->type = $type;
        $this->title = $title;
        $this->message = $message;
        $this->actionUrl = $actionUrl;
        $this->actionText = $actionText;
        $this->data = $data;
        $this->expiresAt = $expiresAt;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $tenantId = $this->getTenantId();

        return [
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'action_text' => $this->actionText,
            'data' => $this->data,
            'expires_at' => $this->expiresAt?->format('Y-m-d H:i:s'),
            'tenant_id' => $tenantId,
        ];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }

    /**
     * Tenant ID'yi al
     */
    protected function getTenantId(): ?int
    {
        if (config('auth-module.multi_tenant.enabled', false)) {
            $tenantHelperClass = config('auth-module.multi_tenant.tenant_helper_class', 'App\Helpers\TenantHelper');
            if (class_exists($tenantHelperClass) && method_exists($tenantHelperClass, 'id')) {
                return $tenantHelperClass::id();
            }
        }

        return null;
    }
}
