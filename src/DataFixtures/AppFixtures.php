<?php

namespace App\DataFixtures;

use App\Entity\Task;
use App\Entity\TaskStatus;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $teacher1 = $this->createUser('teacher@example.com', 'Анна Преподаватель', 'pass123');
        $teacher2 = $this->createUser('colleague@example.com', 'Пётр Коллега', 'pass123');

        $manager->persist($teacher1);
        $manager->persist($teacher2);

        $tasks = [
            [$teacher1, 'Контрольная по математике', 'Решить задачи из главы 5 учебника.', '+14 days', 100, TaskStatus::Active],
            [$teacher1, 'Эссе по литературе', 'Написать эссе на 2–3 страницы о произведении XIX века.', '+21 days', 50, TaskStatus::Active],
            [$teacher2, 'Лабораторная работа по физике', 'Измерить сопротивление проводника и оформить отчёт.', '+7 days', 30, TaskStatus::Active],
            [$teacher2, 'Тест по истории', 'Подготовиться к тесту по теме «Древний мир».', '-3 days', 20, TaskStatus::Completed],
            [$teacher1, 'Проект по информатике', 'Разработать простое веб-приложение на Symfony.', '+30 days', 100, TaskStatus::Active],
        ];

        foreach ($tasks as [$author, $title, $description, $dueDateModifier, $maxScore, $status]) {
            $task = new Task();
            $task->setAuthor($author);
            $task->setTitle($title);
            $task->setDescription($description);
            $task->setDueDate(new \DateTimeImmutable($dueDateModifier));
            $task->setMaxScore($maxScore);
            $task->setStatus($status);
            $manager->persist($task);
        }

        $manager->flush();
    }

    private function createUser(string $email, string $name, string $plainPassword): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        return $user;
    }
}
