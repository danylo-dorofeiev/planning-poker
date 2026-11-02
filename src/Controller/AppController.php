<?php

namespace App\Controller;

use App\Entity\Room;
use App\Form\RoomType;
use App\Repository\JiraConnectionRepository;
use App\Repository\RoomRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AppController extends AbstractController
{
    /* ===> main page === */
    #[Route('/', name: 'app', methods: ['GET', 'POST'])]
    public function app(
        Request $request,
        Security $security,
        RoomRepository $roomRepository,
    ): Response {
        if ($this->getUser()) {
            $user = $security->getUser();

            $room = new Room();
            $room->setOwner($user);

            $roomCreateForm = $this->createForm(RoomType::class, $room, [
                'attr' => [
                    'id' => 'room_create_form',
                ],
                'action' => $this->generateUrl('room_create'),
                'method' => 'POST',
            ]);

            $roomCreateForm->handleRequest($request);

            $page = max(1, $request->query->getInt('page', 1));
            $limit = 15;

            $search = $request->query->get('search');
            $status = $request->query->get('status');

            $sortOption = $request->query->get('sortOption');
            $sortDirection = $request->query->get('sortDirection');

            $onlyOwned = $request->query->get('onlyOwned');

            $totalRooms = $roomRepository->countRoomsByUserWithFilter($user, $search, $status, $onlyOwned);
            $totalPages = max(1, (int) ceil($totalRooms / $limit));

            $page = min($page, $totalPages);

            $recentlyUpdated = $roomRepository->findRecentlyUpdated($user);

            $rooms = $roomRepository->findRoomsByUserWithFilter($user, $search, $status, $sortOption, $sortDirection, $onlyOwned, $page, $limit);

            $hasRooms = $roomRepository->hasRooms($user);

            return $this->render('app/dashboard/index.html.twig', [
                'rooms' => $rooms,
                'totalRooms' => $totalRooms,
                'recentlyUpdated' => $recentlyUpdated,
                'page' => $page,
                'totalPages' => $totalPages,
                'roomCreateForm' => $roomCreateForm,
                'search' => $search,
                'status' => $status,
                'sortOption' => $sortOption,
                'sortDirection' => $sortDirection,
                'onlyOwned' => $onlyOwned,
                'hasRooms' => $hasRooms,
            ]);
        }

        return $this->render('app/index.html.twig');
    }
    /* ===< main page === */

    /* ===> public pages === */
    #[Route('/faq', name: 'app_faq', methods: ['GET'])]
    public function faq(): Response
    {
        return $this->render('pages/public/faq.html.twig');
    }

    #[Route('/imprint', name: 'app_imprint', methods: ['GET'])]
    public function imprint(): Response
    {
        return $this->render('pages/public/imprint.html.twig');
    }

    #[Route('/privacy-statement', name: 'app_privacy', methods: ['GET'])]
    public function privacy(): Response
    {
        return $this->render('pages/public/privacy.html.twig');
    }

    #[Route('/version', name: 'app_version', methods: ['GET'])]
    public function version(): Response
    {
        return $this->render('pages/public/version.html.twig');
    }

    #[Route('/about', name: 'app_about', methods: ['GET'])]
    public function about(): Response
    {
        return $this->render('pages/public/about.html.twig');
    }
    /* ===< public pages === */

    /* ===> app pages === */
    #[IsGranted('ROLE_USER')]
    #[Route('/settings', name: 'app_settings', methods: ['GET'])]
    public function settings(): Response
    {
        return $this->render('pages/app/settings/profile.html.twig');
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/settings/profile', name: 'app_profile', methods: ['GET'])]
    public function profile(): Response
    {
        return $this->render('pages/app/settings/profile.html.twig');
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/settings/appearance', name: 'app_appearance', methods: ['GET'])]
    public function appearance(): Response
    {
        return $this->render('pages/app/settings/appearance.html.twig');
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/settings/services', name: 'app_services', methods: ['GET'])]
    public function services(
        JiraConnectionRepository $jiraConnectionRepository,
    ): Response
    {
        $jiraConnection = $jiraConnectionRepository->findOneBy([
            'user' => $this->getUser(),
        ]);

        return $this->render('pages/app/settings/services.html.twig', [
            'jiraConnection' => $jiraConnection,
        ]);
    }
    /* ===< app pages === */
}
