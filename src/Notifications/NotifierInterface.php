<?php
declare(strict_types=1);

namespace AFE\Notifications;

use AFE\Events\SubmissionCreatedEvent;

interface NotifierInterface
{
    public function isEnabled(): bool;

    public function notify(SubmissionCreatedEvent $event): void;
}
