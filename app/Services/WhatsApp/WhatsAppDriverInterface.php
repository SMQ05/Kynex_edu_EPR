<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

/**
 * Contract for all WhatsApp messaging drivers.
 *
 * Every driver must be able to send a text message and a template message.
 * Template messages are required for Meta Official API (24-hour window rule).
 */
interface WhatsAppDriverInterface
{
    /**
     * Send a free-form text message to a single recipient.
     *
     * @param  string  $to       Recipient phone number in E.164 format (e.g. +923001234567)
     * @param  string  $message  Plain-text message body
     * @return bool              True on success, false on failure
     */
    public function sendText(string $to, string $message): bool;

    /**
     * Send a pre-approved template message.
     *
     * @param  string  $to              Recipient phone number in E.164 format
     * @param  string  $templateName    Template name as registered with the provider
     * @param  array   $parameters      Positional or named template parameters
     * @param  string  $languageCode    BCP-47 language code (default: en)
     * @return bool
     */
    public function sendTemplate(
        string $to,
        string $templateName,
        array $parameters = [],
        string $languageCode = 'en',
    ): bool;

    /**
     * Send a media message (image, document, etc.).
     *
     * @param  string  $to        Recipient phone number in E.164 format
     * @param  string  $mediaUrl  Publicly accessible URL of the media file
     * @param  string  $type      Media type: image, document, audio, video
     * @param  string  $caption   Optional caption for the media
     * @return bool
     */
    public function sendMedia(
        string $to,
        string $mediaUrl,
        string $type = 'image',
        string $caption = '',
    ): bool;
}
