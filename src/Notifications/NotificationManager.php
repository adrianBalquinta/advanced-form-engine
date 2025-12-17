<?php
declare(strict_types=1);

namespace AFE\Notifications;

use AFE\Events\SubmissionCreatedEvent;

class NotificationManager
{
    /** @var NotifierInterface[] */
    private array $notifiers;

    /**
     * @param NotifierInterface[] $notifiers
     */
    public function __construct(array $notifiers)
    {
        $this->notifiers = $notifiers;
    }

    public function onSubmissionCreated(SubmissionCreatedEvent $event): void
    {
        foreach ($this->notifiers as $notifier) {
            if ($notifier->isEnabled()) {
                $notifier->notify($event);
            }
        }
    }
}
