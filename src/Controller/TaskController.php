<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Form\TaskType;
use App\Service\TaskService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tasks')]
class TaskController extends AbstractController
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {
    }

    #[Route('', name: 'app_task_index', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $pagination = $paginator->paginate(
            $this->taskService->getAllTasksQueryBuilder(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('task/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'app_task_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $this->taskService->createTask($task, $user);
            $this->addFlash('success', 'Задание успешно создано.');

            return $this->redirectToRoute('app_task_show', ['id' => $task->getId()]);
        }

        return $this->render('task/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_task_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): Response
    {
        $task = $this->taskService->getTask($id);

        /** @var User $user */
        $user = $this->getUser();

        return $this->render('task/show.html.twig', [
            'task' => $task,
            'canEdit' => $this->taskService->canEdit($task, $user),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_task_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $task = $this->taskService->getTask($id);

        /** @var User $user */
        $user = $this->getUser();
        $this->taskService->assertOwner($task, $user);

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->taskService->updateTask($task, $user);
            $this->addFlash('success', 'Задание успешно обновлено.');

            return $this->redirectToRoute('app_task_show', ['id' => $task->getId()]);
        }

        return $this->render('task/edit.html.twig', [
            'task' => $task,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_task_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        $task = $this->taskService->getTask($id);

        /** @var User $user */
        $user = $this->getUser();
        $this->taskService->assertOwner($task, $user);

        if ($this->isCsrfTokenValid('delete'.$task->getId(), (string) $request->request->get('_token'))) {
            $this->taskService->deleteTask($task, $user);
            $this->addFlash('success', 'Задание удалено.');
        }

        return $this->redirectToRoute('app_task_index');
    }
}
