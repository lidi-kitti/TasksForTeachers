<?php

namespace App\Service;

use App\Entity\Task;
use App\Entity\User;
use App\Exception\AccessDeniedException;
use App\Message\TaskUpdatedNotification;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;

class TaskService
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function getAllTasksQueryBuilder(): QueryBuilder
    {
        return $this->taskRepository->createAllTasksQueryBuilder();
    }

    public function getTask(int $id): Task
    {
        $task = $this->taskRepository->find($id);

        if (!$task instanceof Task) {
            throw new NotFoundHttpException(sprintf('Задание #%d не найдено.', $id));
        }

        return $task;
    }

    public function createTask(Task $task, User $author): Task
    {
        $task->setAuthor($author);
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }

    public function updateTask(Task $task, User $currentUser): Task
    {
        $this->assertOwner($task, $currentUser);
        $task->touchUpdatedAt();
        $this->entityManager->flush();

        $this->messageBus->dispatch(new TaskUpdatedNotification(
            $task->getId(),
            $currentUser->getEmail(),
        ));

        return $task;
    }

    public function deleteTask(Task $task, User $currentUser): void
    {
        $this->assertOwner($task, $currentUser);
        $this->entityManager->remove($task);
        $this->entityManager->flush();
    }

    public function assertOwner(Task $task, User $currentUser): void
    {
        if (!$task->isOwnedBy($currentUser)) {
            throw new AccessDeniedException('Вы можете изменять только свои задания.');
        }
    }

    public function canEdit(Task $task, User $currentUser): bool
    {
        return $task->isOwnedBy($currentUser);
    }
}
