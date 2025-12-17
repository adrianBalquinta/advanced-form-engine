<?php
declare(strict_types=1);

namespace AFE\Notifications;

use AFE\Events\SubmissionCreatedEvent;

class SlackWebhookNotifier implements NotifierInterface
{
    private string $webhookUrl;

    public function __construct(string $webhookUrl)
    {
        $this->webhookUrl = trim($webhookUrl);
    }

    public function isEnabled(): bool
    {
        return $this->webhookUrl !== '';
    }

    public function notify(SubmissionCreatedEvent $event): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $text = sprintf(
            "New AFE submission\nForm ID: %d\nSubmission ID: %d\nName: %s\nEmail: %s",
            $event->formId,
            $event->submissionId,
            $event->data['name'] ?? '',
            $event->data['email'] ?? ''
        );

        wp_remote_post($this->webhookUrl, [
            'timeout' => 5,
            'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
            'body'    => wp_json_encode(['text' => $text]),
        ]);
    }
}
