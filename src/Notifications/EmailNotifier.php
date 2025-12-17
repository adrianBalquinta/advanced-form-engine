<?php
declare(strict_types=1);

namespace AFE\Notifications;

use AFE\Events\SubmissionCreatedEvent;

class EmailNotifier implements NotifierInterface
{
    private string $to;

    public function __construct(string $to)
    {
        $this->to = trim($to);
    }

    public function isEnabled(): bool
    {
        return is_email($this->to) !== false;
    }

    public function notify(SubmissionCreatedEvent $event): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $headers = [];
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';

        // Site domain
        $from_email = 'no-reply@' . wp_parse_url(home_url(), PHP_URL_HOST);
        $headers[] = 'From: Advanced Form Engine <' . $from_email . '>';

        // Reply to the submitter
        if (!empty($event->data['email']) && is_email($event->data['email'])) {
            $headers[] = 'Reply-To: ' . $event->data['email'];
        }

        $subject = sprintf('New AFE submission (Form %d)', $event->formId);

        $bodyLines = [
            "Submission ID: {$event->submissionId}",
            "Form ID: {$event->formId}",
            "",
            "Data:",
        ];

        foreach ($event->data as $k => $v) {
            $bodyLines[] = "{$k}: {$v}";
        }

        $sent = wp_mail($this->to, $subject, implode("\n", $bodyLines), $headers);
        //error_log('[AFE EmailNotifier] wp_mail sent=' . ($sent ? 'true' : 'false'));
    }
}
