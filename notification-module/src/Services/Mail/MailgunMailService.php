<?php

namespace Modules\NotificationModule\Services\Mail;

use Modules\NotificationModule\Contracts\MailProviderInterface;
use Illuminate\Support\Facades\Http;

class MailgunMailService implements MailProviderInterface
{
    protected $domain;
    protected $apiKey;

    public function __construct()
    {
        $this->domain = config('notification-module.mail.mailgun.domain');
        $this->apiKey = config('notification-module.mail.mailgun.api_key');
    }

    /**
     * Mail gönder
     */
    public function send(string $to, string $subject, string $body, array $options = []): bool
    {
        try {
            $from = $options['from'] ?? config('mail.from.address');
            $fromName = $options['from_name'] ?? config('mail.from.name');

            $response = Http::asForm()->withBasicAuth('api', $this->apiKey)
                ->post("https://api.mailgun.net/v3/{$this->domain}/messages", [
                    'from' => "{$fromName} <{$from}>",
                    'to' => $to,
                    'subject' => $subject,
                    'html' => $body,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            \Log::error('Mailgun Mail gönderme hatası: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Çoklu mail gönder
     */
    public function sendBulk(array $recipients, string $subject, string $body, array $options = []): bool
    {
        try {
            $from = $options['from'] ?? config('mail.from.address');
            $fromName = $options['from_name'] ?? config('mail.from.name');

            $response = Http::asForm()->withBasicAuth('api', $this->apiKey)
                ->post("https://api.mailgun.net/v3/{$this->domain}/messages", [
                    'from' => "{$fromName} <{$from}>",
                    'to' => $recipients,
                    'subject' => $subject,
                    'html' => $body,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            \Log::error('Mailgun Bulk Mail gönderme hatası: ' . $e->getMessage());
            return false;
        }
    }
}
