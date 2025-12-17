<?php
declare(strict_types=1);

namespace AFE\Notifications;

use AFE\Events\SubmissionCreatedEvent;

class WebhookNotifier implements NotifierInterface
{
    private string $endpointUrl;

    public function __construct(string $endpointUrl)
    {
        $this->endpointUrl = trim($endpointUrl);
    }

    public function isEnabled(): bool
    {
        return $this->endpointUrl !== '';
    }

    public function notify(SubmissionCreatedEvent $event): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        wp_remote_post($this->endpointUrl, [
            'timeout' => 5,
            'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
            'body' => wp_json_encode([
                'event' => 'afe.submission.created',
                'form_id' => $event->formId,
                'submission_id' => $event->submissionId,
                'data' => $event->data,
                'occurred_at' => gmdate('c'),
            ]),
        ]);
    }
}
