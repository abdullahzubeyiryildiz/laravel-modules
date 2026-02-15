<?php

namespace Modules\NotificationModule\Contracts;

interface MailProviderInterface
{
    /**
     * Mail gönder
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param array $options
     * @return bool
     */
    public function send(string $to, string $subject, string $body, array $options = []): bool;

    /**
     * Çoklu mail gönder
     *
     * @param array $recipients
     * @param string $subject
     * @param string $body
     * @param array $options
     * @return bool
     */
    public function sendBulk(array $recipients, string $subject, string $body, array $options = []): bool;
}
