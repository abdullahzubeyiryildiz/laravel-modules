<?php

namespace Modules\NotificationModule\Services\Mail;

use Modules\NotificationModule\Contracts\MailProviderInterface;
use Illuminate\Support\Facades\Mail;

class SmtpMailService implements MailProviderInterface
{
    /**
     * Mail gönder
     */
    public function send(string $to, string $subject, string $body, array $options = []): bool
    {
        try {
            $from = $options['from'] ?? config('mail.from.address');
            $fromName = $options['from_name'] ?? config('mail.from.name');
            $isHtml = $options['is_html'] ?? true;

            Mail::raw($body, function ($message) use ($to, $subject, $from, $fromName, $isHtml, $body, $options) {
                $message->to($to)
                    ->subject($subject)
                    ->from($from, $fromName);

                if ($isHtml) {
                    $message->html($body);
                }

                // Ek dosyalar varsa
                if (isset($options['attachments'])) {
                    foreach ($options['attachments'] as $attachment) {
                        $message->attach($attachment);
                    }
                }
            });

            return true;
        } catch (\Exception $e) {
            \Log::error('SMTP Mail gönderme hatası: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Çoklu mail gönder
     */
    public function sendBulk(array $recipients, string $subject, string $body, array $options = []): bool
    {
        $success = true;
        foreach ($recipients as $recipient) {
            if (!$this->send($recipient, $subject, $body, $options)) {
                $success = false;
            }
        }
        return $success;
    }
}
