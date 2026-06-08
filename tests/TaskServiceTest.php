<?php

namespace App\Tests;

use App\Entity\Task;
use App\Entity\TaskStatus;
use App\Entity\User;
use App\Exception\AccessDeniedException;
use App\Service\TaskService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TaskServiceTest extends KernelTestCase
{
    public function testAssertOwnerThrowsForForeignTask(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        /** @var TaskService $taskService */
        $taskService = $container->get(TaskService::class);

        $owner = new User();
        $owner->setEmail('owner@example.com');
        $owner->setName('Owner');
        $owner->setPassword('hash');
        $this->setUserId($owner, 1);

        $intruder = new User();
        $intruder->setEmail('intruder@example.com');
        $intruder->setName('Intruder');
        $intruder->setPassword('hash');
        $this->setUserId($intruder, 2);

        $task = new Task();
        $task->setTitle('Test');
        $task->setDescription('Desc');
        $task->setDueDate(new \DateTimeImmutable('+1 day'));
        $task->setMaxScore(10);
        $task->setStatus(TaskStatus::Active);
        $task->setAuthor($owner);

        $this->expectException(AccessDeniedException::class);
        $taskService->assertOwner($task, $intruder);
    }

    private function setUserId(User $user, int $id): void
    {
        $property = new \ReflectionProperty(User::class, 'id');
        $property->setValue($user, $id);
    }
}
