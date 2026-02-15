<?php

namespace Modules\NotificationModule\Services;

use Modules\NotificationModule\Contracts\NotificationServiceInterface;
use Modules\NotificationModule\Notifications\DatabaseNotification;
use Modules\NotificationModule\Exceptions\NotificationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification as LaravelNotification;
use Illuminate\Support\Facades\Schema;

class NotificationManagerService implements NotificationServiceInterface
{
    /**
     * Bildirim gönder (Laravel'in notification sistemini kullanır)
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
    ): LaravelNotification {
        try {
            // Type validation
            $allowedTypes = ['info', 'success', 'warning', 'error'];
            if (!in_array($type, $allowedTypes)) {
                throw new NotificationException("Geçersiz bildirim tipi: {$type}");
            }

            // Laravel'in notification sistemini kullan
            $notification = new DatabaseNotification(
                $type,
                $title,
                $message,
                $actionUrl,
                $actionText,
                $data,
                $expiresAt
            );

            // Notifiable'a bildirim gönder
            $notifiable->notify($notification);

            // Database notification'ı al (en son eklenen)
            $dbNotification = $notifiable->notifications()
                ->where('type', DatabaseNotification::class)
                ->latest()
                ->first();

            // Ek alanları güncelle (custom columns) - eğer migration ile eklenmişse
            if ($dbNotification && Schema::hasColumn('notifications', 'title')) {
                try {
                    \DB::table('notifications')
                        ->where('id', $dbNotification->id)
                        ->update([
                            'title' => $title,
                            'action_url' => $actionUrl,
                            'action_text' => $actionText,
                            'expires_at' => $expiresAt?->format('Y-m-d H:i:s'),
                        ]);
                } catch (\Exception $e) {
                    // Custom columns yoksa devam et (backward compatibility)
                    \Log::debug('Custom notification columns not found: ' . $e->getMessage());
                }
            }

            return $dbNotification ?? $notifiable->notifications()->latest()->first();
        } catch (\Exception $e) {
            \Log::error('Bildirim gönderme hatası: ' . $e->getMessage());
            throw new NotificationException('Bildirim gönderilemedi: ' . $e->getMessage());
        }
    }

    /**
     * Bildirimleri listele (Laravel'in notification sistemini kullanır)
     */
    public function list(
        Model $notifiable,
        ?bool $isRead = null,
        ?int $limit = null,
        ?int $offset = null
    ): \Illuminate\Database\Eloquent\Collection {
        $query = $notifiable->notifications()
            ->where('type', DatabaseNotification::class)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->orderBy('created_at', 'desc');

        if ($isRead !== null) {
            if ($isRead) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        if ($offset !== null) {
            $query->offset($offset);
        }

        return $query->get();
    }

    /**
     * Okunmamış bildirim sayısı (Laravel'in notification sistemini kullanır)
     */
    public function unreadCount(Model $notifiable): int
    {
        return $notifiable->unreadNotifications()
            ->where('type', DatabaseNotification::class)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->count();
    }

    /**
     * Bildirimi okundu olarak işaretle (Laravel'in notification sistemini kullanır)
     */
    public function markAsRead(string $notificationId, Model $notifiable): bool
    {
        $notification = $notifiable->notifications()
            ->where('id', $notificationId)
            ->first();

        if (!$notification) {
            throw new NotificationException('Bildirim bulunamadı.');
        }

        $notification->markAsRead();
        return true;
    }

    /**
     * Tüm bildirimleri okundu olarak işaretle (Laravel'in notification sistemini kullanır)
     */
    public function markAllAsRead(Model $notifiable): int
    {
        return $notifiable->unreadNotifications()
            ->where('type', DatabaseNotification::class)
            ->update(['read_at' => now()]);
    }

    /**
     * Bildirimi sil (Laravel'in notification sistemini kullanır)
     */
    public function delete(string $notificationId, Model $notifiable): bool
    {
        $notification = $notifiable->notifications()
            ->where('id', $notificationId)
            ->first();

        if (!$notification) {
            throw new NotificationException('Bildirim bulunamadı.');
        }

        return $notification->delete();
    }

    /**
     * Süresi dolmuş bildirimleri temizle (Laravel'in notification sistemini kullanır)
     */
    public function cleanExpired(): int
    {
        return LaravelNotification::where('expires_at', '<=', now())
            ->delete();
    }
}
