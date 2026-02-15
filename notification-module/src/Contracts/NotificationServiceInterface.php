<?php

namespace Modules\NotificationModule\Contracts;

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Database\Eloquent\Model;

interface NotificationServiceInterface
{
    /**
     * Bildirim gönder
     */
    public function send(
        Model $notifiable,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?string $actionText = null,
        ?array $data = null,
        ?\DateTime $expiresAt = null
    ): DatabaseNotification;

    /**
     * Bildirimleri listele
     */
    public function list(
        Model $notifiable,
        ?bool $isRead = null,
        ?int $limit = null,
        ?int $offset = null
    ): \Illuminate\Database\Eloquent\Collection;

    /**
     * Okunmamış bildirim sayısı
     */
    public function unreadCount(Model $notifiable): int;

    /**
     * Bildirimi okundu olarak işaretle
     */
    public function markAsRead(string $notificationId, Model $notifiable): bool;

    /**
     * Tüm bildirimleri okundu olarak işaretle
     */
    public function markAllAsRead(Model $notifiable): int;

    /**
     * Bildirimi sil
     */
    public function delete(string $notificationId, Model $notifiable): bool;

    /**
     * Süresi dolmuş bildirimleri temizle
     */
    public function cleanExpired(): int;
}
