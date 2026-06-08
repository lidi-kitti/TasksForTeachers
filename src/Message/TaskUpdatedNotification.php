<?php

namespace App\Message;

final class TaskUpdatedNotification
{
    public function __construct(
        private readonly int $taskId,
        private readonly string $editorEmail,
    ) {
    }

    public function getTaskId(): int
    {
        return $this->taskId;
    }

    public function getEditorEmail(): string
    {
        return $this->editorEmail;
    }
}
