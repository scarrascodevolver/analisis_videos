<?php

namespace App\Mail;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;

class SendGridApiTransport extends AbstractTransport
{
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        // Get email content - prefer HTML, fallback to text
        $content = $email->getHtmlBody() ?: $email->getTextBody();
        $contentType = $email->getHtmlBody() ? 'text/html' : 'text/plain';

        // Ensure content is not empty
        if (empty($content)) {
            $content = 'Password reset email from Los Troncos Rugby Club';
        }

        $data = [
            'personalizations' => [
                [
                    'to' => array_map(fn($addr) => ['email' => $addr->getAddress()], $email->getTo())
                ]
            ],
            'from' => ['email' => $email->getFrom()[0]->getAddress()],
            'subject' => $email->getSubject(),
            'content' => [
                [
                    'type' => $contentType,
                    'value' => $content
                ]
            ]
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.sendgrid.com/v3/mail/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json'
            ]
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode !== 202) {
            throw new \Exception('SendGrid API error: ' . $response);
        }
    }

    public function __toString(): string
    {
        return 'sendgrid_api';
    }
}