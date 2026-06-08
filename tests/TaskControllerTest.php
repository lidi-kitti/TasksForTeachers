<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TaskControllerTest extends WebTestCase
{
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

        $client->request('GET', '/tasks/3/edit');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testTeacherCanDeleteOwnTask(): void
    {
        $client = static::createClient();
        $this->loginAsTeacher($client);

        $crawler = $client->request('GET', '/tasks/1');
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
