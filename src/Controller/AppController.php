<?php

namespace App\Controller;

use App\Entity\Room;
use App\Form\RoomType;
use App\Form\JoinRoomType;
use App\Repository\RoomRepository;
use App\Service\DeckService;
use App\Service\RoomService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final class AppController extends AbstractController
{
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

            $joinRoomForm = $this->createForm(JoinRoomType::class, null, [
                'attr' => [
                    'id' => 'room_join_form',
                ],
                'method' => 'POST',
            ]);

            $joinRoomForm->handleRequest($request);

            if ($joinRoomForm->isSubmitted() && $joinRoomForm->isValid()) {
                $data = $joinRoomForm->getData();
                $uuid = $data['uuid'];

                if (!Uuid::isValid($uuid)) {
                    throw $this->createNotFoundException('Invalid Room ID');
                }

                $room = $roomRepository->findOneBy([
                    'uuid' => $uuid,
                ]);

                if(!$room) {
                    return $this->redirectToRoute('app', [
                        'room_id' => $uuid,
                        'error' => 'room_not_found',
                    ]);
                }

                return $this->redirectToRoute('room_join', [
                    'room_id' => $uuid
                ]);
            }

            $error = $request->query->get('error');

            $page = max(1, $request->query->getInt('page', 1));
            $limit = 15;

            $search = $request->query->get('search');
            $status = $request->query->get('status');

            $sortOption = $request->query->get('sortOption');
            $sortDirection = $request->query->get('sortDirection');

            $totalRooms = $roomRepository->countByOwnerWithFilter($user, $search, $status);
            $totalPages = max(1, (int) ceil($totalRooms / $limit));

            $page = min($page, $totalPages);

            $recentlyUpdated = $roomRepository->findRecentlyUpdated($user);

            $rooms = $roomRepository->findByOwnerWithFilter($user, $search, $status, $sortOption, $sortDirection, $page, $limit);

            $hasRooms = $roomRepository->hasRooms($user);

            return $this->render('app/dashboard/index.html.twig', [
                'rooms' => $rooms,
                'totalRooms' => $totalRooms,
                'recentlyUpdated' => $recentlyUpdated,
                'page' => $page,
                'totalPages' => $totalPages,
                'roomCreateForm' => $roomCreateForm,
                'joinRoomForm' => $joinRoomForm,
                'search' => $search,
                'status' => $status,
                'sortOption' => $sortOption,
                'sortDirection' => $sortDirection,
                'hasRooms' => $hasRooms,
                'error' => $error,
            ]);
        }

        return $this->render('app/index.html.twig');
    }

    #[Route('/faq', name: 'app_faq', methods: ['GET'])]
    public function faq(): Response
    {
        return $this->render('pages/faq.html.twig');
    }

    #[Route('/imprint', name: 'app_imprint', methods: ['GET'])]
    public function imprint(): Response
    {
        return $this->render('pages/imprint.html.twig');
    }

    #[Route('/privacy-statement', name: 'app_privacy', methods: ['GET'])]
    public function privacy(): Response
    {
        return $this->render('pages/privacy.html.twig');
    }

    #[Route('/version', name: 'app_version', methods: ['GET'])]
    public function version(): Response
    {
        return $this->render('pages/version.html.twig');
    }

    #[Route('/about', name: 'app_about', methods: ['GET'])]
    public function about(): Response
    {
        return $this->render('pages/about.html.twig');
    }
}
