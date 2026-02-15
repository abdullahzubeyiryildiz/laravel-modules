<?php

namespace Modules\NotificationModule\Services\Sms;

use Modules\NotificationModule\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Http;

class MutlucellSmsService implements SmsProviderInterface
{
    protected $username;
    protected $password;
    protected $originator;
    protected $apiUrl;

    public function __construct()
    {
        $this->username = config('notification-module.sms.mutlucell.username');
        $this->password = config('notification-module.sms.mutlucell.password');
        $this->originator = config('notification-module.sms.mutlucell.originator');
        $this->apiUrl = config('notification-module.sms.mutlucell.api_url', 'https://api.mutlucell.com/send');
    }

    /**
     * SMS gönder
     */
    public function send(string $phone, string $message, array $options = []): bool
    {
        try {
            // Telefon numarasını temizle (başında 0 varsa kaldır, +90 ekle)
            $phone = $this->formatPhone($phone);
            $originator = $options['originator'] ?? $this->originator;

            $response = Http::asForm()->post($this->apiUrl, [
                'username' => $this->username,
                'password' => $this->password,
                'gsmno' => $phone,
                'message' => $message,
                'msgheader' => $originator,
            ]);

            $result = $response->json();

            // Mutlucell başarılı yanıt kontrolü (API dokümantasyonuna göre düzenlenebilir)
            if (isset($result['status']) && $result['status'] === 'success') {
                return true;
            }

            \Log::error('Mutlucell SMS gönderme hatası: ' . json_encode($result));
            return false;
        } catch (\Exception $e) {
            \Log::error('Mutlucell SMS gönderme hatası: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Çoklu SMS gönder
     */
    public function sendBulk(array $phones, string $message, array $options = []): bool
    {
        $success = true;
        foreach ($phones as $phone) {
            if (!$this->send($phone, $message, $options)) {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * Telefon numarasını formatla
     */
    protected function formatPhone(string $phone): string
    {
        // Boşluk, tire, parantez gibi karakterleri temizle
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Başında +90 varsa olduğu gibi bırak
        if (str_starts_with($phone, '+90')) {
            return substr($phone, 1); // + işaretini kaldır
        }

        // Başında 90 varsa 0 ekle
        if (str_starts_with($phone, '90') && strlen($phone) === 12) {
            return '0' . $phone;
        }

        // Başında 0 varsa olduğu gibi bırak
        if (str_starts_with($phone, '0')) {
            return $phone;
        }

        // Hiçbiri değilse 0 ekle
        return '0' . $phone;
    }
}
