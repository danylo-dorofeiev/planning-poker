<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\DeckService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class UserController extends AbstractController
{
    #[Route('/user/profile', name: 'user_profile', methods: ['GET'])]
    public function profile(
        Security $security,
    ): Response {

        $user = $security->getUser();

        return $this->render('user/profile.html.twig', [
            'user' => $user,
        ]);
    }
}
