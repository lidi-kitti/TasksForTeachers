<?php

namespace App\Tests;

use App\DataFixtures\AppFixtures;
use App\Repository\TaskRepository;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TaskControllerTest extends WebTestCase
{
    private static int $ownTaskId;
    private static int $foreignTaskId;

    public static function setUpBeforeClass(): void
    {
        static::bootKernel();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();
        $executor = new ORMExecutor($em, new ORMPurger($em));
        $loader = new Loader();
        $loader->addFixture($container->get(AppFixtures::class));
        $executor->execute($loader->getFixtures());

        /** @var TaskRepository $taskRepository */
        $taskRepository = $container->get(TaskRepository::class);
        self::$ownTaskId = $taskRepository->findOneBy(['title' => 'Контрольная по математике'])?->getId()
            ?? throw new \RuntimeException('Фикстура «Контрольная по математике» не найдена.');
        self::$foreignTaskId = $taskRepository->findOneBy(['title' => 'Лабораторная работа по физике'])?->getId()
            ?? throw new \RuntimeException('Фикстура «Лабораторная работа по физике» не найдена.');

        static::ensureKernelShutdown();
    }

    public function testGuestCannotAccessTaskList(): void
    {
        $client = static::createClient();
        $client->request('GET', '/tasks');

        $this->assertResponseRedirects('/login');
    }

    public function testTeacherCanCreateTask(): void
    {
        $client = static::createClient();
        $this->loginAsTeacher($client);

        $crawler = $client->request('GET', '/tasks/new');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Создать')->form([
            'task[title]' => 'Тестовое задание',
            'task[description]' => 'Описание тестового задания',
            'task[dueDate]' => (new \DateTimeImmutable('+10 days'))->format('Y-m-d'),
            'task[maxScore]' => '25',
            'task[status]' => 'active',
        ]);

        $client->submit($form);
        $this->assertResponseRedirects();

        $client->followRedirect();
        $this->assertSelectorTextContains('h1', 'Тестовое задание');
    }

    public function testTeacherCannotEditForeignTask(): void
    {
        $client = static::createClient();
        $this->loginAsTeacher($client);

        $client->request('GET', '/tasks/'.self::$foreignTaskId.'/edit');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testTeacherCanDeleteOwnTask(): void
    {
        $client = static::createClient();
        $this->loginAsTeacher($client);

        $crawler = $client->request('GET', '/tasks/'.self::$ownTaskId);
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form[action*="delete"]')->form();
        $client->submit($form);
        $this->assertResponseRedirects('/tasks');
    }

    private function loginAsTeacher(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client): void
    {
        $client->request('GET', '/login');
        $client->submitForm('Войти', [
            '_username' => 'teacher@example.com',
            '_password' => 'pass123',
        ]);
        $this->assertResponseRedirects('/tasks');
        $client->followRedirect();
    }
}
