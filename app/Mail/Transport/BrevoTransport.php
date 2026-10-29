<?php

namespace App\Mail\Transport;

use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Throwable;

class BrevoTransport extends AbstractTransport
{
    public function __construct(
        private readonly Factory $http,
        private readonly string $apiKey,
        private readonly string $endpoint,
        private readonly string $fromAddress,
        private readonly string $fromName,
        private readonly int $timeout,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = $message->getOriginalMessage();

        if (! $email instanceof Email) {
            throw new TransportException('Brevo only supports standard email messages.');
        }

        $body = array_filter([
            'htmlContent' => $email->getHtmlBody(),
            'textContent' => $email->getTextBody(),
        ], static fn (?string $content): bool => $content !== null && $content !== '');

        if ($body === []) {
            throw new TransportException('The email message has no supported content.');
        }

        $payload = [
            'sender' => [
                'email' => $this->fromAddress,
                'name' => $this->fromName,
            ],
            'to' => array_map(static fn ($address): array => [
                'email' => $address->getAddress(),
                'name' => $address->getName(),
            ], $message->getEnvelope()->getRecipients()),
            'subject' => $email->getSubject(),
            ...$body,
        ];

        try {
            $response = $this->http
                ->withHeaders([
                    'api-key' => $this->apiKey,
                    'accept' => 'application/json',
                ])
                ->timeout($this->timeout)
                ->asJson()
                ->post($this->endpoint, $payload);

            $messageId = $response->json('messageId');

            if ($response->status() !== 201 || ! is_string($messageId) || $messageId === '') {
                Log::channel('single')->error('Brevo rejected an email request.', [
                    'status' => $response->status(),
                ]);

                throw new TransportException('Brevo did not accept the email request.');
            }
        } catch (TransportException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::channel('single')->error('Brevo email request failed.', [
                'exception' => $exception::class,
            ]);

            throw new TransportException('Brevo email request failed.');
        }
    }

    public function __toString(): string
    {
        return 'brevo';
    }
}
