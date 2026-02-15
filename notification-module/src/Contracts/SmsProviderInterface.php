<?php

namespace Modules\NotificationModule\Contracts;

interface SmsProviderInterface
{
    /**
     * SMS gönder
     *
     * @param string $phone
     * @param string $message
     * @param array $options
     * @return bool
     */
    public function send(string $phone, string $message, array $options = []): bool;

    /**
     * Çoklu SMS gönder
     *
     * @param array $phones
     * @param string $message
     * @param array $options
     * @return bool
     */
    public function sendBulk(array $phones, string $message, array $options = []): bool;
}
