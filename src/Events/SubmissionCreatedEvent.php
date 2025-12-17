<?php
declare(strict_types=1);

namespace AFE\Events;

class SubmissionCreatedEvent
{
    public int $formId;
    public int $submissionId;

    /** @var array<string,string> */
    public array $data;

    /**
     * @param array<string,string> $data
     */
    public function __construct(int $formId, int $submissionId, array $data)
    {
        $this->formId = $formId;
        $this->submissionId = $submissionId;
        $this->data = $data;
    }
}
