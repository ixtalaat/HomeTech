<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WhatsAppService
{
    /**
     * Send a WhatsApp message through the configured driver.
     *
     * The log driver records the message (development default); twilio
     * and meta drivers call their HTTP APIs with env credentials.
     *
     * @throws RuntimeException
     */
    public function send(string $to, string $message): void
    {
        match (config('whatsapp.driver', 'log')) {
            'twilio' => $this->sendViaTwilio($to, $message),
            'meta' => $this->sendViaMeta($to, $message),
            default => Log::info("[whatsapp:log] to {$to}: {$message}"),
        };
    }

    /**
     * Send via the Twilio WhatsApp API.
     *
     * @throws RuntimeException
     */
    private function sendViaTwilio(string $to, string $message): void
    {
        $sid = config('whatsapp.twilio.sid');
        $token = config('whatsapp.twilio.token');
        $from = config('whatsapp.twilio.from');

        if (! is_string($sid) || $sid === '' || ! is_string($token) || $token === '' || ! is_string($from) || $from === '') {
            throw new RuntimeException(__('Twilio WhatsApp is not configured (TWILIO_SID/AUTH_TOKEN/WHATSAPP_FROM).'));
        }

        $response = Http::asForm()
            ->withBasicAuth($sid, $token)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => "whatsapp:{$from}",
                'To' => "whatsapp:{$to}",
                'Body' => $message,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Twilio rejected the WhatsApp message: '.$response->body());
        }
    }

    /**
     * Send via the Meta WhatsApp Cloud API.
     *
     * @throws RuntimeException
     */
    private function sendViaMeta(string $to, string $message): void
    {
        $token = config('whatsapp.meta.token');
        $phoneNumberId = config('whatsapp.meta.phone_number_id');

        if (! is_string($token) || $token === '' || ! is_string($phoneNumberId) || $phoneNumberId === '') {
            throw new RuntimeException(__('Meta WhatsApp is not configured (WHATSAPP_TOKEN/PHONE_NUMBER_ID).'));
        }

        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/v21.0/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => ['body' => $message],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Meta rejected the WhatsApp message: '.$response->body());
        }
    }
}
