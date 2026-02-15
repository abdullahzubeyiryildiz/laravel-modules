<?php

namespace Modules\NotificationModule\Http\Controllers\Api;

use Modules\NotificationModule\Http\Controllers\Controller;
use Modules\NotificationModule\Contracts\NotificationServiceInterface;
use Modules\NotificationModule\Exceptions\NotificationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class NotificationApiController extends Controller
{
    protected NotificationServiceInterface $notificationService;

    public function __construct(NotificationServiceInterface $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Bildirimleri listele
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'is_read' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $notifications = $this->notificationService->list(
                $user,
                $request->boolean('is_read'),
                $request->integer('limit', 20),
                $request->integer('offset', 0)
            );

            $unreadCount = $this->notificationService->unreadCount($user);

            return $this->successResponse([
                'notifications' => $notifications->map(function ($notification) {
                    // Laravel notification'larında data JSON string olarak saklanır
                    $data = is_string($notification->data)
                        ? json_decode($notification->data, true) ?? []
                        : (is_array($notification->data) ? $notification->data : []);

                    return [
                        'id' => $notification->id,
                        'type' => $data['type'] ?? 'info',
                        'title' => $data['title'] ?? ($notification->title ?? ''),
                        'message' => $data['message'] ?? '',
                        'action_url' => $data['action_url'] ?? ($notification->action_url ?? null),
                        'action_text' => $data['action_text'] ?? ($notification->action_text ?? null),
                        'data' => $data['data'] ?? null,
                        'is_read' => $notification->read_at !== null,
                        'read_at' => $notification->read_at,
                        'expires_at' => $data['expires_at'] ?? ($notification->expires_at ?? null),
                        'created_at' => $notification->created_at,
                        'icon' => $this->getIcon($data['type'] ?? 'info'),
                        'color' => $this->getColor($data['type'] ?? 'info'),
                    ];
                }),
                'unread_count' => $unreadCount,
                'total' => $notifications->count(),
            ], 'Bildirimler listelendi.');
        } catch (NotificationException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            \Log::error('Bildirim listeleme hatası: ' . $e->getMessage());
            return $this->errorResponse('Bildirimler listelenirken bir hata oluştu.', 500);
        }
    }

    /**
     * Bildirim gönder
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notifiable_type' => 'required|string',
            'notifiable_id' => 'required|integer',
            'type' => 'required|string|in:info,success,warning,error',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'action_url' => 'nullable|string|max:500',
            'action_text' => 'nullable|string|max:100',
            'data' => 'nullable|array',
            'expires_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            // Notifiable entity'yi bul
            $notifiableClass = $request->notifiable_type;
            if (!class_exists($notifiableClass)) {
                return $this->errorResponse('Geçersiz notifiable type.', 400);
            }

            $notifiable = $notifiableClass::findOrFail($request->notifiable_id);

            $expiresAt = $request->expires_at ? new \DateTime($request->expires_at) : null;

            $notification = $this->notificationService->send(
                $notifiable,
                $request->type,
                $request->title,
                $request->message,
                $request->action_url,
                $request->action_text,
                $request->data,
                $expiresAt
            );

            return $this->successResponse([
                'notification' => [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'action_url' => $notification->action_url,
                    'action_text' => $notification->action_text,
                    'data' => $notification->data,
                    'is_read' => $notification->is_read,
                    'created_at' => $notification->created_at,
                ],
            ], 'Bildirim başarıyla gönderildi.', 201);
        } catch (NotificationException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            \Log::error('Bildirim gönderme hatası: ' . $e->getMessage());
            return $this->errorResponse('Bildirim gönderilirken bir hata oluştu.', 500);
        }
    }

    /**
     * Bildirimi okundu olarak işaretle
     */
    public function markAsRead(Request $request, string $id)
    {
        $user = Auth::user();

        try {
            $this->notificationService->markAsRead($id, $user);

            return $this->successResponse(null, 'Bildirim okundu olarak işaretlendi.');
        } catch (NotificationException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (\Exception $e) {
            \Log::error('Bildirim okundu işaretleme hatası: ' . $e->getMessage());
            return $this->errorResponse('Bildirim işaretlenirken bir hata oluştu.', 500);
        }
    }

    /**
     * Tüm bildirimleri okundu olarak işaretle
     */
    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();

        try {
            $count = $this->notificationService->markAllAsRead($user);

            return $this->successResponse([
                'marked_count' => $count,
            ], "{$count} bildirim okundu olarak işaretlendi.");
        } catch (\Exception $e) {
            \Log::error('Tüm bildirimleri okundu işaretleme hatası: ' . $e->getMessage());
            return $this->errorResponse('Bildirimler işaretlenirken bir hata oluştu.', 500);
        }
    }

    /**
     * Okunmamış bildirim sayısı
     */
    public function unreadCount(Request $request)
    {
        $user = Auth::user();

        try {
            $count = $this->notificationService->unreadCount($user);

            return $this->successResponse([
                'unread_count' => $count,
            ], 'Okunmamış bildirim sayısı.');
        } catch (\Exception $e) {
            \Log::error('Okunmamış bildirim sayısı hatası: ' . $e->getMessage());
            return $this->errorResponse('Bildirim sayısı alınırken bir hata oluştu.', 500);
        }
    }

    /**
     * Bildirimi sil
     */
    public function destroy(Request $request, string $id)
    {
        $user = Auth::user();

        try {
            $this->notificationService->delete($id, $user);

            return $this->successResponse(null, 'Bildirim silindi.');
        } catch (NotificationException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (\Exception $e) {
            \Log::error('Bildirim silme hatası: ' . $e->getMessage());
            return $this->errorResponse('Bildirim silinirken bir hata oluştu.', 500);
        }
    }

    /**
     * Bildirim tipine göre icon
     */
    protected function getIcon(string $type): string
    {
        return match ($type) {
            'success' => 'check-circle',
            'error' => 'x-circle',
            'warning' => 'alert-triangle',
            'info' => 'info',
            default => 'bell',
        };
    }

    /**
     * Bildirim tipine göre renk
     */
    protected function getColor(string $type): string
    {
        return match ($type) {
            'success' => 'green',
            'error' => 'red',
            'warning' => 'yellow',
            'info' => 'blue',
            default => 'gray',
        };
    }
}
