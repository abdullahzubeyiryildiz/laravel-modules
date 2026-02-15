<?php

namespace Modules\NotificationModule\Services;

use Modules\NotificationModule\Contracts\MailProviderInterface;
use Modules\NotificationModule\Contracts\SmsProviderInterface;

class NotificationService
{
    protected $mailProvider;
    protected $smsProvider;

    public function __construct()
    {
        $this->initializeProviders();
    }

    /**
     * Provider'ları başlat
     */
    protected function initializeProviders(): void
    {
        // Mail provider
        $mailProvider = config('notification-module.mail.provider', 'smtp');
        $mailProviderClass = config("notification-module.mail.providers.{$mailProvider}.class");

        if ($mailProviderClass && class_exists($mailProviderClass)) {
            $this->mailProvider = app($mailProviderClass);
        }

        // SMS provider
        $smsProvider = config('notification-module.sms.provider', 'mutlucell');
        $smsProviderClass = config("notification-module.sms.providers.{$smsProvider}.class");

        if ($smsProviderClass && class_exists($smsProviderClass)) {
            $this->smsProvider = app($smsProviderClass);
        }
    }

    /**
     * Mail gönder
     */
    public function sendMail(string $to, string $subject, string $body, array $options = []): bool
    {
        if (!$this->mailProvider) {
            \Log::warning('Mail provider tanımlı değil');
            return false;
        }

        return $this->mailProvider->send($to, $subject, $body, $options);
    }

    /**
     * SMS gönder
     */
    public function sendSms(string $phone, string $message, array $options = []): bool
    {
        if (!$this->smsProvider) {
            \Log::warning('SMS provider tanımlı değil');
            return false;
        }

        return $this->smsProvider->send($phone, $message, $options);
    }

    /**
     * Mail ve SMS gönder
     */
    public function sendBoth(string $to, string $phone, string $subject, string $mailBody, string $smsMessage, array $options = []): array
    {
        return [
            'mail' => $this->sendMail($to, $subject, $mailBody, $options),
            'sms' => $this->sendSms($phone, $smsMessage, $options),
        ];
    }

    /**
     * Mail provider'ı değiştir
     */
    public function setMailProvider(MailProviderInterface $provider): void
    {
        $this->mailProvider = $provider;
    }

    /**
     * SMS provider'ı değiştir
     */
    public function setSmsProvider(SmsProviderInterface $provider): void
    {
        $this->smsProvider = $provider;
    }
}
