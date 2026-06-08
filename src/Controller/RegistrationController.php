<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\UserRegistrationService;
use Doctrine\DBAL\Exception\ConnectionException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserRegistrationService $registrationService,
        Security $security,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = (string) $form->get('plainPassword')->getData();

            try {
                $registrationService->register($user, $plainPassword);
            } catch (ConnectionException) {
                $this->addFlash(
                    'error',
                    'Не удалось завершить регистрацию: база данных недоступна. Проверьте DATABASE_URL в .env и убедитесь, что PostgreSQL запущен.',
                );

                return $this->render('security/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }

            $security->login($user);

            $this->addFlash('success', 'Регистрация прошла успешно. Добро пожаловать!');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
