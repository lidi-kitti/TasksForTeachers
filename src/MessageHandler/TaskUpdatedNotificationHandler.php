<?php

namespace App\MessageHandler;

use App\Message\TaskUpdatedNotification;
use App\Repository\TaskRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class TaskUpdatedNotificationHandler
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(TaskUpdatedNotification $message): void
    {
        $task = $this->taskRepository->find($message->getTaskId());

        if (null === $task) {
            return;
        }

        $authorEmail = $task->getAuthor()?->getEmail() ?? 'неизвестен';
        $notificationText = sprintf(
            'Задание «%s» было отредактировано пользователем %s.',
            $task->getTitle(),
            $message->getEditorEmail()
        );

        $this->logger->info('Task updated notification', [
            'task_id' => $message->getTaskId(),
            'author' => $authorEmail,
            'editor' => $message->getEditorEmail(),
        ]);

        $session = $this->requestStack->getSession();
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('info', $notificationText);
        }
    }
}
